<?php
include_once '../../includes/session.php';
include_once '../../includes/config.php';
include_once '../../includes/header.php';

// Fetch courses assigned to this lecturer
$lecturer_id = $_SESSION['lecturer_id']; // Set this on login
$sql = "SELECT c.id, c.name FROM courses c
        JOIN lecturer_courses lc ON c.id = lc.course_id
        WHERE lc.lecturer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$courses = $stmt->get_result();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $file_path = '';

    // Upload file if selected
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === 0) {
        $upload_dir = '../../uploads/assignments/';
        $filename = basename($_FILES['assignment_file']['name']);
        $target_file = $upload_dir . time() . "_" . $filename;
        move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file);
        $file_path = $target_file;
    }

    $insert = $conn->prepare("INSERT INTO assignments (course_id, title, description, file_path, deadline) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("issss", $course_id, $title, $description, $file_path, $deadline);

    if ($insert->execute()) {
        $success = "Assignment posted successfully.";
    } else {
        $error = "Error posting assignment.";
    }
}
?>

<h2>📤 Upload Assignment</h2>



$upload_dir = '../../lecturer/uploads/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);  // recursively create folders if needed
}

// then save the file
$filename = basename($_FILES['assignment_file']['name']);
$target_file = $upload_dir . time() . "_" . $filename;





<?php if ($success): ?><p style="color:green"><?= $success ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Course</label>
    <select name="course_id" required>
        <option value="">Select</option>
        <?php while ($course = $courses->fetch_assoc()): ?>
            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Title</label>
    <input type="text" name="title" required><br><br>

    <label>Description</label>
    <textarea name="description" rows="4" required></textarea><br><br>

    <label>Deadline</label>
    <input type="date" name="deadline" required><br><br>

    <label>Upload File (optional)</label>
    <input type="file" name="assignment_file"><br><br>

    <button type="submit">Upload</button>
</form>

<?php include_once '../../includes/footer.php'; ?>
