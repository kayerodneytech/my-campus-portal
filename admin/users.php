<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");
?>

<div class="main">
  <h2>Manage Users</h2>
  <a href="add_user.php" class="btn">➕ Add New User</a>
  <table border="1" cellpadding="10" cellspacing="0">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $query = "SELECT * FROM users";
      $result = mysqli_query($conn, $query);
      while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
          <td>{$row['id']}</td>
          <td>{$row['name']}</td>
          <td>{$row['email']}</td>
          <td>{$row['role']}</td>
          <td>
            <a href='edit_user.php?id={$row['id']}'>✏️</a>
            <a href='delete_user.php?id={$row['id']}' onclick=\"return confirm('Delete this user?');\">🗑️</a>
          </td>
        </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<?php include("../includes/footer.php"); ?>
