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
if (empty($input['email']) || empty($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

$email = trim($input['email']);
$password = $input['password'];

try {
    // Check user credentials
    $stmt = $conn->prepare("SELECT u.*, s.status as student_status 
                           FROM users u 
                           LEFT JOIN students s ON u.id = s.user_id 
                           WHERE u.email = ? AND u.status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit();
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    
    // Set student status if user is a student
    if ($user['role'] === 'student') {
        $_SESSION['student_status'] = $user['student_status'] ?? 'pending';
    }
    
    // Set remember me cookie if requested
    if (isset($input['remember_me']) && $input['remember_me']) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $tokenStmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
        $expiryDate = date('Y-m-d H:i:s', $expiry);
        $tokenStmt->bind_param("issss", $user['id'], $token, $expiryDate, $token, $expiryDate);
        $tokenStmt->execute();
        $tokenStmt->close();
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    // Determine redirect URL based on user role
    $redirect_url = 'index.php';
    switch ($user['role']) {
        case 'admin':
        case 'super_admin':
            $redirect_url = 'admin/dashboard.php';
            break;
        case 'lecturer':
            $redirect_url = 'lecturer/dashboard.php';
            break;
        case 'student':
            $redirect_url = 'student/index.php';
            break;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'redirect_url' => $redirect_url
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during login']);
}
?>