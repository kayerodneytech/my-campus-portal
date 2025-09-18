<?php
// Modal to create elections
$positions = $conn->query("SELECT * FROM election_positions ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Create Election Modal -->
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
                    <form id="electionForm" class="space-y-6">
                        <input type="hidden" name="ajax_request" value="create_election">
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
                            <button type="button" onclick="goToStep(2)"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                Next: Add Candidates
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Step 2: Add Candidates -->
                <div class="step-content" data-step="2">
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
                        <button type="button" onclick="submitElectionForm()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <span id="submitButtonText">Submit Election</span>
                            <span id="submitButtonLoading" class="hidden">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Submitting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>