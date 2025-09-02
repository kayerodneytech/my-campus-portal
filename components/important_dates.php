<?php
/**
 * Important Dates Component
 * Displays important academic dates and deadlines
 * Used on the homepage and dashboard
 */

// Include database connection
require_once '../includes/config.php';

// Fetch important dates from database (announcements table)
$important_dates = [];
try {
    // Query announcements table for important dates
    $stmt = $conn->prepare("SELECT title, created_at FROM announcements WHERE audience = 'all' ORDER BY created_at DESC LIMIT 6");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $important_dates[] = [
            'label' => $row['title'],
            'date' => date('F j, Y', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to sample data if database query fails
    $important_dates = [
        ['label' => 'Spring Semester Begins', 'date' => 'January 15, 2025'],
        ['label' => 'Course Registration Deadline', 'date' => 'January 30, 2025'],
        ['label' => 'Mid-term Exams', 'date' => 'March 10-14, 2025'],
        ['label' => 'Spring Break', 'date' => 'March 24-28, 2025'],
        ['label' => 'Final Exams', 'date' => 'May 5-9, 2025'],
        ['label' => 'Graduation Ceremony', 'date' => 'May 18, 2025']
    ];
}
?>

<div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-3xl mr-3">📅</span>
        Important Dates and Deadlines
    </h2>
    
    <div class="space-y-4">
        <?php foreach ($important_dates as $date_item): ?>
        <div class="flex justify-between items-center py-3 px-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
            <span class="font-medium text-gray-700"><?php echo htmlspecialchars($date_item['label']); ?></span>
            <span class="text-blue-600 font-semibold"><?php echo htmlspecialchars($date_item['date']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
