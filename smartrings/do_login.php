<?php
require_once __DIR__.'/boot.php';
include 'includes/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверяем наличие пользователя по username
$stmt = $pdo->prepare("SELECT * FROM `authorization` WHERE `username` = :username");
$stmt->execute(['username' => $_POST['username']]);
if (!$stmt->rowCount()) {
    flash('Пользователь с такими данными не зарегистрирован');
    header('Location: login.php');
    die();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверяем пароль
if (password_verify($_POST['password'], $user['password_hash'])) {
    // При необходимости обновляем хэш, если устарел
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE `authorization` SET `password_hash` = :password_hash WHERE `username` = :username');
        $stmt->execute([
            'username' => $_POST['username'],
            'password_hash' => $newHash,
        ]);
    }

    // Сохраняем user_id в сессии
    $_SESSION['user_id'] = $user['id'];

    // Вставляем запись в таблицу sessions (время входа)
    try {
        // Время входа (текущее время)
        $loginTime = date('Y-m-d H:i:s');

        // Предполагаем, что в таблице sessions есть поля session_id, user_id, login_timestamp, logout_timestamp
        $insertSql = "INSERT INTO sessions (user_id, login_timestamp) 
                      VALUES (:user_id, :login_timestamp)";
        $stmtInsert = $pdo->prepare($insertSql);
        $stmtInsert->execute([
            'user_id' => $user['id'],
            'login_timestamp' => $loginTime
        ]);
        
        // Получаем session_id, чтобы потом в do_logout.php обновить время выхода
        $sessionId = $pdo->lastInsertId();
        $_SESSION['session_id'] = $sessionId;
    } catch (PDOException $e) {
        // Можно логировать или выводить сообщение
        error_log("Ошибка при записи в sessions: " . $e->getMessage());
        // Не прерываем выполнение, но возможно стоит
    }

    // Если у пользователя нет patient_id — перенаправляем на add_patient
    if (empty($user['patient_id'])) {
        header('Location: add_patient.php');
        die();
    } else {
        header('Location: tables.php');
        die();
    }
}

// Если пароль неверен
flash('Пароль неверен');
header('Location: login.php');
die();