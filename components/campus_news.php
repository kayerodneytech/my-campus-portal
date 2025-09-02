<?php
/**
 * Campus News Component
 * Displays campus news and events
 * Used on the homepage and dashboard
 */

// Include database connection
require_once './includes/config.php';

// Fetch campus news from database (announcements table)
$campus_news = [];
try {
    // Query announcements table for campus news
    $stmt = $conn->prepare("SELECT title, message, created_at FROM announcements WHERE audience = 'all' ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $campus_news[] = [
            'title' => $row['title'],
            'date' => 'Posted: ' . date('F j, Y', strtotime($row['created_at'])),
            'summary' => $row['message']
        ];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to sample data if database query fails
    $campus_news = [
        [
            'title' => 'Annual Science Fair 2025',
            'date' => 'Posted: July 20, 2025',
            'summary' => 'Join us for the most exciting science fair featuring innovative projects from students across all departments.'
        ],
        [
            'title' => 'Guest Lecture: AI in Education',
            'date' => 'Posted: July 18, 2025',
            'summary' => 'Renowned AI expert Dr. Sarah Johnson will discuss the future of artificial intelligence in educational systems.'
        ],
        [
            'title' => 'Career Fair Registration Open',
            'date' => 'Posted: July 15, 2025',
            'summary' => 'Register now for the upcoming career fair featuring top employers from various industries.'
        ]
    ];
}
?>

<div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-3xl mr-3">📢</span>
        Campus News & Events
    </h2>
    
    <div class="space-y-6">
        <?php if (!empty($campus_news)): ?>
            <?php foreach ($campus_news as $news_item): ?>
            <div class="border-l-4 border-blue-500 pl-4 py-2 hover:bg-gray-50 transition-colors duration-200">
                <div class="font-semibold text-gray-800 text-lg mb-1">
                    <?php echo htmlspecialchars($news_item['title']); ?>
                </div>
                <div class="text-sm text-gray-500 mb-2">
                    <?php echo htmlspecialchars($news_item['date']); ?>
                </div>
                <div class="text-gray-600 leading-relaxed">
                    <?php echo htmlspecialchars($news_item['summary']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="text-4xl text-gray-300 mb-4">
                    <i class="fas fa-newspaper"></i>
                    <span class="fa-fallback" style="display: none;">📰</span>
                </div>
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Campus News</h3>
                <p class="text-gray-500">Stay tuned for the latest campus news and events.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
