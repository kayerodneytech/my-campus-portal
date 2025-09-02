<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $idNumber = trim($_POST['idNumber'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $courseOfChoice = $_POST['courseOfChoice'] ?? '';
        $kinFullName = trim($_POST['kinFullName'] ?? '');
        $kinPhone = trim($_POST['kinPhone'] ?? '');
        
        // Validate required fields
        if (empty($fullName) || empty($email) || empty($password) || empty($phone) || 
            empty($idNumber) || empty($dob) || empty($courseOfChoice) || 
            empty($kinFullName) || empty($kinPhone)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit;
        }
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
            exit;
        }
        $check_stmt->close();
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/enrollments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Handle file uploads
        $uploaded_files = [];
        $required_files = ['paymentProof', 'qualificationCert', 'birthCert'];
        
        foreach ($required_files as $file_field) {
            if (!isset($_FILES[$file_field]) || $_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'All document uploads are required.']);
                exit;
            }
            
            $file = $_FILES[$file_field];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file format. Only PDF and image files are allowed.']);
                exit;
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '_' . $file_field . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $uploaded_files[$file_field] = $upload_path;
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed.']);
                exit;
            }
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, id_number, dob, course_of_choice, kin_name, kin_phone, payment_proof, qualification_cert, birth_cert, status, created_at) VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        
        $stmt->bind_param("ssssssssssss", 
            $fullName, $email, $hashed_password, $phone, $idNumber, $dob, 
            $courseOfChoice, $kinFullName, $kinPhone,
            $uploaded_files['paymentProof'],
            $uploaded_files['qualificationCert'],
            $uploaded_files['birthCert']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Enrollment application submitted successfully! You will receive an email confirmation once your application is reviewed.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit enrollment. Please try again.']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your enrollment.']);
        error_log("Enrollment error: " . $e->getMessage());
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
