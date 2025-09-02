<?php
/**
 * Student Organizations Component
 * Displays student organization links and information
 * Used on the homepage and student organizations page
 */

// Include database connection
require_once '../includes/config.php';

// Fetch student organizations from database
$organizations = [];
try {
    // Query school_clubs table to get active organizations
    $stmt = $conn->prepare("
        SELECT id, name, description 
        FROM school_clubs 
        WHERE status = 'active'
        ORDER BY name
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $organizations[] = [
            'name' => $row['name'],
            'id' => 'org-' . $row['id'],
            'description' => $row['description']
        ];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to sample data if database query fails
    $organizations = [
        ['name' => 'Student Government', 'id' => 'student-government-link'],
        ['name' => 'Academic Clubs', 'id' => 'academic-clubs-link'],
        ['name' => 'Cultural Organizations', 'id' => 'cultural-orgs-link'],
        ['name' => 'Sports & Recreation', 'id' => 'sports-recreation-link'],
        ['name' => 'Volunteer Groups', 'id' => 'volunteer-groups-link']
    ];
}
?>

<div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-3xl mr-3">🎯</span>
        Student Organizations
    </h3>
    
    <div class="space-y-3">
        <?php foreach ($organizations as $org): ?>
        <a href="javascript:void(0)" 
           id="<?php echo htmlspecialchars($org['id']); ?>" 
           class="menu-item-link block w-full py-3 px-4 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-200 border border-transparent transition-all duration-200 text-gray-700 hover:text-blue-700">
            <div class="flex items-center justify-between">
                <span class="font-medium"><?php echo htmlspecialchars($org['name']); ?></span>
                <span class="text-blue-500">→</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
