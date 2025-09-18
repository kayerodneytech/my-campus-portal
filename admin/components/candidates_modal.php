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
</div>