<?php
/**
 * Lecturer Catalog Component
 * Displays information about faculty members
 * Used on the homepage and staff catalog page
 */

// Include database connection
require_once './includes/config.php';

// Fetch lecturer data from database
$lecturers = [];
try {
    // Query users and lecturers tables to get lecturer information
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, l.department, l.specialization 
        FROM users u 
        INNER JOIN lecturers l ON u.id = l.user_id 
        WHERE u.role = 'lecturer' AND u.status = 'active'
        ORDER BY u.first_name, u.last_name
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $lecturers[] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'subject' => $row['specialization'] ?: 'General Studies',
            'department' => $row['department'] ?: 'Not Specified'
        ];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to sample data if database query fails
    $lecturers = [
        ['name' => 'John Langa', 'subject' => 'Web Design', 'department' => 'Computer Science'],
        ['name' => 'Anna Sibanda', 'subject' => 'Database Design', 'department' => 'Computer Science'],
        ['name' => 'Zodwa Moyo', 'subject' => 'Cyber Security', 'department' => 'Computer Science'],
        ['name' => 'Thabo Ncube', 'subject' => 'Business Management', 'department' => 'Business'],
        ['name' => 'Linda Moyo', 'subject' => 'Accounting', 'department' => 'Business']
    ];
}
?>

<div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-3xl mr-3">👨‍🏫</span>
        Lecturer Catalog
    </h3>
    
    <div class="space-y-4">
        <?php if (!empty($lecturers)): ?>
            <?php foreach ($lecturers as $lecturer): ?>
            <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                <div class="flex-1">
                    <div class="font-semibold text-gray-800">
                        <?php echo htmlspecialchars($lecturer['name']); ?>
                    </div>
                    <div class="text-sm text-gray-600">
                        <?php echo htmlspecialchars($lecturer['subject']); ?>
                    </div>
                </div>
                <div class="text-sm text-blue-600 font-medium">
                    <?php echo htmlspecialchars($lecturer['department']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="text-4xl text-gray-300 mb-4">
                    <i class="fas fa-user-graduate"></i>
                    <span class="fa-fallback" style="display: none;">👨‍🏫</span>
                </div>
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Lecturers Listed</h3>
                <p class="text-gray-500">Lecturer information will be available soon.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
