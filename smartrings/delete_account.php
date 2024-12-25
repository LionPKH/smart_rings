<?php
require_once 'boot.php';

if (!check_auth()) {
    header('Location: /smartrings');
    exit();
}

$user_id = $_SESSION['user_id'];

include 'includes/db_connect.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->beginTransaction();

    // Получаем patient_id из auth
    $sql = "SELECT patient_id FROM authorization WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $authData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$authData) {
        throw new Exception("Не удалось найти пользователя с ID $user_id.");
    }

    $patient_id = $authData['patient_id'];

    if (!empty($patient_id)) {
        // Удаляем оповещения, связанные с пациентом
        $sql = "DELETE FROM messages WHERE patient_id = :patient_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->execute();

        // Получаем все кольца пациента
        $sql = "SELECT ring_id FROM rings WHERE patient_id = :patient_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->execute();
        $rings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        
        if (!empty($rings)) {
            // Удаляем данные (data) для колец
            $ring_ids_str = implode(',', array_map('intval', $rings));
            $sql = "DELETE FROM data WHERE ring_id IN ($ring_ids_str)";
            $pdo->exec($sql);
        
            // Удаляем кольца
            $sql = "DELETE FROM rings WHERE patient_id = :patient_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Удаляем данные пациента
        $sql = "DELETE FROM patients WHERE patient_id = :patient_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->execute();
    }
        
        // Удаляем пользователя из auth
        $sql = "DELETE FROM authorization WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

    // Завершаем транзакцию
    $pdo->commit();

    // Завершаем сессию и перенаправляем на главную страницу
    session_unset();
    session_destroy();
    header('Location: /smartrings');
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Ошибка при удалении аккаунта: " . $e->getMessage());
}