<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin and logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get all departments for dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle CRUD operations
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
    
    if (empty($course_name) || empty($course_code) || empty($department_id)) {
        $error = 'Course name, code, and department are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, department_id, description, level, credits) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissi", $course_name, $course_code, $department_id, $description, $level, $credits);
        
        if ($stmt->execute()) {
            $course_id = $conn->insert_id;
            $success = 'Course created successfully!';
            
            // Log activity
            logActivity('course_create', "Created course: $course_name ($course_code)", "Course ID: $course_id");
        } else {
            $error = 'Error creating course: ' . $stmt->error;
        }
        $stmt->close();
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
    
    if (empty($course_name) || empty($course_code) || empty($department_id)) {
        $error = 'Course name, code, and department are required.';
    } else {
        $stmt = $conn->prepare("UPDATE courses SET course_name = ?, course_code = ?, department_id = ?, description = ?, level = ?, credits = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssissisi", $course_name, $course_code, $department_id, $description, $level, $credits, $status, $course_id);
        
        if ($stmt->execute()) {
            $success = 'Course updated successfully!';
            
            // Log activity
            logActivity('course_update', "Updated course: $course_name ($course_code)", "Course ID: $course_id");
        } else {
            $error = 'Error updating course: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Delete course
if (isset($_GET['delete'])) {
    $course_id = intval($_GET['delete']);
    
    // Check if course has any applications before deleting
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_applications WHERE course_id = ?");
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $app_count = $result->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($app_count > 0) {
        $error = 'Cannot delete course. There are ' . $app_count . ' applications associated with this course.';
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

// Get all courses with department information
$courses = $conn->query("
    SELECT c.*, d.name as department_name 
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    ORDER BY c.course_name
")->fetch_all(MYSQLI_ASSOC);

// Function to log activities
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                    <h1 class="text-xl font-bold text-gray-800">Admin Portal</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard
                    </a>
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="text-gray-600 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Course Management</h1>
                <p class="text-gray-600">Create, edit, and manage courses</p>
            </div>
            <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-4 md:mt-0">
                <i class="fas fa-plus mr-2"></i>
                Add New Course
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

        <!-- Courses Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</th>
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
                                        <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($course['description']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($course['department_name']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <?php echo ucfirst($course['level']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo $course['credits']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : ''; ?>
                                            <?php echo $course['status'] == 'inactive' ? 'bg-gray-100 text-gray-800' : ''; ?>
                                            <?php echo $course['status'] == 'archived' ? 'bg-red-100 text-red-800' : ''; ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="editCourse(<?php echo $course['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No courses found. <button onclick="openCreateModal()" class="text-blue-600 hover:text-blue-800">Create your first course</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Course Modal -->
    <div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
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
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Course description..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Credits</label>
                        <input type="number" name="credits" min="1" max="999" value="60"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
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
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
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
                    
                    <div class="flex justify-end space-x-3">
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
    // Modal functions
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
    }

    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Edit course
    function editCourse(courseId) {
        fetch('includes/async/get_course_details.php?id=' + courseId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const course = data.course;
                    document.getElementById('edit_course_id').value = course.id;
                    document.getElementById('edit_course_name').value = course.course_name;
                    document.getElementById('edit_course_code').value = course.course_code;
                    document.getElementById('edit_department_id').value = course.department_id;
                    document.getElementById('edit_level').value = course.level;
                    document.getElementById('edit_description').value = course.description || '';
                    document.getElementById('edit_credits').value = course.credits;
                    document.getElementById('edit_status').value = course.status;
                    openEditModal();
                } else {
                    alert('Error loading course details: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error loading course details: ' + error);
            });
    }

    // Delete confirmation
    function confirmDelete(courseId, courseName) {
        if (confirm(`Are you sure you want to delete the course "${courseName}"? This action cannot be undone.`)) {
            window.location.href = `courses.php?delete=${courseId}`;
        }
    }

    // Close modals when clicking outside
    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (!document.getElementById('createModal').classList.contains('hidden')) closeCreateModal();
            if (!document.getElementById('editModal').classList.contains('hidden')) closeEditModal();
        }
    });
    </script>
</body>
</html>