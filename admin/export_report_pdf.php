<?php
require('../fpdf/fpdf.php');
include("../includes/config.php");
$filter_date = $_GET['filter_date'] ?? date('Y');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'System Report ('.$filter_date.')',0,1,'C');
$pdf->Ln();
$pdf->SetFont('Arial','',12);

function writeLine($pdf, $label, $value) {
  $pdf->Cell(80,10,$label,1);
  $pdf->Cell(100,10,$value,1);
  $pdf->Ln();
}

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE YEAR(payment_date) = '$filter_date'"))['total'] ?? 0;
courses_offered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];
$assignments_uploaded = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM assignments"))['count'];
$votes_cast = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM votes"))['count'];

writeLine($pdf, 'Total Students', $total_students);
writeLine($pdf, 'Total Payments', '$'.number_format($total_payments,2));
writeLine($pdf, 'Courses Offered', $courses_offered);
writeLine($pdf, 'Assignments Uploaded', $assignments_uploaded);
writeLine($pdf, 'Votes Cast', $votes_cast);

$pdf->Output();
?>