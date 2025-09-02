<?php
include '../includes/config.php';

// Export logic
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="src_results.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Position', 'Candidate', 'Votes']);

    $positions = $conn->query("SELECT * FROM src_positions");
    while ($position = $positions->fetch_assoc()) {
        $stmt = $conn->prepare("
            SELECT c.full_name, COUNT(v.id) AS votes
            FROM src_candidates c
            LEFT JOIN src_votes v ON c.id = v.candidate_id
            WHERE c.position_id = ?
            GROUP BY c.id
            ORDER BY votes DESC
        ");
        $stmt->bind_param("i", $position['id']);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$position['title'], $r['full_name'], $r['votes']]);
        }
    }

    fclose($out);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SRC Voting Results</title>
    <style>
        table {
            width: 80%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #222;
            color: white;
        }
        h3 {
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <h2>SRC Voting Results</h2>
    <a href="?export=1" style="color: green;">📥 Export Results to CSV</a>

    <?php
    $positions = $conn->query("SELECT * FROM src_positions");

    while ($position = $positions->fetch_assoc()):
    ?>
        <h3><?= htmlspecialchars($position['title']) ?></h3>
        <table>
            <tr>
                <th>Candidate</th>
                <th>Total Votes</th>
            </tr>

            <?php
            $stmt = $conn->prepare("
                SELECT c.full_name, COUNT(v.id) AS votes
                FROM src_candidates c
                LEFT JOIN src_votes v ON c.id = v.candidate_id
                WHERE c.position_id = ?
                GROUP BY c.id
                ORDER BY votes DESC
            ");
            $stmt->bind_param("i", $position['id']);
            $stmt->execute();
            $results = $stmt->get_result();

            while ($row = $results->fetch_assoc()):
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['votes'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endwhile; ?>

</body>
</html>