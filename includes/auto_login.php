<?php
// Auto login with remember token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once 'config.php';
    
    $token = $_COOKIE['remember_token'];
    
    $stmt = $conn->prepare("SELECT u.*, s.status as student_status 
                           FROM remember_tokens rt 
                           JOIN users u ON rt.user_id = u.id 
                           LEFT JOIN students s ON u.id = s.user_id 
                           WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        
        if ($user['role'] === 'student') {
            $_SESSION['student_status'] = $user['student_status'] ?? 'pending';
        }
        
        // Refresh token (optional)
        $newToken = bin2hex(random_bytes(32));
        $newExpiry = time() + (30 * 24 * 60 * 60);
        
        $updateStmt = $conn->prepare("UPDATE remember_tokens SET token = ?, expires_at = ? WHERE user_id = ?");
        $newExpiryDate = date('Y-m-d H:i:s', $newExpiry);
        $updateStmt->bind_param("ssi", $newToken, $newExpiryDate, $user['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        setcookie('remember_token', $newToken, $newExpiry, '/', '', true, true);
    } else {
        // Invalid token, clear cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    $stmt->close();
}
?>