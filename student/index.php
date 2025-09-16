<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Get student details
$studentId = $_SESSION['user_id'];
$studentQuery = $conn->prepare("SELECT s.*, u.first_name, u.last_name, u.email, s.phone 
                               FROM students s 
                               JOIN users u ON s.user_id = u.id 
                               WHERE s.user_id = ?");
$studentQuery->bind_param("i", $studentId);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$student = $studentResult->fetch_assoc();
$studentStatus = $student ? $student['status'] : 'pending';

// Get enrolled courses count
$coursesQuery = $conn->prepare("SELECT COUNT(*) as course_count 
                               FROM course_applications 
                               WHERE student_id = ? AND status = 'approved'");
$coursesQuery->bind_param("i", $studentId);
$coursesQuery->execute();
$coursesResult = $coursesQuery->get_result();
$coursesCount = $coursesResult->fetch_assoc()['course_count'];

// Get assignments count
$assignmentsQuery = $conn->prepare("SELECT COUNT(*) as assignment_count 
                                   FROM assignments 
                                   WHERE id IN (
                                       SELECT id FROM course_applications 
                                       WHERE student_id = ? AND status = 'approved'
                                   )");
$assignmentsQuery->bind_param("i", $studentId);
$assignmentsQuery->execute();
$assignmentsResult = $assignmentsQuery->get_result();
$assignmentsCount = $assignmentsResult->fetch_assoc()['assignment_count'];

// Get payments info
$paymentsQuery = $conn->prepare("SELECT COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as total_paid 
                                FROM payments 
                                WHERE student_id = ? AND status = 'completed'");
$paymentsQuery->bind_param("i", $studentId);
$paymentsQuery->execute();
$paymentsResult = $paymentsQuery->get_result();
$paymentsData = $paymentsResult->fetch_assoc();
$paymentCount = $paymentsData['payment_count'];
$totalPaid = $paymentsData['total_paid'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - MyCamp Portal</title>
    
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
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Student Sidebar -->
   <?php include 'sidebar.php'?>
    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:ml-0">
        <!-- Mobile Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 lg:hidden">
            <div class="flex items-center justify-between p-4">
                <button id="mobile-menu-button" class="text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-8 w-8 rounded-full">
                <div class="w-6"></div> <!-- Spacer for balance -->
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-8">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-800 to-blue-600 rounded-xl shadow-md text-white p-6 mb-8">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($student['first_name']); ?>!</h1>
                        <p class="text-blue-100"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <div class="mt-4 md:mt-0 text-center md:text-right">
                        <div class="text-sm">Account Status</div>
                        <div class="text-xl font-bold"><?php echo ucfirst($studentStatus); ?></div>
                        <div class="text-xs mt-1">Account ID: <?php echo htmlspecialchars($student['application_number']); ?></div>
                    </div>
                </div>
            </div>

            <?php if ($studentStatus === 'pending'): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-yellow-600 text-2xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-yellow-800">Application Under Review</h3>
                            <p class="text-yellow-600">Your application is pending approval. Some features will be available once your application is approved.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6 card-hover <?php echo $studentStatus !== 'active' ? 'opacity-50' : ''; ?>">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-book text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Enrolled Courses</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $coursesCount; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 card-hover <?php echo $studentStatus !== 'active' ? 'opacity-50' : ''; ?>">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-tasks text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Pending Assignments</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $assignmentsCount; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-file-invoice-dollar text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Payments</p>
                            <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($totalPaid, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo ucfirst($studentStatus); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Activity</h2>
                    <div class="space-y-4">
                        <?php
                        $activityQuery = $conn->prepare("
                            SELECT 'payment' as type, CONCAT('Payment of $', amount) as description, created_at 
                            FROM payments 
                            WHERE student_id = ? AND status = 'completed'
                            UNION ALL
                            SELECT 'application' as type, 'Course application submitted' as description, application_date as created_at
                            FROM course_applications 
                            WHERE student_id = ?
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $activityQuery->bind_param("ii", $studentId, $studentId);
                        $activityQuery->execute();
                        $activityResult = $activityQuery->get_result();
                        
                        if ($activityResult->num_rows > 0) {
                            while ($activity = $activityResult->fetch_assoc()) {
                                $icon = $activity['type'] === 'payment' ? 'fa-credit-card' : 'fa-book';
                                $color = $activity['type'] === 'payment' ? 'text-green-600' : 'text-blue-600';
                                echo '
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                        <i class="fas ' . $icon . ' ' . $color . '"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800">' . htmlspecialchars($activity['description']) . '</p>
                                        <p class="text-sm text-gray-500">' . date('M j, Y g:i A', strtotime($activity['created_at'])) . '</p>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '<p class="text-gray-500 text-center py-4">No recent activity</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 gap-3">
                        <a href="profile.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <span class="font-medium">Update Profile</span>
                        </a>
                        
                        <?php if ($studentStatus === 'active'): ?>
                            <a href="courses.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-book text-white"></i>
                                </div>
                                <span class="font-medium">View Courses</span>
                            </a>
                            
                            <a href="timetable.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-calendar text-white"></i>
                                </div>
                                <span class="font-medium">View Timetable</span>
                            </a>
                        <?php endif; ?>
                        
                        <a href="payments.php" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                            <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-credit-card text-white"></i>
                            </div>
                            <span class="font-medium">Make Payment</span>
                        </a>
                        
                        <a href="resources.php" class="flex items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                            <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-book-open text-white"></i>
                            </div>
                            <span class="font-medium">Access Resources</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Features Status -->
            <?php if ($studentStatus === 'pending'): ?>
                <div class="mt-8 bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Features Available After Approval</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-500 mr-3"></i>
                            <span>Course Enrollment & Management</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-500 mr-3"></i>
                            <span>Assignment Submission</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-500 mr-3"></i>
                            <span>Grade Viewing</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-500 mr-3"></i>
                            <span>Library Access</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-500 mr-3"></i>
                            <span>Online Classes</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-500 mr-3"></i>
                            <span>Student Organizations</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <script>
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('student-sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');
        
        // Toggle mobile menu
        mobileMenuButton.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
            mobileOverlay.classList.toggle('hidden');
        });
        
        // Close mobile menu when clicking overlay
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.add('hidden');
            mobileOverlay.classList.add('hidden');
        });
        
        // Close mobile menu when clicking a menu item
        document.querySelectorAll('.menu-item').forEach(function(item) {
            item.addEventListener('click', function() {
                if (window.innerWidth < 1024) {
                    sidebar.classList.add('hidden');
                    mobileOverlay.classList.add('hidden');
                }
            });
        });
    });
    </script>
</body>
</html>