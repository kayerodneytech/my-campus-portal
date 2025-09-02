<?php
// Include database connection and layout
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Check if ID is passed and fetch user details
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
  $user = mysqli_fetch_assoc($result);
} else {
  echo "<p style='color:red;'>Invalid user ID.</p>";
  exit;
}

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $role = $_POST['role'];

  // Optional password update
  if (!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $updateQuery = "UPDATE users SET name='$name', email='$email', role='$role', password='$password' WHERE id=$id";
  } else {
    $updateQuery = "UPDATE users SET name='$name', email='$email', role='$role' WHERE id=$id";
  }

  if (mysqli_query($conn, $updateQuery)) {
    echo "<p style='color:green;'>User updated successfully!</p>";
    // Refresh data after update
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    $user = mysqli_fetch_assoc($result);
  } else {
    echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
  }
}
?>

<div class="main">
  <h2>Edit User</h2>
  <form method="POST" action="">
    <label>Name:</label><br>
    <input type="text" name="name" value="<?php echo $user['name']; ?>" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?php echo $user['email']; ?>" required><br><br>

    <label>New Password (leave blank to keep current):</label><br>
    <input type="password" name="password"><br><br>

    <label>Role:</label><br>
    <select name="role" required>
      <option value="student" <?php if ($user['role'] == 'student') echo 'selected'; ?>>Student</option>
      <option value="lecturer" <?php if ($user['role'] == 'lecturer') echo 'selected'; ?>>Lecturer</option>
      <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
    </select><br><br>

    <button type="submit">Update User</button>
  </form>
</div>

<?php include("../includes/footer.php"); ?>
