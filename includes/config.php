<?php
// Database connection
$host = 'localhost';
$db   = 'chat_portal';
$user = 'root';
$pass = ''; // Use your DB password

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>