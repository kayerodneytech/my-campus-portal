<?php
include_once '../../includes/session.php';
include_once '../../includes/config.php';

$assignment_id = $_GET['id'] ?? 0;

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="assignment_submissions.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student', 'Submitted At', 'Grade', 'Feedback']);

$sql = "
SELECT st.fullname, s.submitted_at, s.grade, s.feedback
FROM submissions s
JOIN students st ON s.student_id = st.id
WHERE s.assignment_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['fullname'],
        $row['submitted_at'],
        $row['grade'],
        $row['feedback']
    ]);
}

fclose($output);
exit;