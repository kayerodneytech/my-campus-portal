<?php
// admin/includes/async/get_election_candidates.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isAdmin() || !isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$electionId = intval($_GET['id'] ?? 0);
$addForm = isset($_GET['add_form']) && $_GET['add_form'] === 'true';
$positionId = isset($_GET['position']) ? intval($_GET['position']) : null;

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
    includeAddCandidateForm($electionId, $positions, $positionId);
} else {
    // Display the candidates list
    includeCandidatesList($electionId, $election, $candidatesByPosition, $positions);
}

function includeCandidatesList($electionId, $election, $candidatesByPosition, $positions)
{
?>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($election['title']); ?> Candidates</h2>
        <?php if (!empty($positions)): ?>
            <button onclick="openAddCandidateForm(<?php echo $electionId; ?>)"
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-user-plus mr-2"></i> Add Candidates
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($candidatesByPosition)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
            <i class="fas fa-users-slash text-yellow-400 text-3xl mb-2"></i>
            <p class="text-yellow-800 font-medium mb-4">No candidates added yet</p>
            <?php if (!empty($positions)): ?>
                <button onclick="openAddCandidateForm(<?php echo $electionId; ?>)"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-user-plus mr-2"></i> Add Candidates Now
                </button>
            <?php else: ?>
                <p class="text-gray-500">No positions defined for this election</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($candidatesByPosition as $position): ?>
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?php echo htmlspecialchars($position['position']['name']); ?>
                            <span class="text-sm text-gray-500 ml-2">
                                (Max <?php echo $position['position']['max_candidates']; ?>)
                            </span>
                        </h3>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">
                            <?php echo count($position['candidates']); ?> candidate<?php echo count($position['candidates']) !== 1 ? 's' : ''; ?>
                        </span>
                        <button onclick="openAddCandidateForm(<?php echo $electionId; ?>, <?php echo $position['position']['id']; ?>)"
                            class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                            <i class="fas fa-plus-circle mr-1"></i> Add
                        </button>
                    </div>
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

function includeAddCandidateForm($electionId, $positions, $positionId = null)
{
    // If positionId is provided, find the position details
    $selectedPosition = null;
    if ($positionId) {
        foreach ($positions as $position) {
            if ($position['id'] == $positionId) {
                $selectedPosition = $position;
                break;
            }
        }
    }
?>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">
            <?php echo $selectedPosition ? 'Add Candidate for ' . htmlspecialchars($selectedPosition['name']) : 'Add Candidate'; ?>
        </h2>
        <button onclick="closeAddCandidateForm()"
            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-sm">
            <i class="fas fa-times mr-2"></i> Back to Candidates
        </button>
    </div>

    <input type="hidden" id="currentElectionId" value="<?php echo $electionId; ?>">

    <?php if (!$selectedPosition): ?>
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
    <?php else: ?>
        <input type="hidden" id="newCandidatePosition" value="<?php echo $selectedPosition['id']; ?>">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
            <div class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                <?php echo htmlspecialchars($selectedPosition['name']); ?>
                <span class="text-xs text-gray-500 ml-2">(Max <?php echo $selectedPosition['max_candidates']; ?>)</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Search for Student *</label>
        <input type="text" id="newCandidateStudentSearch" class="w-full px-4 py-2 border border-gray-300 rounded-lg"
            placeholder="Search by name or student ID...">
        <input type="hidden" id="newCandidateStudent">
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

    <div id="studentSearchResults" class="mt-4"></div>

    <script>
        // Initialize student search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('newCandidateStudentSearch');
            const studentIdInput = document.getElementById('newCandidateStudent');
            let searchTimeout;

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(searchStudents, 300);
                });
            }
        });

        // Function to search for students
        function searchStudents() {
            const positionId = document.getElementById('newCandidatePosition').value;
            const searchTerm = document.getElementById('newCandidateStudentSearch').value;
            const electionId = document.getElementById('currentElectionId').value;

            if (!positionId) {
                alert('Please select a position first');
                return;
            }

            if (searchTerm.length < 2) {
                document.getElementById('studentSearchResults').innerHTML = '';
                return;
            }

            fetch(`search_students.php?term=${encodeURIComponent(searchTerm)}&position=${positionId}&election=${electionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const resultsContainer = document.getElementById('studentSearchResults');
                        if (data.students && data.students.length > 0) {
                            let html = '<div class="space-y-2">';
                            data.students.forEach(student => {
                                html += `
                                    <div class="p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"
                                         onclick="selectStudent(${student.id}, '${student.first_name} ${student.last_name} (${student.application_number})')">
                                        <div class="font-medium">${student.first_name} ${student.last_name}</div>
                                        <div class="text-sm text-gray-500">${student.application_number}</div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            resultsContainer.innerHTML = html;
                        } else {
                            resultsContainer.innerHTML = '<p class="text-gray-500 text-sm mt-2">No students found</p>';
                        }
                    } else {
                        console.error('Error loading students:', data.error || 'Unknown error');
                        document.getElementById('studentSearchResults').innerHTML =
                            '<p class="text-red-500 text-sm mt-2">' + (data.error || 'Error loading students') + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    document.getElementById('studentSearchResults').innerHTML =
                        '<p class="text-red-500 text-sm mt-2">Error loading students. Please try again.</p>';
                });
        }

        // Function to select a student
        function selectStudent(studentId, studentName) {
            document.getElementById('newCandidateStudent').value = studentId;
            document.getElementById('newCandidateStudentSearch').value = studentName;
            document.getElementById('studentSearchResults').innerHTML = '';
        }

        // Function to add a new candidate
        function addNewCandidate() {
            const electionId = document.getElementById('currentElectionId').value;
            const positionId = document.getElementById('newCandidatePosition').value;
            const studentId = document.getElementById('newCandidateStudent').value;
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
    </script>
<?php
}
?>