<?php
/**
 * Mock Payment API
 * Simulates payment processing for MyCamp Portal
 * Handles various payment types and returns mock responses
 */

session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['amount', 'payment_type', 'payment_method'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

$user_id = $_SESSION['user_id'];
$amount = floatval($input['amount']);
$payment_type = $input['payment_type'];
$payment_method = $input['payment_method'];

// Validate payment type
$valid_payment_types = ['application_fee', 'tuition', 'fine', 'other'];
if (!in_array($payment_type, $valid_payment_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment type']);
    exit;
}

// Validate payment method
$valid_payment_methods = ['ecocash', 'onemoney', 'telecash', 'bank_transfer', 'cash'];
if (!in_array($payment_method, $valid_payment_methods)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

// Validate amount
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment amount']);
    exit;
}

try {
    // Generate reference number
    $reference_number = 'PAY' . date('Ymd') . str_pad($user_id, 4, '0', STR_PAD_LEFT) . rand(1000, 9999);
    
    // Simulate payment processing delay
    sleep(2);
    
    // Mock payment success (90% success rate)
    $payment_success = (rand(1, 10) <= 9);
    
    if ($payment_success) {
        // Update payment record
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'completed', 
                reference_number = ?, 
                paid_at = NOW() 
            WHERE student_id = ? AND payment_type = ? AND status = 'pending'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("sis", $reference_number, $user_id, $payment_type);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Create success notification
            $notification_message = "Payment successful! Reference: " . $reference_number . " Amount: $" . number_format($amount, 2);
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->bind_param("is", $user_id, $notification_message);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment processed successfully',
                'reference_number' => $reference_number,
                'amount' => $amount,
                'payment_method' => $payment_method,
                'transaction_id' => 'TXN' . time() . rand(1000, 9999)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No pending payment found for this type']);
        }
        $stmt->close();
    } else {
        // Mock payment failure
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'failed' 
            WHERE student_id = ? AND payment_type = ? AND status = 'pending'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("is", $user_id, $payment_type);
        $stmt->execute();
        $stmt->close();
        
        // Create failure notification
        $notification_message = "Payment failed for " . $payment_type . ". Please try again or contact support.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->bind_param("is", $user_id, $notification_message);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        echo json_encode([
            'success' => false,
            'message' => 'Payment failed. Please try again or contact support.',
            'error_code' => 'PAYMENT_FAILED'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing payment']);
}

$conn->close();
?>
