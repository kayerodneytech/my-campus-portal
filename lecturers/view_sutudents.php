<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Get course ID from URL
if (!isset($_GET['course_id'])) {
    echo "<p>Invalid course.</p>";
    include("../includes/footer.php");
    exit;
}

$course_id = intval($_GET['course_id']);

// Fetch course info
$course_sql = "SELECT course_name, course_code FROM courses WHERE id = ?";
$course_stmt = mysqli_prepare($conn, $course_sql);
mysqli_stmt_bind_param($course_stmt, "i", $course_id);
mysqli_stmt_execute($course_stmt);
$course_result = mysqli_stmt_get_result($course_stmt);

if (mysqli_num_rows($course_result) == 0) {
    echo "<p>Course not found.</p>";
    include("../includes/footer.php");
    exit;
}

$course = mysqli_fetch_assoc($course_result);

// Fetch enrolled students
$students_sql = "SELECT s.id, s.full_name, s.email, s.student_number
                 FROM students s
                 JOIN enrollments e ON s.id = e.student_id
                 WHERE e.course_id = ?";
$students_stmt = mysqli_prepare($conn, $students_sql);
mysqli_stmt_bind_param($students_stmt, "i", $course_id);
mysqli_stmt_execute($students_stmt);
$students_result = mysqli_stmt_get_result($students_stmt);
?>

<div class="main">
  <h2>👥 Enrolled Students — <?php echo htmlspecialchars($course['course_code'] . " - " . $course['course_name']); ?></h2>

  <?php if (mysqli_num_rows($students_result) > 0): ?>
    <table border="1" cellpadding="10" cellspacing="0">
      <thead>
        <tr>
          <th>Student Number</th>
          <th>Full Name</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
          <tr>
            <td><?php echo htmlspecialchars($student['student_number']); ?></td>
            <td><?php echo htmlspecialchars($student['full_name']); ?></td