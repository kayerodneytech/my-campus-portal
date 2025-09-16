<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';


// Get lecturer details
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

// Get lecturer's courses
$courses_query = $conn->prepare("
    SELECT c.*, ca.role, ca.academic_year, ca.semester
    FROM courses c
    JOIN course_assignments ca ON c.id = ca.course_id
    WHERE ca.lecturer_id = ? AND ca.academic_year = YEAR(CURDATE())
    ORDER BY c.course_code
");
$courses_query->bind_param("i", $lecturer_id);
$courses_query->execute();
$courses = $courses_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Get lecturer's classes for today and tomorrow
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$day_map = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$today_day = strtolower(date('l'));

$classes_query = $conn->prepare("
    SELECT cl.*, c.course_name, c.course_code
    FROM classes cl
    JOIN courses c ON cl.course_id = c.id
    WHERE cl.lecturer_id = ? AND cl.status = 'ongoing'
    AND cl.academic_year = YEAR(CURDATE()) 
    AND cl.semester = (
        CASE 
            WHEN MONTH(CURDATE()) BETWEEN 1 AND 4 THEN 'winter'
            WHEN MONTH(CURDATE()) BETWEEN 5 AND 7 THEN 'spring'
            WHEN MONTH(CURDATE()) BETWEEN 8 AND 9 THEN 'summer'
            ELSE 'fall'
        END
    )
");
$classes_query->bind_param("i", $lecturer_id);
$classes_query->execute();
$all_classes = $classes_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Filter classes for today
$today_classes = [];
foreach ($all_classes as $class) {
    $schedule = json_decode($class['schedule'], true);
    if (isset($schedule['days']) && in_array($today_day, $schedule['days'])) {
        $today_classes[] = $class;
    }
}

// Get assignment statistics
$assignments_query = $conn->prepare("
    SELECT 
        a.id,
        a.title,
        a.due_date,
        a.status,
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT CASE WHEN s.status = 'submitted' OR s.status = 'late' OR s.status = 'graded' THEN s.id END) as submitted,
        COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.id END) as graded
    FROM assignments a
    JOIN classes cl ON a.class_id = cl.id
    JOIN class_enrollments ce ON cl.id = ce.class_id
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND ce.student_id = s.student_id
    WHERE cl.lecturer_id = ? AND a.due_date >= CURDATE() - INTERVAL 30 DAY
    GROUP BY a.id
    ORDER BY a.due_date DESC
    LIMIT 5
");
$assignments_query->bind_param("i", $lecturer_id);
$assignments_query->execute();
$assignments = $assignments_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent announcements
$announcements_query = $conn->prepare("
    SELECT a.*, c.course_code, c.course_name
    FROM announcements a
    LEFT JOIN classes cl ON a.class_id = cl.id
    LEFT JOIN courses c ON cl.course_id = c.id
    WHERE a.lecturer_id = ? AND a.is_published = TRUE 
    AND (a.expires_at IS NULL OR a.expires_at >= NOW())
    ORDER BY a.created_at DESC
    LIMIT 5
");
$announcements_query->bind_param("i", $lecturer_id);
$announcements_query->execute();
$announcements = $announcements_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Get student counts for each course
$student_counts = [];
foreach ($courses as $course) {
    $count_query = $conn->prepare("
        SELECT COUNT(DISTINCT ce.student_id) as student_count
        FROM course_assignments ca
        JOIN classes cl ON ca.course_id = cl.course_id AND ca.lecturer_id = cl.lecturer_id
        JOIN class_enrollments ce ON cl.id = ce.class_id
        WHERE ca.course_id = ? AND ca.lecturer_id = ?
        AND cl.academic_year = YEAR(CURDATE())
        AND cl.semester = (
            CASE 
                WHEN MONTH(CURDATE()) BETWEEN 1 AND 4 THEN 'winter'
                WHEN MONTH(CURDATE()) BETWEEN 5 AND 7 THEN 'spring'
                WHEN MONTH(CURDATE()) BETWEEN 8 AND 9 THEN 'summer'
                ELSE 'fall'
            END
        )
    ");
    $count_query->bind_param("ii", $course['id'], $lecturer_id);
    $count_query->execute();
    $count_result = $count_query->get_result()->fetch_assoc();
    $student_counts[$course['id']] = $count_result['student_count'];
}

// Calculate statistics for dashboard
$total_students = array_sum($student_counts);
$total_courses = count($courses);
$total_assignments = count($assignments);
$ungraded_submissions = 0;

foreach ($assignments as $assignment) {
    $ungraded_submissions += ($assignment['submitted'] - $assignment['graded']);
}

// Get course performance data
$performance_data = [];
$performance_labels = [];
foreach ($courses as $course) {
    $performance_query = $conn->prepare("
        SELECT AVG(ce.final_grade) as avg_grade
        FROM class_enrollments ce
        JOIN classes cl ON ce.class_id = cl.id
        WHERE cl.course_id = ? AND cl.lecturer_id = ?
        AND ce.final_grade IS NOT NULL
        AND cl.academic_year = YEAR(CURDATE())
    ");
    $performance_query->bind_param("ii", $course['id'], $lecturer_id);
    $performance_query->execute();
    $performance_result = $performance_query->get_result()->fetch_assoc();
    
    $performance_data[] = $performance_result['avg_grade'] ? round($performance_result['avg_grade'], 1) : 0;
    $performance_labels[] = $course['course_code'];
}

// Get assignment status data
$assignment_status = ['graded' => 0, 'submitted' => 0, 'not_submitted' => 0, 'late' => 0];
foreach ($assignments as $assignment) {
    $assignment_status['graded'] += $assignment['graded'];
    $assignment_status['submitted'] += ($assignment['submitted'] - $assignment['graded']);
    $assignment_status['not_submitted'] += ($assignment['total_students'] - $assignment['submitted']);
    // Note: Late submissions are already included in 'submitted'
}

// Get notification counts
$notifications_query = $conn->prepare("
    SELECT COUNT(*) as count FROM notifications 
    WHERE user_id = ? AND is_read = FALSE
");
$notifications_query->bind_param("i", $lecturer_id);
$notifications_query->execute();
$notifications_count = $notifications_query->get_result()->fetch_assoc()['count'];

$messages_query = $conn->prepare("
    SELECT COUNT(*) as count FROM messages 
    WHERE recipient_id = ? AND read_at IS NULL
");
$messages_query->bind_param("i", $lecturer_id);
$messages_query->execute();
$messages_count = $messages_query->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - MyCamp Portal</title>
    
    <!-- Local Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
  
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'?>

    <!-- Main Content -->
    <div class="main-content flex-1 overflow-hidden">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex justify-between items-center px-6 py-3">
                <div class="flex items-center">
                    <button id="mobileSidebarToggle" class="md:hidden text-gray-600 mr-4">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-800">Lecturer Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="text-gray-600 hover:text-blue-600">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($notifications_count > 0): ?>
                                <span class="notification-dot"><?php echo $notifications_count; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                    <div class="relative">
                        <button class="text-gray-600 hover:text-blue-600">
                            <i class="fas fa-envelope text-xl"></i>
                            <?php if ($messages_count > 0): ?>
                                <span class="notification-dot"><?php echo $messages_count; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                    <div>
                        <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($lecturer['first_name']); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-6 overflow-y-auto" style="max-height: calc(100vh - 80px)">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_courses; ?></h3>
                            <p class="text-gray-600">Courses</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_students; ?></h3>
                            <p class="text-gray-600">Students</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-tasks text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_assignments; ?></h3>
                            <p class="text-gray-600">Assignments</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-red-100 text-red-600 mr-4">
                            <i class="fas fa-clipboard-check text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $ungraded_submissions; ?></h3>
                            <p class="text-gray-600">Submissions to Grade</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Upcoming Classes -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Today's Classes</h2>
                            <a href="schedule.php" class="text-blue-600 hover:text-blue-800 text-sm">View Full Schedule</a>
                        </div>
                        <div class="space-y-4">
                            <?php if (count($today_classes) > 0): ?>
                                <?php foreach ($today_classes as $class): ?>
                                    <?php 
                                    $schedule = json_decode($class['schedule'], true);
                                    $start_time = isset($schedule['start_time']) ? date('g:i A', strtotime($schedule['start_time'])) : 'TBA';
                                    ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($class['course_name']); ?></h3>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($class['course_code']); ?> - <?php echo htmlspecialchars($class['class_code']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-gray-800"><?php echo $start_time; ?></p>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($class['room'] ?: 'Room TBA'); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-calendar-day text-4xl mb-3"></i>
                                    <p>No classes scheduled for today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Recent Announcements</h2>
                            <a href="announcements.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                        </div>
                        <div class="space-y-4">
                            <?php if (count($announcements) > 0): ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="p-4 bg-blue-50 rounded-lg">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <p class="text-gray-600 text-sm">
                                            Posted <?php echo date('M j, g:i A', strtotime($announcement['created_at'])); ?>
                                            <?php if ($announcement['course_code']): ?>
                                                • <?php echo htmlspecialchars($announcement['course_code']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-gray-700 mt-2"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100) . (strlen($announcement['content']) > 100 ? '...' : '')); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-bullhorn text-4xl mb-3"></i>
                                    <p>No recent announcements</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Assignment Status -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Assignment Status</h2>
                        <a href="assignments.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                    </div>
                    <canvas id="assignmentChart" height="250"></canvas>
                </div>

                <!-- Course Performance -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Course Performance</h2>
                        <a href="grades.php" class="text-blue-600 hover:text-blue-800 text-sm">View Details</a>
                    </div>
                    <canvas id="performanceChart" height="250"></canvas>
                </div>
            </div>

            <!-- My Courses -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">My Courses</h2>
                    <a href="courses.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (count($courses) > 0): ?>
                        <?php 
                        $colors = ['blue', 'green', 'purple', 'yellow', 'indigo', 'pink'];
                        $color_index = 0;
                        ?>
                        <?php foreach ($courses as $course): ?>
                            <?php 
                            $color = $colors[$color_index % count($colors)];
                            $color_index++;
                            ?>
                            <div class="course-card bg-gradient-to-r from-<?php echo $color; ?>-50 to-<?php echo $color; ?>-100 rounded-xl shadow-md p-6 border border-<?php echo $color; ?>-200">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($course['course_code']); ?></h3>
                                    <span class="px-2 py-1 bg-<?php echo $color; ?>-200 text-<?php echo $color; ?>-800 text-xs font-semibold rounded-full">
                                        <?php echo ucfirst($course['semester']); ?> <?php echo $course['academic_year']; ?>
                                    </span>
                                </div>
                                <h4 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($course['description'], 0, 80) . (strlen($course['description']) > 80 ? '...' : '')); ?></p>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="text-gray-700 font-semibold"><?php echo $student_counts[$course['id']] ?? 0; ?> Students</span>
                                    </div>
                                    <a href="course.php?id=<?php echo $course['id']; ?>" class="text-<?php echo $color; ?>-600 hover:text-<?php echo $color; ?>-800">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-12 text-gray-500">
                            <i class="fas fa-book-open text-4xl mb-3"></i>
                            <p>No courses assigned for this semester</p>
                            <p class="text-sm mt-2">Contact your department administrator to get assigned to courses</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('mobileSidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Assignment Status Chart
        const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');
        const assignmentChart = new Chart(assignmentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Graded', 'Submitted', 'Not Submitted', 'Late'],
                datasets: [{
                    data: [
                        <?php echo $assignment_status['graded']; ?>,
                        <?php echo $assignment_status['submitted']; ?>,
                        <?php echo $assignment_status['not_submitted']; ?>,
                        <?php echo $assignment_status['late']; ?>
                    ],
                    backgroundColor: [
                        '#10B981',
                        '#3B82F6',
                        '#9CA3AF',
                        '#EF4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Course Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($performance_labels); ?>,
                datasets: [{
                    label: 'Average Grade',
                    data: <?php echo json_encode($performance_data); ?>,
                    backgroundColor: '#3B82F6',
                    borderWidth: 0,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>