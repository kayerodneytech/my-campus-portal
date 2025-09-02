<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Simulate lecturer session (replace with real session later)
$lecturer_id = $_SESSION['lecturer_id']; // Ensure this session is set during login

// Fetch courses assigned to the lecturer
$query = "SELECT c.id, c.course_code, c.course_name, c.semester
          FROM courses c
          JOIN course_lecturers cl ON c.id = cl.course_id
          WHERE cl.lecturer_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $lecturer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="main">
  <h2>📘 My Courses</h2>

  <?php if (mysqli_num_rows($result) > 0): ?>
    <table border="1" cellpadding="10" cellspacing="0">
      <thead>
        <tr>
          <th>Course Code</th>
          <th>Course Name</th>
          <th>Semester</th>
          <th>Students</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($course = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
            <td><?php echo htmlspecialchars($course['semester']); ?></td>
            <td>
              <a href="view_students.php?course_id=<?php echo $course['id']; ?>">View</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>You have not been assigned any courses yet.</p>
  <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>