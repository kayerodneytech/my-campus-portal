<style>
    @font-face {
        font-family: 'Poppins';
        src: url('../assets/fonts/poppins.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    * {
        font-family: 'Poppins', sans-serif;
    }

    .sidebar {
        width: 250px;
        transition: all 0.3s ease;
    }

    .main-content {
        margin-left: 250px;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .sidebar {
            margin-left: -250px;
        }

        .main-content {
            margin-left: 0;
        }

        .sidebar.active {
            margin-left: 0;
        }

        .main-content.active {
            margin-left: 250px;
        }
    }

    .notification-dot {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: #EF4444;
        color: white;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .course-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
</style>

<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize lecturer data if not already set
if (!isset($lecturer) || empty($lecturer)) {
    require_once '../includes/config.php';
    require_once '../includes/auth.php';

    // Only fetch lecturer data if user is logged in
    if (isset($_SESSION['user_id'])) {
        $lecturer_id = $_SESSION['user_id'];
        $lecturer_query = $conn->prepare("
            SELECT u.*, lp.*, d.name as department_name
            FROM users u
            JOIN lecturer_profiles lp ON u.id = lp.user_id
            LEFT JOIN departments d ON lp.department = d.id
            WHERE u.id = ?
        ");
        $lecturer_query->bind_param("i", $lecturer_id);
        $lecturer_query->execute();
        $lecturer = $lecturer_query->get_result()->fetch_assoc();
    } else {
        // Default values if user is not logged in
        $lecturer = [
            'first_name' => 'Guest',
            'last_name' => '',
            'title' => '',
            'department_name' => '',
            'department' => ''
        ];
    }
}
?>

<div class="sidebar bg-blue-800 text-white fixed h-full overflow-y-auto z-10">
    <div class="p-4 border-b border-blue-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                <h1 class="text-xl font-bold">MyCamp Portal</h1>
            </div>
            <button id="sidebarToggle" class="md:hidden text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="p-4 border-b border-blue-700">
        <div class="flex items-center">
            <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center text-white text-lg font-bold">
                <?php echo strtoupper(substr($lecturer['first_name'] ?? 'G', 0, 1) . substr($lecturer['last_name'] ?? '', 0, 1)); ?>
            </div>
            <div class="ml-3">
                <h2 class="font-semibold">
                    <?php echo htmlspecialchars(($lecturer['title'] ?? '') ? $lecturer['title'] . ' ' : '') .
                        htmlspecialchars(($lecturer['first_name'] ?? 'Guest') . ' ' . ($lecturer['last_name'] ?? '')); ?>
                </h2>
                <p class="text-blue-200 text-sm">
                    <?php echo htmlspecialchars($lecturer['department_name'] ?? $lecturer['department'] ?? 'Department'); ?>
                </p>
            </div>
        </div>
    </div>

    <nav class="mt-4">
        <a href="index.php" class="flex items-center px-4 py-3 text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?>">
            <i class="fas fa-tachometer-alt w-5 mr-3"></i>
            Dashboard
        </a>

        <a href="classes.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'bg-blue-700 text-white' : ''; ?>">
            <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
            My Classes
        </a>

        <a href="announcements.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'bg-blue-700 text-white' : ''; ?>">
            <i class="fas fa-bullhorn w-5 mr-3"></i>
            Announcements
        </a>

        <a href="schedule.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'bg-blue-700 text-white' : ''; ?>">
            <i class="fas fa-calendar-alt w-5 mr-3"></i>
            Schedule
        </a>
        <a href="profile.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-700 text-white' : ''; ?>">
            <i class="fas fa-user w-5 mr-3"></i>
            Profile
        </a>
        <a href="../logout.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 hover:text-white">
            <i class="fas fa-sign-out-alt w-5 mr-3"></i>
            Logout
        </a>
    </nav>
</div>