<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin() || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$userId = intval($data['user_id']);
$status = $data['status'];

// Validate status
$validStatuses = ['pending', 'active', 'suspended', 'rejected'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $db->beginTransaction();
    
    // Update student status
    $query = "UPDATE students SET status = :status, updated_at = NOW() WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // If approving, also activate the user account
    if ($status === 'active') {
        $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>