<?php
/**
 * Admin Students Management Page
 *
 * Features:
 * - List, filter, and search students
 * - Update student status (approve/reject/suspend)
 * - Export students to CSV
 * - Pagination for large datasets
 * - Secure against SQL injection and XSS
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
// CSV EXPORT HANDLER
// =============================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    /**
     * Export students to CSV
     * - Sets proper headers for download
     * - Uses fputcsv for safe CSV generation
     * - Explicit column selection to avoid ambiguity
     */
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    // CSV Header Row
    fputcsv($output, [
        'Application Number', 'First Name', 'Last Name', 'Email',
        'Phone', 'Level', 'Status', 'Created At'
    ]);

    // Explicit column selection to avoid ambiguity
    $export_query = "
        SELECT
            s.application_number,
            u.first_name,
            u.last_name,
            u.email,
            s.phone,
            s.level,
            s.status,
            s.created_at
        FROM users u
        JOIN students s ON u.id = s.user_id
        ORDER BY s.created_at DESC
    ";
    $export_result = $conn->query($export_query);

    // Write each student as a CSV row
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// =============================================
// STUDENT STATUS UPDATE HANDLER
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    /**
     * Handle student status updates
     * - Validates admin permissions
     * - Uses transactions for data integrity
     * - Logs status changes with notes
     */

    // Security: Verify admin permissions
    if (!isAdmin()) {
        $error = "Unauthorized access.";
        header("Location: students.php?status=" . ($_GET['status'] ?? 'all') .
               "&search=" . urlencode($_GET['search'] ?? ''));
        exit();
    }

    $user_id = intval($_POST['user_id']);
    $new_status = $_POST['status'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $valid_statuses = ['pending', 'active', 'suspended', 'rejected'];

    if (in_array($new_status, $valid_statuses)) {
        try {
            $conn->begin_transaction();

            // Update student status
            $update_stmt = $conn->prepare("
                UPDATE students
                SET status = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $update_stmt->bind_param("si", $new_status, $user_id);
            $update_stmt->execute();

            // If activating student, also activate user account
            if ($new_status === 'active') {
                $activate_user = $conn->prepare("
                    UPDATE users
                    SET status = 'active'
                    WHERE id = ?
                ");
                $activate_user->bind_param("i", $user_id);
                $activate_user->execute();
                $activate_user->close();

                // Default note if none provided
                if (empty($notes)) {
                    $notes = "Account activated by admin";
                }
            }

            // Add note about the status change
            if (!empty($notes)) {
                $note_stmt = $conn->prepare("
                    INSERT INTO student_notes (student_id, admin_id, note)
                    VALUES (?, ?, ?)
                ");
                $note_stmt->bind_param("iis", $user_id, $_SESSION['user_id'], $notes);
                $note_stmt->execute();
                $note_stmt->close();
            }

            $conn->commit();
            $success = "Student status updated successfully!";

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error updating student status. Please try again.";
            error_log("Status update failed: " . $e->getMessage());
        }
    } else {
        $error = "Invalid status.";
    }

    // Redirect to avoid form resubmission
    header("Location: students.php?status=" . ($_GET['status'] ?? 'all') .
           "&search=" . urlencode($_GET['search'] ?? ''));
    exit();
}

// =============================================
// STUDENT LISTING LOGIC
// =============================================
// Get filter parameters from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query with explicit columns (no ambiguity)
$query = "
    SELECT
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        s.application_number,
        s.phone,
        s.level,
        s.status,
        s.created_at,
        s.user_id
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE u.role = 'student'
";

// Add filters for status and search
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $query .= " AND s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $query .= " AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.email LIKE ?
        OR s.application_number LIKE ?
    )";
    $params = array_merge($params, [
        $search_param, $search_param, $search_param, $search_param
    ]);
    $types .= 'ssss';
}

$query .= " ORDER BY s.created_at DESC";

// Pagination setup
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Execute the main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Count total students for pagination
$count_query = "
    SELECT COUNT(*)
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE u.role = 'student'
";
$count_params = [];

if ($status_filter !== 'all') {
    $count_query .= " AND s.status = ?";
    $count_params[] = $status_filter;
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $count_query .= " AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.email LIKE ?
        OR s.application_number LIKE ?
    )";
    $count_params = array_merge($count_params, [
        $search_param, $search_param, $search_param, $search_param
    ]);
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param(str_repeat('s', count($count_params)), ...$count_params);
}
$count_stmt->execute();
$total_students = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_students / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin Portal</title>

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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
     <!-- Admin Header -->
   <?php include 'components/header.php'; ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Student Management</h1>
                <p class="text-gray-600">Manage student applications and accounts</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="students.php?export=csv" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>
                    Export CSV
                </a>
            </div>
        </div>

        <!-- Notifications -->
        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Students</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Search by name, email, or application number..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Students</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-filter mr-2"></i>
                        Apply Filters
                    </button>
                </div>

                <div>
                    <a href="students.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        <i class="fas fa-times mr-2"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Application #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied On</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($student['application_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <?php echo htmlspecialchars($student['level']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            <?php echo $student['status'] == 'active' ? 'bg-green-100 text-green-800' : (
                                                $student['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : (
                                                    $student['status'] == 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                                )
                                            ); ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewStudent(<?php echo $student['user_id']; ?>)"
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($student['status'] == 'pending'): ?>
                                                <button onclick="approveStudent(<?php echo $student['user_id']; ?>)"
                                                        class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button onclick="rejectStudent(<?php echo $student['user_id']; ?>)"
                                                        class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No students found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing <?php echo count($students); ?> of <?php echo $total_students; ?> students
            </div>
            <?php if ($total_pages > 1): ?>
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
            <?php endif; ?>
        </div>
    </div>

    <!-- Student Detail Modal -->
    <div id="studentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">Student Details</h3>
                    <button onclick="closeStudentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6" id="studentModalContent">
                    <!-- Content loaded by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-800">Update Student Status</h3>
                    <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" id="statusUserId" name="user_id">
                    <input type="hidden" name="update_status" value="1">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="statusSelect" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="Add notes about this status change..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Update Status
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
         * View student details in modal
         */
        function viewStudent(userId) {
            fetch('../includes/async/get_student_details.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const student = data.student;
                        document.getElementById('studentModalContent').innerHTML = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-semibold text-gray-700 mb-2">Personal Information</h4>
                                    <p><strong>Name:</strong> ${student.first_name} ${student.last_name}</p>
                                    <p><strong>Email:</strong> ${student.email}</p>
                                    <p><strong>Phone:</strong> ${student.phone || 'N/A'}</p>
                                    <p><strong>Date of Birth:</strong> ${student.date_of_birth || 'N/A'}</p>
                                    <p><strong>Gender:</strong> ${student.gender || 'N/A'}</p>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700 mb-2">Academic Information</h4>
                                    <p><strong>Application #:</strong> ${student.application_number}</p>
                                    <p><strong>Level:</strong> ${student.level}</p>
                                    <p><strong>Status:</strong> <span class="px-2 py-1 rounded-full text-xs ${getStatusClass(student.status)}">${student.status}</span></p>
                                    <p><strong>Applied On:</strong> ${new Date(student.created_at).toLocaleDateString()}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <h4 class="font-semibold text-gray-700 mb-2">Contact Information</h4>
                                    <p><strong>Address:</strong> ${student.address || 'N/A'}</p>
                                    <p><strong>Nationality:</strong> ${student.nationality || 'N/A'}</p>
                                    <p><strong>ID Number:</strong> ${student.id_number || 'N/A'}</p>
                                    <p><strong>Emergency Contact:</strong> ${student.emergency_contact || 'N/A'}</p>
                                    <p><strong>Emergency Phone:</strong> ${student.emergency_phone || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end space-x-4">
                                <button onclick="closeStudentModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg">
                                    Close
                                </button>
                                <button onclick="openStatusModal(${student.user_id}, '${student.status}')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                                    Change Status
                                </button>
                            </div>
                        `;
                        document.getElementById('studentModal').classList.remove('hidden');
                    }
                });
        }

        /**
         * Close student details modal
         */
        function closeStudentModal() {
            document.getElementById('studentModal').classList.add('hidden');
        }

        /**
         * Get CSS class for status badge
         */
        function getStatusClass(status) {
            switch(status) {
                case 'active': return 'bg-green-100 text-green-800';
                case 'pending': return 'bg-yellow-100 text-yellow-800';
                case 'suspended': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        /**
         * Open status update modal with Approve preset
         */
        function approveStudent(userId) {
            if (confirm('Are you sure you want to approve this student?')) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusSelect').value = 'active';
                document.getElementById('statusModal').classList.remove('hidden');
            }
        }

        /**
         * Open status update modal with Reject preset
         */
        function rejectStudent(userId) {
            if (confirm('Are you sure you want to reject this student?')) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusSelect').value = 'rejected';
                document.getElementById('statusModal').classList.remove('hidden');
            }
        }

        /**
         * Open status update modal with current status
         */
        function openStatusModal(userId, currentStatus) {
            document.getElementById('statusUserId').value = userId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        /**
         * Close status update modal
         */
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('studentModal').addEventListener('click', function(e) {
            if (e.target === this) closeStudentModal();
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) closeStatusModal();
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('studentModal').classList.contains('hidden')) closeStudentModal();
                if (!document.getElementById('statusModal').classList.contains('hidden')) closeStatusModal();
            }
        });
    </script>
</body>
</html>
