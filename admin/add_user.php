<?php
// Include database connection
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password hash
  $role = $_POST['role'];

  // Insert user into the database
  $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
  if (mysqli_query($conn, $query)) {
    echo "<p style='color:green;'>User added successfully!</p>";
  } else {
    echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
  }
}
?>

<div class="main">
  <h2>Add New User</h2>
  <form method="POST" action="">
    <label>Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Role:</label><br>
    <select name="role" required>
      <option value="student">Student</option>
      <option value="lecturer">Lecturer</option>
      <option value="admin">Admin</option>
    </select><br><br>

    <button type="submit">Add User</button>
  </form>
</div>

<?php include("../includes/footer.php"); ?>
