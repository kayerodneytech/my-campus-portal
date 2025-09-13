<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (empty($input['activity_type']) || empty($input['description'])) {
    echo json_encode(['success' => false, 'message' => 'Activity type and description are required']);
    exit();
}

$activity_type = $input['activity_type'];
$description = $input['description'];
$additional_data = isset($input['additional_data']) ? $input['additional_data'] : null;

// Get user ID from session (if logged in)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get client information
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

try {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address, user_agent, additional_data) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $activity_type, $description, $ip_address, $user_agent, $additional_data);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to log activity']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Activity logging error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error logging activity']);
}
?>