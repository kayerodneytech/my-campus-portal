<?php
/**
 * Admin Courses Management Page
 *
 * Features:
 * - Create, read, update, and delete courses
 * - Search and filter functionality
 * - Pagination for large datasets
 * - Mobile-responsive design
 * - Interactive UI elements
 * - Activity logging
 * - Data validation and security
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
// PAGINATION AND SEARCH SETUP
// =============================================

// Get search and filter parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$department_filter = isset($_GET['department']) ? intval($_GET['department']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; // Items per page
$offset = ($page - 1) * $limit;

// =============================================
// GET DEPARTMENTS FOR DROPDOWNS
// =============================================
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// =============================================
// CRUD OPERATIONS
// =============================================

// Initialize notification variables
$error = '';
$success = '';

// Create new course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $department_id = intval($_POST['department_id']);
    $description = trim($_POST['description']);
    $level = $_POST['level'];
    $credits = intval($_POST['credits']);
    $status = $_POST['status'] ?? 'active';

    // Validate required fields
    if (empty($course_name) || empty($course_code) || empty($department_id)) {
        $error = 'Course name, code, and department are required.';
    } else {
        // Check if course code already exists
        $check_code = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $check_code->bind_param("s", $course_code);
        $check_code->execute();
        $check_code->store_result();

        if ($check_code->num_rows > 0) {
            $error = 'A course with this code already exists.';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO courses
                (course_name, course_code, department_id, description, level, credits, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssissis", $course_name, $course_code, $department_id, $description, $level, $credits, $status);

            if ($stmt->execute()) {
                $course_id = $conn->insert_id;
                $success = 'Course created successfully!';

                // Log activity
                logActivity(
                    'course_create',
                    "Created course: $course_name ($course_code)",
                    "Course ID: $course_id, Department: $department_id"
                );
            } else {
                $error = 'Error creating course: ' . $stmt->error;
            }
            $stmt->close();
        }
        $check_code->close();
    }
}

// Update course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $course_id = intval($_POST['course_id']);
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $department_id = intval($_POST['department_id']);
    $description = trim($_POST['description']);
    $level = $_POST['level'];
    $credits = intval($_POST['credits']);
    $status = $_POST['status'];

    // Validate required fields
    if (empty($course_name) || empty($course_code) || empty($department_id)) {
        $error = 'Course name, code, and department are required.';
    } else {
        // Check if course code already exists for another course
        $check_code = $conn->prepare("
            SELECT id FROM courses
            WHERE course_code = ? AND id != ?
        ");
        $check_code->bind_param("si", $course_code, $course_id);
        $check_code->execute();
        $check_code->store_result();

        if ($check_code->num_rows > 0) {
            $error = 'A course with this code already exists.';
        } else {
            $stmt = $conn->prepare("
                UPDATE courses
                SET course_name = ?, course_code = ?, department_id = ?,
                    description = ?, level = ?, credits = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssissisi", $course_name, $course_code, $department_id, $description, $level, $credits, $status, $course_id);

            if ($stmt->execute()) {
                $success = 'Course updated successfully!';

                // Log activity
                logActivity(
                    'course_update',
                    "Updated course: $course_name ($course_code)",
                    "Course ID: $course_id"
                );
            } else {
                $error = 'Error updating course: ' . $stmt->error;
            }
            $stmt->close();
        }
        $check_code->close();
    }
}

// Delete course
if (isset($_GET['delete'])) {
    $course_id = intval($_GET['delete']);

    // Check if course has any applications before deleting
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM course_applications
        WHERE course_id = ?
    ");
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $app_count = $result->fetch_assoc()['count'];
    $check_stmt->close();

    if ($app_count > 0) {
        $error = 'Cannot delete course. There are ' . $app_count . ' applications associated with this course.';
    } else {
        // Check if course has any classes
        $check_classes = $conn->prepare("
            SELECT COUNT(*) as count
            FROM classes
            WHERE course_id = ?
        ");
        $check_classes->bind_param("i", $course_id);
        $check_classes->execute();
        $result = $check_classes->get_result();
        $class_count = $result->fetch_assoc()['count'];
        $check_classes->close();

        if ($class_count > 0) {
            $error = 'Cannot delete course. There are ' . $class_count . ' classes associated with this course.';
        } else {
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->bind_param("i", $course_id);

            if ($stmt->execute()) {
                $success = 'Course deleted successfully!';

                // Log activity
                logActivity('course_delete', "Deleted course ID: $course_id");
            } else {
                $error = 'Error deleting course: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// =============================================
// FETCH COURSES WITH PAGINATION AND FILTERS
// =============================================

// Base query
$query = "
    SELECT c.*, d.name as department_name
    FROM courses c
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE 1=1
";

// Add filters
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($department_filter > 0) {
    $query .= " AND c.department_id = ?";
    $params[] = $department_filter;
    $types .= 'i';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $query .= " AND (
        c.course_name LIKE ?
        OR c.course_code LIKE ?
        OR c.description LIKE ?
        OR d.name LIKE ?
    )";
    $params = array_merge($params, [
        $search_param, $search_param, $search_param, $search_param
    ]);
    $types .= 'ssss';
}

$query .= " ORDER BY c.course_name";

// Add pagination
$query .= " LIMIT ? OFFSET ?";
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
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Count total courses for pagination
$count_query = "
    SELECT COUNT(*)
    FROM courses c
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE 1=1
";

$count_params = [];
$count_types = '';

if ($status_filter !== 'all') {
    $count_query .= " AND c.status = ?";
    $count_params[] = $status_filter;
    $count_types .= 's';
}

if ($department_filter > 0) {
    $count_query .= " AND c.department_id = ?";
    $count_params[] = $department_filter;
    $count_types .= 'i';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $count_query .= " AND (
        c.course_name LIKE ?
        OR c.course_code LIKE ?
        OR c.description LIKE ?
        OR d.name LIKE ?
    )";
    $count_params = array_merge($count_params, [
        $search_param, $search_param, $search_param, $search_param
    ]);
    $count_types .= 'ssss';
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_courses = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_courses / $limit);

// =============================================
// STATISTICS FOR DASHBOARD CARDS
// =============================================
$total_courses_count = getCount('courses');
$active_courses_count = getCount('courses', 'status = "active"');
$inactive_courses_count = getCount('courses', 'status = "inactive"');
$archived_courses_count = getCount('courses', 'status = "archived"');

// Helper function to get count from a table
function getCount($table, $condition = null) {
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM $table" . ($condition ? " WHERE $condition" : "");
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc()['total'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin Portal</title>

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

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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

            .mobile-p-4 {
                padding: 1rem !important;
            }

            .mobile-grid-cols-1 {
                grid-template-columns: 1fr !important;
            }

            .mobile-w-full {
                width: 100% !important;
            }
        }

        /* Animation for cards */
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Course Management</h1>
                <p class="text-gray-600 mobile-text-sm">Create, edit, and manage academic courses</p>
            </div>
            <button onclick="openCreateModal()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-4 md:mt-0 mobile-full">
                <i class="fas fa-plus mr-2"></i>
                <span class="mobile-hidden">Add New Course</span>
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

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Courses -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-book text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Courses</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_courses_count); ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Courses -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-book-open text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Active Courses</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($active_courses_count); ?></p>
                    </div>
                </div>
            </div>

            <!-- Inactive Courses -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gray-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-book text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Inactive Courses</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($inactive_courses_count); ?></p>
                    </div>
                </div>
            </div>

            <!-- Archived Courses -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-archive text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Archived Courses</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($archived_courses_count); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Courses</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Search by name, code, or description..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="0" <?php echo $department_filter === 0 ? 'selected' : ''; ?>>All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full md:w-auto">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                    <a href="courses.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 w-full md:w-auto text-center">
                        <i class="fas fa-times mr-2"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Courses Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider mobile-hidden">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider mobile-hidden">Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider mobile-hidden">Credits</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                        <div class="text-sm text-gray-500 truncate max-w-xs mobile-hidden"><?php echo htmlspecialchars($course['description']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap mobile-hidden">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($course['department_name'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap mobile-hidden">
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <?php echo ucfirst($course['level']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap mobile-hidden">
                                        <span class="text-sm text-gray-900"><?php echo $course['credits']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            <?php
                                            if ($course['status'] == 'active') echo 'bg-green-100 text-green-800';
                                            elseif ($course['status'] == 'inactive') echo 'bg-gray-100 text-gray-800';
                                            elseif ($course['status'] == 'archived') echo 'bg-red-100 text-red-800';
                                            ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="editCourse(<?php echo $course['id']; ?>)"
                                                    class="text-blue-600 hover:text-blue-900 mobile-text-sm"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                                <span class="mobile-hidden ml-1">Edit</span>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars(addslashes($course['course_name'])); ?>')"
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
                                    No courses found matching your criteria.
                                    <button onclick="openCreateModal()" class="text-blue-600 hover:text-blue-800 ml-2">
                                        Create your first course
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
                    Showing <?php echo count($courses); ?> of <?php echo number_format($total_courses); ?> courses
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&department=<?php echo $department_filter; ?>&search=<?php echo urlencode($search_query); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php
                        $active = $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-50';
                        ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&department=<?php echo $department_filter; ?>&search=<?php echo urlencode($search_query); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg <?php echo $active; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&department=<?php echo $department_filter; ?>&search=<?php echo urlencode($search_query); ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Course Modal -->
    <div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">Create New Course</h3>
                    <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="create_course" value="1">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Name *</label>
                            <input type="text" name="course_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g., Introduction to Programming">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Code *</label>
                            <input type="text" name="course_code" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g., CS101">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                            <select name="department_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Level *</label>
                            <select name="level" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="certificate">Certificate</option>
                                <option value="diploma">Diploma</option>
                                <option value="degree">Degree</option>
                                <option value="masters">Masters</option>
                                <option value="short_course">Short Course</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Course description..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Credits</label>
                            <input type="number" name="credits" min="1" max="999" value="60"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeCreateModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Create Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">Edit Course</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="update_course" value="1">
                    <input type="hidden" id="edit_course_id" name="course_id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Name *</label>
                            <input type="text" id="edit_course_name" name="course_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Code *</label>
                            <input type="text" id="edit_course_code" name="course_code" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                            <select id="edit_department_id" name="department_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Level *</label>
                            <select id="edit_level" name="level" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="certificate">Certificate</option>
                                <option value="diploma">Diploma</option>
                                <option value="degree">Degree</option>
                                <option value="masters">Masters</option>
                                <option value="short_course">Short Course</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="edit_description" name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Credits</label>
                            <input type="number" id="edit_credits" name="credits" min="1" max="999"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="edit_status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Update Course
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
         * Open create course modal
         */
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close create course modal
         */
        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        /**
         * Open edit course modal
         */
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close edit course modal
         */
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        /**
         * Fetch and populate course data for editing
         */
        function editCourse(courseId) {
            fetch(`../includes/async/get_course_details.php?id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const course = data.course;

                        // Populate the edit form
                        document.getElementById('edit_course_id').value = course.id;
                        document.getElementById('edit_course_name').value = course.course_name;
                        document.getElementById('edit_course_code').value = course.course_code;
                        document.getElementById('edit_department_id').value = course.department_id;
                        document.getElementById('edit_level').value = course.level;
                        document.getElementById('edit_description').value = course.description || '';
                        document.getElementById('edit_credits').value = course.credits;
                        document.getElementById('edit_status').value = course.status;

                        // Show the edit modal
                        openEditModal();
                    } else {
                        alert('Error fetching course details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching course details');
                });
        }

        /**
         * Confirm and delete course
         */
        function confirmDelete(courseId, courseName) {
            if (confirm(`Are you sure you want to delete the course "${courseName}"? This action cannot be undone.`)) {
                window.location.href = `courses.php?delete=${courseId}&status=<?php echo $status_filter; ?>&department=<?php echo $department_filter; ?>&search=<?php echo urlencode($search_query); ?>`;
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
