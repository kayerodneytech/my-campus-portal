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
$requiredFields = ['first_name', 'last_name', 'email', 'phone', 'password', 'course_id', 'payment_method', 
                  'date_of_birth', 'gender', 'address', 'nationality', 'id_number', 'emergency_contact', 
                  'emergency_phone', 'level'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Validate email
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists
$checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkEmail->bind_param("s", $input['email']);
$checkEmail->execute();
$checkEmail->store_result();

if ($checkEmail->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}
$checkEmail->close();

// Generate application number
$applicationNumber = 'APP' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Hash password
$hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Insert into users table
    $userStmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'student', 'active')");
    $userStmt->bind_param("sssss", $input['first_name'], $input['last_name'], $input['email'], $input['phone'], $hashedPassword);
    
    if (!$userStmt->execute()) {
        throw new Exception("Failed to create user: " . $userStmt->error);
    }
    
    $userId = $conn->insert_id;
    $userStmt->close();

    // Insert into students table with all fields
    $studentStmt = $conn->prepare("INSERT INTO students (user_id, application_number, level, date_of_birth, gender, address, nationality, id_number, emergency_contact, emergency_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $studentStmt->bind_param("isssssssss", $userId, $applicationNumber, $input['level'], $input['date_of_birth'], $input['gender'], $input['address'], $input['nationality'], $input['id_number'], $input['emergency_contact'], $input['emergency_phone']);
    
    if (!$studentStmt->execute()) {
        throw new Exception("Failed to create student record: " . $studentStmt->error);
    }
    $studentStmt->close();

    // Insert into course_applications table
    $courseAppStmt = $conn->prepare("INSERT INTO course_applications (student_id, course_id, status, notes) VALUES (?, ?, 'pending', ?)");
    $paymentNote = "Payment method: " . $input['payment_method'];
    $courseAppStmt->bind_param("iis", $userId, $input['course_id'], $paymentNote);
    
    if (!$courseAppStmt->execute()) {
        throw new Exception("Failed to create course application: " . $courseAppStmt->error);
    }
    $courseAppStmt->close();

    // Insert into payments table
    $paymentStmt = $conn->prepare("INSERT INTO payments (student_id, amount, payment_method, payment_type, reference_number, status, paid_at) VALUES (?, 50.00, ?, 'registration_fee', ?, 'completed', NOW())");
    $referenceNumber = 'PAY' . date('YmdHis') . mt_rand(100, 999);
    $paymentStmt->bind_param("iss", $userId, $input['payment_method'], $referenceNumber);
    
    if (!$paymentStmt->execute()) {
        throw new Exception("Failed to create payment record: " . $paymentStmt->error);
    }
    $paymentStmt->close();

    // Commit transaction
    $conn->commit();

    // Set session for immediate login
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = 'student';
    $_SESSION['user_name'] = $input['first_name'] . ' ' . $input['last_name'];
    $_SESSION['student_status'] = 'pending';

    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => 'Registration completed successfully! Your application number is: ' . $applicationNumber,
        'application_number' => $applicationNumber
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>