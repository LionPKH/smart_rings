<?php
require_once __DIR__ . '/boot.php';

// Включаем отображение ошибок для отладки (уберите в продакшн)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя, пациента и кольца
$sql_user = "
    SELECT 
        a.id AS auth_id, 
        a.username, 
        p.patient_id, 
        p.name, 
        p.surname, 
        p.gender, 
        p.date_of_birth, 
        p.phone, 
        p.email, 
        p.address,
        r.serial_number AS ring_serial
    FROM authorization a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN rings r ON p.patient_id = r.patient_id
    WHERE a.id = :user_id
    LIMIT 1
";

$stmt_user = $pdo->prepare($sql_user);
$stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);

try {
    $stmt_user->execute();
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса пользователя: " . $e->getMessage());
}

// Проверяем, найден ли пользователь
if (!$user) {
    echo "Пользователь не найден.";
    exit();
}

// Проверяем, связан ли пользователь с пациентом
if (!$user['patient_id']) {
    echo "<h2>Мой аккаунт</h2>";
    echo "<p>Данные о пациенте отсутствуют. <a href='add_patient.php'>Добавить данные о пациенте</a></p>";
    include "includes/footer.php";
    exit();
}

$patient_id = $user['patient_id'];

// Получаем оповещения из таблицы messages
$sql_alerts = "
SELECT 
    m.alert_id,
    m.alert_message,
    m.message_timestamp,
    m.resolved
FROM messages m
WHERE m.patient_id = :patient_id AND m.resolved = 0
ORDER BY m.message_timestamp DESC
";

try {
    $stmt_alerts = $pdo->prepare($sql_alerts);
    $stmt_alerts->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt_alerts->execute();
    $alerts = $stmt_alerts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса оповещений: " . $e->getMessage());
}

// Получаем измерения с кольца
$sql_measurements = "
SELECT 
    d.data_id,
    d.timestamp,
    d.heart_rate,
    d.blood_oxygen_level,
    d.sleep_quality,
    d.stress_level,
    d.respiratory_rate,
    d.steps_count
FROM data d
JOIN rings r ON d.ring_id = r.ring_id
WHERE r.patient_id = :patient_id
ORDER BY d.timestamp DESC
";

try {
    $stmt_measurements = $pdo->prepare($sql_measurements);
    $stmt_measurements->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt_measurements->execute();
    $measurements = $stmt_measurements->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении данных с кольца: " . $e->getMessage());
}
?>

<?php include "includes/header.php"; ?>
<h1>Мой аккаунт</h1>

<!-- Информация о пользователе -->
<h2>Информация о Пользователе</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Имя в системе</th>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
    </tr>
    <tr>
        <th>Имя</th>
        <td><?php echo htmlspecialchars($user['name']); ?></td>
    </tr>
    <tr>
        <th>Фамилия</th>
        <td><?php echo htmlspecialchars($user['surname']); ?></td>
    </tr>
    <tr>
        <th>Пол</th>
        <td><?php echo htmlspecialchars($user['gender']); ?></td>
    </tr>
    <tr>
        <th>Дата рождения</th>
        <td><?php echo htmlspecialchars($user['date_of_birth']); ?></td>
    </tr>
    <tr>
        <th>Телефон</th>
        <td><?php echo htmlspecialchars($user['phone']); ?></td>
    </tr>
    <tr>
        <th>Email</th>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
    </tr>
    <tr>
        <th>Адрес</th>
        <td><?php echo htmlspecialchars($user['address']); ?></td>
    </tr>
    <tr>
        <th>Серийный номер кольца</th>
        <td><?php echo htmlspecialchars($user['ring_serial'] ?? 'Нет кольца'); ?></td>
    </tr>
</table>

<hr>

<!-- Оповещения -->
<h2>Активные Оповещения</h2>
<?php if (!empty($alerts)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Сообщение</th>
            <th>Время</th>
            <th>Статус</th>
        </tr>
        <?php foreach ($alerts as $alert): ?>
            <tr>
                <td><?php echo htmlspecialchars($alert['alert_id']); ?></td>
                <td><?php echo htmlspecialchars($alert['alert_message']); ?></td>
                <td><?php echo htmlspecialchars($alert['message_timestamp']); ?></td>
                <td><?php echo $alert['resolved'] ? 'Решено' : 'Активно'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Нет активных оповещений.</p>
<?php endif; ?>

<hr>

<!-- Данные с кольца -->
<h2>Данные с кольца</h2>
<?php if (!empty($measurements)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Время</th>
            <th>Пульс</th>
            <th>Кислород</th>
            <th>Сон</th>
            <th>Стресс</th>
            <th>Дыхание</th>
            <th>Шаги</th>
        </tr>
        <?php foreach ($measurements as $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($data['timestamp']); ?></td>
                <td><?php echo htmlspecialchars($data['heart_rate']); ?></td>
                <td><?php echo htmlspecialchars($data['blood_oxygen_level']); ?></td>
                <td><?php echo htmlspecialchars($data['sleep_quality']); ?></td>
                <td><?php echo htmlspecialchars($data['stress_level']); ?></td>
                <td><?php echo htmlspecialchars($data['respiratory_rate']); ?></td>
                <td><?php echo htmlspecialchars($data['steps_count']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Нет данных.</p>
<?php endif; ?>

<!-- Кнопки управления -->
<hr>
<a href="edit_patient.php" class="account-button account-button-edit">Редактировать данные</a> 
<a href="do_logout.php" class="account-button account-button-logout">Выход</a> 
<a href="delete_account.php" class="account-button account-button-delete" onclick="return confirm('Вы уверены, что хотите удалить аккаунт?')">Удалить аккаунт</a>

<?php include "includes/footer.php"; ?>