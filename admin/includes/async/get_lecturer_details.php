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
    echo json_encode(['success' => false, 'message' => 'Lecturer ID required']);
    exit();
}

$lecturerId = intval($_GET['id']);

$query = "SELECT u.*, lp.* 
          FROM users u 
          JOIN lecturer_profiles lp ON u.id = lp.user_id 
          WHERE u.id = ? AND u.role = 'lecturer'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lecturerId);
$stmt->execute();
$result = $stmt->get_result();

$lecturer = $result->fetch_assoc();

if ($lecturer) {
    echo json_encode(['success' => true, 'lecturer' => $lecturer]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lecturer not found']);
}
?>