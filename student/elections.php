<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Get student details
$studentId = $_SESSION['user_id'];
$studentQuery = $conn->prepare("SELECT s.*, u.first_name, u.last_name, u.email, s.phone
                               FROM students s
                               JOIN users u ON s.user_id = u.id
                               WHERE s.user_id = ?");
$studentQuery->bind_param("i", $studentId);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$student = $studentResult->fetch_assoc();
$studentStatus = $student ? $student['status'] : 'pending';

// Get active elections
$activeElections = $conn->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM election_votes WHERE election_id = e.id AND student_id = $studentId) as has_voted
    FROM elections e
    WHERE e.status = 'active'
    AND NOW() BETWEEN e.start_date AND e.end_date
    ORDER BY e.start_date DESC
")->fetch_all(MYSQLI_ASSOC);

// Get completed elections (for results)
$completedElections = $conn->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM election_votes WHERE election_id = e.id AND student_id = $studentId) as has_voted
    FROM elections e
    WHERE e.status = 'completed'
    ORDER BY e.end_date DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $election_id = intval($_POST['election_id']);
    $votes = $_POST['votes'] ?? [];

    try {
        $conn->begin_transaction();

        // Check if student has already voted
        $checkVote = $conn->prepare("SELECT id FROM election_votes WHERE election_id = ? AND student_id = ?");
        $checkVote->bind_param("ii", $election_id, $studentId);
        $checkVote->execute();
        $checkVote->store_result();

        if ($checkVote->num_rows > 0) {
            $conn->rollback();
            $error = "You have already voted in this election.";
        } else {
            // Record each vote
            foreach ($votes as $position_id => $candidate_id) {
                $stmt = $conn->prepare("INSERT INTO election_votes (election_id, position_id, candidate_id, student_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $election_id, $position_id, $candidate_id, $studentId);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            $success = "Your vote has been recorded successfully!";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error recording your vote: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections - Student Portal</title>

    <!-- Local Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('../assets/fonts/poppins.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Election specific styles */
        .election-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .election-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .candidate-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .candidate-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .candidate-card.selected {
            border-color: #3b82f6;
            background-color: #f0f9ff;
        }

        .candidate-photo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .position-section {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .countdown {
            font-family: monospace;
            font-weight: bold;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <!-- Student Sidebar -->
    <?php include 'sidebar.php' ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:ml-0">
        <!-- Mobile Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 lg:hidden">
            <div class="flex items-center justify-between p-4">
                <button id="mobile-menu-button" class="text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-8 w-8 rounded-full">
                <div class="w-6"></div> <!-- Spacer for balance -->
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-8">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-800 to-blue-600 rounded-xl shadow-md text-white p-6 mb-8">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold mb-2">Student Elections</h1>
                        <p class="text-blue-100">Participate in SRC elections and view results</p>
                    </div>
                    <div class="mt-4 md:mt-0 text-center md:text-right">
                        <div class="text-sm">Your Status</div>
                        <div class="text-xl font-bold"><?php echo ucfirst($studentStatus); ?></div>
                    </div>
                </div>
            </div>

            <?php if ($studentStatus !== 'active'): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-yellow-800">Elections Unavailable</h3>
                            <p class="text-yellow-600">You need to have an active student account to participate in elections.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Active Elections -->
            <?php if ($studentStatus === 'active'): ?>
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Active Elections</h2>
                    </div>

                    <?php if (empty($activeElections)): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                            <i class="fas fa-calendar-check text-blue-400 text-3xl mb-2"></i>
                            <p class="text-blue-800 font-medium">No active elections at the moment</p>
                            <p class="text-blue-600 text-sm mt-2">Check back later for upcoming elections</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeElections as $election): ?>
                            <?php
                            $now = new DateTime();
                            $start = new DateTime($election['start_date']);
                            $end = new DateTime($election['end_date']);
                            $interval = $now->diff($end);
                            $time_remaining = $interval->format('%a days, %h hours, %i minutes');
                            ?>
                            <div class="election-card bg-white rounded-lg p-6 mb-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($election['title']); ?></h3>
                                        <p class="text-gray-600 mt-1">
                                            <?php echo date('M j, Y', strtotime($election['start_date'])); ?> to
                                            <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="status-badge bg-green-100 text-green-800">
                                            Active
                                        </span>
                                        <div class="text-sm text-gray-500 mt-1 countdown">
                                            <?php echo $time_remaining; ?> remaining
                                        </div>
                                    </div>
                                </div>

                                <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($election['description'])); ?></p>

                                <div class="flex justify-between items-center">
                                    <?php if ($election['has_voted']): ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            <i class="fas fa-check-circle mr-1"></i> You've voted
                                        </span>
                                    <?php else: ?>
                                        <a href="elections.php?view=<?php echo $election['id']; ?>#vote"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                            <i class="fas fa-vote-yea mr-2"></i> View Candidates & Vote
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Past Elections (Results) -->
                <div>
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Past Elections</h2>
                    </div>

                    <?php if (empty($completedElections)): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                            <i class="fas fa-history text-gray-400 text-3xl mb-2"></i>
                            <p class="text-gray-500 font-medium">No past elections</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($completedElections as $election): ?>
                            <div class="election-card bg-white rounded-lg p-6 mb-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($election['title']); ?></h3>
                                        <p class="text-gray-600 mt-1">
                                            Completed on <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge bg-blue-100 text-blue-800">Completed</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <a href="elections.php?results=<?php echo $election['id']; ?>"
                                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                        View Results
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Election Details and Voting Section -->
            <?php if (isset($_GET['view']) && $studentStatus === 'active'): ?>
                <?php
                $election_id = intval($_GET['view']);
                $election = $conn->query("SELECT * FROM elections WHERE id = $election_id AND status = 'active'")->fetch_assoc();

                if (!$election) {
                    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8 text-center">
                            <i class="fas fa-exclamation-circle text-red-400 text-3xl mb-2"></i>
                            <p class="text-red-800 font-medium">Election not found or not active</p>
                          </div>';
                } else {
                    // Check if student has already voted
                    $has_voted = $conn->query("SELECT COUNT(*) as count FROM election_votes WHERE election_id = $election_id AND student_id = $studentId")->fetch_assoc()['count'] > 0;

                    // Get positions and candidates
                    $positions = $conn->query("
                        SELECT ep.* FROM election_positions ep
                        JOIN election_candidates ec ON ep.id = ec.position_id
                        WHERE ec.election_id = $election_id
                        GROUP BY ep.id
                        ORDER BY ep.name
                    ")->fetch_all(MYSQLI_ASSOC);

                    // Get all candidates organized by position
                    $candidates_by_position = [];
                    foreach ($positions as $position) {
                        $candidates = $conn->query("
                            SELECT ec.*, u.first_name, u.last_name, u.email
                            FROM election_candidates ec
                            JOIN users u ON ec.student_id = u.id
                            WHERE ec.election_id = $election_id AND ec.position_id = {$position['id']}
                            ORDER BY u.last_name, u.first_name
                        ")->fetch_all(MYSQLI_ASSOC);

                        $candidates_by_position[$position['id']] = [
                            'position' => $position,
                            'candidates' => $candidates
                        ];
                    }
                ?>
                    <div id="vote" class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold text-gray-800">
                                <?php echo htmlspecialchars($election['title']); ?>
                            </h2>
                            <a href="elections.php" class="text-gray-600 hover:text-blue-600">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Elections
                            </a>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-blue-800">Election Period</h3>
                                    <p class="text-gray-600 mt-1">
                                        <?php echo date('M j, Y g:i A', strtotime($election['start_date'])); ?> to
                                        <?php echo date('M j, Y g:i A', strtotime($election['end_date'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($has_voted): ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center mb-8">
                                <i class="fas fa-check-circle text-green-600 text-4xl mb-4"></i>
                                <h3 class="text-green-800 font-medium text-xl mb-2">Thank You for Voting!</h3>
                                <p class="text-gray-600">Your vote in this election has been recorded.</p>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
                                <p class="text-yellow-800 font-medium">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    You can vote for one candidate per position. Your vote is confidential and cannot be changed after submission.
                                </p>
                            </div>

                            <form method="POST" id="voteForm" class="space-y-6">
                                <input type="hidden" name="vote" value="1">
                                <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">

                                <?php foreach ($candidates_by_position as $position_id => $data): ?>
                                    <?php $position = $data['position']; ?>
                                    <div class="position-section">
                                        <h2 class="text-xl font-semibold text-gray-800 mb-4">
                                            <?php echo htmlspecialchars($position['name']); ?>
                                            <span class="text-sm text-gray-500 font-normal ml-2">
                                                (Select 1)
                                            </span>
                                        </h2>

                                        <?php if (empty($data['candidates'])): ?>
                                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                                <p class="text-gray-500">No candidates for this position</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                                <?php foreach ($data['candidates'] as $candidate): ?>
                                                    <div class="candidate-card bg-white rounded-lg overflow-hidden"
                                                        onclick="selectCandidate(<?php echo $position_id; ?>, <?php echo $candidate['id']; ?>)">
                                                        <?php if ($candidate['photo']): ?>
                                                            <img src="../../uploads/election_candidates/<?php echo htmlspecialchars($candidate['photo']); ?>"
                                                                alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>"
                                                                class="candidate-photo">
                                                        <?php else: ?>
                                                            <div class="candidate-photo bg-gray-200 flex items-center justify-center">
                                                                <i class="fas fa-user text-gray-400 text-3xl"></i>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="p-4">
                                                            <h3 class="font-medium text-gray-800 mb-1">
                                                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                            </h3>
                                                            <p class="text-sm text-gray-500 mb-3"><?php echo htmlspecialchars($candidate['email']); ?></p>

                                                            <?php if ($candidate['manifesto']): ?>
                                                                <div class="mb-3">
                                                                    <h4 class="text-sm font-medium text-gray-700 mb-1">Manifesto:</h4>
                                                                    <p class="text-sm text-gray-600 line-clamp-3">
                                                                        <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                                                                    </p>
                                                                </div>
                                                            <?php endif; ?>

                                                            <input type="radio" name="votes[<?php echo $position_id; ?>]"
                                                                value="<?php echo $candidate['id']; ?>"
                                                                id="candidate-<?php echo $candidate['id']; ?>"
                                                                class="hidden">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (!empty($candidates_by_position)): ?>
                                    <div class="flex justify-end mt-8">
                                        <button type="submit"
                                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-lg font-medium">
                                            Cast My Vote
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            <?php endif; ?>

            <!-- Election Results Section -->
            <?php if (isset($_GET['results']) && $studentStatus === 'active'): ?>
                <?php
                $election_id = intval($_GET['results']);
                $election = $conn->query("SELECT * FROM elections WHERE id = $election_id AND status = 'completed'")->fetch_assoc();

                if (!$election) {
                    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8 text-center">
                            <i class="fas fa-exclamation-circle text-red-400 text-3xl mb-2"></i>
                            <p class="text-red-800 font-medium">Election not found or not completed</p>
                          </div>';
                } else {
                    // Get results
                    $results = $conn->query("
                        SELECT
                            er.*,
                            ec.student_id,
                            u.first_name,
                            u.last_name,
                            u.email,
                            ep.name as position_name,
                            ep.max_candidates
                        FROM election_results er
                        JOIN election_candidates ec ON er.candidate_id = ec.id
                        JOIN users u ON ec.student_id = u.id
                        JOIN election_positions ep ON er.position_id = ep.id
                        WHERE er.election_id = $election_id
                        ORDER BY ep.name, er.vote_count DESC
                    ")->fetch_all(MYSQLI_ASSOC);

                    // Group results by position
                    $results_by_position = [];
                    foreach ($results as $result) {
                        $results_by_position[$result['position_id']]['position'] = [
                            'id' => $result['position_id'],
                            'name' => $result['position_name'],
                            'max_candidates' => $result['max_candidates']
                        ];
                        $results_by_position[$result['position_id']]['candidates'][] = $result;
                    }
                ?>
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold text-gray-800">
                                <?php echo htmlspecialchars($election['title']); ?> Results
                            </h2>
                            <a href="elections.php" class="text-gray-600 hover:text-blue-600">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Elections
                            </a>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-blue-800">Election Results</h3>
                                    <p class="text-gray-600 mt-1">
                                        Completed on <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        Completed
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Results by Position -->
                        <?php if (empty($results_by_position)): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                <i class="fas fa-chart-pie text-yellow-400 text-3xl mb-2"></i>
                                <p class="text-yellow-800 font-medium">No results available for this election</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($results_by_position as $position_id => $data): ?>
                                <?php
                                $position = $data['position'];
                                $candidates = $data['candidates'];
                                $total_votes = array_sum(array_column($candidates, 'vote_count'));
                                ?>
                                <div class="position-section mb-10">
                                    <div class="flex justify-between items-center mb-4">
                                        <h2 class="text-xl font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($position['name']); ?>
                                        </h2>
                                        <span class="text-sm text-gray-500">
                                            <?php echo count($candidates); ?> candidate<?php echo count($candidates) > 1 ? 's' : ''; ?>,
                                            <?php echo $total_votes; ?> vote<?php echo $total_votes !== 1 ? 's' : ''; ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                        <div>
                                            <div class="chart-container" style="height: 300px;">
                                                <canvas id="chart-<?php echo $position_id; ?>"
                                                    data-labels='<?php echo json_encode(array_map(function ($c) {
                                                                        return $c['first_name'] . " " . substr($c['last_name'], 0, 1) . ".";
                                                                    }, $candidates)); ?>'
                                                    data-votes='<?php echo json_encode(array_column($candidates, 'vote_count')); ?>'
                                                    data-winners='<?php echo json_encode(array_column(array_filter($candidates, function ($c) {
                                                                        return $c['is_winner'];
                                                                    }), 'candidate_id')); ?>'></canvas>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <?php foreach ($candidates as $candidate): ?>
                                                <div class="bg-white rounded-lg p-4 relative border border-gray-200">
                                                    <?php if ($candidate['is_winner']): ?>
                                                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-green-600 rounded-full flex items-center justify-center text-white text-xs">
                                                            <i class="fas fa-trophy"></i>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="flex items-start">
                                                        <div class="flex-1">
                                                            <h3 class="font-medium text-gray-800 mb-1">
                                                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                            </h3>
                                                            <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($candidate['email']); ?></p>

                                                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-3">
                                                                <div class="bg-blue-600 h-2.5 rounded-full"
                                                                    style="width: <?php echo $total_votes > 0 ? round(($candidate['vote_count'] / $total_votes) * 100) : 0; ?>%"></div>
                                                            </div>

                                                            <div class="flex justify-between items-center">
                                                                <span class="text-sm font-medium text-gray-700">
                                                                    <?php echo $candidate['vote_count']; ?> vote<?php echo $candidate['vote_count'] !== 1 ? 's' : ''; ?>
                                                                    (<?php echo $total_votes > 0 ? round(($candidate['vote_count'] / $total_votes) * 100) : 0; ?>%)
                                                                </span>
                                                                <?php if ($candidate['is_winner']): ?>
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
                    </div>
                <?php } ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <!-- Chart.js for results visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('student-sidebar');
            const mobileOverlay = document.getElementById('mobile-overlay');

            // Toggle mobile menu
            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                mobileOverlay.classList.toggle('hidden');
            });

            // Close mobile menu when clicking overlay
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.add('hidden');
                mobileOverlay.classList.add('hidden');
            });

            // Close mobile menu when clicking a menu item
            document.querySelectorAll('.menu-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        sidebar.classList.add('hidden');
                        mobileOverlay.classList.add('hidden');
                    }
                });
            });

            // Initialize charts for election results
            <?php if (isset($_GET['results'])): ?>
                <?php foreach ($results_by_position as $position_id => $data): ?>
                    const ctx<?php echo $position_id; ?> = document.getElementById('chart-<?php echo $position_id; ?>').getContext('2d');
                    const chartElement = document.getElementById('chart-<?php echo $position_id; ?>');
                    const labels = JSON.parse(chartElement.dataset.labels);
                    const votes = JSON.parse(chartElement.dataset.votes);
                    const winners = JSON.parse(chartElement.dataset.winners);

                    // Create background colors array
                    const backgroundColors = votes.map((vote, index) => {
                        return winners.includes(<?php echo $data['candidates'][0]['candidate_id']; ?>) ? '#10b981' : '#3b82f6';
                    });

                    new Chart(ctx<?php echo $position_id; ?>, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Votes',
                                data: votes,
                                backgroundColor: backgroundColors,
                                borderColor: backgroundColors.map(color => color.replace('0.7', '1')),
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
            <?php endif; ?>
        });

        // Track selected candidates for voting
        const selectedCandidates = {};

        function selectCandidate(positionId, candidateId) {
            // Get all candidate cards for this position
            const cards = document.querySelectorAll(`.position-section:nth-child(${Object.keys(selectedCandidates).length + 1}) .candidate-card`);

            // Remove selection from all cards in this position
            cards.forEach(card => {
                card.classList.remove('selected');
                const radio = card.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            });

            // Select the clicked candidate
            const selectedCard = event.currentTarget;
            selectedCard.classList.add('selected');
            const radio = selectedCard.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            // Store selection
            selectedCandidates[positionId] = candidateId;
        }

        // Form submission validation
        document.getElementById('voteForm')?.addEventListener('submit', function(e) {
            // Check if at least one vote is selected
            const votesSelected = Object.keys(selectedCandidates).length > 0;

            if (!votesSelected) {
                e.preventDefault();
                alert('Please select at least one candidate to vote for.');
                return false;
            }

            // Confirm vote
            if (!confirm('Are you sure you want to submit your vote? You cannot change it later.')) {
                e.preventDefault();
                return false;
            }

            return true;
        });
    </script>
</body>

</html>