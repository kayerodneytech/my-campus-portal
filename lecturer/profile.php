<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
$lecturer_id = $_SESSION['user_id'];
$error = '';
$success = '';
$profile_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Get lecturer details
$lecturer_query = $conn->prepare("
    SELECT u.*, lp.*, d.name as department_name
    FROM users u
    JOIN lecturer_profiles lp ON u.id = lp.user_id
    LEFT JOIN departments d ON lp.department = d.id
    WHERE u.id = ?
");
$lecturer_query->bind_param("i", $lecturer_id);
$lecturer_query->execute();
$lecturer = $lecturer_query->get_result()->fetch_assoc();

// Get all departments for dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    // [Existing profile update code remains the same]
}

// Handle password change
if (isset($_POST['change_password'])) {
    // [Existing password change code remains the same]
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Lecturer Portal</title>
    <script src="../javascript/tailwindcss.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonts/poppins.ttf') format('truetype');
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        .profile-image {
            transition: all 0.3s ease;
            border: 4px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-image:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .tab-button {
            transition: all 0.2s ease;
        }

        .tab-button.active {
            border-bottom: 3px solid #3B82F6;
            font-weight: 600;
        }

        .form-section {
            transition: all 0.3s ease;
        }

        .password-strength {
            height: 4px;
            margin-top: 5px;
            border-radius: 2px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'components/sidebar.php' ?>

    <!-- Main Content -->
    <div class="main-content flex-1 overflow-hidden p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-600">Manage your personal and professional information</p>
        </div>

        <!-- Notifications -->
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
                <button class="ml-auto text-red-700 hover:text-red-900" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-500"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
                <button class="ml-auto text-green-700 hover:text-green-900" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white">
                <div class="flex flex-col md:flex-row items-center">
                    <div class="relative mb-4 md:mb-0 md:mr-6">
                        <img src="<?php echo $lecturer['profile_image'] ?
                                        '../uploads/profiles/' . htmlspecialchars($lecturer['profile_image']) :
                                        'https://ui-avatars.com/api/?name=' . urlencode($lecturer['first_name'] . ' ' . $lecturer['last_name']) .
                                        '&background=3B82F6&color=fff&size=128'; ?>"
                            alt="Profile Image"
                            class="h-32 w-32 rounded-full profile-image">

                        <label for="profile_image" class="absolute bottom-0 right-0 bg-white text-blue-600 p-2 rounded-full cursor-pointer hover:bg-gray-100 shadow-md">
                            <i class="fas fa-camera text-sm"></i>
                            <input type="file" id="profile_image" name="profile_image" class="hidden" form="profileForm">
                        </label>
                    </div>

                    <div class="flex-1">
                        <h2 class="text-2xl font-bold">
                            <?php echo htmlspecialchars($lecturer['title'] ? $lecturer['title'] . ' ' : '') .
                                htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']); ?>
                        </h2>
                        <p class="text-blue-100 mt-1"><?php echo htmlspecialchars($lecturer['employee_id']); ?></p>
                        <p class="text-blue-100"><?php echo htmlspecialchars($lecturer['department_name'] ?: $lecturer['department']); ?></p>
                        <p class="text-blue-100"><?php echo htmlspecialchars($lecturer['specialization']); ?></p>
                    </div>

                    <div class="mt-4 md:mt-0 md:ml-6 text-center md:text-right">
                        <div class="flex justify-center md:justify-end space-x-4">
                            <div class="text-center">
                                <div class="text-blue-100 text-sm">Courses</div>
                                <div class="font-bold text-xl">8</div>
                            </div>
                            <div class="text-center">
                                <div class="text-blue-100 text-sm">Students</div>
                                <div class="font-bold text-xl">124</div>
                            </div>
                            <div class="text-center">
                                <div class="text-blue-100 text-sm">Years</div>
                                <div class="font-bold text-xl">5</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-1">
                    <a href="?tab=profile" class="tab-button px-6 py-4 text-gray-600 hover:text-blue-600 <?php echo $profile_tab === 'profile' ? 'active text-blue-600' : ''; ?>">
                        Profile Information
                    </a>
                    <a href="?tab=contact" class="tab-button px-6 py-4 text-gray-600 hover:text-blue-600 <?php echo $profile_tab === 'contact' ? 'active text-blue-600' : ''; ?>">
                        Contact Details
                    </a>
                    <a href="?tab=security" class="tab-button px-6 py-4 text-gray-600 hover:text-blue-600 <?php echo $profile_tab === 'security' ? 'active text-blue-600' : ''; ?>">
                        Security
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Profile Information Tab -->
                <?php if ($profile_tab === 'profile' || !isset($_GET['tab'])): ?>
                    <form id="profileForm" method="POST" enctype="multipart/form-data" class="form-section">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" name="first_name" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['first_name']); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" name="last_name" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['last_name']); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <select name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Title</option>
                                        <option value="professor" <?php echo $lecturer['title'] == 'professor' ? 'selected' : ''; ?>>Professor</option>
                                        <option value="doctor" <?php echo $lecturer['title'] == 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                        <option value="assistant_professor" <?php echo $lecturer['title'] == 'assistant_professor' ? 'selected' : ''; ?>>Assistant Professor</option>
                                        <option value="lecturer" <?php echo $lecturer['title'] == 'lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                                        <option value="mr" <?php echo $lecturer['title'] == 'mr' ? 'selected' : ''; ?>>Mr.</option>
                                        <option value="mrs" <?php echo $lecturer['title'] == 'mrs' ? 'selected' : ''; ?>>Mrs.</option>
                                        <option value="ms" <?php echo $lecturer['title'] == 'ms' ? 'selected' : ''; ?>>Ms.</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Professional Information</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID *</label>
                                    <input type="text" name="employee_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['employee_id']); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                    <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo $lecturer['department'] == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                                    <input type="text" name="specialization"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['specialization']); ?>"
                                        placeholder="Area of specialization">
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">About Me</h3>
                            <textarea name="bio" rows="5"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Tell us about yourself, your research interests, teaching philosophy, etc."><?php echo htmlspecialchars($lecturer['bio']); ?></textarea>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Contact Details Tab -->
                <?php if ($profile_tab === 'contact'): ?>
                    <form id="contactForm" method="POST" enctype="multipart/form-data" class="form-section">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" name="email" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['email']); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" name="phone"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['phone']); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Extension</label>
                                    <input type="text" name="phone_extension"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['phone_extension']); ?>"
                                        placeholder="Extension number">
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Office Information</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Office Location</label>
                                    <input type="text" name="office_location"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?php echo htmlspecialchars($lecturer['office_location']); ?>"
                                        placeholder="Building and room number">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Office Hours</label>
                                    <textarea name="office_hours" rows="4"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Monday 9:00 AM - 11:00 AM&#10;Wednesday 2:00 PM - 4:00 PM"><?php echo htmlspecialchars($lecturer['office_hours']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Security Tab -->
                <?php if ($profile_tab === 'security'): ?>
                    <form method="POST" class="form-section max-w-md">
                        <input type="hidden" name="change_password" value="1">

                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Change Password</h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password *</label>
                            <div class="relative">
                                <input type="password" name="current_password" id="current_password" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                <button type="button" class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
                                    onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                            <div class="relative">
                                <input type="password" name="new_password" id="new_password" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10"
                                    oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
                                    onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength bg-gray-200 mt-2 rounded">
                                <div id="password-strength-bar" class="h-full rounded" style="width: 0%;"></div>
                            </div>
                            <p id="password-strength-text" class="text-xs mt-1 text-gray-500"></p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="confirm_password" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                <button type="button" class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
                                    onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-lock mr-2"></i> Change Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Preview profile image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Check password strength
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');

            let strength = 0;

            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;

            // Character variety checks
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            // Update UI
            let strengthPercentage = strength * 20;
            let strengthColor = '';

            if (password.length === 0) {
                strengthPercentage = 0;
                strengthText.textContent = '';
            } else if (strengthPercentage <= 40) {
                strengthColor = 'bg-red-500';
                strengthText.textContent = 'Weak password';
                strengthText.className = 'text-xs mt-1 text-red-500';
            } else if (strengthPercentage <= 80) {
                strengthColor = 'bg-yellow-500';
                strengthText.textContent = 'Moderate password';
                strengthText.className = 'text-xs mt-1 text-yellow-500';
            } else {
                strengthColor = 'bg-green-500';
                strengthText.textContent = 'Strong password';
                strengthText.className = 'text-xs mt-1 text-green-500';
            }

            strengthBar.style.width = strengthPercentage + '%';
            strengthBar.className = 'h-full rounded ' + strengthColor;
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');

            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    let isValid = true;

                    // Check required fields
                    const requiredFields = form.querySelectorAll('[required]');
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.classList.add('border-red-500');
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });
        });
    </script>
</body>

</html>