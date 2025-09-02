<?php
/**
 * Process Enrollment API
 * Handles user registration and returns JSON response
 * Works with the new database structure
 */

session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password', 'course_of_interest'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

// Validate password confirmation
if ($input['password'] !== $input['confirm_password']) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }
        
        // Validate email format
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate password strength
if (strlen($input['password']) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            exit;
        }
        
try {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $input['email']);
        $check_stmt->execute();
    $check_result = $check_stmt->get_result();
        
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists']);
            exit;
        }
        $check_stmt->close();
        
    // Start transaction
    $conn->begin_transaction();
    
    // Insert user
    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
    $user_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'student', 'pending')");
    $user_stmt->bind_param("sssss", $input['first_name'], $input['last_name'], $input['email'], $input['phone'], $hashed_password);
    
    if (!$user_stmt->execute()) {
        throw new Exception("Failed to create user account");
    }
    
    $user_id = $conn->insert_id;
    $user_stmt->close();
    
    // Generate application number
    $application_number = 'APP' . date('Y') . str_pad($user_id, 6, '0', STR_PAD_LEFT);
    
    // Insert student record
    $student_stmt = $conn->prepare("INSERT INTO students (user_id, application_number, level) VALUES (?, ?, ?)");
    $student_stmt->bind_param("iss", $user_id, $application_number, $input['course_of_interest']);
    
    if (!$student_stmt->execute()) {
        throw new Exception("Failed to create student record");
    }
    $student_stmt->close();
    
    // Create initial payment record for application fee
    $payment_stmt = $conn->prepare("INSERT INTO payments (student_id, amount, payment_type, status) VALUES (?, 50.00, 'application_fee', 'pending')");
    $payment_stmt->bind_param("i", $user_id);
    $payment_stmt->execute();
    $payment_stmt->close();
    
    // Create welcome notification
    $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $welcome_message = "Welcome to MyCamp Portal! Your application has been submitted successfully. Application Number: " . $application_number;
    $notification_stmt->bind_param("is", $user_id, $welcome_message);
    $notification_stmt->execute();
    $notification_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
            echo json_encode([
                'success' => true, 
        'message' => 'Application submitted successfully! Your application number is ' . $application_number . '. Please check your email for further instructions.',
        'application_number' => $application_number
            ]);
        
    } catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
        error_log("Enrollment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your application. Please try again.']);
}

$conn->close();
?>
