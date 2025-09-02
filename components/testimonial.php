<?php
/**
 * Testimonial Component
 * Displays student testimonials
 * Used on the homepage and about page
 */

// Sample testimonial data - in a real application, this would come from a database
$testimonial = [
    'text' => "MyCamp Portal has transformed my academic experience. The integrated resources and user-friendly interface make it easy to stay organized and connected with the campus community.",
    'author' => 'Syscoe Mundere, Class of 2024, Computer Science'
];
?>

<div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-8 text-white mb-8">
    <div class="text-center">
        <div class="text-6xl mb-4">💬</div>
        <blockquote class="text-xl md:text-2xl font-medium mb-6 leading-relaxed">
            "<?php echo htmlspecialchars($testimonial['text']); ?>"
        </blockquote>
        <cite class="text-blue-100 text-lg">
            - <?php echo htmlspecialchars($testimonial['author']); ?>
        </cite>
    </div>
</div>
