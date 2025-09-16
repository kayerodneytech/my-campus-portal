<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin and logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get election ID
$election_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$election_id) {
    header('Content-Type: text/html');
    echo '<div class="text-center py-8"><i class="fas fa-exclamation-circle text-red-500 text-2xl mb-4"></i><p class="text-red-500">Invalid election ID</p></div>';
    exit();
}

// Get election details
$election = $conn->query("SELECT * FROM elections WHERE id = $election_id")->fetch_assoc();
if (!$election) {
    header('Content-Type: text/html');
    echo '<div class="text-center py-8"><i class="fas fa-exclamation-circle text-red-500 text-2xl mb-4"></i><p class="text-red-500">Election not found</p></div>';
    exit();
}

// Get positions for this election
$positions = $conn->query("
    SELECT ep.* FROM election_positions ep
    JOIN election_candidates ec ON ep.id = ec.position_id
    WHERE ec.election_id = $election_id
    GROUP BY ep.id
    UNION
    SELECT ep.* FROM election_positions ep
    WHERE ep.id IN (
        SELECT position_id FROM election_candidates
        WHERE election_id = $election_id
    )
    ORDER BY ep.name
")->fetch_all(MYSQLI_ASSOC);

// Get all candidates
$candidates = $conn->query("
    SELECT ec.*, u.first_name, u.last_name, u.email, ep.name as position_name
    FROM election_candidates ec
    JOIN users u ON ec.student_id = u.id
    JOIN election_positions ep ON ec.position_id = ep.id
    WHERE ec.election_id = $election_id
    ORDER BY ep.name, u.last_name, u.first_name
")->fetch_all(MYSQLI_ASSOC);
?>

<h2 class="text-2xl font-semibold text-gray-800 mb-6">
    <?php echo htmlspecialchars($election['title']); ?> Candidates
    <span class="text-sm text-gray-500 font-normal ml-2">
        (<?php echo date('M j, Y', strtotime($election['start_date'])); ?> to
        <?php echo date('M j, Y', strtotime($election['end_date'])); ?>)
    </span>
</h2>

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-blue-800">Election Status</h3>
            <p class="text-gray-600 mt-1">
                <?php echo date('M j, Y g:i A', strtotime($election['start_date'])); ?> to
                <?php echo date('M j, Y g:i A', strtotime($election['end_date'])); ?>
            </p>
        </div>
        <div>
            <span class="px-3 py-1 text-xs font-medium rounded-full
                <?php echo $election['status'] === 'active' ? 'bg-green-100 text-green-800' :
                      ($election['status'] === 'completed' ? 'bg-blue-100 text-blue-800' :
                      ($election['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); ?>">
                <?php echo ucfirst($election['status']); ?>
            </span>
        </div>
    </div>
</div>

<button onclick="openAddCandidateModal(<?php echo $election_id; ?>)"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mb-6">
    <i class="fas fa-plus mr-2"></i> Add Candidate
</button>

<!-- Candidates by Position -->
<?php if (empty($positions)): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-8">
        <i class="fas fa-user-slash text-yellow-400 text-3xl mb-2"></i>
        <p class="text-yellow-800 font-medium">No positions selected for this election</p>
        <p class="text-yellow-600 text-sm mt-2">You need to select positions when creating the election</p>
    </div>
<?php else: ?>
    <?php foreach ($positions as $position): ?>
        <?php
        $position_candidates = array_filter($candidates, function($c) use ($position) {
            return $c['position_id'] == $position['id'];
        });
        ?>
        <div class="mb-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">
                    <?php echo htmlspecialchars($position['name']); ?>
                    <span class="text-sm text-gray-500 font-normal ml-2">
                        (Max <?php echo $position['max_candidates']; ?> candidate<?php echo $position['max_candidates'] > 1 ? 's' : ''; ?>)
                    </span>
                </h3>
                <button onclick="openAddCandidateModal(<?php echo $election_id; ?>, <?php echo $position['id']; ?>)"
                        class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Candidate
                </button>
            </div>

            <?php if (empty($position_candidates)): ?>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <i class="fas fa-user-slash text-gray-300 text-2xl mb-2"></i>
                    <p class="text-gray-500">No candidates for this position</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($position_candidates as $candidate): ?>
                        <div class="candidate-card bg-white rounded-lg p-4">
                            <?php if ($candidate['photo']): ?>
                                <img src="../../uploads/election_candidates/<?php echo htmlspecialchars($candidate['photo']); ?>"
                                     alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>"
                                     class="candidate-photo mb-4 mx-auto block">
                            <?php else: ?>
                                <div class="w-[80px] h-[80px] bg-gray-200 rounded-lg mb-4 mx-auto flex items-center justify-center">
                                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                                </div>
                            <?php endif; ?>

                            <h3 class="font-medium text-gray-800 text-center mb-1">
                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 text-center mb-3">
                                <?php echo htmlspecialchars($candidate['email']); ?>
                            </p>

                            <?php if ($candidate['manifesto']): ?>
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-1">Manifesto:</h4>
                                    <p class="text-sm text-gray-600 line-clamp-3">
                                        <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between items-center mt-4">
                                <span class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($candidate['position_name']); ?>
                                </span>
                                <button onclick="confirmRemoveCandidate(<?php echo $candidate['id']; ?>, '<?php echo htmlspecialchars(addslashes($candidate['first_name'] . ' ' . $candidate['last_name'])); ?>')"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="flex justify-end mt-8 space-x-4">
    <?php if ($election['status'] === 'draft'): ?>
        <a href="../elections.php?action=start&id=<?php echo $election_id; ?>"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-play mr-2"></i> Start Election
        </a>
    <?php endif; ?>

    <?php if ($election['status'] === 'active'): ?>
        <a href="../elections.php?action=end&id=<?php echo $election_id; ?>"
           class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
            <i class="fas fa-stop mr-2"></i> End Election
        </a>
    <?php endif; ?>

    <?php if ($election['status'] === 'completed'): ?>
        <button onclick="window.location.href='../elections.php?action=results&id=<?php echo $election_id; ?>'"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <i class="fas fa-poll mr-2"></i> View Results
        </button>
    <?php endif; ?>

    <button onclick="closeCandidatesModal()"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
        Close
    </button>
</div>
