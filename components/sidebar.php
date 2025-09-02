<?php
/**
 * Sidebar Navigation Component
 * Responsive sidebar with mobile-friendly design
 * Used across all pages for navigation
 */

// Sample navigation items - in a real application, this would come from a database or config
$nav_items = [
    ['id' => 'welcome-board-link', 'label' => 'Welcome Board', 'fa_icon' => 'fas fa-home', 'emoji_fallback' => '🏠', 'active' => true],
    ['id' => 'student-login-link', 'label' => 'Student Login', 'fa_icon' => 'fas fa-sign-in-alt', 'emoji_fallback' => '🔐', 'active' => false, 'onclick' => 'openLoginModal()'],
    ['id' => 'apply-now-link', 'label' => 'Apply Now/Enroll', 'fa_icon' => 'fas fa-user-plus', 'emoji_fallback' => '📝', 'active' => false, 'onclick' => 'openRegisterModal()'],
    ['id' => 'staff-catalog-link', 'label' => 'Staff Catalog', 'fa_icon' => 'fas fa-users', 'emoji_fallback' => '👥', 'active' => false],
    ['id' => 'courses-offered-link', 'label' => 'Courses Offered', 'fa_icon' => 'fas fa-book', 'emoji_fallback' => '📚', 'active' => false],
    ['id' => 'payments-link', 'label' => 'Payments', 'fa_icon' => 'fas fa-credit-card', 'emoji_fallback' => '💳', 'active' => false],
    ['id' => 'online-classes-link', 'label' => 'Online Classes', 'fa_icon' => 'fas fa-laptop', 'emoji_fallback' => '💻', 'active' => false],
    ['id' => 'academic-support-link', 'label' => 'Academic Support', 'fa_icon' => 'fas fa-graduation-cap', 'emoji_fallback' => '🎓', 'active' => false],
    ['id' => 'library-resources-link', 'label' => 'Library Resources', 'fa_icon' => 'fas fa-book-open', 'emoji_fallback' => '📖', 'active' => false],
    ['id' => 'student-organizations-link', 'label' => 'Student Organizations', 'fa_icon' => 'fas fa-users-cog', 'emoji_fallback' => '🎯', 'active' => false],
    ['id' => 'campus-map-link', 'label' => 'Campus Map', 'fa_icon' => 'fas fa-map', 'emoji_fallback' => '🗺️', 'active' => false]
];
?>

<!-- Mobile Menu Button -->
<button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-600 text-white p-3 rounded-lg shadow-lg hover:bg-blue-700 transition-colors duration-200">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<!-- Sidebar -->
<nav id="sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-80 bg-gradient-to-b from-blue-600 to-blue-700 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out lg:transition-none">
    <!-- Sidebar Header -->
    <div class="p-6 border-b border-blue-500">
        <div class="text-center">
            <img src="1753449127073.jpg" alt="MyCamp Portal Logo" class="w-20 h-20 mx-auto mb-4 rounded-full border-4 border-white shadow-lg">
            <h1 class="text-2xl font-bold mb-2">MyCamp Portal</h1>
            <p class="text-blue-100 text-sm italic">One stop place for life in college.</p>
        </div>
    </div>
    
    <!-- Navigation Items -->
    <div class="p-4 space-y-2 overflow-y-auto">
        <?php foreach ($nav_items as $item): ?>
                 <a href="#" 
            id="<?php echo htmlspecialchars($item['id']); ?>" 
            <?php if (isset($item['onclick'])): ?>
                onclick="<?php echo htmlspecialchars($item['onclick']); ?>"
            <?php endif; ?>
            class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md <?php echo $item['active'] ? 'bg-blue-500 shadow-md' : 'hover:bg-blue-500'; ?>">
             <span class="mr-4">
                 <i class="<?php echo htmlspecialchars($item['fa_icon']); ?> text-xl"></i>
                 <span class="fa-fallback" style="display: none;"><?php echo $item['emoji_fallback']; ?></span>
             </span>
             <span class="font-medium"><?php echo htmlspecialchars($item['label']); ?></span>
         </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Sidebar Footer -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-blue-500">
        <div class="text-center text-blue-100 text-sm">
            <p>© 2025 MyCamp Portal</p>
            <p class="text-xs mt-1">Version 1.0</p>
        </div>
    </div>
</nav>

<!-- Mobile Overlay -->
<div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

<script>
// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobile-overlay');
    
    // Toggle mobile menu
    mobileMenuButton.addEventListener('click', function() {
        sidebar.classList.toggle('-translate-x-full');
        mobileOverlay.classList.toggle('hidden');
    });
    
    // Close mobile menu when clicking overlay
    mobileOverlay.addEventListener('click', function() {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay.classList.add('hidden');
    });
    
    // Close mobile menu when clicking a menu item
    document.querySelectorAll('.menu-item').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth < 1024) { // lg breakpoint
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) { // lg breakpoint
            sidebar.classList.remove('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        }
    });
});
</script>
