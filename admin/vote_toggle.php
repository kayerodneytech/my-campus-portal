<?php
include '../includes/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['voting_status'];
    $deadline = $_POST['voting_deadline'];

    $stmt = $conn->prepare("UPDATE src_settings SET is_voting_open = ?, voting_deadline = ? WHERE id = 1");
    $stmt->bind_param("is", $status, $deadline);
    $stmt->execute();
}
$settings = $conn->query("SELECT * FROM src_settings WHERE id = 1")->fetch_assoc();
?>

<form method="POST">
    <label>Voting Status:</label>
    <select name="voting_status">
        <option value="1" <?= $settings['is_voting_open'] ? 'selected' : '' ?>>Open</option>
        <option value="0" <?= !$settings['is_voting_open'] ? 'selected' : '' ?>>Closed</option>
    </select><br><br>
    <label>Voting Deadline:</label>
    <input type="datetime-local" name="voting_deadline" value="<?= date('Y-m-d\TH:i', strtotime($settings['voting_deadline'])) ?>"><br><br>
    <button type="submit">Update Settings</button>
</form>