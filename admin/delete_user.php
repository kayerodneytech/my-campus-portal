<?php
// Include DB config
include("../includes/config.php");

// Check if user ID is provided
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);

  // Delete user by ID
  $query = "DELETE FROM users WHERE id = $id";
  if (mysqli_query($conn, $query)) {
    // Redirect after deletion
    header("Location: users.php?msg=deleted");
    exit();
  } else {
    echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
  }
} else {
  echo "<p style='color:red;'>Invalid request.</p>";
}
?>
