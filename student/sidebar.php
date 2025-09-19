<?php
// students/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);

// Get student status if not already defined
if (!isset($studentStatus)) {

    require_once '../includes/config.php';

    if (isset($_SESSION['user_id'])) {
        $studentId = $_SESSION['user_id'];
        $student = $conn->query("SELECT status FROM students WHERE user_id = $studentId")->fetch_assoc();
        $studentStatus = $student ? $student['status'] : 'pending';
    } else {
        $studentStatus = 'pending';
    }
}
?>
<nav id="student-sidebar" class="w-80 bg-gradient-to-b from-blue-600 to-blue-700 text-white fixed lg:relative z-40 h-screen lg:h-auto overflow-y-auto hidden lg:block">
    <!-- Sidebar Header -->
    <div class="p-6 border-b border-blue-500">
        <div class="text-center">
            <img src="../1753449127073.jpg" alt="MyCamp Portal Logo" class="w-20 h-20 mx-auto mb-4 rounded-full border-4 border-white shadow-lg">
            <h1 class="text-2xl font-bold mb-2">Student Portal</h1>
            <p class="text-blue-100 text-sm">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</p>
            <div class="mt-2 px-3 py-1 bg-<?php echo $studentStatus === 'active' ? 'green' : 'yellow'; ?>-500 rounded-full text-xs font-medium">
                <?php echo ucfirst($studentStatus); ?>
            </div>
        </div>
    </div>

    <!-- Navigation Items -->
    <div class="p-4 space-y-2 overflow-y-auto h-[calc(100vh-12rem)]">
        <!-- Always available items -->
        <a href="index.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 bg-blue-500 shadow-md">
            <span class="mr-4">
                <i class="fas fa-home text-xl"></i>
            </span>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="profile.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
            <span class="mr-4">
                <i class="fas fa-user text-xl"></i>
            </span>
            <span class="font-medium">My Profile</span>
        </a>

        <!-- Conditionally available items -->
        <?php if ($studentStatus === 'active'): ?>
            <a href="courses.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-book text-xl"></i>
                </span>
                <span class="font-medium">My Courses</span>
            </a>

            <a href="#" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-tasks text-xl"></i>
                </span>
                <span class="font-medium">Assignments</span>
            </a>



            <a href="#" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-calendar text-xl"></i>
                </span>
                <span class="font-medium">Timetable</span>
            </a>

            <!-- Add Elections Link -->
            <a href="elections.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-vote-yea text-xl"></i>
                </span>
                <span class="font-medium">Elections</span>
            </a>
        <?php endif; ?>

        <!-- Always available items -->
        <a href="payments.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
            <span class="mr-4">
                <i class="fas fa-credit-card text-xl"></i>
            </span>
            <span class="font-medium">Payments</span>
        </a>

        <a href="#" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
            <span class="mr-4">
                <i class="fas fa-book-open text-xl"></i>
            </span>
            <span class="font-medium">Resources</span>
        </a>

        <?php if ($studentStatus === 'active'): ?>
            <a href="#" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-book-reader text-xl"></i>
                </span>
                <span class="font-medium">Library</span>
            </a>

            <a href="#" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-graduation-cap text-xl"></i>
                </span>
                <span class="font-medium">Academic Support</span>
            </a>

            <a href="organizations.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-blue-500 hover:shadow-md">
                <span class="mr-4">
                    <i class="fas fa-users text-xl"></i>
                </span>
                <span class="font-medium">Student Organizations</span>
            </a>
        <?php endif; ?>

        <a href="../logout.php" class="menu-item flex items-center px-4 py-3 rounded-lg transition-all duration-200 hover:bg-red-500 hover:shadow-md mt-4">
            <span class="mr-4">
                <i class="fas fa-sign-out-alt text-xl"></i>
            </span>
            <span class="font-medium">Logout</span>
        </a>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-blue-500 mt-auto">
        <div class="text-center text-blue-100 text-sm">
            <p>© 2025 MyCamp Portal</p>
            <p class="text-xs mt-1">Student Edition v1.0</p>
        </div>
    </div>
</nav>