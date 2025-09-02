<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = ''; 
$db   = 'my_camp_portal_db';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>