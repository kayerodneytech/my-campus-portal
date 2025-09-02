<?php include("../includes/config.php"); ?>
<?php include("../includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<div class="main">
  <h1>Welcome, Admin</h1>
  <div class="tiles">
    <div class="card">
      <h3>👨‍🎓 Students</h3>
      <p><?php echo getCount('students'); ?></p>
    </div>
    <div class="card">
      <h3>📚 Courses</h3>
      <p><?php echo getCount('courses'); ?></p>
    </div>
    <div class="card">
      <h3>📝 Assignments</h3>
      <p><?php echo getCount('assignments'); ?></p>
    </div>
    <div class="card">
      <h3>🗳️ Voting</h3>
      <p><?php echo isVotingActive() ? 'Active' : 'Inactive'; ?></p>
    </div>
    <div class="card">
      <h3>🔔 Notifications</h3>
      <p><?php echo getCount('notifications'); ?></p>
    </div>
    <div class="card">
      <h3>👩‍🏫 Lecturers</h3>
      <p><?php echo getCount('lecturers'); ?></p>
    </div>
  </div>
</div>

<?php include("../includes/footer.php"); ?>

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