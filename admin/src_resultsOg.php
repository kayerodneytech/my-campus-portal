<?php
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="src_results.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Position', 'Candidate', 'Votes']);

    $pos = $conn->query("SELECT * FROM src_positions");
    while ($p = $pos->fetch_assoc()) {
        $stmt = $conn->prepare("
            SELECT c.full_name, COUNT(v.id) AS votes
            FROM src_candidates c
            LEFT JOIN src_votes v ON c.id = v.candidate_id
            WHERE c.position_id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("i", $p['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$p['title'], $r['full_name'], $r['votes']]);
        }
    }
    fclose($out);
    exit;
}
?>

<a href="?export=1" style="color:green;">Export Results as CSV</a>