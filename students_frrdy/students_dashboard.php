<?php

session_start();
require_once 'dbconnect.php';

// If user is logged in and is a student, use their info; otherwise, use demo/anonymous
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['full_name'];
} else {
    $user_id = null;
    $user_name = 'Guest Student';
}

$stats = [
    'courses' => 0,
    'assignments' => 0,
    'avg_grade' => 0,
    'attendance' => 0,
    'pending_payments' => 0,
    'borrowed_books' => 0
];

if ($is_logged_in) {
    // Get enrolled courses count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($stats['courses']);
    $stmt->fetch();
    $stmt->close();

    // Get pending assignments count
    $query = "SELECT COUNT(*) FROM course_content cc 
        JOIN enrollments e ON cc.course_id = e.course_id 
        WHERE e.student_id = ? AND cc.type = 'assignment' 
        AND cc.id NOT IN (SELECT content_id FROM student_submissions WHERE student_id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($stats['assignments']);
    $stmt->fetch();
    $stmt->close();

    // Get average grade (numeric only)
    $query = "SELECT AVG(CAST(grade AS DECIMAL(10,2))) FROM grades WHERE student_id = ? AND grade REGEXP '^[0-9]+$'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($avg_grade);
    $stmt->fetch();
    $stats['avg_grade'] = $avg_grade ? round($avg_grade, 1) : 0;
    $stmt->close();

    // Get attendance percentage
    $query = "SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(*) as total
        FROM attendance WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($present, $total);
    $stmt->fetch();
    $stats['attendance'] = ($total > 0) ? round(($present / $total) * 100, 1) : 0;
    $stmt->close();

    // Get pending payments count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE student_id = ? AND status = 'pending'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($stats['pending_payments']);
    $stmt->fetch();
    $stmt->close();

    // Get borrowed books count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM borrows WHERE student_id = ? AND status = 'borrowed'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($stats['borrowed_books']);
    $stmt->fetch();
    $stmt->close();

    // Get recent activities (limit 5)
    $recent_activities = [];
    // Assignment submissions
    $query = "SELECT 'assignment' as type, 'Assignment Submitted' as title, submitted_at as date FROM student_submissions WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($type, $title, $date);
    while ($stmt->fetch()) {
        $recent_activities[] = ['type' => $type, 'title' => $title, 'date' => $date];
    }
    $stmt->close();
    // Instead, just add a grade activity with no date
    $query = "SELECT 'grade' as type, 'Grade Received' as title FROM grades WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($type, $title);
    while ($stmt->fetch()) {
        $recent_activities[] = ['type' => $type, 'title' => $title, 'date' => null];
    }
    $stmt->close();
    // Payments (use created_at)
    $query = "SELECT 'payment' as type, 'Payment Made' as title, created_at as date FROM payments WHERE student_id = ? AND status = 'paid'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($type, $title, $date);
    while ($stmt->fetch()) {
        $recent_activities[] = ['type' => $type, 'title' => $title, 'date' => $date];
    }
    $stmt->close();
    // Sort and limit to 5
    usort($recent_activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    $recent_activities = array_slice($recent_activities, 0, 5);

    // Get notifications (limit 3)
    $notifications = [];
    $query = "SELECT message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($notif_message, $notif_date);
    while ($stmt->fetch()) {
        $notifications[] = ['message' => $notif_message, 'created_at' => $notif_date];
    }
    $stmt->close();
} else {
    // Demo/anonymous data for guests
    $stats = [
        'courses' => 3,
        'assignments' => 2,
        'avg_grade' => 85,
        'attendance' => 92,
        'pending_payments' => 1,
        'borrowed_books' => 0
    ];
    $recent_activities = [
        ['type' => 'assignment', 'title' => 'Assignment Submitted', 'date' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['type' => 'grade', 'title' => 'Grade Received', 'date' => date('Y-m-d H:i:s', strtotime('-3 days'))],
        ['type' => 'payment', 'title' => 'Payment Made', 'date' => date('Y-m-d H:i:s', strtotime('-5 days'))]
    ];
    $notifications = [
        ['message' => 'Welcome to the Student Dashboard! Explore features as a guest.', 'created_at' => date('Y-m-d H:i:s')]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | MyCamp Portal</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1>Student Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($user_name); ?></h3>
                    <span>Student</span>
                </div>
            </div>
            <?php if ($is_logged_in): ?>
                <a href="logout.php" class="logout-btn" style="margin-left:20px; color:#fff; background:#4e54c8; padding:8px 18px; border-radius:6px; text-decoration:none; font-weight:600;">Logout</a>
            <?php endif; ?>
        </div>

        <!-- Dashboard Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon courses">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?php echo $stats['courses']; ?></div>
                <div class="stat-label">Enrolled Courses</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon assignments">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-number"><?php echo $stats['assignments']; ?></div>
                <div class="stat-label">Pending Assignments</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon grades">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo $stats['avg_grade']; ?>%</div>
                <div class="stat-label">Average Grade</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon attendance">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $stats['attendance']; ?>%</div>
                <div class="stat-label">Attendance Rate</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon payments">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-number"><?php echo $stats['pending_payments']; ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon library">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-number"><?php echo $stats['borrowed_books']; ?></div>
                <div class="stat-label">Borrowed Books</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title">Quick Actions</h2>
            <div class="action-buttons">
                <a href="courses.php" class="action-btn">
                    <i class="fas fa-book"></i>
                    View Courses
                </a>
                <a href="assignments.php" class="action-btn">
                    <i class="fas fa-tasks"></i>
                    Assignments
                </a>
                <a href="grades.php" class="action-btn">
                    <i class="fas fa-chart-line"></i>
                    View Grades
                </a>
                <a href="timetable.php" class="action-btn">
                    <i class="fas fa-calendar"></i>
                    Timetable
                </a>
                <a href="library.php" class="action-btn">
                    <i class="fas fa-book-open"></i>
                    Library
                </a>
                <a href="payments.php" class="action-btn">
                    <i class="fas fa-credit-card"></i>
                    Payments
                </a>
                <a href="accommodation.php" class="action-btn">
                    <i class="fas fa-home"></i>
                    Accommodation
                </a>
                <a href="src_voting.php" class="action-btn">
                    <i class="fas fa-vote-yea"></i>
                    SRC Voting
                </a>
                <a href="messages.php" class="action-btn">
                    <i class="fas fa-envelope"></i>
                    Messages
                </a>
            </div>
        </div>

        <!-- Recent Activities and Notifications -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Recent Activities -->
            <div class="recent-activities">
                <h2 class="section-title">Recent Activities</h2>
                <?php if (empty($recent_activities)): ?>
                    <p style="color: #bdbdbd; text-align: center; padding: 20px;">No recent activities</p>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php
                                switch ($activity['type']) {
                                    case 'assignment':
                                        echo '<i class="fas fa-tasks"></i>';
                                        break;
                                    case 'grade':
                                        echo '<i class="fas fa-chart-line"></i>';
                                        break;
                                    case 'payment':
                                        echo '<i class="fas fa-credit-card"></i>';
                                        break;
                                    default:
                                        echo '<i class="fas fa-bell"></i>';
                                }
                                ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Notifications -->
            <div class="notifications">
                <h2 class="section-title">Notifications</h2>
                <?php if (empty($notifications)): ?>
                    <p style="color: #bdbdbd; text-align: center; padding: 20px;">No new notifications</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item info">
                            <div class="notification-title">New Notification</div>
                            <div class="notification-text"><?php echo htmlspecialchars($notification['message']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
