<?php
require_once 'boot.php';

if (!check_auth()) {
    header('Location: /smartrings');
    exit();
}

$user_id = $_SESSION['user_id'];

include 'includes/db_connect.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получаем patient_id пользователя
$sql = "SELECT patient_id FROM authorization WHERE id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$authData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$authData || empty($authData['patient_id'])) {
    // Нет пациента для редактирования
    die("У вас нет данных пациента для редактирования. <a href='add_patient.php'>Добавить данные</a>");
}

$patient_id = $authData['patient_id'];

// Если форма отправлена — обновляем данные
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $gender = trim($_POST['gender']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // Валидация
    if (empty($name)) $errors[] = 'Введите имя.';
    if (empty($surname)) $errors[] = 'Введите фамилию.';
    if (empty($gender)) $errors[] = 'Выберите пол.';
    if (empty($date_of_birth)) {
        $errors[] = 'Введите дату рождения.';
    } else {
        $dob_timestamp = strtotime($date_of_birth);
        if (!$dob_timestamp || $dob_timestamp > time()) {
            $errors[] = 'Некорректная дата рождения.';
        }
    }
    if (empty($phone)) $errors[] = 'Введите номер телефона.';
    if (empty($email)) {
        $errors[] = 'Введите электронную почту.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат электронной почты.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE patients SET name=:name, surname=:surname, gender=:gender, date_of_birth=:date_of_birth, phone=:phone, email=:email, address=:address WHERE patient_id = :patient_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':surname', $surname);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->execute();

            header('Location: account.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при обновлении данных: ' . $e->getMessage();
        }
    }
} else {
    // Загрузка текущих данных
    $sql = "SELECT name, surname, gender, date_of_birth, phone, email, address FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Запись о пациенте не найдена. <a href='add_patient.php'>Добавить данные</a>");
    }

    // Значения по умолчанию для формы
    $name = $patient['name'];
    $surname = $patient['surname'];
    $gender = $patient['gender'];
    $date_of_birth = $patient['date_of_birth'];
    $phone = $patient['phone'];
    $email = $patient['email'];
    $address = $patient['address'];
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/measurements.css">

<h1>Редактировать данные пациента</h1>
<?php if (!empty($errors)): ?>
    <div style="color: red;">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="">
    <label for="name">Имя:</label><br>
    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>"><br><br>

    <label for="surname">Фамилия:</label><br>
    <input type="text" id="surname" name="surname" required value="<?php echo htmlspecialchars($surname); ?>"><br><br>

    <label for="gender">Пол:</label><br>
    <select id="gender" name="gender" required>
        <option value="">Выберите пол</option>
        <option value="Мужской" <?php if($gender==='Мужской') echo 'selected'; ?>>Мужской</option>
        <option value="Женский" <?php if($gender==='Женский') echo 'selected'; ?>>Женский</option>
    </select><br><br>

    <label for="date_of_birth">Дата рождения:</label><br>
    <input type="date" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($date_of_birth); ?>"><br><br>

    <label for="phone">Номер телефона:</label><br>
    <input type="tel" id="phone" name="phone" required pattern="\+?\d{10,15}" value="<?php echo htmlspecialchars($phone); ?>"><br><br>

    <label for="email">Электронная почта:</label><br>
    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>"><br><br>

    <label for="address">Адрес:</label><br>
    <textarea id="address" name="address" rows="3" cols="30"><?php echo htmlspecialchars($address); ?></textarea><br><br>

    <button type="submit">Сохранить изменения</button>
</form>
<?php include 'includes/footer.php'; ?>