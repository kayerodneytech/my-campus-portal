<?php
include("../includes/config.php");

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="payments_export.csv"');

$output = fopen("php://output", "w");
fputcsv($output, ['ID', 'Student ID', 'Payment Type', 'Amount', 'Date', 'Description']);

$query = "SELECT payments.*, payment_types.type_name FROM payments JOIN payment_types ON payments.type_id = payment_types.id ORDER BY payment_date DESC";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
  fputcsv($output, [
    $row['id'],
    $row['student_id'],
    $row['type_name'],
    $row['amount'],
    $row['payment_date'],
    $row['description']
  ]);
}

fclose($output);
exit;
?>
