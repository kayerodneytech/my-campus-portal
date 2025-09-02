<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../students_frrdy/style.css">
</head>
<body>
  <?php include("includes/sidebar.php"); ?>
  <div class="dashboard-container" style="margin-left:240px;">
    <div class="dashboard-header">
      <h1>Admin Dashboard</h1>
      <img src="../1753449127073.jpg" alt="MyCamp Portal Logo" style="max-width: 60px; border-radius: 12px;">
    </div>
    <div class="dashboard-stats">
      <a href="students.php" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon courses"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-number"><?php echo getCount('students'); ?></div>
        <div class="stat-label">Students</div>
      </a>
      <a href="courses.php" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon library"><i class="fas fa-book"></i></div>
        <div class="stat-number"><?php echo getCount('courses'); ?></div>
        <div class="stat-label">Courses</div>
      </a>
      <div class="stat-card">
        <div class="stat-icon assignments"><i class="fas fa-tasks"></i></div>
        <div class="stat-number"><?php echo getCount('assignments'); ?></div>
        <div class="stat-label">Assignments</div>
      </div>
      <a href="voting.php" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon grades"><i class="fas fa-vote-yea"></i></div>
        <div class="stat-number"><?php echo isVotingActive() ? 'Active' : 'Inactive'; ?></div>
        <div class="stat-label">Voting</div>
      </a>
      <a href="notifications.php" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon attendance"><i class="fas fa-bell"></i></div>
        <div class="stat-number"><?php echo getCount('notifications'); ?></div>
        <div class="stat-label">Notifications</div>
      </a>
      <a href="lecturers.php" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon payments"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-number"><?php echo getCount('lecturers'); ?></div>
        <div class="stat-label">Lecturers</div>
      </a>
    </div>

    <!-- Quick Actions (from student dashboard, adapted for admin) -->
    <div class="quick-actions">
      <h2 class="section-title">Quick Actions</h2>
      <div class="action-buttons">
        <a href="users.php" class="action-btn"><i class="fas fa-users"></i>Manage Users</a>
        <a href="courses.php" class="action-btn"><i class="fas fa-book"></i>Manage Courses</a>
        <a href="assignments.php" class="action-btn"><i class="fas fa-tasks"></i>Assignments</a>
        <a href="reports.php" class="action-btn"><i class="fas fa-chart-bar"></i>Reports</a>
        <a href="payments.php" class="action-btn"><i class="fas fa-credit-card"></i>Payments</a>
        <a href="notifications.php" class="action-btn"><i class="fas fa-bell"></i>Notifications</a>
        <a href="voting.php" class="action-btn"><i class="fas fa-vote-yea"></i>Voting</a>
        <a href="settings.php" class="action-btn"><i class="fas fa-cog"></i>Settings</a>
        <a href="../logout.php" class="action-btn"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>
    </div>

    <!-- Recent Activities and Notifications (admin version, placeholder) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
      <div class="recent-activities">
        <h2 class="section-title">Recent Activities</h2>
        <p style="color: #bdbdbd; text-align: center; padding: 20px;">No recent activities (admin)</p>
      </div>
      <div class="notifications">
        <h2 class="section-title">Notifications</h2>
        <p style="color: #bdbdbd; text-align: center; padding: 20px;">No new notifications (admin)</p>
      </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  </div>
    </div>
  </div>

  <?php
    function getCount($table) {
      include("../includes/config.php");
      $query = "SELECT COUNT(*) AS total FROM $table";
      $result = mysqli_query($conn, $query);
      $row = mysqli_fetch_assoc($result);
      return $row['total'];
    }

    function isVotingActive() {
      include("../includes/config.php");
      $query = "SELECT status FROM voting_status LIMIT 1";
      $result = mysqli_query($conn, $query);
      $row = mysqli_fetch_assoc($result);
      return $row && $row['status'] == 'active';
    }
  ?>
</body>
</html>
