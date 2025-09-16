<?php
// Enhanced authentication functions

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && 
           ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'super_admin');
}

function isLecturer() {
    return isLoggedIn() && isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'lecturer' && 
           isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'active';
}

function isStudent() {
    return isLoggedIn() && isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'student' && 
           isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'active';
}

function isActiveUser() {
    return isLoggedIn() && isset($_SESSION['user_status']) && 
           $_SESSION['user_status'] === 'active';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireLecturer() {
    requireLogin();
    if (!isLecturer()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireActiveUser() {
    requireLogin();
    if (!isActiveUser()) {
        header('Location: ../index.php?message=account_inactive');
        exit();
    }
}

// Get user display name based on role
function getUserDisplayName() {
    if (!isLoggedIn()) return 'Guest';
    
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'lecturer') {
        // For lecturers, you might want to include their title
        return $_SESSION['user_name']; // You can enhance this later
    }
    
    return $_SESSION['user_name'];
}

// Get user identifier (application number for students, employee ID for lecturers)
function getUserIdentifier() {
    return $_SESSION['user_identifier'] ?? null;
}
?>