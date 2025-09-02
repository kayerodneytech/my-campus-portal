<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Total students
$students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'student'"));

// Total payments
$total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM payments"));

// Total courses
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM courses"));

// Total assignments
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM assignments"));

// Total votes cast
$total_votes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM votes"));
?>

<div class="main">
  <h2>System Reports Dashboard</h2>
  <img src="../1753449127073.jpg" alt="MyCamp Portal Logo" style="max-width: 60px; display: block; margin: 20px auto 10px auto;">

  <div class="dashboard-cards">
    <div class="card">
      <h3>📚 Total Students</h3>
      <p><?= $students['total'] ?></p>
    </div>

    <div class="card">
      <h3>💰 Total Payments Received</h3>
      <p>$<?= number_format($total_payments['total'], 2) ?></p>
    </div>

    <div class="card">
      <h3>📖 Courses Offered</h3>
      <p><?= $total_courses['total'] ?></p>
    </div>

    <div class="card">
      <h3>📝 Assignments</h3>
      <p><?= $total_assignments['total'] ?></p>
    </div>

    <div class="card">
      <h3>🗳️ Votes Cast</h3>
      <p><?= $total_votes['total'] ?></p>
    </div>
  </div>

  <style>
    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 20px;
    }
    .card {
      background: #1e1e2f;
      padding: 20px;
      border-radius: 10px;
      color: white;
      text-align: center;
      box-shadow: 0 0 8px rgba(0,0,0,0.2);
    }
    .card h3 {
      margin: 0 0 10px;
      font-size: 18px;
    }
    .card p {
      font-size: 24px;
      font-weight: bold;
    }
  </style>
</div>

<?php include("../includes/footer.php"); ?>
