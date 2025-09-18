<?php
// admin/includes/async/handle_election.php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/auth.php';
require_once '../../utils/activity_logger.php';

header('Content-Type: application/json');

if (!isAdmin() || !isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax_request']) && $_POST['ajax_request'] === 'create_election') {
        handleCreateElection();
    } elseif (isset($_POST['ajax_request']) && $_POST['ajax_request'] === 'add_single_candidate') {
        handleAddSingleCandidate();
    }
}

function handleCreateElection()
{
    global $conn;

    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $selected_positions = $_POST['positions'] ?? [];
        $candidates = json_decode($_POST['candidates'], true) ?? [];

        // Validate inputs
        $errors = [];
        if (empty($title)) $errors[] = "Title is required";
        if (empty($start_date) || empty($end_date)) $errors[] = "Start and end dates are required";
        if (strtotime($start_date) >= strtotime($end_date)) $errors[] = "End date must be after start date";
        if (empty($selected_positions)) $errors[] = "At least one position must be selected";

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }

        $conn->begin_transaction();

        // Create election
        $stmt = $conn->prepare("INSERT INTO elections (title, description, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $description, $start_date, $end_date, $_SESSION['user_id']);
        $stmt->execute();
        $election_id = $conn->insert_id;
        $stmt->close();

        // Add candidates
        foreach ($candidates as $candidate) {
            $position_id = intval($candidate['position_id']);
            $student_id = intval($candidate['student_id']);
            $manifesto = trim($candidate['manifesto']);

            // Check if student is already a candidate for this position
            $check = $conn->prepare("SELECT id FROM election_candidates WHERE election_id = ? AND position_id = ? AND student_id = ?");
            $check->bind_param("iii", $election_id, $position_id, $student_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                // Add candidate
                $stmt = $conn->prepare("INSERT INTO election_candidates (election_id, position_id, student_id, manifesto) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $election_id, $position_id, $student_id, $manifesto);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Log activity
        logActivity('election_create', "Created election: $title", "Election ID: $election_id");

        $conn->commit();

        echo json_encode([
            'success' => true,
            'election_id' => $election_id,
            'message' => 'Election created successfully'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => "Database error: " . $e->getMessage()
        ]);
    }
}
function handleAddSingleCandidate()
{
    global $conn;

    try {
        $election_id = intval($_POST['election_id']);
        $position_id = intval($_POST['position_id']);
        $student_id = intval($_POST['student_id']);
        $manifesto = trim($_POST['manifesto']);

        // Validate inputs
        $errors = [];
        if (empty($election_id)) $errors[] = "Election ID is required";
        if (empty($position_id)) $errors[] = "Position ID is required";
        if (empty($student_id)) $errors[] = "Student ID is required";

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }

        // Check if student is already a candidate for this position
        $check = $conn->prepare("SELECT id FROM election_candidates WHERE election_id = ? AND position_id = ? AND student_id = ?");
        $check->bind_param("iii", $election_id, $position_id, $student_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'This student is already a candidate for this position']);
            exit();
        }

        // REMOVED: The max candidates check that was incorrectly limiting candidates
        // The max_candidates field should only be used when determining winners, not when adding candidates

        // Add candidate
        $stmt = $conn->prepare("INSERT INTO election_candidates (election_id, position_id, student_id, manifesto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $election_id, $position_id, $student_id, $manifesto);
        $stmt->execute();
        $stmt->close();

        // Log activity
        $electionTitle = $conn->query("SELECT title FROM elections WHERE id = $election_id")->fetch_assoc()['title'];
        logActivity('election_add_candidate', "Added candidate to election: $electionTitle", "Election ID: $election_id, Position ID: $position_id, Student ID: $student_id");

        echo json_encode([
            'success' => true,
            'message' => 'Candidate added successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => "Database error: " . $e->getMessage()
        ]);
    }
}
