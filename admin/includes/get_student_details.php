<?php
require_once 'config.php';
require_once 'auth.php';

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
          WHERE u.id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo json_encode(['success' => true, 'student' => $student]);
} else {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
}
?>