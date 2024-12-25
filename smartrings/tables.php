<?php include 'includes/header.php'; ?>

<?php
// Параметры подключения к базе данных
$servername = "localhost";
$username = "root"; // По умолчанию в XAMPP
$password = ""; // По умолчанию пусто
$dbname = "smart_rings";

// Создание соединения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Функция для вывода всех столбцов выбранной таблицы
function displayTable($conn, $tableName) {
    $sql = "SELECT * FROM $tableName";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<h2>" . ucfirst($tableName) . "</h2>";
        echo "<table><tr>";

        // Вывод заголовков столбцов
        while ($field_info = $result->fetch_field()) {
            echo "<th>" . htmlspecialchars($field_info->name) . "</th>";
        }
        echo "</tr>";

        // Вывод данных
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "0 results for table: " . htmlspecialchars($tableName) . "<br>";
    }
}


// Список таблиц для вывода
$tables = ['patients', 'sessions', 'rings', 'data', 'limit_values'];
foreach ($tables as $table) {
    displayTable($conn, $table);
}


// Закрытие соединения
$conn->close();
?>

<?php include 'includes/footer.php'; ?>