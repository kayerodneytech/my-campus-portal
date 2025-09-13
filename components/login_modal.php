<?php
/**
 * Login Modal Component
 * Modern modal-based login form with Tailwind CSS
 * Handles login for all user types
 */
?>

<!-- Login Modal -->
<div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="loginModalContent">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-sign-in-alt text-white text-xl"></i>
                        <span class="fa-fallback text-white text-xl" style="display: none;">🔐</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
                        <p class="text-gray-600">Sign in to your account</p>
                    </div>
                </div>
                <button onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <i class="fas fa-times text-xl"></i>
                    <span class="fa-fallback text-xl" style="display: none;">✕</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <form id="loginForm" class="space-y-4">
                    <div id="loginError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">⚠️</span>
                            <span id="loginErrorText"></span>
                        </div>
                    </div>

                    <div>
                        <label for="loginEmail" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">📧</span>
                            Email Address
                        </label>
                        <input type="email" id="loginEmail" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                               placeholder="Enter your email">
                    </div>

                    <div>
                        <label for="loginPassword" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>
                            <span class="fa-fallback mr-2" style="display: none;">🔒</span>
                            Password
                        </label>
                        <div class="relative">
                            <input type="password" id="loginPassword" name="password" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
                                   placeholder="Enter your password">
                            <button type="button" onclick="togglePasswordVisibility('loginPassword')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="loginPasswordToggle"></i>
                                <span class="fa-fallback" style="display: none;">👁️</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember_me" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" id="loginSubmitBtn"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 font-medium">
                        <span id="loginBtnText">Sign In</span>
                        <i class="fas fa-spinner fa-spin ml-2 hidden" id="loginSpinner"></i>
                        <span class="fa-fallback ml-2 hidden" style="display: none;">⏳</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <button onclick="switchToRegister()" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                            Apply Now
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Login Modal Functions
function openLoginModal() {
    const modal = document.getElementById('loginModal');
    const content = document.getElementById('loginModalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Focus on email input
    setTimeout(() => {
        document.getElementById('loginEmail').focus();
    }, 300);
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    const content = document.getElementById('loginModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('loginForm').reset();
        document.getElementById('loginError').classList.add('hidden');
    }, 300);
}

function switchToRegister() {
    closeLoginModal();
    setTimeout(() => {
        openRegisterModal();
    }, 300);
}

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(inputId + 'Toggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// Login Form Submission
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('loginSubmitBtn');
    const btnText = document.getElementById('loginBtnText');
    const spinner = document.getElementById('loginSpinner');
    const errorDiv = document.getElementById('loginError');
    const errorText = document.getElementById('loginErrorText');
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Signing In...';
    spinner.classList.remove('hidden');
    errorDiv.classList.add('hidden');
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('includes/async/process_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
      // After successful login, log the activity
if (result.success) {
    // Log login activity
    fetch('includes/async/log_activity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            activity_type: 'login',
            description: 'User logged in successfully'
        })
    }).catch(error => console.error('Activity logging error:', error));
    
    // Show success message
    btnText.textContent = 'Success!';
    spinner.classList.add('hidden');
    
    // Redirect after short delay
    setTimeout(() => {
        window.location.href = result.redirect_url || 'index.php';
   }, 1000);
} else {
            // Show error message
            errorText.textContent = result.message || 'Login failed. Please try again.';
            errorDiv.classList.remove('hidden');
            
            // Reset button state
            submitBtn.disabled = false;
            btnText.textContent = 'Sign In';
            spinner.classList.add('hidden');
        }
    } catch (error) {
        console.error('Login error:', error);
        errorText.textContent = 'Network error. Please try again.';
        errorDiv.classList.remove('hidden');
        
        // Reset button state
        submitBtn.disabled = false;
        btnText.textContent = 'Sign In';
        spinner.classList.add('hidden');
    }
});

// Close modal when clicking outside
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('loginModal').classList.contains('hidden')) {
        closeLoginModal();
    }
});
</script>