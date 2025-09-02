<?php
include '../includes/config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $date = $_POST['event_date'];

    $stmt = $conn->prepare("INSERT INTO admin_events (title, description, event_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $desc, $date);
    $stmt->execute();

    // Notify all students
    $students = $conn->query("SELECT id FROM students");
    while ($s = $students->fetch_assoc()) {
        $msg = "New Event: $title on $date";
        $n = $conn->prepare("INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)");
        $n->bind_param("iss", $s['id'], $title, $msg);
        $n->execute();
    }

    echo "Event added and students notified.";
}
?>