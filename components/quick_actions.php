<?php
/**
 * Quick Actions Component
 * Displays quick action cards for easy navigation
 * Used on the main dashboard and homepage
 */
?>

<section class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Course Materials Card -->
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-100">
            <div class="mb-4">
                <i class="fas fa-book text-4xl text-blue-600"></i>
                <span class="fa-fallback text-4xl" style="display: none;">📚</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Course Materials</h3>
            <p class="text-gray-600">Access your online courses and materials</p>
        </div>
        
        <!-- Academic Records Card -->
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-100">
            <div class="mb-4">
                <i class="fas fa-clipboard-list text-4xl text-green-600"></i>
                <span class="fa-fallback text-4xl" style="display: none;">📋</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Academic Records</h3>
            <p class="text-gray-600">View grades and transcripts</p>
        </div>
        
        <!-- Student Login Card -->
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-100">
            <div class="mb-4">
                <i class="fas fa-sign-in-alt text-4xl text-purple-600"></i>
                <span class="fa-fallback text-4xl" style="display: none;">🔐</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Student Login</h3>
            <p class="text-gray-600">Access your student dashboard</p>
        </div>
    </div>
</section>
