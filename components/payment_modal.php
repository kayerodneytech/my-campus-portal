<?php
/**
 * Payment Modal Component
 * Modern modal for processing payments with Tailwind CSS
 * Used in student dashboard and other payment areas
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    return;
}

// Fetch pending payments for the user
require_once './includes/config.php';
$pending_payments = [];

try {
    $stmt = $conn->prepare("
        SELECT id, amount, payment_type, created_at 
        FROM payments 
        WHERE student_id = ? AND status = 'pending' 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pending_payments[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching payments: " . $e->getMessage());
}
?>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-95 opacity-0" id="paymentModalContent">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-credit-card text-white text-xl"></i>
                        <span class="fa-fallback text-white text-xl" style="display: none;">💳</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Make Payment</h2>
                        <p class="text-gray-600">Process your pending payments</p>
                    </div>
                </div>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <i class="fas fa-times text-xl"></i>
                    <span class="fa-fallback text-xl" style="display: none;">✕</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- Pending Payments List -->
                <?php if (!empty($pending_payments)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pending Payments</h3>
                    <div class="space-y-3">
                        <?php foreach ($pending_payments as $payment): ?>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-800 capitalize">
                                        <?php echo str_replace('_', ' ', $payment['payment_type']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Due: <?php echo date('M j, Y', strtotime($payment['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-800">
                                        $<?php echo number_format($payment['amount'], 2); ?>
                                    </p>
                                    <button onclick="selectPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>, '<?php echo $payment['payment_type']; ?>')"
                                            class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm">
                                        Pay Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <div class="text-6xl text-gray-300 mb-4">
                        <i class="fas fa-check-circle"></i>
                        <span class="fa-fallback" style="display: none;">✅</span>
                    </div>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">No Pending Payments</h3>
                    <p class="text-gray-500">You're all caught up with your payments!</p>
                </div>
                <?php endif; ?>

                <!-- Payment Form -->
                <div id="paymentForm" class="hidden">
                    <form id="paymentFormElement" class="space-y-6">
                        <div id="paymentError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">⚠️</span>
                                <span id="paymentErrorText"></span>
                            </div>
                        </div>

                        <div id="paymentSuccess" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">✅</span>
                                <span id="paymentSuccessText"></span>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2">Payment Details</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Type:</span>
                                    <span id="selectedPaymentType" class="font-medium text-gray-800 ml-2"></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Amount:</span>
                                    <span id="selectedAmount" class="font-medium text-gray-800 ml-2"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-credit-card mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">💳</span>
                                Payment Method *
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="ecocash" class="mr-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-mobile-alt text-green-600 mr-2"></i>
                                        <span class="fa-fallback text-green-600 mr-2" style="display: none;">📱</span>
                                        <span>EcoCash</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="onemoney" class="mr-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>
                                        <span class="fa-fallback text-blue-600 mr-2" style="display: none;">📱</span>
                                        <span>OneMoney</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="telecash" class="mr-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-mobile-alt text-purple-600 mr-2"></i>
                                        <span class="fa-fallback text-purple-600 mr-2" style="display: none;">📱</span>
                                        <span>TeleCash</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="bank_transfer" class="mr-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-university text-indigo-600 mr-2"></i>
                                        <span class="fa-fallback text-indigo-600 mr-2" style="display: none;">🏦</span>
                                        <span>Bank Transfer</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Payment Instructions -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h5 class="font-medium text-yellow-800 mb-2">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">ℹ️</span>
                                Payment Instructions
                            </h5>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• This is a mock payment system for testing purposes</li>
                                <li>• Payments are simulated and will be marked as completed</li>
                                <li>• In a real system, you would be redirected to your payment provider</li>
                            </ul>
                        </div>

                        <div class="flex space-x-4">
                            <button type="button" onclick="closePaymentModal()"
                                    class="flex-1 bg-gray-300 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-400 transition-colors duration-200 font-medium">
                                Cancel
                            </button>
                            <button type="submit" id="paymentSubmitBtn"
                                    class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 font-medium">
                                <span id="paymentBtnText">Process Payment</span>
                                <i class="fas fa-spinner fa-spin ml-2 hidden" id="paymentSpinner"></i>
                                <span class="fa-fallback ml-2 hidden" style="display: none;">⏳</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Payment Modal Functions
function openPaymentModal() {
    const modal = document.getElementById('paymentModal');
    const content = document.getElementById('paymentModalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    const content = document.getElementById('paymentModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('paymentForm').classList.add('hidden');
        document.getElementById('paymentFormElement').reset();
        document.getElementById('paymentError').classList.add('hidden');
        document.getElementById('paymentSuccess').classList.add('hidden');
    }, 300);
}

function selectPayment(paymentId, amount, paymentType) {
    document.getElementById('selectedPaymentType').textContent = paymentType.replace('_', ' ');
    document.getElementById('selectedAmount').textContent = '$' + amount.toFixed(2);
    document.getElementById('paymentForm').classList.remove('hidden');
    
    // Store payment details for form submission
    document.getElementById('paymentFormElement').dataset.paymentId = paymentId;
    document.getElementById('paymentFormElement').dataset.amount = amount;
    document.getElementById('paymentFormElement').dataset.paymentType = paymentType;
}

// Payment Form Submission
document.getElementById('paymentFormElement').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('paymentSubmitBtn');
    const btnText = document.getElementById('paymentBtnText');
    const spinner = document.getElementById('paymentSpinner');
    const errorDiv = document.getElementById('paymentError');
    const successDiv = document.getElementById('paymentSuccess');
    const errorText = document.getElementById('paymentErrorText');
    const successText = document.getElementById('paymentSuccessText');
    
    const formData = new FormData(this);
    const paymentMethod = formData.get('payment_method');
    
    if (!paymentMethod) {
        errorText.textContent = 'Please select a payment method';
        errorDiv.classList.remove('hidden');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Processing...';
    spinner.classList.remove('hidden');
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');
    
    const paymentData = {
        amount: parseFloat(this.dataset.amount),
        payment_type: this.dataset.paymentType,
        payment_method: paymentMethod
    };
    
    try {
        const response = await fetch('api/payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            successText.textContent = result.message + ' Reference: ' + result.reference_number;
            successDiv.classList.remove('hidden');
            btnText.textContent = 'Success!';
            spinner.classList.add('hidden');
            
            // Close modal and reload page after delay
            setTimeout(() => {
                closePaymentModal();
                window.location.reload();
            }, 2000);
        } else {
            // Show error message
            errorText.textContent = result.message || 'Payment failed. Please try again.';
            errorDiv.classList.remove('hidden');
            
            // Reset button state
            submitBtn.disabled = false;
            btnText.textContent = 'Process Payment';
            spinner.classList.add('hidden');
        }
    } catch (error) {
        console.error('Payment error:', error);
        errorText.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('hidden');
        
        // Reset button state
        submitBtn.disabled = false;
        btnText.textContent = 'Process Payment';
        spinner.classList.add('hidden');
    }
});

// Close modal when clicking outside
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('paymentModal').classList.contains('hidden')) {
        closePaymentModal();
    }
});
</script>
