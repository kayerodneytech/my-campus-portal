<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php"); // Lecturer sidebar
?>

<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container">
  <div class="dashboard-header">
    <h1>Lecturer Dashboard</h1>
    <img src="../1753449127073.jpg" alt="MyCamp Portal Logo" style="max-width: 60px; border-radius: 12px;">
  </div>
  <div class="dashboard-stats">
    <a href="my_courses.php" class="stat-card" style="text-decoration:none;">
      <div class="stat-icon library"><i class="fas fa-book"></i></div>
      <div class="stat-number">My Courses</div>
      <div class="stat-label">View and manage your assigned courses.</div>
    </a>
    <a href="assignments.php" class="stat-card" style="text-decoration:none;">
      <div class="stat-icon assignments"><i class="fas fa-tasks"></i></div>
      <div class="stat-number">Assignments</div>
      <div class="stat-label">Upload, view, and grade student work.</div>
    </a>
    <a href="attendance.php" class="stat-card" style="text-decoration:none;">
      <div class="stat-icon attendance"><i class="fas fa-calendar-check"></i></div>
      <div class="stat-number">Attendance</div>
      <div class="stat-label">Mark and view attendance records.</div>
    </a>
    <a href="live_classes.php" class="stat-card" style="text-decoration:none;">
      <div class="stat-icon grades"><i class="fas fa-video"></i></div>
      <div class="stat-number">Online Classes</div>
      <div class="stat-label">Manage your live class links.</div>
    </a>
    <a href="announcements.php" class="stat-card" style="text-decoration:none;">
      <div class="stat-icon payments"><i class="fas fa-bullhorn"></i></div>
      <div class="stat-number">Announcements</div>
      <div class="stat-label">Send course announcements.</div>
    </a>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<?php include("../includes/footer.php"); ?>