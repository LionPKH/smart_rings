<?php
require_once 'boot.php';

if (!check_auth()) {
    header('Location: /smartrings');
    die("");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

    // Проверка дубликатов
    $name_lower = mb_strtolower($name);
    $surname_lower = mb_strtolower($surname);
    $phone_norm = preg_replace('/\D+/', '', $phone);
    $email_lower = mb_strtolower($email);

    if (!empty($name) && !empty($surname) && !empty($phone) && !empty($email)) {
        $sql = "SELECT patient_id FROM patients 
                WHERE LOWER(name) = :name AND LOWER(surname) = :surname AND phone = :phone AND LOWER(email) = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name_lower);
        $stmt->bindParam(':surname', $surname_lower);
        $stmt->bindParam(':phone', $phone_norm);
        $stmt->bindParam(':email', $email_lower);
        $stmt->execute();
        $existingPatient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingPatient) {
            $errors[] = 'Пациент с такими именем, фамилией, номером телефона и почтой уже существует.';
        }
    }

    if (empty($errors)) {
        if (!isset($_SESSION['user_id'])) {
            $errors[] = 'Пользователь не авторизован.';
        } else {
            $user_id = $_SESSION['user_id'];

            try {
                $pdo->beginTransaction();

                // Вставляем пациента
                $sql = "INSERT INTO patients (name, surname, gender, date_of_birth, phone, email, address) 
                        VALUES (:name, :surname, :gender, :date_of_birth, :phone, :email, :address)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':surname', $surname);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':date_of_birth', $date_of_birth);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':address', $address);
                $stmt->execute();

                $patient_id = $pdo->lastInsertId();

                // Обновляем auth
                $sql = "UPDATE authorization SET patient_id = :patient_id WHERE id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':patient_id', $patient_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                // Добавляем новый браслет для этого пациента
                // Пусть будет уникальный serial_number, модель и сегодняшняя дата активации
                $serial_number = 'SN' . rand(3000, 3999); 
                $model = 'Model X';
                $activated_date = date('Y-m-d');

                $sql = "INSERT INTO rings (patient_id, serial_number, model, activated_date)
                        VALUES (:patient_id, :serial_number, :model, :activated_date)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':patient_id', $patient_id);
                $stmt->bindParam(':serial_number', $serial_number);
                $stmt->bindParam(':model', $model);
                $stmt->bindParam(':activated_date', $activated_date);
                $stmt->execute();

                $ring_id = $pdo->lastInsertId();

                // Добавляем стандартные измерения для этого браслета
                // Предположим стандартные значения:
                // heart_rate=75, blood_pressure_systolic=120, blood_pressure_diastolic=80, blood_glucose_level=5.5, temperature=36.6
                $measurement_time = date('Y-m-d H:i:s');
                $sql = "INSERT INTO data (ring_id, timestamp, heart_rate, blood_oxygen_level, sleep_quality, stress_level, respiratory_rate, steps_count)
                        VALUES (:ring_id, :timestamp, 75, 98.00, 80, 30, 15, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':ring_id', $ring_id, PDO::PARAM_INT);
                $stmt->bindParam(':timestamp', $measurement_time);
                $stmt->execute();

                $pdo->commit();

                header('Location: tables.php');
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Ошибка при сохранении данных: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<h1>Добавить данные пациента</h1>
<link rel="stylesheet" href="assets/css/measurements.css">

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
    <input type="text" id="name" name="name" required value="<?php echo isset($name)?htmlspecialchars($name):''; ?>"><br><br>

    <label for="surname">Фамилия:</label><br>
    <input type="text" id="surname" name="surname" required value="<?php echo isset($surname)?htmlspecialchars($surname):''; ?>"><br><br>

    <label for="gender">Пол:</label><br>
    <select id="gender" name="gender" required>
        <option value="">Выберите пол</option>
        <option value="Мужской" <?php if(isset($gender)&&$gender==='Мужской') echo 'selected'; ?>>Мужской</option>
        <option value="Женский" <?php if(isset($gender)&&$gender==='Женский') echo 'selected'; ?>>Женский</option>
    </select><br><br>

    <label for="date_of_birth">Дата рождения:</label><br>
    <input type="date" id="date_of_birth" name="date_of_birth" required value="<?php echo isset($date_of_birth)?htmlspecialchars($date_of_birth):''; ?>"><br><br>

    <label for="phone">Номер телефона:</label><br>
    <input type="tel" id="phone" name="phone" required pattern="\+?\d{10,15}" value="<?php echo isset($phone)?htmlspecialchars($phone):''; ?>"><br><br>

    <label for="email">Электронная почта:</label><br>
    <input type="email" id="email" name="email" required value="<?php echo isset($email)?htmlspecialchars($email):''; ?>"><br><br>

    <label for="address">Адрес:</label><br>
    <textarea id="address" name="address" rows="3" cols="30"><?php echo isset($address)?htmlspecialchars($address):''; ?></textarea><br><br>

    <button type="submit">Сохранить</button>
</form>
<?php include 'includes/footer.php'; ?>