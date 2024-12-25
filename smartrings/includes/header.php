<?php 
require_once 'boot.php';

if (!check_auth()) {
    header('Location: /smartrings');
    die("");
} 
?>
<!-- header.php -->
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Веб-интерфейс "smart_rings"</title>
    <link rel="stylesheet" href="assets/css/tables.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        function enableEdit(rowId) {
            var inputs = document.querySelectorAll('[data-row="' + rowId + '"]');
            inputs.forEach(function(input) {
                input.removeAttribute('readonly');
                input.style.backgroundColor = '#fff';
            });
            document.getElementById('edit-btn-' + rowId).style.display = 'none';
            document.getElementById('save-btn-' + rowId).style.display = 'inline';
        }
    </script>
</head>

<body>
    <header>
        <nav>
            <ul>
                <li><a href="tables.php">Главная</a></li>
                <li><a href="data.php">Показатели пациентов</a></li>
                <li><a href="messages.php">Оповещения</a></li>
                <!-- Изменяем кнопку "Выход" на ссылку "Мой аккаунт" -->
                <li><a href="account.php">Мой аккаунт</a></li>
            </ul>
        </nav>
    </header>
    <main>