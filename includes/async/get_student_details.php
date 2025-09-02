<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isAdmin() || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit();
}

$userId = intval($_GET['id']);

$query = "SELECT u.*, s.* 
          FROM users u 
          JOIN students s ON u.id = s.user_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$student = $result->fetch_assoc();

if ($student) {
    echo json_encode(['success' => true, 'student' => $student]);
} else {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
}
?>