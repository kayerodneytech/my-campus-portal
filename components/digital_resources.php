<?php
/**
 * Digital Resources Component
 * Displays digital resources and links
 * Used on the homepage and resources page
 */

// Sample digital resources data - in a real application, this would come from a database
$digital_resources = [
    ['name' => 'Online Learning Platform', 'url' => '#'],
    ['name' => 'Digital Library', 'url' => '#'],
    ['name' => 'Research Databases', 'url' => '#'],
    ['name' => 'Student Portal Mobile App', 'url' => '#'],
    ['name' => 'Tech Support', 'url' => '#']
];
?>

<div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-3xl mr-3">🌐</span>
        Digital Resources
    </h3>
    
    <div class="space-y-3">
        <?php foreach ($digital_resources as $resource): ?>
        <a href="<?php echo htmlspecialchars($resource['url']); ?>" 
           class="block w-full py-3 px-4 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-200 border border-transparent transition-all duration-200 text-gray-700 hover:text-blue-700">
            <div class="flex items-center justify-between">
                <span class="font-medium"><?php echo htmlspecialchars($resource['name']); ?></span>
                <span class="text-blue-500">→</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
