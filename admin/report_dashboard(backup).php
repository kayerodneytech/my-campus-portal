<?php
include("../includes/config.php");
include("../includes/header.php");
include("includes/sidebar.php");

// Handle filtering
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y');

// Count queries
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE YEAR(payment_date) = '$filter_date'"))['total'] ?? 0;
courses_offered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];
$assignments_uploaded = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM assignments"))['count'];
$votes_cast = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM votes"))['count'];
?>

<div class="main">
  <h2>📊 System Report Dashboard</h2>
  <form method="GET" style="margin-bottom:20px;">
    <label>Filter by Year:</label>
    <input type="number" name="filter_date" value="<?php echo $filter_date; ?>" min="2020" max="<?php echo date('Y'); ?>">
    <button type="submit">Apply</button>
    <a href="export_report.php?filter_date=<?php echo $filter_date; ?>" class="btn">📤 Export CSV</a>
    <a href="export_report_pdf.php?filter_date=<?php echo $filter_date; ?>" class="btn">📄 Export PDF</a>
  </form>

  <div class="report-grid">
    <div class="tile">👥 Total Students: <strong><?php echo $total_students; ?></strong></div>
    <div class="tile">💵 Total Payments: <strong>$<?php echo number_format($total_payments, 2); ?></strong></div>
    <div class="tile">📚 Courses Offered: <strong><?php echo $courses_offered; ?></strong></div>
    <div class="tile">📝 Assignments Uploaded: <strong><?php echo $assignments_uploaded; ?></strong></div>
    <div class="tile">🗳️ Votes Cast: <strong><?php echo $votes_cast; ?></strong></div>
  </div>
</div>

<?php include("../includes/footer.php"); ?>

<!-- CSS Suggestion for tile layout -->
<style>
  .report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 20px;
  }
  .tile {
    background: #1e1e2f;
    color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    font-size: 18px;
  }
</style>

-- export_report.php (CSV Export)
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

-- export_report_pdf.php (PDF Export)
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
