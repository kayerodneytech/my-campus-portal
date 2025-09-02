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
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $hashed_password, $role);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                // Login success
                $_SESSION["user_id"] = $id;
                $_SESSION["user_name"] = $name;
                $_SESSION["user_role"] = $role;
                
                // Return redirect URL based on role
                $redirect_url = '';
                switch ($role) {
                    case 'admin':
                        $redirect_url = 'admin/index.php';
                        break;
                    case 'lecturer':
                        $redirect_url = 'lecturers/index.php';
                        break;
                    case 'student':
                        $redirect_url = 'students/index.php';
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
