<?php
// messages.php

require_once __DIR__ . '/boot.php';

// Включаем отображение ошибок для отладки (уберите в продакшн)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Выполняем "большой" SQL-запрос, чтобы найти все потенциальные оповещения
$sql = "
SELECT 
    p.patient_id,
    CONCAT(p.name, ' ', p.surname) AS patient_name,
    d.data_id,
    d.timestamp,
    mt.message_type_id,
    mt.message_type,
    mt.metric,
    CASE 
        WHEN mt.metric = 'heart_rate' THEN d.heart_rate
        WHEN mt.metric = 'blood_oxygen_level' THEN d.blood_oxygen_level
        WHEN mt.metric = 'sleep_quality' THEN d.sleep_quality
        WHEN mt.metric = 'stress_level' THEN d.stress_level
        WHEN mt.metric = 'respiratory_rate' THEN d.respiratory_rate
        WHEN mt.metric = 'steps_count' THEN d.steps_count
        ELSE NULL
    END AS metric_value,
    CASE
        WHEN mt.min_value IS NOT NULL AND (
            (mt.metric = 'heart_rate' AND d.heart_rate < mt.min_value) OR
            (mt.metric = 'blood_oxygen_level' AND d.blood_oxygen_level < mt.min_value) OR
            (mt.metric = 'sleep_quality' AND d.sleep_quality < mt.min_value) OR
            (mt.metric = 'stress_level' AND d.stress_level < mt.min_value) OR
            (mt.metric = 'respiratory_rate' AND d.respiratory_rate < mt.min_value) OR
            (mt.metric = 'steps_count' AND d.steps_count < mt.min_value)
        )
        THEN CONCAT(mt.message_type, ' - Показатель ', mt.metric, ' ниже нормы: ',
            CASE 
                WHEN mt.metric = 'heart_rate' THEN d.heart_rate
                WHEN mt.metric = 'blood_oxygen_level' THEN d.blood_oxygen_level
                WHEN mt.metric = 'sleep_quality' THEN d.sleep_quality
                WHEN mt.metric = 'stress_level' THEN d.stress_level
                WHEN mt.metric = 'respiratory_rate' THEN d.respiratory_rate
                WHEN mt.metric = 'steps_count' THEN d.steps_count
                ELSE NULL
            END
        )
        
        WHEN mt.max_value IS NOT NULL AND (
            (mt.metric = 'heart_rate' AND d.heart_rate > mt.max_value) OR
            (mt.metric = 'blood_oxygen_level' AND d.blood_oxygen_level > mt.max_value) OR
            (mt.metric = 'sleep_quality' AND d.sleep_quality > mt.max_value) OR
            (mt.metric = 'stress_level' AND d.stress_level > mt.max_value) OR
            (mt.metric = 'respiratory_rate' AND d.respiratory_rate > mt.max_value) OR
            (mt.metric = 'steps_count' AND d.steps_count > mt.max_value)
        )
        THEN CONCAT(mt.message_type, ' - Показатель ', mt.metric, ' выше нормы: ',
            CASE 
                WHEN mt.metric = 'heart_rate' THEN d.heart_rate
                WHEN mt.metric = 'blood_oxygen_level' THEN d.blood_oxygen_level
                WHEN mt.metric = 'sleep_quality' THEN d.sleep_quality
                WHEN mt.metric = 'stress_level' THEN d.stress_level
                WHEN mt.metric = 'respiratory_rate' THEN d.respiratory_rate
                WHEN mt.metric = 'steps_count' THEN d.steps_count
                ELSE NULL
            END
        )
        
        ELSE NULL
    END AS alert_message
FROM patients p
JOIN rings r ON p.patient_id = r.patient_id
JOIN data d ON r.ring_id = d.ring_id
JOIN limit_values mt ON mt.metric IN ('heart_rate', 'blood_oxygen_level', 'sleep_quality', 'stress_level', 'respiratory_rate', 'steps_count')
WHERE (
    (mt.min_value IS NOT NULL AND (
        (mt.metric = 'heart_rate' AND d.heart_rate < mt.min_value) OR
        (mt.metric = 'blood_oxygen_level' AND d.blood_oxygen_level < mt.min_value) OR
        (mt.metric = 'sleep_quality' AND d.sleep_quality < mt.min_value) OR
        (mt.metric = 'stress_level' AND d.stress_level < mt.min_value) OR
        (mt.metric = 'respiratory_rate' AND d.respiratory_rate < mt.min_value) OR
        (mt.metric = 'steps_count' AND d.steps_count < mt.min_value)
    ))
    OR
    (mt.max_value IS NOT NULL AND (
        (mt.metric = 'heart_rate' AND d.heart_rate > mt.max_value) OR
        (mt.metric = 'blood_oxygen_level' AND d.blood_oxygen_level > mt.max_value) OR
        (mt.metric = 'sleep_quality' AND d.sleep_quality > mt.max_value) OR
        (mt.metric = 'stress_level' AND d.stress_level > mt.max_value) OR
        (mt.metric = 'respiratory_rate' AND d.respiratory_rate > mt.max_value) OR
        (mt.metric = 'steps_count' AND d.steps_count > mt.max_value)
    ))
)
ORDER BY d.timestamp DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}

// 2. Заполняем таблицу messages в базе данных (если alert_message != null)
//    и проверяем дубликаты, чтобы не вставлять их повторно

try {
    $pdo->beginTransaction();

    foreach ($alerts as $alert) {
        // Если alert_message = null, значит отклонения нет
        if (!empty($alert['alert_message'])) {

            // Проверка на дубликат: в таблице messages
            // Считаем, что уникальным является сочетание patient_id + data_id + message_type_id + alert_message
            // (или другое, на ваше усмотрение)
            $checkSql = "SELECT alert_id
                         FROM messages
                         WHERE patient_id = :patient_id
                           AND data_id = :data_id
                           AND message_type_id = :message_type_id
                           AND alert_message = :alert_message
                         LIMIT 1";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':patient_id', $alert['patient_id'], PDO::PARAM_INT);
            $checkStmt->bindParam(':data_id', $alert['data_id'], PDO::PARAM_INT);
            $checkStmt->bindParam(':message_type_id', $alert['message_type_id'], PDO::PARAM_INT);
            $checkStmt->bindParam(':alert_message', $alert['alert_message'], PDO::PARAM_STR);
            $checkStmt->execute();

            $existingAlert = $checkStmt->fetch(PDO::FETCH_ASSOC);

            // Если НЕ нашли дубль, тогда вставляем
            if (!$existingAlert) {
                $sql_insert = "INSERT INTO messages (patient_id, data_id, message_type_id, alert_message)
                               VALUES (:patient_id, :data_id, :message_type_id, :alert_message)";
                $stmt_insert = $pdo->prepare($sql_insert);

                $stmt_insert->bindParam(':patient_id', $alert['patient_id'], PDO::PARAM_INT);
                $stmt_insert->bindParam(':data_id', $alert['data_id'], PDO::PARAM_INT);
                $stmt_insert->bindParam(':message_type_id', $alert['message_type_id'], PDO::PARAM_INT);
                $stmt_insert->bindParam(':alert_message', $alert['alert_message'], PDO::PARAM_STR);

                $stmt_insert->execute();
            }
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Ошибка при вставке оповещений: " . $e->getMessage());
}

?>
<?php include "includes/header.php" ?>
<h1>Активные оповещения</h1>

<!-- 3. Отображаем оповещения, которые мы только что получили в $alerts.
     При желании можно показать данные именно из таблицы `messages`.
     Но для примера оставим как есть. -->

<?php if (!empty($alerts)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Пациент</th>
            <th>Тип оповещения</th>
            <th>Сообщение</th>
            <th>Измерение</th>
            <th>Время</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($alerts as $alert): ?>
            <?php if (!empty($alert['alert_message'])): ?>
                <tr>
                    <td><?php echo htmlspecialchars($alert['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($alert['message_type']); ?></td>
                    <td><?php echo htmlspecialchars($alert['alert_message']); ?></td>
                    <td><?php echo htmlspecialchars($alert['metric_value']); ?></td>
                    <td><?php echo htmlspecialchars($alert['timestamp']); ?></td>
                    <td>
                        <form method="post" action="resolve_problem.php" onsubmit="return confirm('Вы уверены, что хотите отметить оповещение как решенное?');">
                            <input type="hidden" name="data_id" value="<?php echo htmlspecialchars($alert['data_id']); ?>">
                            <input type="hidden" name="metric" value="<?php echo htmlspecialchars($alert['metric']); ?>">
                            <button type="submit">Отметить как решенное</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Нет активных оповещений.</p>
<?php endif; ?>
<?php include "includes/footer.php" ?>