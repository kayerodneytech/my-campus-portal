<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $first_name, $last_name, $hashed_password, $role, $status);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                // Check if account is active
                if ($status !== 'active') {
                    echo json_encode(['success' => false, 'message' => 'Your account is not active. Please contact support.']);
                    exit;
                }
                
                // Login success
                $_SESSION["user_id"] = $id;
                $_SESSION["user_name"] = $first_name . ' ' . $last_name;
                $_SESSION["user_email"] = $email;
                $_SESSION["user_role"] = $role;
                
                // Return redirect URL based on role
                $redirect_url = '';
                switch ($role) {
                    case 'admin':
                    case 'super_admin':
                        $redirect_url = 'admin/index.php';
                        break;
                    case 'lecturer':
                        $redirect_url = 'lecturers/dashboard.php';
                        break;
                    case 'student':
                        $redirect_url = 'students_frrdy/students_dashboard.php';
                        break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'Invalid user role.']);
                        exit;
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Login successful! Redirecting...', 
                    'redirect_url' => $redirect_url
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No user found with that email address.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
