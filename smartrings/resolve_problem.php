<?php
// resolve_alert.php

require_once __DIR__ . '/boot.php';

// Включаем отображение ошибок для отладки (уберите в продакшн)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Функция для получения нормальных значений метрик
function getNormalValue($metric) {
    $normal_values = [
        'heart_rate' => 75,                // Среднее нормальное значение пульса
        'blood_oxygen_level' => 98.00,     // Спирометрия (SpO2)
        'sleep_quality' => 80,             // Качество сна
        'stress_level' => 30,              // Уровень стресса
        'respiratory_rate' => 15,          // Дыхательная частота
        'steps_count' => 10000              // Количество шагов
    ];

    return isset($normal_values[$metric]) ? $normal_values[$metric] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['data_id']) && isset($_POST['metric'])) {
        $data_id = $_POST['data_id'];
        $metric = $_POST['metric'];

        // Получаем нормальное значение для метрики
        $normal_value = getNormalValue($metric);

        if ($normal_value === null) {
            die("Неизвестная метрика.");
        }

        // Определяем SQL-запрос для обновления метрики
        $sql = "";

        switch ($metric) {
            case 'heart_rate':
                $sql = "UPDATE data SET heart_rate = :normal_value WHERE data_id = :data_id";
                break;
            case 'blood_oxygen_level':
                $sql = "UPDATE data SET blood_oxygen_level = :normal_value WHERE data_id = :data_id";
                break;
            case 'sleep_quality':
                $sql = "UPDATE data SET sleep_quality = :normal_value WHERE data_id = :data_id";
                break;
            case 'stress_level':
                $sql = "UPDATE data SET stress_level = :normal_value WHERE data_id = :data_id";
                break;
            case 'respiratory_rate':
                $sql = "UPDATE data SET respiratory_rate = :normal_value WHERE data_id = :data_id";
                break;
            case 'steps_count':
                $sql = "UPDATE data SET steps_count = :normal_value WHERE data_id = :data_id";
                break;
            default:
                die("Неизвестная метрика.");
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':normal_value', $normal_value);
            $stmt->bindParam(':data_id', $data_id, PDO::PARAM_INT);
            $stmt->execute();

            // Перенаправляем обратно на страницу оповещений
            header('Location: messages.php');
            exit();
        } catch (PDOException $e) {
            die("Ошибка при обновлении данных: " . $e->getMessage());
        }
    } else {
        die("Неверные параметры.");
    }
} else {
    die("Неверный метод запроса.");
}
?>