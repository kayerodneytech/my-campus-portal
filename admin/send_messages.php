<?php
include_once '../../includes/session.php';
include_once '../../includes/config.php';
include_once '../../includes/header.php';

$students = [];

// Fetch student list
$result = $conn->query("SELECT id, full_name FROM students ORDER BY full_name ASC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = $_POST['recipient'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    if (empty($subject) || empty($message)) {
        $error = "Subject and message cannot be empty.";
    } else {
        if ($recipient === 'all') {
            $stmt = $conn->prepare("SELECT id FROM students");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($student = $result->fetch_assoc()) {
                $insert = $conn->prepare("INSERT INTO messages (student_id, subject, message, sender) VALUES (?, ?, ?, 'Admin')");
                $insert->bind_param("iss", $student['id'], $subject, $message);
                $insert->execute();
                $insert->close();
            }
            $success = "Message sent to all students.";
        } else {
            $insert = $conn->prepare("INSERT INTO messages (student_id, subject, message, sender) VALUES (?, ?, ?, 'Admin')");
            $insert->bind_param("iss", $recipient, $subject, $message);
            if ($insert->execute()) {
                $success = "Message sent successfully.";
            } else {
                $error = "Failed to send message.";
            }
            $insert->close();
        }
    }
}
?>

<style>
    .form-group {
        margin-bottom: 15px;
        color: white;
    }
    label {
        display: block;
        margin-bottom: 6px;
    }
    input, textarea, select {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: none;
        outline: none;
        background: #2d2d3c;
        color: white;
    }
    button {
        padding: 10px 20px;
        background: #6c63ff;
        border: none;
        border-radius: 8px;
        color: white;
        cursor: pointer;
    }
    .alert {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .success {
        background: #28a745;
        color: white;
    }
    .error {
        background: #dc3545;
        color: white;
    }
</style>

<h2>📨 Send Message</h2>

<?php if ($success): ?>
    <div class="alert success"><?= $success ?></div>
<?php elseif ($error): ?>
    <div class="alert error"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label>Recipient</label>
        <select name="recipient">
            <option value="all">All Students</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" required>
    </div>

    <div class="form-group">
        <label>Message</label>
        <textarea name="message" rows="5" required></textarea>
    </div>

    <button type="submit">Send</button>
</form>

<?php include_once '../../includes/footer.php'; ?>