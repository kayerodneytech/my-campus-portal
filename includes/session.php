<?php
// Session & access control
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/index.php");
    exit();
}

// Optionally check role
function checkRole($role) {
    if ($_SESSION['role'] !== $role) {
        header("Location: /unauthorized.php");
        exit();
    }
}
?>