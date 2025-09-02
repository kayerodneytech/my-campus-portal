<?php
/**
 * Database Initialization Script
 * Adds sample data to the MyCamp Portal database for testing
 * Run this script once to populate the database with sample data
 */

require_once '../includes/config.php';

try {
    // Sample announcements data
    $announcements = [
        ['title' => 'Spring Semester Begins', 'message' => 'Welcome to the Spring 2025 semester! Classes begin on January 15th.', 'audience' => 'all'],
        ['title' => 'Course Registration Deadline', 'message' => 'Last day to register for courses is January 30th. Don\'t miss out!', 'audience' => 'students'],
        ['title' => 'Mid-term Exams', 'message' => 'Mid-term examinations will be held from March 10-14, 2025.', 'audience' => 'all'],
        ['title' => 'Annual Science Fair 2025', 'message' => 'Join us for the most exciting science fair featuring innovative projects from students across all departments.', 'audience' => 'all'],
        ['title' => 'Guest Lecture: AI in Education', 'message' => 'Renowned AI expert Dr. Sarah Johnson will discuss the future of artificial intelligence in educational systems.', 'audience' => 'all'],
        ['title' => 'Career Fair Registration Open', 'message' => 'Register now for the upcoming career fair featuring top employers from various industries.', 'audience' => 'students']
    ];

    // Insert sample announcements
    $stmt = $conn->prepare("INSERT INTO announcements (title, message, posted_by, audience) VALUES (?, ?, 1, ?)");
    foreach ($announcements as $announcement) {
        $stmt->bind_param("sss", $announcement['title'], $announcement['message'], $announcement['audience']);
        $stmt->execute();
    }
    $stmt->close();

    // Sample departments
    $departments = [
        ['name' => 'Computer Science', 'description' => 'Department of Computer Science and Information Technology'],
        ['name' => 'Business', 'description' => 'Department of Business and Management'],
        ['name' => 'Engineering', 'description' => 'Department of Engineering and Technology']
    ];

    $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
    foreach ($departments as $dept) {
        $stmt->bind_param("ss", $dept['name'], $dept['description']);
        $stmt->execute();
    }
    $stmt->close();

    // Sample users (lecturers)
    $lecturers = [
        ['first_name' => 'John', 'last_name' => 'Langa', 'email' => 'john.langa@mycamp.edu', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'lecturer'],
        ['first_name' => 'Anna', 'last_name' => 'Sibanda', 'email' => 'anna.sibanda@mycamp.edu', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'lecturer'],
        ['first_name' => 'Zodwa', 'last_name' => 'Moyo', 'email' => 'zodwa.moyo@mycamp.edu', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'lecturer'],
        ['first_name' => 'Thabo', 'last_name' => 'Ncube', 'email' => 'thabo.ncube@mycamp.edu', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'lecturer'],
        ['first_name' => 'Linda', 'last_name' => 'Moyo', 'email' => 'linda.moyo@mycamp.edu', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'lecturer']
    ];

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    foreach ($lecturers as $lecturer) {
        $stmt->bind_param("sssss", $lecturer['first_name'], $lecturer['last_name'], $lecturer['email'], $lecturer['password'], $lecturer['role']);
        $stmt->execute();
    }
    $stmt->close();

    // Sample lecturer details
    $lecturer_details = [
        ['user_id' => 1, 'department' => 'Computer Science', 'specialization' => 'Web Design'],
        ['user_id' => 2, 'department' => 'Computer Science', 'specialization' => 'Database Design'],
        ['user_id' => 3, 'department' => 'Computer Science', 'specialization' => 'Cyber Security'],
        ['user_id' => 4, 'department' => 'Business', 'specialization' => 'Business Management'],
        ['user_id' => 5, 'department' => 'Business', 'specialization' => 'Accounting']
    ];

    $stmt = $conn->prepare("INSERT INTO lecturers (user_id, department, specialization) VALUES (?, ?, ?)");
    foreach ($lecturer_details as $detail) {
        $stmt->bind_param("iss", $detail['user_id'], $detail['department'], $detail['specialization']);
        $stmt->execute();
    }
    $stmt->close();

    // Sample school clubs
    $clubs = [
        ['name' => 'Student Government', 'description' => 'Representing student interests and organizing campus events'],
        ['name' => 'Computer Science Club', 'description' => 'For students interested in programming and technology'],
        ['name' => 'Business Society', 'description' => 'Networking and professional development for business students'],
        ['name' => 'Sports Club', 'description' => 'Promoting physical fitness and team sports'],
        ['name' => 'Cultural Society', 'description' => 'Celebrating diversity and cultural awareness']
    ];

    $stmt = $conn->prepare("INSERT INTO school_clubs (name, description, status) VALUES (?, ?, 'active')");
    foreach ($clubs as $club) {
        $stmt->bind_param("ss", $club['name'], $club['description']);
        $stmt->execute();
    }
    $stmt->close();

    echo "Sample data inserted successfully!<br>";
    echo "You can now test the portal with sample data.<br>";
    echo "Default lecturer login: john.langa@mycamp.edu / password123<br>";

} catch (Exception $e) {
    echo "Error inserting sample data: " . $e->getMessage();
}

$conn->close();
?>
