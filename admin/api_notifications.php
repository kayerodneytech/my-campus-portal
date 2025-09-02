<?php
// admin/api_notifications.php
include("../includes/config.php");
header('Content-Type: application/json');

$message = trim($_POST['message'] ?? '');
$recipient_type = $_POST['recipient_type'] ?? 'all';

if ($message && in_array($recipient_type, ['all','students','lecturers'])) {
    $stmt = $conn->prepare("INSERT INTO notifications (message, recipient_type, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('ss', $message, $recipient_type);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing message or invalid recipient type']);
}
