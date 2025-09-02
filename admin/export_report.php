<?php
include("../includes/config.php");
$filter_date = $_GET['filter_date'] ?? date('Y');

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="system_report_'.$filter_date.'.csv"');

$output = fopen("php://output", "w");
fputcsv($output, ['Metric', 'Value']);

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE YEAR(payment_date) = '$filter_date'"))['total'] ?? 0;
courses_offered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];
$assignments_uploaded = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM assignments"))['count'];
$votes_cast = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM votes"))['count'];

fputcsv($output, ['Total Students', $total_students]);
fputcsv($output, ['Total Payments', $total_payments]);
fputcsv($output, ['Courses Offered', $courses_offered]);
fputcsv($output, ['Assignments Uploaded', $assignments_uploaded]);
fputcsv($output, ['Votes Cast', $votes_cast]);

fclose($output);
exit;
?>