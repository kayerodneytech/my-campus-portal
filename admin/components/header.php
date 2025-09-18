<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Title - Always visible -->
            <div class="flex items-center">
                <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-2 sm:mr-3">
                <h1 class="text-lg sm:text-xl font-bold text-gray-800">Admin Portal</h1>
            </div>

            <!-- Navigation and User Info - Collapsible on mobile -->
            <div class="hidden md:flex items-center space-x-2 sm:space-x-4">
                <a href="index.php" class="text-gray-600 hover:text-blue-600 px-2 sm:px-3 py-1.5 sm:py-2 rounded-md text-xs sm:text-sm font-medium flex items-center">
                    <i class="fas fa-tachometer-alt mr-1 sm:mr-2"></i>
                    <span>Dashboard</span>
                </a>
                <span class="text-gray-600 text-xs sm:text-sm hidden lg:inline">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="text-gray-600 hover:text-red-600 px-2 sm:px-3 py-1.5 sm:py-2 rounded-md text-xs sm:text-sm font-medium flex items-center">
                    <i class="fas fa-sign-out-alt mr-1 sm:mr-2"></i>
                    <span>Logout</span>
                </a>
            </div>

            <!-- Mobile Menu Button - Visible only on mobile -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-gray-600 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu - Hidden by default -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200 py-2">
            <div class="flex flex-col space-y-2 px-2 pt-2 pb-3">
                <a href="index.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Dashboard
                </a>
                <div class="px-3 py-2 text-gray-600 text-sm">
                    Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </div>
                <a href="logout.php" class="text-gray-600 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script>