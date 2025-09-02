<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $student_id = $_POST['student_id'];
  $type_id = $_POST['type_id'];
  $amount = $_POST['amount'];
  $payment_date = $_POST['payment_date'];
  $description = mysqli_real_escape_string($conn, $_POST['description']);

  $query = "INSERT INTO payments (student_id, type_id, amount, payment_date, description) VALUES ('$student_id', '$type_id', '$amount', '$payment_date', '$description')";
  if (mysqli_query($conn, $query)) {
    echo "<p style='color:green;'>Payment recorded successfully.</p>";
  } else {
    echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
  }
}
?>
<div class="main">
  <h2>Record New Payment</h2>
  <form method="POST" action="">
    <label>Student ID:</label><br>
    <input type="number" name="student_id" required><br><br>

    <label>Payment Type:</label><br>
    <select name="type_id" required>
      <?php
      $res = mysqli_query($conn, "SELECT * FROM payment_types");
      while ($row = mysqli_fetch_assoc($res)) {
        echo "<option value='{$row['id']}'>{$row['type_name']}</option>";
      }
      ?>
    </select><br><br>

    <label>Amount:</label><br>
    <input type="number" step="0.01" name="amount" required><br><br>

    <label>Payment Date:</label><br>
    <input type="date" name="payment_date" required><br><br>

    <label>Description:</label><br>
    <textarea name="description"></textarea><br><br>

    <button type="submit">Submit Payment</button>
  </form>
</div>
<?php include("../includes/footer.php"); ?>
