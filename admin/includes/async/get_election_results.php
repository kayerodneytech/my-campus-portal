<?php
// admin/includes/async/get_election_results.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isAdmin() || !isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$electionId = intval($_GET['id'] ?? 0);

// Get election details
$election = $conn->query("SELECT * FROM elections WHERE id = $electionId")->fetch_assoc();
if (!$election) {
    echo '<div class="text-center py-8"><p class="text-red-500">Election not found</p></div>';
    exit();
}

function processElectionResults($electionId)
{
    global $conn;

    try {
        $conn->begin_transaction();

        // Clear any existing results for this election
        $conn->query("DELETE FROM election_results WHERE election_id = $electionId");

        // Get all positions for this election
        $positions = $conn->query("
            SELECT ep.id, ep.name, ep.max_candidates
            FROM election_positions ep
            JOIN election_candidates ec ON ep.id = ec.position_id
            WHERE ec.election_id = $electionId
            GROUP BY ep.id
        ")->fetch_all(MYSQLI_ASSOC);

        foreach ($positions as $position) {
            $positionId = $position['id'];
            $maxWinners = $position['max_candidates'];

            // Get vote counts for each candidate in this position
            $candidates = $conn->query("
                SELECT ec.id as candidate_id, ec.student_id, COUNT(ev.id) as vote_count
                FROM election_candidates ec
                LEFT JOIN election_votes ev ON ec.id = ev.candidate_id
                WHERE ec.election_id = $electionId AND ec.position_id = $positionId
                GROUP BY ec.id
                ORDER BY vote_count DESC
            ")->fetch_all(MYSQLI_ASSOC);

            // Determine winners (top maxWinners candidates)
            $winners = array_slice($candidates, 0, $maxWinners);
            $winnerIds = array_column($winners, 'candidate_id');

            // Insert results for each candidate
            foreach ($candidates as $candidate) {
                $isWinner = in_array($candidate['candidate_id'], $winnerIds);

                $stmt = $conn->prepare("
                    INSERT INTO election_results
                    (election_id, position_id, candidate_id, vote_count, is_winner)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "iiiii",
                    $electionId,
                    $positionId,
                    $candidate['candidate_id'],
                    $candidate['vote_count'],
                    $isWinner
                );
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error processing election results: " . $e->getMessage());
        return false;
    }
}


if (isset($_GET['action']) && isset($_GET['id'])) {
    handleElectionStatusUpdate();
}

function handleElectionStatusUpdate()
{
    global $conn;
    $election_id = intval($_GET['id']);
    $action = $_GET['action'];

    try {
        if ($action === 'start' && $conn->query("SELECT status FROM elections WHERE id = $election_id")->fetch_assoc()['status'] === 'draft') {
            $conn->query("UPDATE elections SET status = 'active' WHERE id = $election_id");
            logActivity('election_start', "Started election ID: $election_id");

            // Send notification to all students
            $election = $conn->query("SELECT title FROM elections WHERE id = $election_id")->fetch_assoc();
            $conn->query("
                INSERT INTO notifications (user_id, title, message, type, reference_id)
                SELECT id, 'New Election Started', 'A new election \"{$election['title']}\" has started. Cast your vote now!', 'election', $election_id
                FROM users
                WHERE role = 'student'
            ");
            header("Location: elections.php?success=" . urlencode("Election started successfully"));
            exit();
        }

        if ($action === 'end' && $conn->query("SELECT status FROM elections WHERE id = $election_id")->fetch_assoc()['status'] === 'active') {
            $conn->query("UPDATE elections SET status = 'completed' WHERE id = $election_id");
            logActivity('election_end', "Ended election ID: $election_id");

            // Process results when election ends
            require_once 'includes/async/get_election_results.php';
            processElectionResults($election_id);

            header("Location: elections.php?success=" . urlencode("Election ended successfully"));
            exit();
        }

        if ($action === 'cancel') {
            $conn->query("UPDATE elections SET status = 'cancelled' WHERE id = $election_id");
            logActivity('election_cancel', "Cancelled election ID: $election_id");
            header("Location: elections.php?success=" . urlencode("Election cancelled successfully"));
            exit();
        }
    } catch (Exception $e) {
        header("Location: elections.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}
// Check if results have been processed
$resultsProcessed = $conn->query("SELECT COUNT(*) as count FROM election_results WHERE election_id = $electionId")->fetch_assoc()['count'] > 0;

if (!$resultsProcessed) {
    // Process results if they haven't been processed yet
    processElectionResults($electionId);
}

// Get results by position
$positions = $conn->query("
    SELECT ep.id, ep.name, ep.max_candidates
    FROM election_positions ep
    JOIN election_candidates ec ON ep.id = ec.position_id
    WHERE ec.election_id = $electionId
    GROUP BY ep.id
    ORDER BY ep.name
")->fetch_all(MYSQLI_ASSOC);

// Get total votes for this election
$totalVotes = $conn->query("SELECT COUNT(*) as count FROM election_votes WHERE election_id = $electionId")->fetch_assoc()['count'];
?>
<div class="flex flex-col md:flex-row justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">
        <?php echo htmlspecialchars($election['title']); ?> Results
    </h2>
    <div class="text-sm text-gray-600">
        <span class="mr-4"><i class="fas fa-users mr-1"></i> <?php echo $totalVotes; ?> votes cast</span>
        <span><i class="fas fa-calendar mr-1"></i> Completed on <?php echo date('M j, Y', strtotime($election['end_date'])); ?></span>
    </div>
</div>

<?php if (empty($positions)): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <i class="fas fa-chart-pie text-yellow-400 text-3xl mb-2"></i>
        <p class="text-yellow-800 font-medium">No results available for this election</p>
    </div>
<?php else: ?>
    <?php foreach ($positions as $position): ?>
        <?php
        $positionId = $position['id'];
        $maxWinners = $position['max_candidates'];

        // Get results for this position
        $results = $conn->query("
            SELECT er.*, ec.student_id, u.first_name, u.last_name, u.email,
                   (SELECT COUNT(*) FROM election_votes WHERE candidate_id = er.candidate_id) as vote_count
            FROM election_results er
            JOIN election_candidates ec ON er.candidate_id = ec.id
            JOIN users u ON ec.student_id = u.id
            WHERE er.election_id = $electionId AND er.position_id = $positionId
            ORDER BY er.vote_count DESC
        ")->fetch_all(MYSQLI_ASSOC);

        // If no results in election_results table, get from votes directly
        if (empty($results)) {
            $results = $conn->query("
                SELECT ec.id as candidate_id, ec.student_id, u.first_name, u.last_name, u.email,
                       COUNT(ev.id) as vote_count, 0 as is_winner
                FROM election_candidates ec
                JOIN users u ON ec.student_id = u.id
                LEFT JOIN election_votes ev ON ec.id = ev.candidate_id
                WHERE ec.election_id = $electionId AND ec.position_id = $positionId
                GROUP BY ec.id
                ORDER BY vote_count DESC
            ")->fetch_all(MYSQLI_ASSOC);
        }

        // Calculate total votes for this position
        $positionTotalVotes = array_sum(array_column($results, 'vote_count'));

        // Determine winners (top maxWinners candidates)
        $winners = array_slice($results, 0, $maxWinners);
        ?>
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    <?php echo htmlspecialchars($position['name']); ?>
                    <span class="text-sm text-gray-500 ml-2">(<?php echo $maxWinners; ?> winner<?php echo $maxWinners !== 1 ? 's' : ''; ?>)</span>
                </h3>
                <span class="text-sm text-gray-500">
                    <?php echo count($results); ?> candidate<?php echo count($results) !== 1 ? 's' : ''; ?>,
                    <?php echo $positionTotalVotes; ?> vote<?php echo $positionTotalVotes !== 1 ? 's' : ''; ?>
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="chart-<?php echo $positionId; ?>"
                            data-labels='<?php echo json_encode(array_map(function ($r) {
                                                return $r['first_name'] . " " . substr($r['last_name'], 0, 1) . ".";
                                            }, $results)); ?>'
                            data-votes='<?php echo json_encode(array_column($results, 'vote_count')); ?>'
                            data-winners='<?php echo json_encode(array_column($winners, 'candidate_id')); ?>'></canvas>
                    </div>
                </div>
                <div class="space-y-4">
                    <?php foreach ($results as $result): ?>
                        <div class="bg-white rounded-lg p-4 relative border border-gray-200">
                            <?php if ($result['is_winner'] || in_array($result['candidate_id'], array_column($winners, 'candidate_id'))): ?>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-green-600 rounded-full flex items-center justify-center text-white text-xs">
                                    <i class="fas fa-trophy"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-800 mb-1">
                                        <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($result['email']); ?></p>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-3">
                                        <div class="bg-blue-600 h-2.5 rounded-full"
                                            style="width: <?php echo $positionTotalVotes > 0 ? round(($result['vote_count'] / $positionTotalVotes) * 100) : 0; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">
                                            <?php echo $result['vote_count']; ?> vote<?php echo $result['vote_count'] !== 1 ? 's' : ''; ?>
                                            (<?php echo $positionTotalVotes > 0 ? round(($result['vote_count'] / $positionTotalVotes) * 100) : 0; ?>%)
                                        </span>
                                        <?php if ($result['is_winner'] || in_array($result['candidate_id'], array_column($winners, 'candidate_id'))): ?>
                                            <span class="text-sm font-medium text-green-600">
                                                Winner
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Initialize charts for each position
    <?php foreach ($positions as $position): ?>
        const ctx<?php echo $position['id']; ?> = document.getElementById('chart-<?php echo $position['id']; ?>').getContext('2d');
        const chartElement = document.getElementById('chart-<?php echo $position['id']; ?>');
        const labels = JSON.parse(chartElement.dataset.labels);
        const votes = JSON.parse(chartElement.dataset.votes);
        const winners = JSON.parse(chartElement.dataset.winners);

        // Create background colors array
        const backgroundColors = votes.map((vote, index) => {
            return winners.includes(<?php echo $results[0]['candidate_id']; ?>) ? '#10b981' : '#3b82f6';
        });

        new Chart(ctx<?php echo $position['id']; ?>, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Votes',
                    data: votes,
                    backgroundColor: votes.map((vote, index) =>
                        winners.includes(parseInt(chartElement.dataset.candidateIds.split(',')[index])) ? '#10b981' : '#3b82f6'
                    ),
                    borderColor: votes.map((vote, index) =>
                        winners.includes(parseInt(chartElement.dataset.candidateIds.split(',')[index])) ? '#059669' : '#2563eb'
                    ),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                return context.raw + ' votes (' + percentage + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Votes'
                        }
                    }
                }
            }
        });
    <?php endforeach; ?>
</script>
<script>

</script>