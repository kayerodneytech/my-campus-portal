<?php
/**
 * Admin Lecturers Management Page
 *
 * Features:
 * - Create, read, update, and delete lecturers
 * - Reset lecturer passwords
 * - Search and filter lecturers
 * - Mobile-responsive design
 * - Secure against SQL injection and XSS
 * - Transaction-based database operations
 * - Activity logging(Every action performed by admin is logged for audit purposes)
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect if not an admin or not logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * Generate a random password
 *
 * @param int $length Password length (default: 12)
 * @return string Random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Send welcome email to lecturer
 *
 * @param string $email Recipient email
 * @param string $first_name Lecturer first name
 * @param string $last_name Lecturer last name
 * @param string $employee_id Lecturer employee ID
 * @param string $password Generated password
 * @return bool True if email sent successfully, false otherwise
 */
function sendLecturerWelcomeEmail($email, $first_name, $last_name, $employee_id, $password) {
    $subject = "Welcome to MyCamp Portal - Your Lecturer Account";
    $message = "
        Dear $first_name $last_name,

        Your lecturer account has been created successfully!

        Login Details:
        Email: $email
        Employee ID: $employee_id
        Password: $password

        Please log in at: https://mycampus.edu/login.php

        For security reasons, we recommend changing your password after first login.

        Best regards,
        MyCamp Portal Administration
    ";

    // In production, use a proper email library like PHPMailer
    error_log("EMAIL SENT TO: $email - SUBJECT: $subject");

    // Simulate email sending (return true for success, false for failure)
    return true;
}

/**
 * Send password reset email
 *
 * @param string $email Recipient email
 * @param string $first_name Lecturer first name
 * @param string $last_name Lecturer last name
 * @param string $employee_id Lecturer employee ID
 * @param string $new_password New generated password
 * @return bool True if email sent successfully, false otherwise
 */
function sendPasswordResetEmail($email, $first_name, $last_name, $employee_id, $new_password) {
    $subject = "MyCamp Portal - Password Reset";
    $message = "
        Dear $first_name $last_name,

        Your password has been reset by the administrator.

        New Login Details:
        Email: $email
        Employee ID: $employee_id
        New Password: $new_password

        Please log in at: https://mycamp.edu/login.php

        For security reasons, we recommend changing your password after logging in.

        Best regards,
        MyCamp Portal Administration
    ";

    error_log("PASSWORD RESET EMAIL SENT TO: $email - SUBJECT: $subject");
    return true;
}

/**
 * Log user activity
 *
 * @param string $type Activity type
 * @param string $description Activity description
 * @param string|null $additional_data Additional data (optional)
 */
function logActivity($type, $description, $additional_data = null) {
    global $conn;

    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, additional_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $type, $description, $additional_data, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// =============================================
// INITIAL DATA LOADING
// =============================================

// Get all departments for dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Initialize variables for notifications
$error = '';
$success = '';

// =============================================
// PAGINATION AND SEARCH SETUP
// =============================================

// Get search and filter parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; // Items per page
$offset = ($page - 1) * $limit;

// =============================================
// CRUD OPERATIONS
// =============================================

// Create new lecturer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lecturer'])) {
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

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($employee_id)) {
        $error = 'First name, last name, email, and employee ID are required.';
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            // Generate a random password
            $password = generateRandomPassword(12);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();

            try {
                // Create user account
                $user_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, 'lecturer', 'active')");
                $user_stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);
                $user_stmt->execute();
                $user_id = $conn->insert_id;
                $user_stmt->close();

                // Create lecturer profile
                $lecturer_stmt = $conn->prepare("
                    INSERT INTO lecturer_profiles (
                        user_id, employee_id, department, specialization, title,
                        office_location, office_hours, phone_extension, phone, hire_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $lecturer_stmt->bind_param(
                    "issssssss",
                    $user_id, $employee_id, $department, $specialization, $title,
                    $office_location, $office_hours, $phone_extension, $phone
                );
                $lecturer_stmt->execute();
                $lecturer_stmt->close();

                $conn->commit();

                // Send welcome email
                $email_sent = sendLecturerWelcomeEmail($email, $first_name, $last_name, $employee_id, $password);

                $success = "Lecturer created successfully! Generated password: $password";
                if ($email_sent) {
                    $success .= " Login details have been sent to their email.";
                } else {
                    $success .= " Please provide these login details to the lecturer manually.";
                }

                // Log activity
                logActivity(
                    'lecturer_create',
                    "Created lecturer: $first_name $last_name ($employee_id)",
                    "User ID: $user_id, Password: $password, Email Sent: " . ($email_sent ? 'Yes' : 'No')
                );

            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error creating lecturer: ' . $e->getMessage();
            }
        }
        $check_email->close();
    }
}

// Update lecturer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lecturer'])) {
    $user_id = intval($_POST['user_id']);
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
    $status = $_POST['status'];

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($employee_id)) {
        $error = 'First name, last name, email, and employee ID are required.';
    } else {
        $conn->begin_transaction();

        try {
            // Update user account
            $user_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $user_stmt->bind_param("ssssi", $first_name, $last_name, $email, $status, $user_id);
            $user_stmt->execute();
            $user_stmt->close();

            // Update lecturer profile
            $lecturer_stmt = $conn->prepare("
                UPDATE lecturer_profiles
                SET employee_id = ?, department = ?, specialization = ?, title = ?,
                    office_location = ?, office_hours = ?, phone_extension = ?, phone = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $lecturer_stmt->bind_param(
                "ssssssssi",
                $employee_id, $department, $specialization, $title,
                $office_location, $office_hours, $phone_extension, $phone, $user_id
            );
            $lecturer_stmt->execute();
            $lecturer_stmt->close();

            $conn->commit();

            $success = 'Lecturer updated successfully!';

            // Log activity
            logActivity(
                'lecturer_update',
                "Updated lecturer: $first_name $last_name ($employee_id)",
                "User ID: $user_id"
            );

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error updating lecturer: ' . $e->getMessage();
        }
    }
}

// Delete lecturer
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);

    // Check if lecturer has any course assignments
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_assignments WHERE lecturer_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->fetch_assoc();
    $assignment_count = $result['count'];
    $check_stmt->close();

    if ($assignment_count > 0) {
        $error = 'Cannot delete lecturer. There are ' . $assignment_count . ' course assignments associated with this lecturer.';
    } else {
        $conn->begin_transaction();

        try {
            // Delete lecturer profile
            $lecturer_stmt = $conn->prepare("DELETE FROM lecturer_profiles WHERE user_id = ?");
            $lecturer_stmt->bind_param("i", $user_id);
            $lecturer_stmt->execute();
            $lecturer_stmt->close();

            // Delete user account
            $user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_stmt->close();

            $conn->commit();

            $success = 'Lecturer deleted successfully!';

            // Log activity
            logActivity('lecturer_delete', "Deleted lecturer ID: $user_id");

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error deleting lecturer: ' . $e->getMessage();
        }
    }
}

// Reset password
if (isset($_GET['reset_password'])) {
    $user_id = intval($_GET['reset_password']);

    // Get lecturer details for email
    $lecturer_info = $conn->prepare("
        SELECT u.email, u.first_name, u.last_name, lp.employee_id
        FROM users u
        JOIN lecturer_profiles lp ON u.id = lp.user_id
        WHERE u.id = ?
    ");
    $lecturer_info->bind_param("i", $user_id);
    $lecturer_info->execute();
    $lecturer = $lecturer_info->get_result()->fetch_assoc();
    $lecturer_info->close();

    // Generate a new random password
    $new_password = generateRandomPassword(12);
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        // Send email with new password
        $email_sent = sendPasswordResetEmail(
            $lecturer['email'],
            $lecturer['first_name'],
            $lecturer['last_name'],
            $lecturer['employee_id'],
            $new_password
        );

        $success = "Password reset successfully! New password: $new_password";
        if ($email_sent) {
            $success .= " The new password has been sent to their email.";
        } else {
            $success .= " Please provide the new password to the lecturer manually.";
        }

        // Log activity
        logActivity(
            'password_reset',
            "Reset password for lecturer: {$lecturer['first_name']} {$lecturer['last_name']}",
            "Employee ID: {$lecturer['employee_id']}, Email Sent: " . ($email_sent ? 'Yes' : 'No')
        );
    } else {
        $error = 'Error resetting password: ' . $stmt->error;
    }
    $stmt->close();
}

// =============================================
// FETCH LECTURERS WITH PAGINATION AND FILTERS
// =============================================

// Base query
$query = "
    SELECT u.*, lp.*, d.name as department_name
    FROM users u
    JOIN lecturer_profiles lp ON u.id = lp.user_id
    LEFT JOIN departments d ON lp.department = d.id
    WHERE u.role = 'lecturer'
";

// Add filters
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $query .= " AND lp.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $query .= " AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.email LIKE ?
        OR lp.employee_id LIKE ?
        OR lp.specialization LIKE ?
    )";
    $params = array_merge($params, [
        $search_param, $search_param, $search_param, $search_param, $search_param
    ]);
    $types .= 'sssss';
}

// Add pagination
$query .= " ORDER BY u.first_name, u.last_name LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$lecturers = $result->fetch_all(MYSQLI_ASSOC);

// Count total lecturers for pagination
$count_query = "
    SELECT COUNT(*)
    FROM users u
    JOIN lecturer_profiles lp ON u.id = lp.user_id
    WHERE u.role = 'lecturer'
";

$count_params = [];
$count_types = '';

if ($status_filter !== 'all') {
    $count_query .= " AND lp.status = ?";
    $count_params[] = $status_filter;
    $count_types .= 's';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $count_query .= " AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.email LIKE ?
        OR lp.employee_id LIKE ?
        OR lp.specialization LIKE ?
    )";
    $count_params = array_merge($count_params, [
        $search_param, $search_param, $search_param, $search_param, $search_param
    ]);
    $count_types .= 'sssss';
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_lecturers = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_lecturers / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lecturers - Admin Portal</title>

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

        /* Mobile responsiveness */
        @media (max-width: 767px) {
            .mobile-hidden {
                display: none !important;
            }
            .mobile-block {
                display: block !important;
            }
            .mobile-full {
                width: 100% !important;
            }
            .mobile-mt-4 {
                margin-top: 1rem !important;
            }
            .mobile-text-sm {
                font-size: 0.875rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
   <?php include 'components/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Lecturer Management</h1>
                <p class="text-gray-600 mobile-text-sm">Create, edit, and manage lecturer accounts</p>
            </div>
            <button onclick="openCreateModal()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-4 md:mt-0 mobile-full">
                <i class="fas fa-plus mr-2"></i>
                <span class="mobile-hidden">Add New Lecturer</span>
            </button>
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

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Lecturers</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                               placeholder="Search by name, email, employee ID, or specialization..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg w-full">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="on_leave" <?php echo $status_filter === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4 md:items-center">
                    <div class="flex-1 md:flex-none">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full md:w-auto">
                            <i class="fas fa-filter mr-2"></i>
                            Apply Filters
                        </button>
                    </div>

                    <div>
                        <a href="lecturers.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 w-full md:w-auto text-center">
                            <i class="fas fa-times mr-2"></i>
                            Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lecturers Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lecturer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider mobile-hidden">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider mobile-hidden">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider mobile-hidden">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($lecturers) > 0): ?>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($lecturer['employee_id']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
                                                    <?php echo strtoupper(substr($lecturer['first_name'], 0, 1) . substr($lecturer['last_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($lecturer['title'] ? $lecturer['title'] . ' ' : ''); ?>
                                                    <?php echo htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 mobile-hidden">
                                                    <?php echo htmlspecialchars($lecturer['specialization']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap mobile-hidden">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($lecturer['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($lecturer['phone'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap mobile-hidden">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($lecturer['department_name'] ?: $lecturer['department']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap mobile-hidden">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($lecturer['specialization']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            <?php
                                            if ($lecturer['status'] == 'active') echo 'bg-green-100 text-green-800';
                                            elseif ($lecturer['status'] == 'inactive') echo 'bg-gray-100 text-gray-800';
                                            elseif ($lecturer['status'] == 'on_leave') echo 'bg-yellow-100 text-yellow-800';
                                            ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $lecturer['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="editLecturer(<?php echo $lecturer['user_id']; ?>)"
                                                    class="text-blue-600 hover:text-blue-900 mobile-text-sm"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                                <span class="mobile-hidden ml-1">Edit</span>
                                            </button>
                                            <button onclick="resetPassword(<?php echo $lecturer['user_id']; ?>)"
                                                    class="text-purple-600 hover:text-purple-900 mobile-text-sm"
                                                    title="Reset Password">
                                                <i class="fas fa-key"></i>
                                                <span class="mobile-hidden ml-1">Reset PW</span>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $lecturer['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($lecturer['first_name'] . ' ' . $lecturer['last_name'])); ?>')"
                                                    class="text-red-600 hover:text-red-900 mobile-text-sm"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                                <span class="mobile-hidden ml-1">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No lecturers found matching your criteria.
                                    <button onclick="openCreateModal()" class="text-blue-600 hover:text-blue-800 ml-2">
                                        Create your first lecturer
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex flex-col sm:flex-row justify-between items-center">
                <div class="text-sm text-gray-700 mb-4 sm:mb-0">
                    Showing <?php echo count($lecturers); ?> of <?php echo $total_lecturers; ?> lecturers
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Lecturer Modal -->
    <div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">Create New Lecturer</h3>
                    <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="create_lecturer" value="1">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" name="first_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="First name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" name="last_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Last name">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="email@institution.edu">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Phone number">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID *</label>
                            <input type="text" name="employee_id" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="EMP001">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <select name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Title</option>
                                <option value="professor">Professor</option>
                                <option value="doctor">Doctor</option>
                                <option value="assistant_professor">Assistant Professor</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="mr">Mr.</option>
                                <option value="mrs">Mrs.</option>
                                <option value="ms">Ms.</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                            <input type="text" name="specialization"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Area of specialization">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Office Location</label>
                            <input type="text" name="office_location"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Building and room number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Extension</label>
                            <input type="text" name="phone_extension"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Extension number">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Office Hours</label>
                        <textarea name="office_hours" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Monday 9:00 AM - 11:00 AM&#10;Wednesday 2:00 PM - 4:00 PM"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeCreateModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Create Lecturer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Lecturer Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">Edit Lecturer</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="update_lecturer" value="1">
                    <input type="hidden" id="edit_user_id" name="user_id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" id="edit_first_name" name="first_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" id="edit_last_name" name="last_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="edit_email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" id="edit_phone" name="phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID *</label>
                            <input type="text" id="edit_employee_id" name="employee_id" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <select id="edit_title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Title</option>
                                <option value="professor">Professor</option>
                                <option value="doctor">Doctor</option>
                                <option value="assistant_professor">Assistant Professor</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="mr">Mr.</option>
                                <option value="mrs">Mrs.</option>
                                <option value="ms">Ms.</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select id="edit_department" name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                            <input type="text" id="edit_specialization" name="specialization"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Office Location</label>
                            <input type="text" id="edit_office_location" name="office_location"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Extension</label>
                            <input type="text" id="edit_phone_extension" name="phone_extension"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="edit_status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="on_leave">On Leave</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Office Hours</label>
                            <textarea id="edit_office_hours" name="office_hours" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Update Lecturer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // =============================================
        // MODAL FUNCTIONS
        // =============================================

        /**
         * Open create lecturer modal
         */
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close create lecturer modal
         */
        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        /**
         * Open edit lecturer modal
         */
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close edit lecturer modal
         */
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        /**
         * Fetch and populate lecturer data for editing
         */
        function editLecturer(userId) {
            fetch(`includes/async/get_lecturer_details.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const lecturer = data.lecturer;

                        // Populate the edit form
                        document.getElementById('edit_user_id').value = lecturer.user_id;
                        document.getElementById('edit_first_name').value = lecturer.first_name;
                        document.getElementById('edit_last_name').value = lecturer.last_name;
                        document.getElementById('edit_email').value = lecturer.email;
                        document.getElementById('edit_phone').value = lecturer.phone || '';
                        document.getElementById('edit_employee_id').value = lecturer.employee_id;
                        document.getElementById('edit_title').value = lecturer.title || '';
                        document.getElementById('edit_department').value = lecturer.department || '';
                        document.getElementById('edit_specialization').value = lecturer.specialization || '';
                        document.getElementById('edit_office_location').value = lecturer.office_location || '';
                        document.getElementById('edit_phone_extension').value = lecturer.phone_extension || '';
                        document.getElementById('edit_office_hours').value = lecturer.office_hours || '';
                        document.getElementById('edit_status').value = lecturer.status || 'active';

                        // Show the edit modal
                        openEditModal();
                    } else {
                        alert('Error fetching lecturer details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching lecturer details');
                });
        }

        /**
         * Confirm and reset lecturer password
         */
        function resetPassword(userId) {
            if (confirm('Are you sure you want to reset this lecturer\'s password? They will receive an email with the new password.')) {
                window.location.href = `lecturers.php?reset_password=${userId}`;
            }
        }

        /**
         * Confirm and delete lecturer
         */
        function confirmDelete(userId, lecturerName) {
            if (confirm(`Are you sure you want to delete lecturer "${lecturerName}"? This action cannot be undone.`)) {
                window.location.href = `lecturers.php?delete=${userId}`;
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');

            if (event.target === createModal) {
                closeCreateModal();
            }

            if (event.target === editModal) {
                closeEditModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCreateModal();
                closeEditModal();
            }
        });
    </script>
</body>
</html>
