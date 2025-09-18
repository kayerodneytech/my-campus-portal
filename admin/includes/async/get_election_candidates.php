<?php
session_start();
require_once '../config.php';
require_once '../auth.php';

if (!isAdmin() || !isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$electionId = intval($_GET['id'] ?? 0);
$addForm = isset($_GET['add_form']) && $_GET['add_form'] === 'true';

// Get election details
$election = $conn->query("SELECT * FROM elections WHERE id = $electionId")->fetch_assoc();
if (!$election) {
    echo '<div class="text-center py-8"><p class="text-red-500">Election not found</p></div>';
    exit();
}

// Get candidates for this election
$candidates = $conn->query("
    SELECT ec.*, u.first_name, u.last_name, ep.name as position_name, ep.max_candidates
    FROM election_candidates ec
    JOIN users u ON ec.student_id = u.id
    JOIN election_positions ep ON ec.position_id = ep.id
    WHERE ec.election_id = $electionId
    ORDER BY ep.name, u.last_name, u.first_name
")->fetch_all(MYSQLI_ASSOC);

// Get all positions for this election
$positions = $conn->query("
    SELECT ep.*, COUNT(ec.id) as candidate_count
    FROM election_positions ep
    LEFT JOIN election_candidates ec ON ep.id = ec.position_id AND ec.election_id = $electionId
    WHERE ep.id IN (
        SELECT position_id FROM election_candidates WHERE election_id = $electionId
    )
    GROUP BY ep.id
    ORDER BY ep.name
")->fetch_all(MYSQLI_ASSOC);

// If no positions found, get all positions from the election
if (empty($positions)) {
    $positions = $conn->query("
        SELECT ep.*, 0 as candidate_count
        FROM election_positions ep
        WHERE ep.id IN (
            SELECT position_id FROM election_candidates WHERE election_id = $electionId
        )
        ORDER BY ep.name
    ")->fetch_all(MYSQLI_ASSOC);
}

// If still no positions, get all available positions
if (empty($positions)) {
    $positions = $conn->query("
        SELECT ep.*, 0 as candidate_count
        FROM election_positions ep
        ORDER BY ep.name
    ")->fetch_all(MYSQLI_ASSOC);
}

// Group candidates by position
$candidatesByPosition = [];
foreach ($candidates as $candidate) {
    $candidatesByPosition[$candidate['position_id']]['position'] = [
        'id' => $candidate['position_id'],
        'name' => $candidate['position_name'],
        'max_candidates' => $candidate['max_candidates']
    ];
    $candidatesByPosition[$candidate['position_id']]['candidates'][] = $candidate;
}

if ($addForm) {
    // Display the add candidate form
    includeAddCandidateForm($electionId, $positions);
} else {
    // Display the candidates list
    includeCandidatesList($electionId, $election, $candidatesByPosition, $positions);
}

function includeCandidatesList($electionId, $election, $candidatesByPosition, $positions)
{
?>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($election['title']); ?> Candidates</h2>
        <button onclick="openAddCandidateForm(<?php echo $electionId; ?>)"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
            <i class="fas fa-user-plus mr-2"></i> Add Candidates
        </button>
    </div>

    <?php if (empty($candidatesByPosition)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
            <i class="fas fa-users-slash text-yellow-400 text-3xl mb-2"></i>
            <p class="text-yellow-800 font-medium mb-4">No candidates added yet</p>
            <button onclick="openAddCandidateForm(<?php echo $electionId; ?>)"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-user-plus mr-2"></i> Add Candidates Now
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($candidatesByPosition as $position): ?>
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <?php echo htmlspecialchars($position['position']['name']); ?>
                        <span class="text-sm text-gray-500 ml-2">
                            (Max <?php echo $position['position']['max_candidates']; ?>)
                        </span>
                    </h3>
                    <span class="text-sm text-gray-500">
                        <?php echo count($position['candidates']); ?> candidate<?php echo count($position['candidates']) !== 1 ? 's' : ''; ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($position['candidates'] as $candidate): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($candidate['position_name']); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($candidate['manifesto'])): ?>
                                <div class="mt-3">
                                    <h5 class="text-sm font-medium text-gray-700 mb-1">Manifesto:</h5>
                                    <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php
}

function includeAddCandidateForm($electionId, $positions)
{
?>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Add Candidate to Election</h2>
        <button onclick="closeAddCandidateForm()"
            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-sm">
            <i class="fas fa-times mr-2"></i> Back to Candidates
        </button>
    </div>

    <input type="hidden" id="currentElectionId" value="<?php echo $electionId; ?>">

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
        <select id="newCandidatePosition" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Select Position</option>
            <?php foreach ($positions as $position): ?>
                <option value="<?php echo $position['id']; ?>"
                    data-max="<?php echo $position['max_candidates']; ?>">
                    <?php echo htmlspecialchars($position['name']); ?>
                    (Max <?php echo $position['max_candidates']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Student *</label>
        <select id="newCandidateStudent" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Search for student...</option>
        </select>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Manifesto</label>
        <textarea id="newCandidateManifesto" rows="4"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg"
            placeholder="Describe the candidate's goals and qualifications..."></textarea>
    </div>

    <div class="flex justify-end space-x-3">
        <button onclick="closeAddCandidateForm()"
            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
            Cancel
        </button>
        <button onclick="addNewCandidate()"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <span id="addCandidateButtonText"><i class="fas fa-user-plus mr-2"></i> Add Candidate</span>
            <span id="addCandidateButtonLoading" class="hidden">
                <i class="fas fa-spinner fa-spin mr-2"></i> Adding...
            </span>
        </button>
    </div>

    <script>
        // Initialize student search for the new candidate form
        document.addEventListener('DOMContentLoaded', function() {
            const studentSelect = document.getElementById('newCandidateStudent');
            if (studentSelect) {
                let searchInitialized = false;
                let searchTimeout;

                studentSelect.addEventListener('focus', function() {
                    if (!searchInitialized) {
                        loadStudentsForNewCandidate();
                        searchInitialized = true;
                    }
                });

                studentSelect.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(loadStudentsForNewCandidate, 300);
                });
            }
        });

        // Function to load students for the new candidate form
        function loadStudentsForNewCandidate() {
            const positionId = document.getElementById('newCandidatePosition').value;
            const searchTerm = document.getElementById('newCandidateStudent').value;
            const electionId = document.getElementById('currentElectionId').value;

            if (!positionId) {
                alert('Please select a position first');
                return;
            }

            const select = document.getElementById('newCandidateStudent');

            fetch(`search_students.php?term=${encodeURIComponent(searchTerm)}&position=${positionId}&election=${electionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Clear existing options (except the first one)
                        while (select.options.length > 1) {
                            select.remove(1);
                        }

                        // Add new students
                        if (data.students && data.students.length > 0) {
                            data.students.forEach(student => {
                                const option = document.createElement('option');
                                option.value = student.id;
                                option.textContent = `${student.first_name} ${student.last_name} (${student.application_number})`;
                                select.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "No students found";
                            option.disabled = true;
                            select.appendChild(option);
                        }
                    } else {
                        console.error('Error loading students:', data.error || 'Unknown error');
                        alert(data.error || 'Error loading students. Please check the console for details.');
                    }
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    alert(`Error loading students: ${error.message}`);
                });
        }

        // Function to add a new candidate
        function addNewCandidate() {
            const electionId = document.getElementById('currentElectionId').value;
            const positionId = document.getElementById('newCandidatePosition').value;
            const positionSelect = document.getElementById('newCandidatePosition');
            const positionName = positionSelect.options[positionSelect.selectedIndex]?.text || '';
            const studentId = document.getElementById('newCandidateStudent').value;
            const studentSelect = document.getElementById('newCandidateStudent');
            const studentText = studentSelect.options[studentSelect.selectedIndex]?.text || '';
            const manifesto = document.getElementById('newCandidateManifesto').value;

            if (!positionId) {
                alert('Please select a position');
                return;
            }

            if (!studentId) {
                alert('Please select a student');
                return;
            }

            // Show loading state
            document.getElementById('addCandidateButtonText').classList.add('hidden');
            document.getElementById('addCandidateButtonLoading').classList.remove('hidden');

            // Prepare data for submission
            const submissionData = {
                election_id: electionId,
                position_id: positionId,
                student_id: studentId,
                manifesto: manifesto
            };

            fetch('handle_election.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax_request=add_single_candidate&` + new URLSearchParams(submissionData).toString()
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Candidate added successfully!');
                        // Refresh the candidates list
                        loadCandidatesList(electionId);
                    } else {
                        alert('Error adding candidate: ' + (data.error || 'Unknown error'));
                        // Reset button state
                        document.getElementById('addCandidateButtonText').classList.remove('hidden');
                        document.getElementById('addCandidateButtonLoading').classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error adding candidate:', error);
                    alert('Error adding candidate. Please try again.');
                    // Reset button state
                    document.getElementById('addCandidateButtonText').classList.remove('hidden');
                    document.getElementById('addCandidateButtonLoading').classList.add('hidden');
                });
        }

        // Function to load the candidates list
        function loadCandidatesList(electionId) {
            fetch(`get_election_candidates.php?id=${electionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('candidatesModalContent').innerHTML = html;
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
    </script>
<?php
}
?>