<?php
// admin/includes/async/search_students.php
session_start();
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isAdmin() || !isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$term = $_GET['term'] ?? '';
$positionId = intval($_GET['position'] ?? 0);
$electionId = intval($_GET['election'] ?? 0);

try {
    $query = "
        SELECT u.id, u.first_name, u.last_name, s.application_number
        FROM users u
        JOIN students s ON u.id = s.user_id
        WHERE u.role = 'student' AND s.status = 'active'
    ";

    $params = [];
    $types = '';

    if (!empty($term)) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.application_number LIKE ?)";
        $searchTerm = "%$term%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
        $types = 'sss';
    }

    // Exclude students who are already candidates for this position in this election
    if ($electionId > 0 && $positionId > 0) {
        $query .= " AND u.id NOT IN (
            SELECT student_id FROM election_candidates
            WHERE election_id = ? AND position_id = ?
        )";
        $params = array_merge($params, [$electionId, $positionId]);
        $types .= 'ii';
    }

    $query .= " ORDER BY u.last_name, u.first_name LIMIT 20";

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
} catch (Exception $e) {
    error_log("Error in search_students.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => "Database error: " . $e->getMessage()
    ]);
}
