<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");
?>
<div class="main">
  <h2>All Payments</h2>

  <!-- Filter Form -->
  <form method="GET" action="">
    <label>Student ID:</label>
    <input type="text" name="student_id" value="<?= isset($_GET['student_id']) ? $_GET['student_id'] : '' ?>">
    
    <label>Payment Type:</label>
    <select name="type_id">
      <option value="">-- All --</option>
      <?php
      $types = mysqli_query($conn, "SELECT * FROM payment_types");
      while ($type = mysqli_fetch_assoc($types)) {
        $selected = (isset($_GET['type_id']) && $_GET['type_id'] == $type['id']) ? 'selected' : '';
        echo "<option value='{$type['id']}' $selected>{$type['type_name']}</option>";
      }
      ?>
    </select>

    <button type="submit">🔍 Filter</button>
    <a href="payments.php">🔄 Reset</a>
    <a href="export_payments.php" class="btn">📤 Export to CSV</a>
  </form>
  <br>

  <table border="1" cellpadding="10" cellspacing="0">
    <thead>
      <tr>
        <th>ID</th>
        <th>Student ID</th>
        <th>Payment Type</th>
        <th>Amount</th>
        <th>Date</th>
        <th>Description</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $where = [];
      if (!empty($_GET['student_id'])) {
        $sid = mysqli_real_escape_string($conn, $_GET['student_id']);
        $where[] = "student_id = '$sid'";
      }
      if (!empty($_GET['type_id'])) {
        $tid = mysqli_real_escape_string($conn, $_GET['type_id']);
        $where[] = "type_id = '$tid'";
      }
      $where_clause = count($where) ? "WHERE " . implode(" AND ", $where) : "";
      
      $query = "SELECT payments.*, payment_types.type_name FROM payments JOIN payment_types ON payments.type_id = payment_types.id $where_clause ORDER BY payment_date DESC";
      $result = mysqli_query($conn, $query);
      while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
          <td>{$row['id']}</td>
          <td>{$row['student_id']}</td>
          <td>{$row['type_name']}</td>
          <td>{$row['amount']}</td>
          <td>{$row['payment_date']}</td>
          <td>{$row['description']}</td>
          <td>
            <a href='edit_payment.php?id={$row['id']}'>✏️</a>
            <a href='delete_payment.php?id={$row['id']}' onclick=\"return confirm('Delete this payment?');\">🗑️</a>
          </td>
        </tr>";
      }
      ?>
    </tbody>
  </table>
</div>
<?php include("../includes/footer.php"); ?>
