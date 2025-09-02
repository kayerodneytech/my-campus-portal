<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MyCamp Portal</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 0;
        }
        header {
            background: #004080;
            color: white;
            padding: 15px 30px;
        }
        nav a {
            color: white;
            margin-right: 20px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <img src="/chat_system/1753449127073.jpg" alt="MyCamp Portal Logo" style="max-width: 60px; vertical-align: middle; margin-right: 15px;">
        <h2 style="display: inline-block; vertical-align: middle; margin: 0;">MyCamp Portal</h2>
        <nav>
            <a href="/dashboard/student/index.php">Home</a>
            <a href="/dashboard/student/courses.php">Courses</a>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>
    <main style="padding: 20px;">