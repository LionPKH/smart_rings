<?php
// measurements.php

require_once __DIR__ . '/boot.php';

// Fetch measurements
$sql = "SELECT d.*, p.name AS patient_name, p.surname AS patient_surname, r.serial_number AS bracelet_serial
        FROM data d
        JOIN rings r ON d.ring_id = r.ring_id
        JOIN patients p ON r.patient_id = p.patient_id
        ORDER BY d.timestamp DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include "includes/header.php" ?>
    <h1>Измерения пациентов</h1>
    <form method="post" action="save_data.php">
    <table>
        <tr>
            <th>Patient Name</th>
            <th>Timestamp</th>
            <th>Heart Rate</th>
            <th>Blood Oxygen Level</th>
            <th>Sleep Quality</th>
            <th>Stress Level</th>
            <th>Respiratory Rate</th>
            <th>Steps Count</th>
            <th>Actions</th>
        </tr>
        <?php foreach($measurements as $measurement): ?>
            <tr>
                <td><?php echo htmlspecialchars($measurement['patient_name'] . ' ' . $measurement['patient_surname']); ?></td>
                <td><?php echo htmlspecialchars($measurement['timestamp']); ?></td>
                <td>
                    <input type="text" name="heart_rate[<?php echo $measurement['data_id']; ?>]" value="<?php echo htmlspecialchars($measurement['heart_rate']); ?>" readonly data-row="<?php echo $measurement['data_id']; ?>" class="input-field">
                </td>
                <td>
                    <input type="text" name="blood_oxygen_level[<?php echo $measurement['data_id']; ?>]" value="<?php echo htmlspecialchars($measurement['blood_oxygen_level']); ?>" readonly data-row="<?php echo $measurement['data_id']; ?>" class="input-field">
                </td>
                <td>
                    <input type="text" name="sleep_quality[<?php echo $measurement['data_id']; ?>]" value="<?php echo htmlspecialchars($measurement['sleep_quality']); ?>" readonly data-row="<?php echo $measurement['data_id']; ?>" class="input-field">
                </td>
                <td>
                    <input type="text" name="stress_level[<?php echo $measurement['data_id']; ?>]" value="<?php echo htmlspecialchars($measurement['stress_level']); ?>" readonly data-row="<?php echo $measurement['data_id']; ?>" class="input-field">
                </td>
                <td>
                    <input type="text" name="respiratory_rate[<?php echo $measurement['data_id']; ?>]" value="<?php echo htmlspecialchars($measurement['respiratory_rate']); ?>" readonly data-row="<?php echo $measurement['data_id']; ?>" class="input-field">
                </td>
                <td>
                    <input type="text" name="steps_count[<?php echo $measurement['data_id']; ?>]" value="<?php echo htmlspecialchars($measurement['steps_count']); ?>" readonly data-row="<?php echo $measurement['data_id']; ?>" class="input-field">
                </td>
                <td>
                    <button type="button" id="edit-btn-<?php echo $measurement['data_id']; ?>" class="edit-btn" onclick="enableEdit('<?php echo $measurement['data_id']; ?>')">Edit</button>
                    <button type="submit" name="save" value="<?php echo $measurement['data_id']; ?>" id="save-btn-<?php echo $measurement['data_id']; ?>" class="save-btn" style="display: none;">Save</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </form>
<?php include "includes/footer.php" ?>

