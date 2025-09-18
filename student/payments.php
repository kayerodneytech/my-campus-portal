<?php
session_start();
require_once '../includes/config.php';

// Redirect if not logged in as a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Get student details
$studentId = $_SESSION['user_id'];
$studentQuery = $conn->prepare("
    SELECT s.*, u.first_name, u.last_name, u.email, s.phone
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ?
");
$studentQuery->bind_param("i", $studentId);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$student = $studentResult->fetch_assoc();
$studentStatus = $student ? $student['status'] : 'pending';

// Fetch payment history
$paymentHistory = $conn->query("
    SELECT p.*, c.course_name
    FROM payments p
    LEFT JOIN course_applications ca ON p.reference_number = CONCAT('PAY', ca.id)
    LEFT JOIN courses c ON ca.course_id = c.id
    WHERE p.student_id = $studentId
    ORDER BY p.paid_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Fetch upcoming payments (e.g., tuition, fines)
$upcomingPayments = $conn->query("
    SELECT
        'Tuition' AS type,
        c.course_name,
        c.credits * 50 AS amount,
        DATE_ADD(NOW(), INTERVAL 30 DAY) AS due_date,
        CONCAT('TUITION-', c.id, '-', $studentId) AS reference
    FROM class_enrollments ce
    JOIN classes cl ON ce.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE ce.student_id = $studentId
    AND ce.status = 'enrolled'
    AND NOT EXISTS (
        SELECT 1 FROM payments p
        WHERE p.student_id = $studentId
        AND p.reference_number = CONCAT('TUITION-', c.id, '-', $studentId)
    )
    UNION ALL
    SELECT
        'Fine' AS type,
        'Library Fine' AS course_name,
        f.amount,
        f.issued_on AS due_date,
        CONCAT('FINE-', f.id) AS reference
    FROM fines f
    WHERE f.student_id = $studentId
    AND f.status = 'unpaid'
    ORDER BY due_date ASC
")->fetch_all(MYSQLI_ASSOC);

// Handle new payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    $reference = $_POST['reference'];
    $amount = $_POST['amount'];
    $paymentMethod = $_POST['payment_method'];

    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments
        (student_id, amount, payment_method, payment_type, reference_number, status, paid_at)
        VALUES (?, ?, ?, ?, ?, 'completed', NOW())
    ");
    $paymentType = strpos($reference, 'TUITION') !== false ? 'tuition' : 'fine';
    $stmt->bind_param("idsss", $studentId, $amount, $paymentMethod, $paymentType, $reference);
    $stmt->execute();

    // Update fine status if applicable
    if (strpos($reference, 'FINE') !== false) {
        $fineId = str_replace('FINE-', '', $reference);
        $conn->query("UPDATE fines SET status = 'paid' WHERE id = $fineId");
    }

    $success = "Payment of $$amount for $reference recorded successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Student Portal</title>
    <!-- Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonts/poppins.ttf') format('truetype');
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <!-- Student Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Rest of your payments.php code -->
    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:ml-0">
        <!-- Mobile Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 lg:hidden">
            <div class="flex items-center justify-between p-4">
                <button id="mobile-menu-button" class="text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-8 w-8 rounded-full">
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-8">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-800 to-blue-600 rounded-xl shadow-md text-white p-6 mb-8">
                <h1 class="text-2xl md:text-3xl font-bold mb-2">Payments</h1>
                <p class="text-blue-100">View your payment history and make new payments</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Payment History -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Payment History</h2>
                </div>
                <?php if (empty($paymentHistory)): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                        <i class="fas fa-history text-gray-400 text-3xl mb-2"></i>
                        <p class="text-gray-500 font-medium">No payment history found</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($paymentHistory as $payment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($payment['paid_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($payment['course_name'] ?? 'Payment'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            $<?php echo number_format($payment['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php echo $payment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Payments -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Upcoming Payments</h2>
                </div>
                <?php if (empty($upcomingPayments)): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                        <i class="fas fa-calendar-check text-gray-400 text-3xl mb-2"></i>
                        <p class="text-gray-500 font-medium">No upcoming payments</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($upcomingPayments as $payment): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 card-hover">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($payment['type']); ?>
                                        </h3>
                                        <p class="text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($payment['course_name']); ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full
                                        <?php echo $payment['type'] === 'Tuition' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $payment['type']; ?>
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <p class="text-gray-700">
                                        <span class="font-medium">Amount:</span>
                                        $<?php echo number_format($payment['amount'], 2); ?>
                                    </p>
                                    <p class="text-gray-700">
                                        <span class="font-medium">Due:</span>
                                        <?php echo date('M j, Y', strtotime($payment['due_date'])); ?>
                                    </p>
                                </div>
                                <button
                                    onclick="openPaymentModal(
                                        '<?php echo $payment['reference']; ?>',
                                        '<?php echo $payment['amount']; ?>',
                                        '<?php echo htmlspecialchars($payment['type'] . ': ' . $payment['course_name']); ?>'
                                    )"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-credit-card mr-2"></i> Pay Now
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 id="paymentModalTitle" class="text-xl font-semibold text-gray-800">Make Payment</h2>
                <button onclick="closePaymentModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" id="paymentForm">
                <input type="hidden" name="make_payment" value="1">
                <input type="hidden" name="reference" id="paymentReference">
                <input type="hidden" name="amount" id="paymentAmount">

                <div class="mb-4">
                    <label for="paymentDescription" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <input
                        type="text"
                        id="paymentDescription"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        readonly>
                </div>

                <div class="mb-4">
                    <label for="paymentAmountDisplay" class="block text-sm font-medium text-gray-700 mb-1">
                        Amount
                    </label>
                    <input
                        type="text"
                        id="paymentAmountDisplay"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        readonly>
                </div>

                <div class="mb-4">
                    <label for="paymentMethod" class="block text-sm font-medium text-gray-700 mb-1">
                        Payment Method
                    </label>
                    <select
                        name="payment_method"
                        id="paymentMethod"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Select Payment Method</option>
                        <option value="ecocash">EcoCash</option>
                        <option value="visa">Visa/Mastercard</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        onclick="closePaymentModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('student-sidebar');
            const mobileOverlay = document.getElementById('mobile-overlay');

            // Toggle mobile menu
            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                mobileOverlay.classList.toggle('hidden');
            });

            // Close mobile menu when clicking overlay
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.add('hidden');
                mobileOverlay.classList.add('hidden');
            });
        });

        // Payment modal functions
        function openPaymentModal(reference, amount, description) {
            document.getElementById('paymentReference').value = reference;
            document.getElementById('paymentAmount').value = amount;
            document.getElementById('paymentAmountDisplay').value = '$' + parseFloat(amount).toFixed(2);
            document.getElementById('paymentDescription').value = description;
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }
    </script>
</body>

</html>