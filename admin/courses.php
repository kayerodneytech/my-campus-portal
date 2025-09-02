<?php
// admin/courses.php
include("includes/sidebar.php");
include("../includes/config.php");
?>
<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container" style="margin-left:240px;">
  <div class="dashboard-header">
    <h1>Manage Courses</h1>
  </div>
  <div class="dashboard-stats">
    <table style="width:100%;background:#232526;color:#fff;border-radius:12px;overflow:hidden;">
      <thead>
        <tr style="background:#4e54c8;color:#fff;">
          <th>Course Code</th>
          <th>Course Name</th>
          <th>Lecturer(s)</th>
          <th>Enrolled Students</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $courses = $conn->query("SELECT * FROM courses");
        while($course = $courses->fetch_assoc()):
          // Get lecturers for this course
          $lects = $conn->query("SELECT l.full_name FROM lecturers l JOIN course_lecturers cl ON l.id=cl.lecturer_id WHERE cl.course_id=".$course['id']);
          $lecturer_names = [];
          while($l = $lects->fetch_assoc()) $lecturer_names[] = $l['full_name'];
          // Get students for this course
          $studs = $conn->query("SELECT s.full_name FROM students s JOIN enrollments e ON s.id=e.student_id WHERE e.course_id=".$course['id']);
          $student_names = [];
          while($s = $studs->fetch_assoc()) $student_names[] = $s['full_name'];
        ?>
        <tr>
          <td><?php echo htmlspecialchars($course['code']); ?></td>
          <td><?php echo htmlspecialchars($course['name']); ?></td>
          <td><?php echo implode(', ', $lecturer_names) ?: '<span style=\'color:#bdbdbd\'>None</span>'; ?></td>
          <td>
            <details>
              <summary><?php echo count($student_names); ?> students</summary>
              <ul style="margin:0 0 0 20px; color:#bdbdbd;">
                <?php foreach($student_names as $sn): ?>
                  <li><?php echo htmlspecialchars($sn); ?></li>
                <?php endforeach; ?>
              </ul>
            </details>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
