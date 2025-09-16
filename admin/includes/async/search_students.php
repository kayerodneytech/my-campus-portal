<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/auth.php';

// Always return JSON
header('Content-Type: application/json');

// Check if user is admin and logged in
if (!isAdmin() || !isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Get search parameters
$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$position_id = isset($_GET['position']) ? intval($_GET['position']) : 0;
$election_id = isset($_GET['election']) ? intval($_GET['election']) : 0;

// Get position max candidates if position is specified
$position_max = 1;
if ($position_id > 0) {
    $position = $conn->query("SELECT max_candidates FROM election_positions WHERE id = $position_id")->fetch_assoc();
    $position_max = $position ? $position['max_candidates'] : 1;
}

// Build query
$query = "
    SELECT u.id, u.first_name, u.last_name, u.student_id, u.email
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE u.role = 'student'
    AND s.status = 'active'
";

$params = [];
$types = '';

if (!empty($term)) {
    $query .= " AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.student_id LIKE ?
        OR u.email LIKE ?
    )";
    $search_term = "%$term%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= 'ssss';
}

// Exclude students already candidates in this election (if election ID was provided)
if ($election_id > 0) {
    $query .= " AND u.id NOT IN (
        SELECT student_id FROM election_candidates
        WHERE election_id = ?
    )";
    $params[] = $election_id;
    $types .= 'i';
}

// Exclude students already in this position (if position has max 1 candidate)
if ($position_id > 0 && $position_max == 1) {
    if ($election_id > 0) {
        $query .= " AND u.id NOT IN (
            SELECT student_id FROM election_candidates
            WHERE position_id = ? AND election_id = ?
        )";
        $params = array_merge($params, [$position_id, $election_id]);
        $types .= 'ii';
    } else {
        $query .= " AND u.id NOT IN (
            SELECT student_id FROM election_candidates
            WHERE position_id = ?
        )";
        $params[] = $position_id;
        $types .= 'i';
    }
}

$query .= " ORDER BY u.last_name, u.first_name LIMIT 20";

try {
    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
} catch (Exception $e) {
    error_log("Error in search_students.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error searching students: ' . $e->getMessage()
    ]);
}
