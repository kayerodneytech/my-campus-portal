<?php
// Database connection
$host = 'localhost';
$db   = 'my_camp_portal_db';
$user = 'root';
$pass = ''; 

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>