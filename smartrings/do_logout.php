<?php
// do_logout.php
require_once __DIR__.'/boot.php';
include 'includes/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверяем, есть ли session_id, который мы создали при логине
$sessionId = $_SESSION['session_id'] ?? null;

if ($sessionId) {
    try {
        $logoutTime = date('Y-m-d H:i:s');
        $updateSql = "UPDATE sessions
                      SET logout_timestamp = :logout_time
                      WHERE session_id = :session_id";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            'logout_time' => $logoutTime,
            'session_id' => $sessionId,
        ]);
    } catch (PDOException $e) {
        error_log("Ошибка при обновлении sessions.logout_timestamp: " . $e->getMessage());
        // Можно не прерывать выполнение
    }
}

// Обнуляем user_id и session_id в сессии
$_SESSION['user_id'] = null;
$_SESSION['session_id'] = null;

// Можно удалить сессию полностью:
session_destroy();

// Перенаправляем на главную (или на login.php)
header('Location: /smartrings');
exit();