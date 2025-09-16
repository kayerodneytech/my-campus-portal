<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is admin and logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get all elections with status counts
$elections = $conn->query("
    SELECT
        e.*,
        (SELECT COUNT(*) FROM election_candidates ec WHERE ec.election_id = e.id) as candidate_count,
        (SELECT COUNT(*) FROM election_votes ev WHERE ev.election_id = e.id) as vote_count
    FROM elections e
    ORDER BY
        CASE
            WHEN e.status = 'active' THEN 1
            WHEN e.status = 'draft' THEN 2
            WHEN e.status = 'completed' THEN 3
            ELSE 4
        END,
        e.start_date DESC
")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM elections")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'active'")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'completed'")->fetch_assoc()['count'],
    'upcoming' => $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'draft' AND start_date > NOW()")->fetch_assoc()['count']
];

// Get all positions for modal forms
$positions = $conn->query("SELECT * FROM election_positions ORDER BY name")->fetch_all(MYSQLI_ASSOC);


// Handle AJAX requests for election creation
if (isset($_POST['ajax_request']) && $_POST['ajax_request'] === 'create_election') {
    header('Content-Type: application/json');

    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $selected_positions = $_POST['positions'] ?? [];

        // Validate inputs
        $errors = [];
        if (empty($title)) $errors[] = "Title is required";
        if (empty($start_date) || empty($end_date)) $errors[] = "Start and end dates are required";
        if (strtotime($start_date) >= strtotime($end_date)) $errors[] = "End date must be after start date";
        if (empty($selected_positions)) $errors[] = "At least one position must be selected";

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit();
        }

        $conn->begin_transaction();

        // Create election
        $stmt = $conn->prepare("INSERT INTO elections (title, description, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $description, $start_date, $end_date, $_SESSION['user_id']);
        $stmt->execute();
        $election_id = $conn->insert_id;
        $stmt->close();

        // Log activity
        logActivity('election_create', "Created election: $title", "Election ID: $election_id");

        $conn->commit();

        echo json_encode([
            'success' => true,
            'election_id' => $election_id
        ]);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => "Database error: " . $e->getMessage()
        ]);
        exit();
    }
}

// Handle AJAX requests for adding candidates
if (isset($_POST['ajax_request']) && $_POST['ajax_request'] === 'add_candidates') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $election_id = intval($input['election_id']);
        $candidates = $input['candidates'] ?? [];

        $conn->begin_transaction();

        foreach ($candidates as $candidate) {
            $position_id = intval($candidate['position_id']);
            $student_id = intval($candidate['student_id']);
            $manifesto = trim($candidate['manifesto']);

            // Check if student is already a candidate for this position
            $check = $conn->prepare("SELECT id FROM election_candidates WHERE election_id = ? AND position_id = ? AND student_id = ?");
            $check->bind_param("iii", $election_id, $position_id, $student_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                // Add candidate
                $stmt = $conn->prepare("INSERT INTO election_candidates (election_id, position_id, student_id, manifesto) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $election_id, $position_id, $student_id, $manifesto);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Candidates added successfully'
        ]);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => "Database error: " . $e->getMessage()
        ]);
        exit();
    }
}


// Handle AJAX requests for adding candidates
if (isset($_POST['ajax_request']) && $_POST['ajax_request'] === 'add_candidates') {
    console_log("AJAX request received for adding candidates");

    $election_id = intval($_POST['election_id']);
    $candidates = json_decode($_POST['candidates'], true);

    try {
        $conn->begin_transaction();
        console_log("Starting transaction for adding candidates to election $election_id");

        foreach ($candidates as $candidate) {
            $position_id = intval($candidate['position_id']);
            $student_id = intval($candidate['student_id']);
            $manifesto = trim($candidate['manifesto']);

            // Check if student is already a candidate for this position
            $check = $conn->prepare("SELECT id FROM election_candidates WHERE election_id = ? AND position_id = ? AND student_id = ?");
            $check->bind_param("iii", $election_id, $position_id, $student_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                // Add candidate
                $stmt = $conn->prepare("INSERT INTO election_candidates (election_id, position_id, student_id, manifesto) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $election_id, $position_id, $student_id, $manifesto);
                $stmt->execute();
                $stmt->close();
                console_log("Added candidate: student $student_id for position $position_id");
            } else {
                console_log("Skipping duplicate candidate: student $student_id for position $position_id");
            }
        }

        $conn->commit();
        console_log("Transaction committed. Candidates added successfully");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Candidates added successfully'
        ]);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        console_log("Error adding candidates: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => "Database error: " . $e->getMessage()
        ]);
        exit();
    }
}

// Handle election status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
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

// Helper function for console logging
function console_log($data)
{
    if (is_array($data) || is_object($data)) {
        error_log(print_r($data, true));
    } else {
        error_log($data);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections Management - Admin Portal</title>

    <!-- Local Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Flatpickr for date/time picking -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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

        /* Modal styles */
        .modal {
            transition: all 0.3s ease;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Step indicator styles */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
        }

        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            right: -50%;
            height: 2px;
            background-color: #e5e7eb;
            z-index: 1;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e5e7eb;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .step.active .step-number {
            background-color: #3b82f6;
            color: white;
        }

        .step.completed .step-number {
            background-color: #10b981;
            color: white;
        }

        .step-title {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .step.active .step-title,
        .step.completed .step-title {
            color: #1f2937;
            font-weight: 500;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        /* Candidate card styles */
        .candidate-card {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
        }

        .candidate-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Status badge styles */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Election card styles */
        .election-card {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
        }

        .election-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Empty state styles */
        .empty-state {
            background-color: #f8fafc;
            border: 1px dashed #e2e8f0;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <?php include 'components/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Elections Management</h1>
                    <p class="text-gray-600">Create and manage student elections</p>
                </div>
                <button onclick="openCreateElectionModal()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Create New Election
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-vote-yea text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Elections</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-play-circle text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Active Elections</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['active']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Completed Elections</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['completed']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-alt text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Upcoming Elections</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['upcoming']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Elections List -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Votes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($elections)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    <div class="py-8">
                                        <i class="fas fa-vote-yea text-gray-300 text-4xl mb-4 block"></i>
                                        <p class="font-medium">No elections created yet</p>
                                        <button onclick="openCreateElectionModal()"
                                            class="text-blue-600 hover:text-blue-800 mt-2 block">
                                            Create your first election →
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($elections as $election): ?>
                                <?php
                                $now = new DateTime();
                                $start = new DateTime($election['start_date']);
                                $end = new DateTime($election['end_date']);

                                // Calculate time remaining for active elections
                                $time_remaining = '';
                                if ($election['status'] === 'active' && $now < $end) {
                                    $interval = $now->diff($end);
                                    $time_remaining = $interval->format('%a days, %h hours');
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($election['title']); ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('M j, Y', strtotime($election['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('M j', strtotime($election['start_date'])); ?> -
                                            <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                                        </div>
                                        <?php if ($election['status'] === 'active' && $now < $end): ?>
                                            <div class="text-xs text-gray-500 countdown">
                                                <?php echo $time_remaining; ?> remaining
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">
                                            <?php echo $election['candidate_count']; ?> candidate<?php echo $election['candidate_count'] !== 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">
                                            <?php echo $election['vote_count']; ?> vote<?php echo $election['vote_count'] !== 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge
                                            <?php echo $election['status'] === 'active' ? 'bg-green-100 text-green-800' : ($election['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : ($election['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); ?>">
                                            <?php echo ucfirst($election['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="openCandidatesModal(<?php echo $election['id']; ?>)"
                                                class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-users"></i> Candidates
                                            </button>

                                            <?php if ($election['status'] === 'draft' && $now >= $start): ?>
                                                <a href="?action=start&id=<?php echo $election['id']; ?>"
                                                    class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-play"></i> Start
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($election['status'] === 'active'): ?>
                                                <a href="?action=end&id=<?php echo $election['id']; ?>"
                                                    class="text-orange-600 hover:text-orange-900">
                                                    <i class="fas fa-stop"></i> End
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($election['status'] === 'completed'): ?>
                                                <button onclick="openResultsModal(<?php echo $election['id']; ?>)"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-poll"></i> Results
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($election['status'] !== 'active'): ?>
                                                <a href="?action=cancel&id=<?php echo $election['id']; ?>"
                                                    class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to cancel this election?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Election Modal-->
    <div id="createElectionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Create New Election</h3>
                    <button onclick="closeCreateElectionModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-title">Election Details</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-title">Add Candidates</div>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6">
                    <!-- Step 1: Election Details -->
                    <div class="step-content active" data-step="1">
                        <form id="electionForm" class="space-y-6" onsubmit="return false;">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Election Title *</label>
                                <input type="text" name="title" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., 2025 SRC Elections">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Describe the purpose of this election..."></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date & Time *</label>
                                    <input type="text" name="start_date" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg flatpickr"
                                        placeholder="Select date and time">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date & Time *</label>
                                    <input type="text" name="end_date" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg flatpickr"
                                        placeholder="Select date and time">
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Positions to Elect *</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <?php foreach ($positions as $position): ?>
                                        <div class="flex items-center">
                                            <input type="checkbox" name="positions[]" value="<?php echo $position['id']; ?>"
                                                id="position-<?php echo $position['id']; ?>"
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="position-<?php echo $position['id']; ?>"
                                                class="ml-2 block text-sm text-gray-700">
                                                <?php echo htmlspecialchars($position['name']); ?>
                                                <span class="text-xs text-gray-500">(Max <?php echo $position['max_candidates']; ?>)</span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="closeCreateElectionModal()"
                                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                                    Cancel
                                </button>
                                <button type="button" onclick="submitElectionForm()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    <span id="nextButtonText">Next: Add Candidates</span>
                                    <span id="nextButtonLoading" class="hidden">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Creating...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Step 2: Add Candidates -->
                    <div class="step-content" data-step="2">
                        <input type="hidden" id="newElectionId" name="election_id" value="">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                            <select id="candidatePosition" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>"
                                        data-max="<?php echo $position['max_candidates']; ?>">
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Student *</label>
                            <select id="candidateStudent" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Search for student...</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Manifesto</label>
                            <textarea id="candidateManifesto" rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                placeholder="Describe the candidate's goals and qualifications..."></textarea>
                        </div>

                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Added Candidates</h4>
                            <div id="addedCandidatesList" class="space-y-3">
                                <p class="text-gray-500 text-sm">No candidates added yet</p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="goToStep(1)"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                                Back
                            </button>
                            <button type="button" onclick="addCandidateToList()"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                Add Candidate
                            </button>
                            <button type="button" onclick="submitElectionWithCandidates()"
                                id="finishButton"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <span id="finishButtonText">Finish</span>
                                <span id="finishButtonLoading" class="hidden">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Candidates Modal -->
    <div id="candidatesModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800" id="candidatesModalTitle">Election Candidates</h3>
                    <button onclick="closeCandidatesModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6" id="candidatesModalContent">
                    <!-- Content loaded via AJAX -->
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mb-4"></i>
                        <p class="text-gray-500">Loading candidates...</p>
                    </div>
                </div>
            </div>
        </div>



        <script>
            // Track current step and data
            let currentStep = 1;
            let newElectionId = null;
            let addedCandidates = [];

            // Initialize components when DOM is loaded
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize date pickers
                flatpickr(".flatpickr", {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    minDate: "today"
                });

                // Initialize student search for the modal
                const studentSelect = document.getElementById('candidateStudent');
                if (studentSelect) {
                    studentSelect.addEventListener('focus', function() {
                        if (this.options.length <= 1) {
                            loadStudents();
                        }
                    });

                    let searchTimeout;
                    studentSelect.addEventListener('input', function() {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(loadStudents, 300);
                    });
                }
            });

            // Load students for candidate selection
            function loadStudents() {
                const positionId = document.getElementById('candidatePosition').value;
                const searchTerm = document.getElementById('candidateStudent').value;

                if (!positionId) {
                    alert('Please select a position first');
                    return;
                }

                console.log(`Loading students for position ${positionId} with search term: ${searchTerm}`);

                fetch(`includes/async/search_students.php?term=${encodeURIComponent(searchTerm)}&position=${positionId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Students data received:', data);
                        if (data.success) {
                            const select = document.getElementById('candidateStudent');
                            // Clear existing options (except the first one)
                            while (select.options.length > 1) {
                                select.remove(1);
                            }

                            // Add new students
                            data.students.forEach(student => {
                                // Check if student is already added
                                const alreadyAdded = addedCandidates.some(c => c.student_id == student.id);
                                if (!alreadyAdded) {
                                    const option = document.createElement('option');
                                    option.value = student.id;
                                    option.textContent = `${student.first_name} ${student.last_name} (${student.student_id})`;
                                    select.appendChild(option);
                                }
                            });
                        } else {
                            console.error('Error loading students:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                        alert('Error loading students. Please try again.');
                    });
            }

            // Submit election form via AJAX
            function submitElectionForm() {
                console.log('Submitting election form');

                const form = document.getElementById('electionForm');
                const formData = new FormData(form);
                formData.append('ajax_request', 'create_election');

                // Show loading state
                document.getElementById('nextButtonText').classList.add('hidden');
                document.getElementById('nextButtonLoading').classList.remove('hidden');

                fetch('elections.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Election creation response:', data);

                        if (data.success) {
                            newElectionId = data.election_id;
                            document.getElementById('newElectionId').value = newElectionId;
                            goToStep(2);
                        } else {
                            let errorMessage = 'Error creating election: ';
                            if (data.errors && Array.isArray(data.errors)) {
                                errorMessage += data.errors.join('\n');
                            } else if (data.error) {
                                errorMessage += data.error;
                            } else {
                                errorMessage += 'Unknown error';
                            }
                            alert(errorMessage);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error creating election. Please check the console for details.');
                    })
                    .finally(() => {
                        // Hide loading state
                        document.getElementById('nextButtonText').classList.remove('hidden');
                        document.getElementById('nextButtonLoading').classList.add('hidden');
                    });
            }
            // Add candidate to the list
            function addCandidateToList() {
                const positionId = document.getElementById('candidatePosition').value;
                const positionSelect = document.getElementById('candidatePosition');
                const positionName = positionSelect.options[positionSelect.selectedIndex]?.text || '';

                const studentId = document.getElementById('candidateStudent').value;
                const studentSelect = document.getElementById('candidateStudent');
                const studentText = studentSelect.options[studentSelect.selectedIndex]?.text || '';

                const manifesto = document.getElementById('candidateManifesto').value;

                if (!positionId) {
                    alert('Please select a position');
                    return;
                }

                if (!studentId) {
                    alert('Please select a student');
                    return;
                }

                // Check if this student is already added for this position
                const alreadyAdded = addedCandidates.some(c =>
                    c.student_id == studentId && c.position_id == positionId
                );

                if (alreadyAdded) {
                    alert('This student is already a candidate for this position');
                    return;
                }

                // Get position max candidates
                const positionMax = parseInt(positionSelect.options[positionSelect.selectedIndex]?.dataset.max || 1);

                // Check if position has reached max candidates
                const positionCandidateCount = addedCandidates.filter(c =>
                    c.position_id == positionId
                ).length;

                if (positionCandidateCount >= positionMax) {
                    alert(`This position can only have ${positionMax} candidate(s)`);
                    return;
                }

                // Add to our tracking array
                addedCandidates.push({
                    position_id: positionId,
                    position_name: positionName,
                    student_id: studentId,
                    student_name: studentText.split(' (')[0],
                    manifesto: manifesto
                });

                // Update the UI
                updateCandidatesList();

                // Reset form
                document.getElementById('candidateStudent').value = '';
                document.getElementById('candidateManifesto').value = '';
            }

            // Update the candidates list UI
            function updateCandidatesList() {
                const container = document.getElementById('addedCandidatesList');
                container.innerHTML = '';

                if (addedCandidates.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-sm">No candidates added yet</p>';
                    return;
                }

                // Group by position
                const byPosition = {};
                addedCandidates.forEach(candidate => {
                    if (!byPosition[candidate.position_id]) {
                        byPosition[candidate.position_id] = {
                            position_name: candidate.position_name,
                            candidates: []
                        };
                    }
                    byPosition[candidate.position_id].candidates.push(candidate);
                });

                // Create UI for each position
                for (const positionId in byPosition) {
                    const position = byPosition[positionId];

                    const positionDiv = document.createElement('div');
                    positionDiv.className = 'mb-4';

                    const positionHeader = document.createElement('h5');
                    positionHeader.className = 'font-medium text-gray-700 mb-2';
                    positionHeader.textContent = position.position_name;
                    positionDiv.appendChild(positionHeader);

                    const candidatesGrid = document.createElement('div');
                    candidatesGrid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-3';
                    positionDiv.appendChild(candidatesGrid);

                    position.candidates.forEach(candidate => {
                        const candidateDiv = document.createElement('div');
                        candidateDiv.className = 'bg-gray-50 p-3 rounded-lg';

                        const nameDiv = document.createElement('div');
                        nameDiv.className = 'font-medium text-gray-800';
                        nameDiv.textContent = candidate.student_name;
                        candidateDiv.appendChild(nameDiv);

                        const positionDiv = document.createElement('div');
                        positionDiv.className = 'text-xs text-gray-500';
                        positionDiv.textContent = candidate.position_name;
                        candidateDiv.appendChild(positionDiv);

                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'text-red-500 hover:text-red-700 text-xs mt-1';
                        removeBtn.innerHTML = '<i class="fas fa-times mr-1"></i> Remove';
                        removeBtn.onclick = () => removeCandidate(candidate.student_id, positionId);
                        candidateDiv.appendChild(removeBtn);

                        candidatesGrid.appendChild(candidateDiv);
                    });

                    container.appendChild(positionDiv);
                }
            }

            // Remove candidate from the list
            function removeCandidate(studentId, positionId) {
                addedCandidates = addedCandidates.filter(c =>
                    !(c.student_id == studentId && c.position_id == positionId)
                );
                updateCandidatesList();
            }

            // Submit election with candidates
            function submitElectionWithCandidates() {
                if (!newElectionId) {
                    alert('Please complete the election details first');
                    return;
                }

                if (addedCandidates.length === 0) {
                    if (confirm('No candidates have been added. Do you want to create the election without candidates?')) {
                        closeCreateElectionModal();
                        window.location.reload();
                        return;
                    } else {
                        return;
                    }
                }

                // Show loading state
                document.getElementById('finishButtonText').classList.add('hidden');
                document.getElementById('finishButtonLoading').classList.remove('hidden');

                // Prepare data for submission
                const submissionData = {
                    ajax_request: 'add_candidates',
                    election_id: newElectionId,
                    candidates: addedCandidates
                };

                console.log('Submitting candidates:', submissionData);

                fetch('elections.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(submissionData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Candidates submission response:', data);

                        if (data.success) {
                            alert('Election and candidates created successfully!');
                            closeCreateElectionModal();
                            window.location.reload();
                        } else {
                            alert('Error adding candidates: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error adding candidates:', error);
                        alert('Error adding candidates. Please try again.');
                    })
                    .finally(() => {
                        // Hide loading state
                        document.getElementById('finishButtonText').classList.remove('hidden');
                        document.getElementById('finishButtonLoading').classList.add('hidden');
                    });
            }

            // Navigate between steps
            function goToStep(step) {
                // Update step indicator
                document.querySelectorAll('.step').forEach(el => {
                    el.classList.remove('active', 'completed');
                    if (parseInt(el.dataset.step) < step) {
                        el.classList.add('completed');
                    }
                    if (parseInt(el.dataset.step) === step) {
                        el.classList.add('active');
                    }
                });

                // Update content
                document.querySelectorAll('.step-content').forEach(el => {
                    el.classList.remove('active');
                    if (parseInt(el.dataset.step) === step) {
                        el.classList.add('active');
                    }
                });

                currentStep = step;
            }

            // Modal functions
            function openCreateElectionModal() {
                // Reset modal state
                currentStep = 1;
                newElectionId = null;
                addedCandidates = [];

                document.getElementById('createElectionModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';

                // Reset step indicator
                document.querySelectorAll('.step').forEach(el => {
                    el.classList.remove('active', 'completed');
                    if (parseInt(el.dataset.step) === 1) {
                        el.classList.add('active');
                    }
                });

                // Reset content
                document.querySelectorAll('.step-content').forEach(el => {
                    el.classList.remove('active');
                    if (parseInt(el.dataset.step) === 1) {
                        el.classList.add('active');
                    }
                });

                // Reset form
                document.getElementById('electionForm').reset();
                document.getElementById('addedCandidatesList').innerHTML = '<p class="text-gray-500 text-sm">No candidates added yet</p>';
            }

            function closeCreateElectionModal() {
                document.getElementById('createElectionModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            function openCandidatesModal(electionId) {
                document.getElementById('candidatesModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';

                // Load candidates via AJAX
                fetch(`includes/async/get_election_candidates.php?id=${electionId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        document.getElementById('candidatesModalContent').innerHTML = html;
                        const titleElement = document.querySelector('#candidatesModalContent h2');
                        if (titleElement) {
                            document.getElementById('candidatesModalTitle').textContent = titleElement.textContent;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading candidates:', error);
                        document.getElementById('candidatesModalContent').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-circle text-red-500 text-2xl mb-4"></i>
                            <p class="text-red-500">Error loading candidates</p>
                            <p class="text-gray-500 text-sm mt-2">${error.message}</p>
                            <button onclick="location.reload()"
                                    class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Reload Page
                            </button>
                        </div>
                    `;
                    });
            }

            function closeCandidatesModal() {
                document.getElementById('candidatesModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Close modals when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        const modalId = this.id;
                        if (modalId === 'createElectionModal') closeCreateElectionModal();
                        if (modalId === 'candidatesModal') closeCandidatesModal();
                    }
                });
            });

            // Close modals with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeCreateElectionModal();
                    closeCandidatesModal();
                }
            });
        </script>
</body>

</html>