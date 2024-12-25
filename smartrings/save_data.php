<?php
// save_data.php

require_once __DIR__ . '/boot.php';

// Включаем отображение ошибок для отладки (уберите в продакшн)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['save'])) {
    $data_id = $_POST['save'];
    
    // Получаем новые значения из POST
    $heart_rate = isset($_POST['heart_rate'][$data_id]) ? $_POST['heart_rate'][$data_id] : null;
    $blood_oxygen_level = isset($_POST['blood_oxygen_level'][$data_id]) ? $_POST['blood_oxygen_level'][$data_id] : null;
    $sleep_quality = isset($_POST['sleep_quality'][$data_id]) ? $_POST['sleep_quality'][$data_id] : null;
    $stress_level = isset($_POST['stress_level'][$data_id]) ? $_POST['stress_level'][$data_id] : null;
    $respiratory_rate = isset($_POST['respiratory_rate'][$data_id]) ? $_POST['respiratory_rate'][$data_id] : null;
    $steps_count = isset($_POST['steps_count'][$data_id]) ? $_POST['steps_count'][$data_id] : null;

    // Валидация данных
    $errors = [];
    if (!is_numeric($heart_rate) || $heart_rate < 0) $errors[] = 'Некорректное значение пульса.';
    if (!is_numeric($blood_oxygen_level) || $blood_oxygen_level < 0 || $blood_oxygen_level > 100) $errors[] = 'Некорректное значение уровня кислорода.';
    if (!is_numeric($sleep_quality) || $sleep_quality < 0 || $sleep_quality > 100) $errors[] = 'Некорректное значение качества сна.';
    if (!is_numeric($stress_level) || $stress_level < 0 || $stress_level > 100) $errors[] = 'Некорректное значение уровня стресса.';
    if (!is_numeric($respiratory_rate) || $respiratory_rate < 0) $errors[] = 'Некорректное значение дыхательной частоты.';
    if (!is_numeric($steps_count) || $steps_count < 0) $errors[] = 'Некорректное значение количества шагов.';

    if (empty($errors)) {
        $sql = "UPDATE data SET 
                heart_rate = :heart_rate, 
                blood_oxygen_level = :blood_oxygen_level, 
                sleep_quality = :sleep_quality, 
                stress_level = :stress_level, 
                respiratory_rate = :respiratory_rate,
                steps_count = :steps_count
                WHERE data_id = :data_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':heart_rate', $heart_rate);
        $stmt->bindParam(':blood_oxygen_level', $blood_oxygen_level);
        $stmt->bindParam(':sleep_quality', $sleep_quality);
        $stmt->bindParam(':stress_level', $stress_level);
        $stmt->bindParam(':respiratory_rate', $respiratory_rate);
        $stmt->bindParam(':steps_count', $steps_count);
        $stmt->bindParam(':data_id', $data_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            header('Location: data.php');
            exit;
        } catch (PDOException $e) {
            die("Ошибка при обновлении данных: " . $e->getMessage());
        }
    } else {
        // Если есть ошибки, выводим их
        include 'includes/header.php';
        echo "<h1>Ошибка обновления данных</h1>";
        echo "<div style='color: red;'><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
        echo "<a href='data.php?edit=" . htmlspecialchars($data_id) . "'>Вернуться к редактированию</a>";
        include 'includes/footer.php';
        exit;
    }

} else {
    die("Неверный запрос");
}
?>