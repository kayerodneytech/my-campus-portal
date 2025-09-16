<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

$lecturer_id = $_SESSION['user_id'];
$error = '';
$success = '';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $employee_id = trim($_POST['employee_id']);
    $department = trim($_POST['department']);
    $specialization = trim($_POST['specialization']);
    $title = $_POST['title'];
    $office_location = trim($_POST['office_location']);
    $office_hours = trim($_POST['office_hours']);
    $phone_extension = trim($_POST['phone_extension']);
    $bio = trim($_POST['bio']);
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($employee_id)) {
        $error = 'First name, last name, email, and employee ID are required.';
    } else {
        $conn->begin_transaction();
        
        try {
            // Update user account
            $user_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $user_stmt->bind_param("sssi", $first_name, $last_name, $email, $lecturer_id);
            $user_stmt->execute();
            $user_stmt->close();
            
            // Handle profile image upload
            $profile_image = $lecturer['profile_image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old image if exists
                if ($profile_image) {
                    $old_file = $upload_dir . $profile_image;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'profile_' . $lecturer_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $file_path)) {
                    $profile_image = $file_name;
                } else {
                    throw new Exception('Failed to upload profile image.');
                }
            }
            
            // Update lecturer profile
            $lecturer_stmt = $conn->prepare("
                UPDATE lecturer_profiles 
                SET employee_id = ?, department = ?, specialization = ?, title = ?, 
                    office_location = ?, office_hours = ?, phone_extension = ?, phone = ?, 
                    bio = ?, profile_image = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $lecturer_stmt->bind_param("ssssssssssi", $employee_id, $department, $specialization, $title, 
                                      $office_location, $office_hours, $phone_extension, $phone, 
                                      $bio, $profile_image, $lecturer_id);
            $lecturer_stmt->execute();
            $lecturer_stmt->close();
            
            $conn->commit();
            
            $success = 'Profile updated successfully!';
            // Refresh lecturer data
            $lecturer_query->execute();
            $lecturer = $lecturer_query->get_result()->fetch_assoc();
            
            // Update session data
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_email'] = $email;
            
            // Log activity
            logActivity('profile_update', "Updated profile information");
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        // Verify current password
        $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $check_stmt->bind_param("i", $lecturer_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if (password_verify($current_password, $result['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $lecturer_id);
            
            if ($update_stmt->execute()) {
                $success = 'Password changed successfully!';
                // Log activity
                logActivity('password_change', "Changed password");
            } else {
                $error = 'Error changing password: ' . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

function logActivity($type, $description, $additional_data = null) {
    global $conn, $lecturer_id;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, additional_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $lecturer_id, $type, $description, $additional_data, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
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
        }
        .profile-image:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
      <!-- Sidebar -->
   <?php include 'sidebar.php'?>
      <!-- Main Content -->
    <div class="main-content flex-1 overflow-hidden p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-600">Manage your personal and professional information</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-center">
                        <div class="relative inline-block">
                            <img src="<?php echo $lecturer['profile_image'] ? '../uploads/profiles/' . htmlspecialchars($lecturer['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($lecturer['first_name'] . ' ' . $lecturer['last_name']) . '&size=128'; ?>" 
                                 alt="Profile Image" 
                                 class="h-32 w-32 rounded-full mx-auto profile-image">
                            <label for="profile_image" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full cursor-pointer hover:bg-blue-700">
                                <i class="fas fa-camera"></i>
                                <input type="file" id="profile_image" name="profile_image" class="hidden" form="profileForm">
                            </label>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 mt-4">
                            <?php echo htmlspecialchars($lecturer['title'] ? $lecturer['title'] . ' ' : '') . htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']); ?>
                        </h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($lecturer['employee_id']); ?></p>
                        <p class="text-gray-600"><?php echo htmlspecialchars($lecturer['department_name'] ?: $lecturer['department']); ?></p>
                        <p class="text-gray-600"><?php echo htmlspecialchars($lecturer['specialization']); ?></p>
                        
                        <div class="mt-6 space-y-2">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-envelope w-5 mr-3"></i>
                                <span><?php echo htmlspecialchars($lecturer['email']); ?></span>
                            </div>
                            <?php if ($lecturer['phone']): ?>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-phone w-5 mr-3"></i>
                                    <span><?php echo htmlspecialchars($lecturer['phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($lecturer['phone_extension']): ?>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-phone-alt w-5 mr-3"></i>
                                    <span>Ext. <?php echo htmlspecialchars($lecturer['phone_extension']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($lecturer['office_location']): ?>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-map-marker-alt w-5 mr-3"></i>
                                    <span><?php echo htmlspecialchars($lecturer['office_location']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Office Hours -->
                <?php if ($lecturer['office_hours']): ?>
                    <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Office Hours</h3>
                        <div class="text-gray-600 whitespace-pre-wrap"><?php echo htmlspecialchars($lecturer['office_hours']); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Profile Form -->
            <div class="lg:col-span-2">
                <form id="profileForm" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Personal Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" name="first_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['first_name']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" name="last_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['last_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['email']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['phone']); ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID *</label>
                            <input type="text" name="employee_id" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['employee_id']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <select name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $lecturer['department'] == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                            <input type="text" name="specialization"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['specialization']); ?>"
                                   placeholder="Area of specialization">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Office Location</label>
                            <input type="text" name="office_location"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['office_location']); ?>"
                                   placeholder="Building and room number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Extension</label>
                            <input type="text" name="phone_extension"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($lecturer['phone_extension']); ?>"
                                   placeholder="Extension number">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Office Hours</label>
                        <textarea name="office_hours" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                  placeholder="Monday 9:00 AM - 11:00 AM&#10;Wednesday 2:00 PM - 4:00 PM"><?php echo htmlspecialchars($lecturer['office_hours']); ?></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                        <textarea name="bio" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                  placeholder="Tell us about yourself, your research interests, etc."><?php echo htmlspecialchars($lecturer['bio']); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            Update Profile
                        </button>
                    </div>
                </form>
                
                <!-- Change Password Form -->
                <form method="POST" class="bg-white rounded-xl shadow-md p-6 mt-6">
                    <input type="hidden" name="change_password" value="1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Change Password</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                            <input type="password" name="new_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                            <input type="password" name="confirm_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                            Change Password
                        </button>
                    </div>
                </form>
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
    </script>
</body>
</html>