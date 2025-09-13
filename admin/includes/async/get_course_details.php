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
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit();
}

$courseId = intval($_GET['id']);

$query = "SELECT c.*, d.name as department_name 
          FROM courses c 
          LEFT JOIN departments d ON c.department_id = d.id 
          WHERE c.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $courseId);
$stmt->execute();
$result = $stmt->get_result();

$course = $result->fetch_assoc();

if ($course) {
    echo json_encode(['success' => true, 'course' => $course]);
} else {
    echo json_encode(['success' => false, 'message' => 'Course not found']);
}
?>