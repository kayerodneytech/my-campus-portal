<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Get student details
$studentId = $_SESSION['user_id'];
$studentQuery = $conn->prepare("SELECT s.*, u.first_name, u.last_name, u.email, s.phone, s.profile_image 
                               FROM students s 
                               JOIN users u ON s.user_id = u.id 
                               WHERE s.user_id = ?");
$studentQuery->bind_param("i", $studentId);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$student = $studentResult->fetch_assoc();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update personal information
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $dateOfBirth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $nationality = trim($_POST['nationality']);
        $idNumber = trim($_POST['id_number']);
        $emergencyContact = trim($_POST['emergency_contact']);
        $emergencyPhone = trim($_POST['emergency_phone']);

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($phone)) {
            $error = 'Please fill in all required fields.';
        } else {
            $conn->begin_transaction();
            try {
                // Update users table
                $updateUser = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
                $updateUser->bind_param("sssi", $firstName, $lastName, $phone, $studentId);
                $updateUser->execute();
                $updateUser->close();

                // Update students table
                $updateStudent = $conn->prepare("UPDATE students SET date_of_birth = ?, gender = ?, address = ?, nationality = ?, id_number = ?, emergency_contact = ?, emergency_phone = ?, updated_at = NOW() WHERE user_id = ?");
                $updateStudent->bind_param("sssssssi", $dateOfBirth, $gender, $address, $nationality, $idNumber, $emergencyContact, $emergencyPhone, $studentId);
                $updateStudent->execute();
                $updateStudent->close();

                $conn->commit();
                $success = 'Profile updated successfully!';
                
                // Refresh student data
                $studentQuery->execute();
                $studentResult = $studentQuery->get_result();
                $student = $studentResult->fetch_assoc();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
    elseif (isset($_POST['update_password'])) {
        // Update password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all password fields.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            // Verify current password
            $checkPassword = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $checkPassword->bind_param("i", $studentId);
            $checkPassword->execute();
            $checkPassword->store_result();
            $checkPassword->bind_result($hashedPassword);
            $checkPassword->fetch();

            if (password_verify($currentPassword, $hashedPassword)) {
                // Update password
                $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePassword = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $updatePassword->bind_param("si", $newHashedPassword, $studentId);
                
                if ($updatePassword->execute()) {
                    $success = 'Password updated successfully!';
                } else {
                    $error = 'Error updating password.';
                }
                $updatePassword->close();
            } else {
                $error = 'Current password is incorrect.';
            }
            $checkPassword->close();
        }
    }
    elseif (isset($_POST['update_email'])) {
        // Update email
        $newEmail = trim($_POST['new_email']);
        $confirmEmail = trim($_POST['confirm_email']);
        $password = $_POST['password'];

        if (empty($newEmail) || empty($confirmEmail) || empty($password)) {
            $error = 'Please fill in all email update fields.';
        } elseif ($newEmail !== $confirmEmail) {
            $error = 'Email addresses do not match.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email already exists
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkEmail->bind_param("si", $newEmail, $studentId);
            $checkEmail->execute();
            $checkEmail->store_result();

            if ($checkEmail->num_rows > 0) {
                $error = 'Email address is already in use.';
            } else {
                // Verify password
                $checkPassword = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $checkPassword->bind_param("i", $studentId);
                $checkPassword->execute();
                $checkPassword->store_result();
                $checkPassword->bind_result($hashedPassword);
                $checkPassword->fetch();

                if (password_verify($password, $hashedPassword)) {
                    // Update email
                    $updateEmail = $conn->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");
                    $updateEmail->bind_param("si", $newEmail, $studentId);
                    
                    if ($updateEmail->execute()) {
                        $success = 'Email updated successfully!';
                        // Refresh student data
                        $studentQuery->execute();
                        $studentResult = $studentQuery->get_result();
                        $student = $studentResult->fetch_assoc();
                    } else {
                        $error = 'Error updating email.';
                    }
                    $updateEmail->close();
                } else {
                    $error = 'Password is incorrect.';
                }
                $checkPassword->close();
            }
            $checkEmail->close();
        }
    }
}

// Get student status for sidebar
$statusQuery = $conn->prepare("SELECT status FROM students WHERE user_id = ?");
$statusQuery->bind_param("i", $studentId);
$statusQuery->execute();
$statusResult = $statusQuery->get_result();
$studentStatus = $statusResult->fetch_assoc()['status'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
    
    <!-- Local Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonts/poppins.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        * {
            font-family: 'Poppins', sans-serif;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Student Sidebar -->
    <?php 
    // Set student status for sidebar
    $student_status = $studentStatus;
    include 'sidebar.php'; 
    ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:ml-0">
        <!-- Mobile Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 lg:hidden">
            <div class="flex items-center justify-between p-4">
                <button id="mobile-menu-button" class="text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="text-xl font-bold text-gray-800">My Profile</h1>
                <div class="w-6"></div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-8">
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">My Profile</h1>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 bg-<?php echo $studentStatus === 'active' ? 'green' : 'yellow'; ?>-100 text-<?php echo $studentStatus === 'active' ? 'green' : 'yellow'; ?>-800 rounded-full text-sm">
                        <?php echo ucfirst($studentStatus); ?>
                    </span>
                    <span class="text-sm text-gray-500"><?php echo htmlspecialchars($student['application_number']); ?></span>
                </div>
            </div>

            <!-- Notifications -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Personal Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6">Personal Information</h2>
                        
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" value="<?php echo htmlspecialchars($student['email']); ?>" disabled
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                                    <p class="text-xs text-gray-500 mt-1">Use the email update section  to change your email</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                    <select name="gender" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo $student['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($student['address']); ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nationality</label>
                                    <input type="text" name="nationality" value="<?php echo htmlspecialchars($student['nationality']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Number</label>
                                    <input type="text" name="id_number" value="<?php echo htmlspecialchars($student['id_number']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact</label>
                                    <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($student['emergency_contact']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Phone</label>
                                    <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($student['emergency_phone']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" name="update_profile" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Settings -->
                <div class="space-y-8">
                    <!-- Password Update -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Change Password</h2>
                        
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" name="current_password" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password" name="new_password" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <button type="submit" name="update_password" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
                                Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Email Update -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Change Email Address</h2>
                        
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Email Address</label>
                                <input type="email" name="new_email" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Email</label>
                                <input type="email" name="confirm_email" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" name="password" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <button type="submit" name="update_email" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">
                                Update Email
                            </button>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Account Information</h2>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Member Since:</span>
                                <span class="font-medium"><?php echo date('M j, Y', strtotime($student['created_at'])); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Last Updated:</span>
                                <span class="font-medium"><?php echo date('M j, Y', strtotime($student['updated_at'])); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Application Number:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($student['application_number']); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Education Level:</span>
                                <span class="font-medium"><?php echo ucfirst($student['level']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
    </script>
</body>
</html>