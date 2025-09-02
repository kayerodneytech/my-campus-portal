<?php
/**
 * Registration Modal Component
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

                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">👤</span>
                                First Name *
                            </label>
                            <input type="text" id="firstName" name="first_name" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your first name">
                        </div>

                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">👤</span>
                                Last Name *
                            </label>
                            <input type="text" id="lastName" name="last_name" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your last name">
                        </div>
                    </div>

                    <div>
                        <label for="registerEmail" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">📧</span>
                            Email Address *
                        </label>
                        <input type="email" id="registerEmail" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                               placeholder="Enter your email address">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">📞</span>
                            Phone Number *
                        </label>
                        <input type="tel" id="phone" name="phone" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                               placeholder="Enter your phone number">
                    </div>

                    <!-- Password Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="registerPassword" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">🔒</span>
                                Password *
                            </label>
                            <div class="relative">
                                <input type="password" id="registerPassword" name="password" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
                                       placeholder="Create a password">
                                <button type="button" onclick="togglePasswordVisibility('registerPassword')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="registerPasswordToggle"></i>
                                    <span class="fa-fallback" style="display: none;">👁️</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2"></i>
                                <span class="fa-fallback mr-2" style="display: none;">🔒</span>
                                Confirm Password *
                            </label>
                            <div class="relative">
                                <input type="password" id="confirmPassword" name="confirm_password" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
                                       placeholder="Confirm your password">
                                <button type="button" onclick="togglePasswordVisibility('confirmPassword')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="confirmPasswordToggle"></i>
                                    <span class="fa-fallback" style="display: none;">👁️</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Course Selection -->
                    <div>
                        <label for="courseOfInterest" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-graduation-cap mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">🎓</span>
                            Course of Interest *
                        </label>
                        <select id="courseOfInterest" name="course_of_interest" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="">-- Select a Course --</option>
                            <optgroup label="Day Classes">
                                <option value="ND In Information Technology">ND In Information Technology</option>
                                <option value="NC In Brick and Block Laying">NC In Brick and Block Laying</option>
                                <option value="NC In Wood Machining And Manufacturing Technology">NC In Wood Machining And Manufacturing Technology</option>
                                <option value="NC In Information Technology (IT)">NC In Information Technology (IT)</option>
                                <option value="NC In Beauty Therapy">NC In Beauty Therapy</option>
                                <option value="NC In Hairdressing">NC In Hairdressing</option>
                                <option value="NC In Cosmetology">NC In Cosmetology</option>
                            </optgroup>
                            <optgroup label="Evening Classes">
                                <option value="NC In Diesel Plant Fitting">NC In Diesel Plant Fitting</option>
                                <option value="NC In Auto Electrics and Electronics">NC In Auto Electrics and Electronics</option>
                                <option value="NC In Motor Vehicle Mechanics">NC In Motor Vehicle Mechanics</option>
                                <option value="NC In Accountancy">NC In Accountancy</option>
                            </optgroup>
                            <optgroup label="Short Courses">
                                <option value="Microsoft Packages">Microsoft Packages</option>
                                <option value="Programming">Programming</option>
                                <option value="Graphic Design">Graphic Design</option>
                                <option value="Web Development">Web Development</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-start">
                        <input type="checkbox" id="terms" name="terms" required
                               class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="terms" class="ml-2 text-sm text-gray-600">
                            I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Terms and Conditions</a> 
                            and <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" id="registerSubmitBtn"
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 font-medium">
                        <span id="registerBtnText">Submit Application</span>
                        <i class="fas fa-spinner fa-spin ml-2 hidden" id="registerSpinner"></i>
                        <span class="fa-fallback ml-2 hidden" style="display: none;">⏳</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Already have an account? 
                        <button onclick="switchToLogin()" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                            Sign In
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Registration Modal Functions
function openRegisterModal() {
    const modal = document.getElementById('registerModal');
    const content = document.getElementById('registerModalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
    
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
    }, 300);
}

function switchToLogin() {
    closeRegisterModal();
    setTimeout(() => {
        openLoginModal();
    }, 300);
}

// Registration Form Submission
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('registerSubmitBtn');
    const btnText = document.getElementById('registerBtnText');
    const spinner = document.getElementById('registerSpinner');
    const errorDiv = document.getElementById('registerError');
    const successDiv = document.getElementById('registerSuccess');
    const errorText = document.getElementById('registerErrorText');
    const successText = document.getElementById('registerSuccessText');
    
    // Validate passwords match
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password !== confirmPassword) {
        errorText.textContent = 'Passwords do not match.';
        errorDiv.classList.remove('hidden');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Submitting...';
    spinner.classList.remove('hidden');
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('process_enrollment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            successText.textContent = result.message || 'Application submitted successfully!';
            successDiv.classList.remove('hidden');
            btnText.textContent = 'Success!';
            spinner.classList.add('hidden');
            
            // Reset form and close modal after delay
            setTimeout(() => {
                closeRegisterModal();
            }, 2000);
        } else {
            // Show error message
            errorText.textContent = result.message || 'Registration failed. Please try again.';
            errorDiv.classList.remove('hidden');
            
            // Reset button state
            submitBtn.disabled = false;
            btnText.textContent = 'Submit Application';
            spinner.classList.add('hidden');
        }
    } catch (error) {
        console.error('Registration error:', error);
        errorText.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('hidden');
        
        // Reset button state
        submitBtn.disabled = false;
        btnText.textContent = 'Submit Application';
        spinner.classList.add('hidden');
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
