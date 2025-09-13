<?php
/**
 * Multi-step Registration Modal Component
 * Modern modal-based registration form with Tailwind CSS
 * Used across the portal for user registration
 */
?>

<!-- Registration Modal -->
<div id="registerModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-95 opacity-0" id="registerModalContent">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-plus text-white text-xl"></i>
                        <span class="fa-fallback text-white text-xl" style="display: none;">📝</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Apply Now</h2>
                        <p class="text-gray-600">Join MyCamp Portal today</p>
                    </div>
                </div>
                <button onclick="closeRegisterModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <i class="fas fa-times text-xl"></i>
                    <span class="fa-fallback text-xl" style="display: none;">✕</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        <div class="flex-1 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold step-indicator" data-step="1">1</div>
                            <div class="ml-2 text-sm font-medium text-blue-600 step-label">Personal Info</div>
                        </div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
                        <div class="flex-1 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center text-sm font-bold step-indicator" data-step="2">2</div>
                            <div class="ml-2 text-sm font-medium text-gray-500 step-label">Course Selection</div>
                        </div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
                        <div class="flex-1 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center text-sm font-bold step-indicator" data-step="3">3</div>
                            <div class="ml-2 text-sm font-medium text-gray-500 step-label">Payment</div>
                        </div>
                    </div>
                </div>

                <form id="registerForm" class="space-y-6">
                    <div id="registerError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">⚠️</span>
                            <span id="registerErrorText"></span>
                        </div>
                    </div>

                    <div id="registerSuccess" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">✅</span>
                            <span id="registerSuccessText"></span>
                        </div>
                    </div>

                    <!-- Step 1: Personal Information -->
                    <div id="step1" class="step-content">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2"></i>
                                    First Name *
                                </label>
                                <input type="text" id="firstName" name="first_name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter your first name">
                            </div>

                            <div>
                                <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2"></i>
                                    Last Name *
                                </label>
                                <input type="text" id="lastName" name="last_name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter your last name">
                            </div>
                        </div>

                        <div>
                            <label for="registerEmail" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2"></i>
                                Email Address *
                            </label>
                            <input type="email" id="registerEmail" name="email" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter your email address">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2"></i>
                                Phone Number *
                            </label>
                            <input type="tel" id="phone" name="phone" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter your phone number">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar mr-2"></i>
                                    Date of Birth *
                                </label>
                                <input type="date" id="dateOfBirth" name="date_of_birth" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-venus-mars mr-2"></i>
                                    Gender *
                                </label>
                                <select id="gender" name="gender" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                                                    </select>
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-home mr-2"></i>
                                Address *
                            </label>
                            <textarea id="address" name="address" required rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Enter your full address"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-flag mr-2"></i>
                                    Nationality *
                                </label>
                                <input type="text" id="nationality" name="nationality" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Your nationality">
                            </div>

                            <div>
                                <label for="idNumber" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-card mr-2"></i>
                                    ID Number *
                                </label>
                                <input type="text" id="idNumber" name="id_number" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="National ID/Passport">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="emergencyContact" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user-plus mr-2"></i>
                                    Emergency Contact *
                                </label>
                                <input type="text" id="emergencyContact" name="emergency_contact" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Contact person name">
                            </div>

                            <div>
                                <label for="emergencyPhone" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone-alt mr-2"></i>
                                    Emergency Phone *
                                </label>
                                <input type="tel" id="emergencyPhone" name="emergency_phone" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Emergency phone number">
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="registerPassword" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2"></i>
                                    Password *
                                </label>
                                <div class="relative">
                                    <input type="password" id="registerPassword" name="password" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                                           placeholder="Create a password">
                                    <button type="button" onclick="togglePasswordVisibility('registerPassword')"
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-eye" id="registerPasswordToggle"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2"></i>
                                    Confirm Password *
                                </label>
                                <div class="relative">
                                    <input type="password" id="confirmPassword" name="confirm_password" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                                           placeholder="Confirm your password">
                                    <button type="button" onclick="togglePasswordVisibility('confirmPassword')"
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-eye" id="confirmPasswordToggle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="button" onclick="nextStep(2)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Next: Course Selection <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Course Selection -->
                    <div id="step2" class="step-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Course Selection</h3>
                        
                        <div>
                            <label for="courseOfInterest" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                Course of Interest *
                            </label>
                            <select id="courseOfInterest" name="course_id" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Select a Course --</option>
                                <?php
                                // Get courses from database for the dropdown
                                $courseQuery = "SELECT id, course_name, level FROM courses ORDER BY level, course_name";
                                $courseResult = $conn->query($courseQuery);
                                
                                if ($courseResult && $courseResult->num_rows > 0) {
                                    $currentLevel = '';
                                    while ($course = $courseResult->fetch_assoc()) {
                                        if ($course['level'] !== $currentLevel) {
                                            if ($currentLevel !== '') {
                                                echo '</optgroup>';
                                            }
                                            $currentLevel = $course['level'];
                                            echo '<optgroup label="' . ucfirst($currentLevel) . ' Courses">';
                                        }
                                        echo '<option value="' . $course['id'] . '">' . htmlspecialchars($course['course_name']) . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label for="educationLevel" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                Education Level *
                            </label>
                            <select id="educationLevel" name="level" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Select Level --</option>
                                <option value="certificate">Certificate</option>
                                <option value="diploma">Diploma</option>
                                <option value="degree">Degree</option>
                                <option value="masters">Masters</option>
                            </select>
                        </div>

                        <div class="flex justify-between mt-6">
                            <button type="button" onclick="prevStep(1)" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button type="button" onclick="nextStep(3)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Next: Payment <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Payment -->
                    <div id="step3" class="step-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Registration Fee Payment</h3>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-blue-800">Registration Fee</p>
                                    <p class="text-sm text-blue-600">One-time payment to complete your application</p>
                                </div>
                                <div class="text-2xl font-bold text-blue-800">$50.00</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-credit-card mr-2"></i>
                                Payment Method *
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="ecocash" required class="mr-3 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="font-medium text-gray-800">EcoCash</div>
                                        <div class="text-sm text-gray-600">Pay via EcoCash mobile money</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="debit_card" required class="mr-3 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="font-medium text-gray-800">Debit/Credit Card</div>
                                        <div class="text-sm text-gray-600">Pay with Visa/Mastercard</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Payment Details (will show based on selection) -->
                        <div id="ecocashDetails" class="payment-details hidden mt-4">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-sm text-yellow-800 mb-2">Please use the following details for EcoCash payment:</p>
                                <div class="space-y-2">
                                    <p class="text-sm"><strong>Merchant:</strong> MyCamp Institution</p>
                                    <p class="text-sm"><strong>Account:</strong> 0771234567</p>
                                    <p class="text-sm"><strong>Reference:</strong> Your phone number</p>
                                </div>
                            </div>
                        </div>

                        <div id="debitCardDetails" class="payment-details hidden mt-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <p class="text-sm text-green-800 mb-2">Enter your card details:</p>
                                <div class="space-y-3">
                                    <input type="text" name="card_number" placeholder="Card Number" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="text" name="expiry_date" placeholder="MM/YY" 
                                               class="px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                        <input type="text" name="cvv" placeholder="CVV" 
                                               class="px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between mt-6">
                            <button type="button" onclick="prevStep(2)" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button type="button" id="payButton" onclick="processPayment()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                Pay Registration Fee <i class="fas fa-lock ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Final Registration -->
                    <div id="step4" class="step-content hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Complete Registration</h3>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                                <div>
                                    <p class="font-semibold text-green-800">Payment Successful!</p>
                                    <p class="text-sm text-green-600">Your registration fee has been processed successfully.</p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <p class="text-gray-600 mb-4">Click the button below to complete your registration:</p>
                            <button type="submit" id="finalRegisterBtn" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700">
                                Complete Registration <i class="fas fa-check ml-2"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Already have an account? 
                        <button onclick="switchToLogin()" class="text-blue-600 hover:text-blue-800 font-medium">
                            Sign In
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;

// Registration Modal Functions
function openRegisterModal() {
    const modal = document.getElementById('registerModal');
    const content = document.getElementById('registerModalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Check if a course was selected from the courses page
    const selectedCourseId = localStorage.getItem('selectedCourseId');
    const selectedCourseName = localStorage.getItem('selectedCourse');
    
    if (selectedCourseId && selectedCourseName) {
        // Find and select the course in the dropdown
        const courseSelect = document.getElementById('courseOfInterest');
        for (let i = 0; i < courseSelect.options.length; i++) {
            if (courseSelect.options[i].value === selectedCourseId) {
                courseSelect.selectedIndex = i;
                break;
            }
        }
        
        // Clear the stored selection
        localStorage.removeItem('selectedCourseId');
        localStorage.removeItem('selectedCourse');
    }
    
    // Focus on first name input
    setTimeout(() => {
        document.getElementById('firstName').focus();
    }, 300);
}

function closeRegisterModal() {
    const modal = document.getElementById('registerModal');
    const content = document.getElementById('registerModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('registerForm').reset();
        document.getElementById('registerError').classList.add('hidden');
        document.getElementById('registerSuccess').classList.add('hidden');
        resetSteps();
    }, 300);
}

function resetSteps() {
    currentStep = 1;
    document.querySelectorAll('.step-content').forEach(step => step.classList.add('hidden'));
    document.getElementById('step1').classList.remove('hidden');
    
    // Reset step indicators
    document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
        if (index === 0) {
            indicator.classList.add('bg-blue-600', 'text-white');
            indicator.classList.remove('bg-gray-300', 'text-gray-600');
        } else {
            indicator.classList.remove('bg-blue-600', 'text-white');
            indicator.classList.add('bg-gray-300', 'text-gray-600');
        }
    });
    
    document.querySelectorAll('.step-label').forEach((label, index) => {
        if (index === 0) {
            label.classList.add('text-blue-600');
            label.classList.remove('text-gray-500');
        } else {
            label.classList.remove('text-blue-600');
            label.classList.add('text-gray-500');
        }
    });
}

function nextStep(step) {
    // Validate current step
    if (currentStep === 1 && !validateStep1()) return;
    if (currentStep === 2 && !validateStep2()) return;
    
    document.getElementById(`step${currentStep}`).classList.add('hidden');
    document.getElementById(`step${step}`).classList.remove('hidden');
    
    // Update step indicators
    document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.remove('bg-blue-600', 'text-white');
    document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.add('bg-gray-300', 'text-gray-600');
    document.querySelector(`.step-label[data-step="${currentStep}"]`).classList.remove('text-blue-600');
    document.querySelector(`.step-label[data-step="${currentStep}"]`).classList.add('text-gray-500');
    
    document.querySelector(`.step-indicator[data-step="${step}"]`).classList.remove('bg-gray-300', 'text-gray-600');
    document.querySelector(`.step-indicator[data-step="${step}"]`).classList.add('bg-blue-600', 'text-white');
    document.querySelector(`.step-label[data-step="${step}"]`).classList.remove('text-gray-500');
    document.querySelector(`.step-label[data-step="${step}"]`).classList.add('text-blue-600');
    
    currentStep = step;
}

function prevStep(step) {
    document.getElementById(`step${currentStep}`).classList.add('hidden');
    document.getElementById(`step${step}`).classList.remove('hidden');
    
    // Update step indicators
    document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.remove('bg-blue-600', 'text-white');
    document.querySelector(`.step-indicator[data-step="${currentStep}"]`).classList.add('bg-gray-300', 'text-gray-600');
    document.querySelector(`.step-label[data-step="${currentStep}"]`).classList.remove('text-blue-600');
    document.querySelector(`.step-label[data-step="${currentStep}"]`).classList.add('text-gray-500');
    
    document.querySelector(`.step-indicator[data-step="${step}"]`).classList.remove('bg-gray-300', 'text-gray-600');
    document.querySelector(`.step-indicator[data-step="${step}"]`).classList.add('bg-blue-600', 'text-white');
    document.querySelector(`.step-label[data-step="${step}"]`).classList.remove('text-gray-500');
    document.querySelector(`.step-label[data-step="${step}"]`).classList.add('text-blue-600');
    
    currentStep = step;
}

function validateStep1() {
    const requiredFields = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'gender', 'address', 'nationality', 'id_number', 'emergency_contact', 'emergency_phone', 'password', 'confirm_password'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    // Validate password match
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    if (password !== confirmPassword) {
        document.getElementById('registerErrorText').textContent = 'Passwords do not match.';
        document.getElementById('registerError').classList.remove('hidden');
        isValid = false;
    }
    
    return isValid;
}

function validateStep2() {
    const courseSelect = document.getElementById('courseOfInterest');
    const levelSelect = document.getElementById('educationLevel');
    let isValid = true;
    
    if (!courseSelect.value) {
        courseSelect.classList.add('border-red-500');
        isValid = false;
    } else {
        courseSelect.classList.remove('border-red-500');
    }
    
    if (!levelSelect.value) {
        levelSelect.classList.add('border-red-500');
        isValid = false;
    } else {
        levelSelect.classList.remove('border-red-500');
    }
    
    return isValid;
}

function processPayment() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
        document.getElementById('registerErrorText').textContent = 'Please select a payment method.';
        document.getElementById('registerError').classList.remove('hidden');
        return;
    }
    
    // Show loading state
    const payButton = document.getElementById('payButton');
    payButton.disabled = true;
    payButton.innerHTML = 'Processing Payment <i class="fas fa-spinner fa-spin ml-2"></i>';
    
    // Simulate payment processing
    setTimeout(() => {
        // Mock successful payment
        payButton.innerHTML = 'Payment Successful <i class="fas fa-check ml-2"></i>';
        payButton.classList.remove('bg-green-600');
        payButton.classList.add('bg-green-700');
        
        // Move to final step
        setTimeout(() => {
            nextStep(4);
        }, 1000);
    }, 2000);
}

// Payment method selection
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-details').forEach(detail => detail.classList.add('hidden'));
        if (this.value === 'ecocash') {
            document.getElementById('ecocashDetails').classList.remove('hidden');
        } else if (this.value === 'debit_card') {
            document.getElementById('debitCardDetails').classList.remove('hidden');
        }
    });
});

// Registration Form Submission
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('finalRegisterBtn');
    const btnText = submitBtn.querySelector('span') || submitBtn;
    const errorDiv = document.getElementById('registerError');
    const successDiv = document.getElementById('registerSuccess');
    const errorText = document.getElementById('registerErrorText');
    const successText = document.getElementById('registerSuccessText');
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Processing <i class="fas fa-spinner fa-spin ml-2"></i>';
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('includes/async/process_enrollment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            successText.textContent = result.message || 'Registration completed successfully!';
            successDiv.classList.remove('hidden');
            submitBtn.innerHTML = 'Success! <i class="fas fa-check ml-2"></i>';
            
            // Log registration activity
    fetch('../includes/async/log_activity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            activity_type: 'registration',
            description: 'New user registration: ' + data.first_name + ' ' + data.last_name,
            additional_data: 'Application: ' + result.application_number
        })
    }).catch(error => console.error('Activity logging error:', error));
    
            // Redirect to student dashboard after delay
            setTimeout(() => {
                window.location.href = 'student/index.php';
            }, 2000);
        } else {
            // Show error message
            errorText.textContent = result.message || 'Registration failed. Please try again.';
            errorDiv.classList.remove('hidden');
            
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Complete Registration <i class="fas fa-check ml-2"></i>';
        }
    } catch (error) {
        console.error('Registration error:', error);
        errorText.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('hidden');
        
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Complete Registration <i class="fas fa-check ml-2"></i>';
    }
});

// Close modal when clicking outside
document.getElementById('registerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRegisterModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('registerModal').classList.contains('hidden')) {
        closeRegisterModal();
    }
});
</script>