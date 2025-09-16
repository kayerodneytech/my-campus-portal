<?php
/**
 * Admin Dashboard
 *
 * Features:
 * - Comprehensive statistics overview
 * - Recent activities feed
 * - System status monitoring
 * - Quick access to common admin tasks
 * - Mobile-responsive design
 * - Interactive UI elements
 * - Performance optimized queries
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect if not an admin or not logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

/**
 * Get count of records from a table with optional conditions
 *
 * @param string $table Table name
 * @param string|null $condition Optional WHERE condition
 * @return int Count of records
 */
function getCount($table, $condition = null) {
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM $table" . ($condition ? " WHERE $condition" : "");
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

/**
 * Get count of pending records from a table
 *
 * @param string $table Table name
 * @param string $statusColumn Status column name (default: 'status')
 * @return int Count of pending records
 */
function getPendingCount($table, $statusColumn = 'status') {
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM $table WHERE $statusColumn = 'pending'";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

// =============================================
// DASHBOARD STATISTICS
// =============================================

// Get statistics with optimized queries
$totalStudents = getCount('students');
$pendingStudents = getPendingCount('students');
$totalCourses = getCount('courses');
$totalLecturers = getCount('users', "role = 'lecturer'");
$pendingApplications = getPendingCount('course_applications');

// Payment statistics with single query
$paymentStats = $conn->query("
    SELECT
        COUNT(*) as total_payments,
        COALESCE(SUM(amount), 0) as total_revenue,
        COUNT(DISTINCT student_id) as unique_payers
    FROM payments
    WHERE status = 'completed'
")->fetch_assoc();

$totalPayments = $paymentStats['total_payments'];
$revenue = $paymentStats['total_revenue'];
$uniquePayers = $paymentStats['unique_payers'];

// =============================================
// RECENT ACTIVITIES (LAST 7 DAYS)
// =============================================
$recentActivities = $conn->query("
    SELECT
        'student' as type,
        CONCAT('New student registration: ', u.first_name, ' ', u.last_name) as description,
        s.created_at,
        u.id as user_id
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

    UNION ALL

    SELECT
        'payment' as type,
        CONCAT('Payment received: $', p.amount, ' from ', u.first_name, ' ', u.last_name) as description,
        p.created_at,
        p.student_id as user_id
    FROM payments p
    JOIN users u ON p.student_id = u.id
    WHERE p.status = 'completed' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

    UNION ALL

    SELECT
        'application' as type,
        CONCAT('Course application: ', c.course_name, ' by ', u.first_name, ' ', u.last_name) as description,
        ca.application_date as created_at,
        ca.student_id as user_id
    FROM course_applications ca
    JOIN courses c ON ca.course_id = c.id
    JOIN users u ON ca.student_id = u.id
    WHERE ca.application_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)

    UNION ALL

    SELECT
        'lecturer' as type,
        CONCAT('New lecturer: ', u.first_name, ' ', u.last_name, ' (', lp.employee_id, ')') as description,
        lp.created_at,
        lp.user_id
    FROM lecturer_profiles lp
    JOIN users u ON lp.user_id = u.id
    WHERE lp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

    ORDER BY created_at DESC
    LIMIT 10
");

// =============================================
// SYSTEM STATUS MONITORING
// =============================================
$systemStatus = [
    'database' => [
        'status' => $conn->ping(),
        'message' => $conn->ping() ? 'Connected' : 'Connection failed',
        'icon' => 'fa-database',
        'color' => $conn->ping() ? 'green' : 'red'
    ],
    'storage' => [
        'status' => disk_free_space("/") > 104857600, // More than 100MB free
        'message' => disk_free_space("/") > 104857600 ? 'Sufficient' : 'Low disk space',
        'icon' => 'fa-hdd',
        'color' => disk_free_space("/") > 104857600 ? 'green' : 'yellow'
    ],
    'memory' => [
        'status' => true, // Placeholder - would use system calls in production
        'message' => 'Normal',
        'icon' => 'fa-memory',
        'color' => 'green'
    ],
    'uptime' => [
        'status' => true, // Placeholder
        'message' => '100%',
        'icon' => 'fa-server',
        'color' => 'green'
    ]
];

// =============================================
// ENROLLMENT STATISTICS (LAST 30 DAYS)
// =============================================
$enrollmentStats = $conn->query("
    SELECT
        DATE(ce.enrollment_date) as date,
        COUNT(*) as count
    FROM class_enrollments ce
    WHERE ce.enrollment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(ce.enrollment_date)
    ORDER BY date
");

$enrollmentData = [];
$enrollmentLabels = [];
if ($enrollmentStats) {
    while ($row = $enrollmentStats->fetch_assoc()) {
        $enrollmentData[] = $row['count'];
        $enrollmentLabels[] = date('M j', strtotime($row['date']));
    }
}

// =============================================
// REVENUE STATISTICS (LAST 6 MONTHS)
// =============================================
$revenueStats = $conn->query("
    SELECT
        DATE_FORMAT(paid_at, '%Y-%m') as month,
        SUM(amount) as total
    FROM payments
    WHERE status = 'completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
    ORDER BY month
");

$revenueData = [];
$revenueLabels = [];
if ($revenueStats) {
    while ($row = $revenueStats->fetch_assoc()) {
        $revenueData[] = $row['total'];
        $revenueLabels[] = date('M \'y', strtotime($row['month'] . '-01'));
    }
}
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

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        /* Mobile responsiveness */
        @media (max-width: 767px) {
            .mobile-hidden {
                display: none !important;
            }

            .mobile-block {
                display: block !important;
            }

            .mobile-full {
                width: 100% !important;
            }

            .mobile-mt-4 {
                margin-top: 1rem !important;
            }

            .mobile-text-sm {
                font-size: 0.875rem !important;
            }

            .mobile-p-4 {
                padding: 1rem !important;
            }

            .mobile-grid-cols-1 {
                grid-template-columns: 1fr !important;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                    <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 mobile-hidden">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="text-gray-600 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span class="mobile-hidden">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard Overview</h1>
                    <p class="text-gray-600 mobile-text-sm">Welcome to the administration panel</p>
                </div>
                <div class="text-sm text-gray-500">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Students Card -->
            <a href="students.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg block">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-graduate text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($totalStudents); ?></p>
                        <?php if ($pendingStudents > 0): ?>
                            <p class="text-xs text-yellow-600 mt-1"><?php echo number_format($pendingStudents); ?> pending</p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <!-- Courses Card -->
            <a href="courses.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg block">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-book text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Courses</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($totalCourses); ?></p>
                    </div>
                </div>
            </a>

            <!-- Lecturers Card -->
            <a href="lecturers.php" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg block">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Lecturers</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($totalLecturers); ?></p>
                    </div>
                </div>
            </a>

            <!-- Revenue Card -->
            <div class="stat-card bg-white rounded-xl shadow-md p-6 block">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($revenue, 2); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo number_format($totalPayments); ?> payments from <?php echo number_format($uniquePayers); ?> students</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Quick Actions -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions Card -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="students.php?status=pending" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user-check text-white"></i>
                            </div>
                            <div>
                                <span class="font-medium">Approve Students</span>
                                <span class="block text-sm text-gray-500"><?php echo number_format($pendingStudents); ?> pending</span>
                            </div>
                        </a>

                        <a href="courses.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-book text-white"></i>
                            </div>
                            <span>Manage Courses</span>
                        </a>

                        <a href="lecturers.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-chalkboard-teacher text-white"></i>
                            </div>
                            <span>Manage Lecturers</span>
                        </a>
<a href="elections.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mr-3">
        <i class="fas fa-vote-yea text-white"></i>
    </div>
    <span>Manage Elections</span>
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

                <!-- System Status Card -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">System Status</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($systemStatus as $key => $status): ?>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3
                                    <?php echo 'bg-' . $status['color'] . '-100'; ?>">
                                    <i class="fas <?php echo $status['icon']; ?> text-<?php echo $status['color']; ?>-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium capitalize"><?php echo str_replace('_', ' ', $key); ?></p>
                                    <p class="text-sm text-<?php echo $status['color']; ?>-600">
                                        <?php echo $status['message']; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content (Recent Activities and Charts) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Activities -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
                        <a href="activity.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>

                    <?php if ($recentActivities && $recentActivities->num_rows > 0): ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                            <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                <a href="
                                    <?php
                                    if ($activity['type'] === 'student') echo "students.php?search=" . urlencode($activity['user_id']);
                                    elseif ($activity['type'] === 'lecturer') echo "lecturers.php?search=" . urlencode($activity['user_id']);
                                    else echo "#";
                                    ?>
                                " class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3
                                        <?php
                                        if ($activity['type'] === 'payment') echo 'bg-green-100';
                                        elseif ($activity['type'] === 'student') echo 'bg-blue-100';
                                        elseif ($activity['type'] === 'application') echo 'bg-yellow-100';
                                        elseif ($activity['type'] === 'lecturer') echo 'bg-purple-100';
                                        ?>">
                                        <i class="fas
                                            <?php
                                            if ($activity['type'] === 'payment') echo 'fa-dollar-sign text-green-600';
                                            elseif ($activity['type'] === 'student') echo 'fa-user-graduate text-blue-600';
                                            elseif ($activity['type'] === 'application') echo 'fa-clipboard-list text-yellow-600';
                                            elseif ($activity['type'] === 'lecturer') echo 'fa-chalkboard-teacher text-purple-600';
                                            ?>">
                                        </i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No recent activities</p>
                    <?php endif; ?>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Enrollment Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Enrollment Statistics (Last 30 Days)</h2>
                        <div class="h-64">
                            <canvas id="enrollmentChart"></canvas>
                        </div>
                    </div>

                    <!-- Revenue Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Revenue Overview (Last 6 Months)</h2>
                        <div class="h-64">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Course Applications -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Course Applications</h3>
                        <p class="text-2xl font-bold text-gray-800 mt-2"><?php echo number_format($pendingApplications); ?></p>
                        <p class="text-sm text-gray-500">pending approval</p>
                    </div>
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-yellow-600"></i>
                    </div>
                </div>
                <a href="applications.php" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800">
                    View all applications →
                </a>
            </div>

            <!-- System Notifications -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">System Notifications</h3>
                        <p class="text-sm text-gray-500 mt-1">No new notifications</p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-bell text-blue-600"></i>
                    </div>
                </div>
                <a href="notifications.php" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800">
                    View notifications →
                </a>
            </div>

            <!-- Help Center -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Help Center</h3>
                        <p class="text-sm text-gray-500 mt-1">Need assistance with the admin panel?</p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-question-circle text-green-600"></i>
                    </div>
                </div>
                <a href="help.php" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800">
                    Visit help center →
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Enrollment Chart
            const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
            const enrollmentChart = new Chart(enrollmentCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($enrollmentLabels); ?>,
                    datasets: [{
                        label: 'Daily Enrollments',
                        data: <?php echo json_encode($enrollmentData); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' enrollments';
                                }
                            }
                        }
                    }
                }
            });

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($revenueLabels); ?>,
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: <?php echo json_encode($revenueData); ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });

            // Hover effects to stat cards
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
