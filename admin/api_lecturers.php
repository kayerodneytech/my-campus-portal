<?php
// admin/api_lecturers.php
include("../includes/config.php");
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $status = 'active';
        if ($name && $email) {
            $stmt = $conn->prepare("INSERT INTO lecturers (full_name, email, status) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $name, $email, $status);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing fields']);
        }
        break;
    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        if ($id && $name && $email) {
            $stmt = $conn->prepare("UPDATE lecturers SET full_name=?, email=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $email, $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing fields']);
        }
        break;
    case 'remove':
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM lecturers WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing id']);
        }
        break;
    case 'toggle_lock':
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $result = $conn->query("SELECT status FROM lecturers WHERE id=$id");
            $row = $result->fetch_assoc();
            $new_status = ($row['status'] == 'locked') ? 'active' : 'locked';
            $stmt = $conn->prepare("UPDATE lecturers SET status=? WHERE id=?");
            $stmt->bind_param('si', $new_status, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing id']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
