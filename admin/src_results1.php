<?php
include '../includes/config.php';

// Get all SRC positions
$positions = $conn->query("SELECT * FROM src_positions");
?>

<h2>SRC Voting Results</h2>

<?php while ($position = $positions->fetch_assoc()): ?>
    <h3><?= htmlspecialchars($position['title']) ?></h3>
    <table border="1" cellpadding="5">
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
    </table><br>
<?php endwhile; ?>

<!-- CSV Export Link -->
<a href="?export=1" style="color: green;">Export Results to CSV</a>