<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin and logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
function getCount($table) {
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM $table WHERE status != 'deleted'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getPendingCount($table) {
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM $table WHERE status = 'pending'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Get statistics
$totalStudents = getCount('students');
$pendingStudents = getPendingCount('students');
$totalCourses = getCount('courses');
$totalLecturers = getCount('users', "role = 'lecturer'");
$pendingApplications = getPendingCount('course_applications');
$totalPayments = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'completed'")->fetch_assoc()['total'];
$revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'")->fetch_assoc()['total'];

// Get recent activities
$recentActivities = $conn->query("
    SELECT 'student' as type, CONCAT('New student registration: ', u.first_name, ' ', u.last_name) as description, s.created_at 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'payment' as type, CONCAT('Payment received: $', amount) as description, created_at 
    FROM payments 
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'application' as type, CONCAT('Course application: ', c.course_name) as description, ca.application_date as created_at
    FROM course_applications ca 
    JOIN courses c ON ca.course_id = c.id 
    WHERE ca.application_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC 
    LIMIT 10
");

// Get system status
$systemStatus = [
    'database' => $conn->ping(),
    'storage' => disk_free_space("/") > 104857600, // More than 100MB free
    'memory' => true, // Placeholder
    'uptime' => true  // Placeholder
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MyCamp Portal</title>
    
    <!-- Local Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                    <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="text-gray-600 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="text-gray-600">Welcome to the administration panel</p>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Students Card -->
            <a href="students.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-graduate text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $totalStudents; ?></p>
                        <?php if ($pendingStudents > 0): ?>
                            <p class="text-xs text-yellow-600"><?php echo $pendingStudents; ?> pending approval</p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <!-- Courses Card -->
            <a href="courses.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-book text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Courses</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $totalCourses; ?></p>
                    </div>
                </div>
            </a>

            <!-- Lecturers Card -->
            <a href="lecturers.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Lecturers</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $totalLecturers; ?></p>
                    </div>
                </div>
            </a>

            <!-- Revenue Card -->
            <div class="stat-card bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($revenue, 2); ?></p>
                        <p class="text-xs text-gray-500"><?php echo $totalPayments; ?> payments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Quick Actions -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="students.php?filter=pending" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user-check text-white"></i>
                            </div>
                            <span>Approve Students (<?php echo $pendingStudents; ?>)</span>
                        </a>
                        
                        <a href="applications.php" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-clipboard-list text-white"></i>
                            </div>
                            <span>Course Applications (<?php echo $pendingApplications; ?>)</span>
                        </a>
                        
                        <a href="courses.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-book text-white"></i>
                            </div>
                            <span>Manage Courses</span>
                        </a>
                        
                        <a href="users.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <span>Manage Users</span>
                        </a>
                        
                        <a href="reports.php" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                            <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-chart-bar text-white"></i>
                            </div>
                            <span>View Reports</span>
                        </a>
                        
                        <a href="settings.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-cog text-white"></i>
                            </div>
                            <span>System Settings</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
                        <a href="activities.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if ($recentActivities && $recentActivities->num_rows > 0): ?>
                            <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 
                                        <?php echo $activity['type'] === 'payment' ? 'bg-green-100' : ''; ?>
                                        <?php echo $activity['type'] === 'student' ? 'bg-blue-100' : ''; ?>
                                        <?php echo $activity['type'] === 'application' ? 'bg-yellow-100' : ''; ?>">
                                        <i class="fas 
                                            <?php echo $activity['type'] === 'payment' ? 'fa-dollar-sign text-green-600' : ''; ?>
                                            <?php echo $activity['type'] === 'student' ? 'fa-user text-blue-600' : ''; ?>
                                            <?php echo $activity['type'] === 'application' ? 'fa-clipboard-list text-yellow-600' : ''; ?>">
                                        </i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">No recent activities</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">System Status</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 
                                <?php echo $systemStatus['database'] ? 'bg-green-100' : 'bg-red-100'; ?>">
                                <i class="fas fa-database <?php echo $systemStatus['database'] ? 'text-green-600' : 'text-red-600'; ?>"></i>
                            </div>
                            <div>
                                <p class="font-medium">Database</p>
                                <p class="text-sm <?php echo $systemStatus['database'] ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $systemStatus['database'] ? 'Online' : 'Offline'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 
                                <?php echo $systemStatus['storage'] ? 'bg-green-100' : 'bg-yellow-100'; ?>">
                                <i class="fas fa-hdd <?php echo $systemStatus['storage'] ? 'text-green-600' : 'text-yellow-600'; ?>"></i>
                            </div>
                            <div>
                                <p class="font-medium">Storage</p>
                                <p class="text-sm <?php echo $systemStatus['storage'] ? 'text-green-600' : 'text-yellow-600'; ?>">
                                    <?php echo $systemStatus['storage'] ? 'Normal' : 'Low'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-100">
                                <i class="fas fa-memory text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Memory</p>
                                <p class="text-sm text-green-600">Normal</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-100">
                                <i class="fas fa-server text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">Uptime</p>
                                <p class="text-sm text-green-600">100%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Enrollment Stats -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Enrollment Statistics</h2>
                <div class="text-center py-8">
                    <p class="text-gray-500">Enrollment charts will be displayed here</p>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Revenue Overview</h2>
                <div class="text-center py-8">
                    <p class="text-gray-500">Revenue charts will be displayed here</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Simple animations
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to stat cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-3px)';
                card.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 1px 3px 0 rgba(0, 0, 0, 0.1)';
            });
        });
    });
    </script>
</body>
</html>