<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';



$lecturer_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get lecturer's classes
$classes_query = $conn->prepare("
    SELECT cl.id, cl.class_code, c.course_code, c.course_name 
    FROM classes cl
    JOIN courses c ON cl.course_id = c.id
    WHERE cl.lecturer_id = ? AND cl.status = 'ongoing'
    AND cl.academic_year = YEAR(CURDATE())
    ORDER BY c.course_code, cl.class_code
");
$classes_query->bind_param("i", $lecturer_id);
$classes_query->execute();
$classes = $classes_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_assignment'])) {
        $class_id = intval($_POST['class_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $assignment_type = $_POST['assignment_type'];
        $due_date = $_POST['due_date'];
        $max_score = floatval($_POST['max_score']);
        $weight = floatval($_POST['weight']);
        $instructions = trim($_POST['instructions']);
        $status = $_POST['status'];
        
        if (empty($title) || empty($due_date) || $max_score <= 0 || $weight <= 0) {
            $error = 'Title, due date, max score, and weight are required.';
        } else {
            // Handle file upload
            $attachment = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/assignments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                $file_name = 'assignment_' . time() . '_' . uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                    $attachment = $file_name;
                } else {
                    $error = 'Failed to upload attachment.';
                }
            }
            
            if (!$error) {
                $stmt = $conn->prepare("
                    INSERT INTO assignments (class_id, title, description, assignment_type, due_date, max_score, weight, instructions, attachment, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("issssdssss", $class_id, $title, $description, $assignment_type, $due_date, $max_score, $weight, $instructions, $attachment, $status);
                
                if ($stmt->execute()) {
                    $success = 'Assignment created successfully!';
                    // Log activity
                    logActivity('assignment_create', "Created assignment: $title", "Class ID: $class_id");
                } else {
                    $error = 'Error creating assignment: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['update_assignment'])) {
        $assignment_id = intval($_POST['assignment_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $assignment_type = $_POST['assignment_type'];
        $due_date = $_POST['due_date'];
        $max_score = floatval($_POST['max_score']);
        $weight = floatval($_POST['weight']);
        $instructions = trim($_POST['instructions']);
        $status = $_POST['status'];
        
        if (empty($title) || empty($due_date) || $max_score <= 0 || $weight <= 0) {
            $error = 'Title, due date, max score, and weight are required.';
        } else {
            // Handle file upload
            $attachment = $_POST['existing_attachment'];
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/assignments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old file if exists
                if ($attachment) {
                    $old_file = $upload_dir . $attachment;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                $file_name = 'assignment_' . time() . '_' . uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                    $attachment = $file_name;
                } else {
                    $error = 'Failed to upload attachment.';
                }
            }
            
            if (!$error) {
                $stmt = $conn->prepare("
                    UPDATE assignments 
                    SET title = ?, description = ?, assignment_type = ?, due_date = ?, max_score = ?, weight = ?, instructions = ?, attachment = ?, status = ?, updated_at = NOW()
                    WHERE id = ? AND class_id IN (SELECT id FROM classes WHERE lecturer_id = ?)
                ");
                $stmt->bind_param("ssssddsssii", $title, $description, $assignment_type, $due_date, $max_score, $weight, $instructions, $attachment, $status, $assignment_id, $lecturer_id);
                
                if ($stmt->execute()) {
                    $success = 'Assignment updated successfully!';
                    // Log activity
                    logActivity('assignment_update', "Updated assignment: $title", "Assignment ID: $assignment_id");
                } else {
                    $error = 'Error updating assignment: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $assignment_id = intval($_GET['delete']);
    
    // First get assignment details to delete attachment
    $get_stmt = $conn->prepare("
        SELECT attachment FROM assignments 
        WHERE id = ? AND class_id IN (SELECT id FROM classes WHERE lecturer_id = ?)
    ");
    $get_stmt->bind_param("ii", $assignment_id, $lecturer_id);
    $get_stmt->execute();
    $assignment = $get_stmt->get_result()->fetch_assoc();
    $get_stmt->close();
    
    // Delete attachment file if exists
    if ($assignment && $assignment['attachment']) {
        $file_path = '../uploads/assignments/' . $assignment['attachment'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete assignment
    $stmt = $conn->prepare("
        DELETE FROM assignments 
        WHERE id = ? AND class_id IN (SELECT id FROM classes WHERE lecturer_id = ?)
    ");
    $stmt->bind_param("ii", $assignment_id, $lecturer_id);
    
    if ($stmt->execute()) {
        $success = 'Assignment deleted successfully!';
        // Log activity
        logActivity('assignment_delete', "Deleted assignment ID: $assignment_id");
    } else {
        $error = 'Error deleting assignment: ' . $stmt->error;
    }
    $stmt->close();
}

// Handle publish/unpublish action
if (isset($_GET['publish'])) {
    $assignment_id = intval($_GET['publish']);
    $status = $_GET['status'] === 'publish' ? 'published' : 'draft';
    
    $stmt = $conn->prepare("
        UPDATE assignments 
        SET status = ?, updated_at = NOW()
        WHERE id = ? AND class_id IN (SELECT id FROM classes WHERE lecturer_id = ?)
    ");
    $stmt->bind_param("sii", $status, $assignment_id, $lecturer_id);
    
    if ($stmt->execute()) {
        $action = $status === 'published' ? 'published' : 'unpublished';
        $success = "Assignment $action successfully!";
        // Log activity
        logActivity('assignment_publish', "$action assignment", "Assignment ID: $assignment_id");
    } else {
        $error = 'Error updating assignment status: ' . $stmt->error;
    }
    $stmt->close();
}

// Get assignments with filters
$class_filter = isset($_GET['class']) ? intval($_GET['class']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = ["a.class_id IN (SELECT id FROM classes WHERE lecturer_id = $lecturer_id)"];
if ($class_filter > 0) {
    $where_conditions[] = "a.class_id = $class_filter";
}
if ($status_filter && in_array($status_filter, ['draft', 'published', 'closed'])) {
    $where_conditions[] = "a.status = '$status_filter'";
}

$where_clause = implode(' AND ', $where_conditions);

$assignments_query = "
    SELECT a.*, c.course_code, c.course_name, cl.class_code,
           (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count,
           (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id AND status = 'graded') as graded_count
    FROM assignments a
    JOIN classes cl ON a.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE $where_clause
    ORDER BY a.due_date DESC, a.created_at DESC
";
$assignments_result = $conn->query($assignments_query);
$assignments = $assignments_result->fetch_all(MYSQLI_ASSOC);

// Get assignment for editing if requested
$edit_assignment = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("
        SELECT a.*, c.course_code, c.course_name, cl.class_code
        FROM assignments a
        JOIN classes cl ON a.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        WHERE a.id = ? AND cl.lecturer_id = ?
    ");
    $edit_stmt->bind_param("ii", $edit_id, $lecturer_id);
    $edit_stmt->execute();
    $edit_assignment = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
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
    <title>Manage Assignments - Lecturer Portal</title>
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex ">
      <?php include 'sidebar.php'?>
      <!-- Main Content -->
    <div class="main-content flex-1 overflow-hidden p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Assignment Management</h1>
                <p class="text-gray-600">Create and manage assignments for your classes</p>
            </div>
            <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-4 md:mt-0">
                <i class="fas fa-plus mr-2"></i>
                Create Assignment
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

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Assignments</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select name="class" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['course_code'] . ' - ' . $class['class_code']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Statuses</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full">
                        <i class="fas fa-filter mr-2"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Assignments Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submissions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($assignments) > 0): ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['assignment_type']); ?> • <?php echo $assignment['max_score']; ?> points</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($assignment['course_code']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['class_code']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $assignment['graded_count']; ?> graded / <?php echo $assignment['submission_count']; ?> submitted
                                        </div>
                                        <?php if ($assignment['submission_count'] > 0): ?>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($assignment['graded_count'] / $assignment['submission_count']) * 100; ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $assignment['status'] == 'published' ? 'bg-green-100 text-green-800' : ''; ?>
                                            <?php echo $assignment['status'] == 'draft' ? 'bg-gray-100 text-gray-800' : ''; ?>
                                            <?php echo $assignment['status'] == 'closed' ? 'bg-red-100 text-red-800' : ''; ?>">
                                            <?php echo ucfirst($assignment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="assignment_submissions.php?id=<?php echo $assignment['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Submissions">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?edit=<?php echo $assignment['id']; ?>" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($assignment['status'] == 'draft'): ?>
                                                <a href="?publish=<?php echo $assignment['id']; ?>&status=publish" class="text-green-600 hover:text-green-900" title="Publish">
                                                    <i class="fas fa-paper-plane"></i>
                                                </a>
                                            <?php elseif ($assignment['status'] == 'published'): ?>
                                                <a href="?publish=<?php echo $assignment['id']; ?>&status=draft" class="text-gray-600 hover:text-gray-900" title="Unpublish">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo $assignment['id']; ?>" class="text-red-600 hover:text-red-900" title="Delete" onclick="return confirm('Are you sure you want to delete this assignment?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No assignments found. <button onclick="openCreateModal()" class="text-blue-600 hover:text-blue-800">Create your first assignment</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit Assignment Modal -->
    <div id="assignmentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">
                        <?php echo $edit_assignment ? 'Edit Assignment' : 'Create New Assignment'; ?>
                    </h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" class="p-6" enctype="multipart/form-data">
                    <input type="hidden" name="<?php echo $edit_assignment ? 'update_assignment' : 'create_assignment'; ?>" value="1">
                    <?php if ($edit_assignment): ?>
                        <input type="hidden" name="assignment_id" value="<?php echo $edit_assignment['id']; ?>">
                        <input type="hidden" name="existing_attachment" value="<?php echo htmlspecialchars($edit_assignment['attachment']); ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Class *</label>
                            <select name="class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" 
                                        <?php echo ($edit_assignment && $edit_assignment['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['course_code'] . ' - ' . $class['class_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                        <input type="text" name="title" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                               value="<?php echo $edit_assignment ? htmlspecialchars($edit_assignment['title']) : ''; ?>"
                               placeholder="Assignment title">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                  placeholder="Assignment description"><?php echo $edit_assignment ? htmlspecialchars($edit_assignment['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Type *</label>
                            <select name="assignment_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="homework" <?php echo ($edit_assignment && $edit_assignment['assignment_type'] == 'homework') ? 'selected' : ''; ?>>Homework</option>
                                <option value="quiz" <?php echo ($edit_assignment && $edit_assignment['assignment_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                <option value="exam" <?php echo ($edit_assignment && $edit_assignment['assignment_type'] == 'exam') ? 'selected' : ''; ?>>Exam</option>
                                <option value="project" <?php echo ($edit_assignment && $edit_assignment['assignment_type'] == 'project') ? 'selected' : ''; ?>>Project</option>
                                <option value="presentation" <?php echo ($edit_assignment && $edit_assignment['assignment_type'] == 'presentation') ? 'selected' : ''; ?>>Presentation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="draft" <?php echo ($edit_assignment && $edit_assignment['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($edit_assignment && $edit_assignment['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="closed" <?php echo ($edit_assignment && $edit_assignment['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
                            <input type="datetime-local" name="due_date" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo $edit_assignment ? date('Y-m-d\TH:i', strtotime($edit_assignment['due_date'])) : ''; ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Score *</label>
                            <input type="number" name="max_score" required step="0.01" min="0"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo $edit_assignment ? $edit_assignment['max_score'] : ''; ?>"
                                   placeholder="100">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Weight (%) *</label>
                        <input type="number" name="weight" required step="0.01" min="0" max="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                               value="<?php echo $edit_assignment ? $edit_assignment['weight'] : ''; ?>"
                               placeholder="20">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                        <textarea name="instructions" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                  placeholder="Assignment instructions"><?php echo $edit_assignment ? htmlspecialchars($edit_assignment['instructions']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Attachment</label>
                        <input type="file" name="attachment"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php if ($edit_assignment && $edit_assignment['attachment']): ?>
                            <p class="text-sm text-gray-600 mt-2">
                                Current file: <?php echo htmlspecialchars($edit_assignment['attachment']); ?>
                                <a href="../uploads/assignments/<?php echo $edit_assignment['attachment']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 ml-2">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <?php echo $edit_assignment ? 'Update Assignment' : 'Create Assignment'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('assignmentModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('assignmentModal').classList.add('hidden');
            <?php if ($edit_assignment): ?>
                window.location.href = 'assignments.php';
            <?php endif; ?>
        }
        
        // Open modal if editing
        <?php if ($edit_assignment): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('assignmentModal').classList.remove('hidden');
            });
        <?php endif; ?>
        
        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('assignmentModal');
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>