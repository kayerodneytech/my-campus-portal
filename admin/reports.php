<?php
/**
 * Admin Reports Dashboard
 *
 * Features:
 * - System overview with key metrics
 * - Enrollment trends with charts
 * - Financial reporting
 * - CSV export functionality
 * - Date range filtering
 * - Department filtering
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect if not an admin or not logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$department_filter = isset($_GET['department']) ? intval($_GET['department']) : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Validate date range
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get all departments for filters
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// =============================================
// REPORT DATA FUNCTIONS
// =============================================

/**
 * Get enrollment statistics by date range
 */
function getEnrollmentStats($conn, $start_date, $end_date, $department_id = 0) {
    $query = "
        SELECT
            DATE(ce.enrollment_date) as date,
            COUNT(*) as count,
            d.name as department_name
        FROM class_enrollments ce
        JOIN classes c ON ce.class_id = c.id
        JOIN courses co ON c.course_id = co.id
        LEFT JOIN departments d ON co.department_id = d.id
        WHERE ce.enrollment_date BETWEEN ? AND ?
    ";

    $params = [$start_date, $end_date];
    $types = 'ss';

    if ($department_id > 0) {
        $query .= " AND co.department_id = ?";
        $params[] = $department_id;
        $types .= 'i';
    }

    $query .= " GROUP BY DATE(ce.enrollment_date), d.name ORDER BY date";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [
            'labels' => [],
            'datasets' => [],
            'has_data' => $result->num_rows > 0
        ];

        $department_data = [];

        while ($row = $result->fetch_assoc()) {
            $dept_name = $row['department_name'] ?: 'All Departments';

            if (!isset($department_data[$dept_name])) {
                $department_data[$dept_name] = [
                    'label' => $dept_name,
                    'data' => [],
                    'backgroundColor' => 'rgba(' . rand(50, 200) . ', ' . rand(50, 200) . ', ' . rand(50, 200) . ', 0.5)',
                    'borderColor' => 'rgba(' . rand(50, 200) . ', ' . rand(50, 200) . ', ' . rand(50, 200) . ', 1)'
                ];
            }

            $department_data[$dept_name]['data'][$row['date']] = $row['count'];
            if (!in_array($row['date'], $data['labels'])) {
                $data['labels'][] = $row['date'];
            }
        }

        // Sort dates chronologically
        sort($data['labels']);

        // Fill in missing dates with zeros
        foreach ($department_data as &$dept) {
            $filled_data = [];
            foreach ($data['labels'] as $date) {
                $filled_data[] = $dept['data'][$date] ?? 0;
            }
            $dept['data'] = $filled_data;
            $data['datasets'][] = $dept;
        }

        return $data;
    } catch (Exception $e) {
        error_log("Enrollment stats error: " . $e->getMessage());
        return [
            'labels' => [],
            'datasets' => [],
            'has_data' => false,
            'error' => 'Error loading enrollment data'
        ];
    }
}

/**
 * Get revenue statistics by date range
 */
function getRevenueStats($conn, $start_date, $end_date, $department_id = 0) {
    $query = "
        SELECT
            DATE(p.paid_at) as date,
            SUM(p.amount) as total,
            COUNT(*) as count,
            d.name as department_name
        FROM payments p
        JOIN students s ON p.student_id = s.user_id
        JOIN class_enrollments ce ON s.user_id = ce.student_id
        JOIN classes c ON ce.class_id = c.id
        JOIN courses co ON c.course_id = co.id
        LEFT JOIN departments d ON co.department_id = d.id
        WHERE p.status = 'completed'
        AND p.paid_at BETWEEN ? AND ?
    ";

    $params = [$start_date, $end_date];
    $types = 'ss';

    if ($department_id > 0) {
        $query .= " AND co.department_id = ?";
        $params[] = $department_id;
        $types .= 'i';
    }

    $query .= " GROUP BY DATE(p.paid_at), d.name ORDER BY date";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [
            'labels' => [],
            'datasets' => [],
            'has_data' => $result->num_rows > 0
        ];

        $department_data = [];

        while ($row = $result->fetch_assoc()) {
            $dept_name = $row['department_name'] ?: 'All Departments';

            if (!isset($department_data[$dept_name])) {
                $department_data[$dept_name] = [
                    'label' => $dept_name,
                    'data' => [],
                    'backgroundColor' => 'rgba(' . rand(50, 200) . ', ' . rand(50, 200) . ', ' . rand(50, 200) . ', 0.5)',
                    'borderColor' => 'rgba(' . rand(50, 200) . ', ' . rand(50, 200) . ', ' . rand(50, 200) . ', 1)'
                ];
            }

            $department_data[$dept_name]['data'][$row['date']] = $row['total'];
            if (!in_array($row['date'], $data['labels'])) {
                $data['labels'][] = $row['date'];
            }
        }

        // Sort dates chronologically
        sort($data['labels']);

        // Fill in missing dates with zeros
        foreach ($department_data as &$dept) {
            $filled_data = [];
            foreach ($data['labels'] as $date) {
                $filled_data[] = $dept['data'][$date] ?? 0;
            }
            $dept['data'] = $filled_data;
            $data['datasets'][] = $dept;
        }

        return $data;
    } catch (Exception $e) {
        error_log("Revenue stats error: " . $e->getMessage());
        return [
            'labels' => [],
            'datasets' => [],
            'has_data' => false,
            'error' => 'Error loading revenue data'
        ];
    }
}

/**
 * Get payment method distribution
 */
function getPaymentMethodDistribution($conn, $start_date, $end_date) {
    $query = "
        SELECT
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total
        FROM payments
        WHERE status = 'completed'
        AND paid_at BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY count DESC
    ";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [
            'labels' => [],
            'counts' => [],
            'totals' => [],
            'has_data' => $result->num_rows > 0
        ];

        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = ucfirst(str_replace('_', ' ', $row['payment_method']));
            $data['counts'][] = $row['count'];
            $data['totals'][] = $row['total'];
        }

        return $data;
    } catch (Exception $e) {
        error_log("Payment method error: " . $e->getMessage());
        return [
            'labels' => [],
            'counts' => [],
            'totals' => [],
            'has_data' => false,
            'error' => 'Error loading payment method data'
        ];
    }
}

// =============================================
// GENERATE REPORT DATA
// =============================================

// Generate all report data based on filters
$enrollmentStats = getEnrollmentStats($conn, $start_date, $end_date, $department_filter);
$revenueStats = getRevenueStats($conn, $start_date, $end_date, $department_filter);
$paymentMethods = getPaymentMethodDistribution($conn, $start_date, $end_date);

// Get key metrics for overview
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$total_lecturers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'lecturer'")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'completed'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Portal</title>

    <!-- Local Tailwind CSS -->
    <script src="../javascript/tailwindcss.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <!-- Tabulator for tables -->
    <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">
    <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>

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
            .chart-container {
                height: 250px !important;
            }
        }

        /* Chart container styling */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            min-height: 300px;
        }

        /* Card hover effects */
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* Tab styling */
        .tab-button {
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
            padding-bottom: 0.5rem;
        }
        .tab-button.active {
            border-bottom-color: #3b82f6;
            color: #2563eb;
            font-weight: 600;
        }
        .tab-button:hover:not(.active) {
            color: #60a5fa;
        }

        /* Empty state styling */
        .empty-state {
            background-color: #f8fafc;
            border: 1px dashed #e2e8f0;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
      <!-- Admin Header -->
   <?php include 'components/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Reports & Analytics</h1>
                    <p class="text-gray-600 mobile-text-sm">Comprehensive system reporting and data visualization</p>
                </div>
                <div class="text-sm text-gray-500">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <!-- Date Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" id="start_date" value="<?php echo $start_date; ?>"
                               class="px-4 py-2 border border-gray-300 rounded-lg w-full">
                        <span class="flex items-center px-2 text-gray-500">to</span>
                        <input type="date" id="end_date" value="<?php echo $end_date; ?>"
                               class="px-4 py-2 border border-gray-300 rounded-lg w-full">
                    </div>
                </div>

                <!-- Department Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select id="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="0">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Export Button -->
                <div>
                    <button id="export-btn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 w-full">
                        <i class="fas fa-file-export mr-2"></i> Export CSV
                    </button>
                </div>

                <!-- Apply Filters Button -->
                <div>
                    <button onclick="applyFilters()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-xl shadow-md p-1 mb-6">
            <div class="flex space-x-1">
                <button onclick="changeTab('overview')"
                        class="tab-button flex-1 py-3 text-center <?php echo $tab === 'overview' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie mr-2"></i> Overview
                </button>
                <button onclick="changeTab('enrollment')"
                        class="tab-button flex-1 py-3 text-center <?php echo $tab === 'enrollment' ? 'active' : ''; ?>">
                    <i class="fas fa-users mr-2"></i> Enrollment
                </button>
                <button onclick="changeTab('financial')"
                        class="tab-button flex-1 py-3 text-center <?php echo $tab === 'financial' ? 'active' : ''; ?>">
                    <i class="fas fa-dollar-sign mr-2"></i> Financial
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <!-- Overview Tab -->
            <?php if ($tab === 'overview'): ?>
                <div id="overview-tab">
                    <!-- Key Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Total Students -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-user-graduate text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Students</p>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_students); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Courses -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-book text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Courses</p>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_courses); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Lecturers -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-chalkboard-teacher text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Lecturers</p>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_lecturers); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-orange-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-dollar-sign text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Revenue</p>
                                    <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($total_revenue, 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment and Revenue Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Enrollment Chart -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-800">Enrollment Trends</h2>
                                <div class="flex space-x-2">
                                    <button onclick="exportChart('enrollmentChart', 'enrollment_trends')"
                                            class="text-gray-600 hover:text-blue-600">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                <?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?>
                            </p>
                            <div class="chart-container">
                                <?php if ($enrollmentStats['has_data']): ?>
                                    <canvas id="enrollmentChart"
                                            data-labels='<?php echo json_encode($enrollmentStats['labels']); ?>'
                                            data-datasets='<?php echo json_encode($enrollmentStats['datasets']); ?>'></canvas>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-chart-line text-gray-300 text-4xl mb-4"></i>
                                        <h3 class="text-gray-500 font-medium">No enrollment data available</h3>
                                        <p class="text-gray-400 text-sm mt-2">Try adjusting your date range or filters</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Revenue Chart -->
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-800">Revenue Trends</h2>
                                <div class="flex space-x-2">
                                    <button onclick="exportChart('revenueChart', 'revenue_trends')"
                                            class="text-gray-600 hover:text-blue-600">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                <?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?>
                            </p>
                            <div class="chart-container">
                                <?php if ($revenueStats['has_data']): ?>
                                    <canvas id="revenueChart"
                                            data-labels='<?php echo json_encode($revenueStats['labels']); ?>'
                                            data-datasets='<?php echo json_encode($revenueStats['datasets']); ?>'></canvas>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-dollar-sign text-gray-300 text-4xl mb-4"></i>
                                        <h3 class="text-gray-500 font-medium">No revenue data available</h3>
                                        <p class="text-gray-400 text-sm mt-2">Try adjusting your date range or filters</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">Payment Method Distribution</h2>
                            <div class="flex space-x-2">
                                <button onclick="exportChart('paymentChart', 'payment_methods')"
                                        class="text-gray-600 hover:text-blue-600">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <?php if ($paymentMethods['has_data']): ?>
                                <canvas id="paymentChart"
                                        data-labels='<?php echo json_encode($paymentMethods['labels']); ?>'
                                        data-totals='<?php echo json_encode($paymentMethods['totals']); ?>'></canvas>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-credit-card text-gray-300 text-4xl mb-4"></i>
                                    <h3 class="text-gray-500 font-medium">No payment method data available</h3>
                                    <p class="text-gray-400 text-sm mt-2">Try adjusting your date range or filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <!-- Enrollment Tab -->
            <?php elseif ($tab === 'enrollment'): ?>
                <div id="enrollment-tab">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Enrollment Report</h2>
                        <button onclick="exportCSV('enrollment')"
                                class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-file-csv mr-2"></i> Export CSV
                        </button>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">
                            Date Range: <?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?>
                        </h3>
                        <?php if ($department_filter > 0): ?>
                            <?php
                            $dept_name = $conn->query("SELECT name FROM departments WHERE id = $department_filter")->fetch_assoc()['name'];
                            ?>
                            <h3 class="text-lg font-semibold text-gray-700">Department: <?php echo htmlspecialchars($dept_name); ?></h3>
                        <?php else: ?>
                            <h3 class="text-lg font-semibold text-gray-700">All Departments</h3>
                        <?php endif; ?>
                    </div>

                    <!-- Enrollment Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">Daily Enrollments by Department</h3>
                            <button onclick="exportChart('detailedEnrollmentChart', 'detailed_enrollment')"
                                    class="text-gray-600 hover:text-blue-600">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                        <div class="chart-container">
                            <?php if ($enrollmentStats['has_data']): ?>
                                <canvas id="detailedEnrollmentChart"
                                        data-labels='<?php echo json_encode($enrollmentStats['labels']); ?>'
                                        data-datasets='<?php echo json_encode($enrollmentStats['datasets']); ?>'></canvas>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-bar text-gray-300 text-4xl mb-4"></i>
                                    <h3 class="text-gray-500 font-medium">No enrollment data available</h3>
                                    <p class="text-gray-400 text-sm mt-2">Try adjusting your date range or filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Enrollment Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div id="enrollmentTable"></div>
                    </div>
                </div>

            <!-- Financial Tab -->
            <?php elseif ($tab === 'financial'): ?>
                <div id="financial-tab">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Financial Report</h2>
                        <button onclick="exportCSV('financial')"
                                class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-file-csv mr-2"></i> Export CSV
                        </button>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">
                            Date Range: <?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?>
                        </h3>
                        <?php if ($department_filter > 0): ?>
                            <?php
                            $dept_name = $conn->query("SELECT name FROM departments WHERE id = $department_filter")->fetch_assoc()['name'];
                            ?>
                            <h3 class="text-lg font-semibold text-gray-700">Department: <?php echo htmlspecialchars($dept_name); ?></h3>
                        <?php else: ?>
                            <h3 class="text-lg font-semibold text-gray-700">All Departments</h3>
                        <?php endif; ?>
                    </div>

                    <!-- Financial Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Total Revenue -->
                        <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-dollar-sign text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Revenue</p>
                                    <p class="text-2xl font-bold text-gray-800">
                                        $<?php
                                        $total = 0;
                                        foreach ($revenueStats['datasets'] as $dataset) {
                                            $total += array_sum($dataset['data']);
                                        }
                                        echo number_format($total, 2);
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Transactions -->
                        <div class="bg-green-50 rounded-xl p-6 border border-green-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-credit-card text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Transactions</p>
                                    <p class="text-2xl font-bold text-gray-800">
                                        <?php
                                        $query = "
                                            SELECT COUNT(*) as count
                                            FROM payments p
                                            JOIN students s ON p.student_id = s.user_id
                                            JOIN class_enrollments ce ON s.user_id = ce.student_id
                                            JOIN classes c ON ce.class_id = c.id
                                            JOIN courses co ON c.course_id = co.id
                                            WHERE p.status = 'completed'
                                            AND p.paid_at BETWEEN ? AND ?
                                        ";

                                        $params = [$start_date, $end_date];
                                        $types = 'ss';

                                        if ($department_filter > 0) {
                                            $query .= " AND co.department_id = ?";
                                            $params[] = $department_filter;
                                            $types .= 'i';
                                        }

                                        try {
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param($types, ...$params);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo number_format($result->fetch_assoc()['count']);
                                        } catch (Exception $e) {
                                            echo "0";
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Average Transaction -->
                        <div class="bg-orange-50 rounded-xl p-6 border border-orange-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-orange-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-money-bill-wave text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Average Transaction</p>
                                    <p class="text-2xl font-bold text-gray-800">
                                        $<?php
                                        $query = "
                                            SELECT AVG(amount) as avg
                                            FROM payments p
                                            JOIN students s ON p.student_id = s.user_id
                                            JOIN class_enrollments ce ON s.user_id = ce.student_id
                                            JOIN classes c ON ce.class_id = c.id
                                            JOIN courses co ON c.course_id = co.id
                                            WHERE p.status = 'completed'
                                            AND p.paid_at BETWEEN ? AND ?
                                        ";

                                        $params = [$start_date, $end_date];
                                        $types = 'ss';

                                        if ($department_filter > 0) {
                                            $query .= " AND co.department_id = ?";
                                            $params[] = $department_filter;
                                            $types .= 'i';
                                        }

                                        try {
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param($types, ...$params);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $avg = $result->fetch_assoc()['avg'];
                                            echo number_format($avg ? $avg : 0, 2);
                                        } catch (Exception $e) {
                                            echo "0.00";
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">Daily Revenue by Department</h3>
                            <button onclick="exportChart('detailedRevenueChart', 'detailed_revenue')"
                                    class="text-gray-600 hover:text-blue-600">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                        <div class="chart-container">
                            <?php if ($revenueStats['has_data']): ?>
                                <canvas id="detailedRevenueChart"
                                        data-labels='<?php echo json_encode($revenueStats['labels']); ?>'
                                        data-datasets='<?php echo json_encode($revenueStats['datasets']); ?>'></canvas>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-line text-gray-300 text-4xl mb-4"></i>
                                    <h3 class="text-gray-500 font-medium">No revenue data available</h3>
                                    <p class="text-gray-400 text-sm mt-2">Try adjusting your date range or filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Revenue Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
                        <div id="revenueTable"></div>
                    </div>

                    <!-- Payment Method Breakdown -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">Payment Method Breakdown</h3>
                            <button onclick="exportChart('financialPaymentChart', 'payment_method_breakdown')"
                                    class="text-gray-600 hover:text-blue-600">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                        <div class="chart-container">
                            <?php if ($paymentMethods['has_data']): ?>
                                <canvas id="financialPaymentChart"
                                        data-labels='<?php echo json_encode($paymentMethods['labels']); ?>'
                                        data-totals='<?php echo json_encode($paymentMethods['totals']); ?>'></canvas>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-credit-card text-gray-300 text-4xl mb-4"></i>
                                    <h3 class="text-gray-500 font-medium">No payment method data available</h3>
                                    <p class="text-gray-400 text-sm mt-2">Try adjusting your date range or filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize all charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Register datalabels plugin
            Chart.register(ChartDataLabels);

            // =============================================
            // OVERVIEW TAB CHARTS
            // =============================================
            <?php if ($tab === 'overview'): ?>
                // Enrollment Chart
                const enrollmentCtx = document.getElementById('enrollmentChart');
                if (enrollmentCtx) {
                    const enrollmentData = {
                        labels: JSON.parse(enrollmentCtx.dataset.labels),
                        datasets: JSON.parse(enrollmentCtx.dataset.datasets)
                    };

                    new Chart(enrollmentCtx, {
                        type: 'line',
                        data: {
                            labels: enrollmentData.labels,
                            datasets: enrollmentData.datasets.map(dataset => ({
                                ...dataset,
                                tension: 0.3,
                                fill: true
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                title: {
                                    display: false,
                                },
                                datalabels: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Enrollments'
                                    }
                                }
                            }
                        }
                    });
                }

                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx) {
                    const revenueData = {
                        labels: JSON.parse(revenueCtx.dataset.labels),
                        datasets: JSON.parse(revenueCtx.dataset.datasets)
                    };

                    new Chart(revenueCtx, {
                        type: 'bar',
                        data: {
                            labels: revenueData.labels,
                            datasets: revenueData.datasets.map(dataset => ({
                                ...dataset,
                                type: 'line',
                                fill: false,
                                borderWidth: 2
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                title: {
                                    display: false,
                                },
                                datalabels: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '$' + parseFloat(context.raw).toFixed(2);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Revenue ($)'
                                    }
                                }
                            }
                        }
                    });
                }

                // Payment Methods Chart
                const paymentCtx = document.getElementById('paymentChart');
                if (paymentCtx) {
                    const paymentData = {
                        labels: JSON.parse(paymentCtx.dataset.labels),
                        totals: JSON.parse(paymentCtx.dataset.totals)
                    };

                    new Chart(paymentCtx, {
                        type: 'pie',
                        data: {
                            labels: paymentData.labels,
                            datasets: [{
                                data: paymentData.totals,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                title: {
                                    display: false,
                                },
                                datalabels: {
                                    formatter: function(value, ctx) {
                                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return '$' + parseFloat(value).toFixed(2) + '\n' + percentage + '%';
                                    },
                                    color: '#ffffff',
                                    font: {
                                        weight: 'bold',
                                        size: 12
                                    }
                                }
                            }
                        }
                    });
                }
            <?php endif; ?>

            // =============================================
            // ENROLLMENT TAB CHARTS
            // =============================================
            <?php if ($tab === 'enrollment'): ?>
                // Detailed Enrollment Chart
                const detailedEnrollmentCtx = document.getElementById('detailedEnrollmentChart');
                if (detailedEnrollmentCtx) {
                    const detailedEnrollmentData = {
                        labels: JSON.parse(detailedEnrollmentCtx.dataset.labels),
                        datasets: JSON.parse(detailedEnrollmentCtx.dataset.datasets)
                    };

                    new Chart(detailedEnrollmentCtx, {
                        type: 'line',
                        data: {
                            labels: detailedEnrollmentData.labels,
                            datasets: detailedEnrollmentData.datasets.map(dataset => ({
                                ...dataset,
                                tension: 0.3,
                                fill: true
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                title: {
                                    display: true,
                                    text: 'Daily Enrollments by Department',
                                    font: {
                                        size: 16
                                    }
                                },
                                datalabels: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Enrollments'
                                    }
                                }
                            }
                        }
                    });
                }

                // Initialize Enrollment Table
                const enrollmentTableData = [];
                const labels = <?php echo json_encode($enrollmentStats['labels']); ?>;

                <?php if ($enrollmentStats['has_data']): ?>
                    <?php foreach ($enrollmentStats['labels'] as $index => $date): ?>
                        const row = {
                            date: '<?php echo date('M j, Y', strtotime($date)); ?>'
                        };
                        let total = 0;

                        <?php foreach ($enrollmentStats['datasets'] as $datasetKey => $dataset): ?>
                            row['dept_<?php echo $datasetKey; ?>'] = <?php echo $dataset['data'][$index] ?? 0; ?>;
                            total += <?php echo $dataset['data'][$index] ?? 0; ?>;
                        <?php endforeach; ?>

                        row.total = total;
                        enrollmentTableData.push(row);
                    <?php endforeach; ?>

                    // Add totals row
                    const totalsRow = { date: 'Total' };
                    let grandTotal = 0;

                    <?php foreach ($enrollmentStats['datasets'] as $datasetKey => $dataset): ?>
                        const datasetTotal = <?php echo array_sum($dataset['data']); ?>;
                        totalsRow['dept_<?php echo $datasetKey; ?>'] = datasetTotal;
                        grandTotal += datasetTotal;
                    <?php endforeach; ?>

                    totalsRow.total = grandTotal;
                    enrollmentTableData.push(totalsRow);
                <?php endif; ?>

                const enrollmentTable = new Tabulator("#enrollmentTable", {
                    data: enrollmentTableData,
                    layout: "fitColumns",
                    responsiveLayout: "collapse",
                    tooltips: true,
                    addRowPos: "top",
                    pagination: "local",
                    paginationSize: 10,
                    paginationSizeSelector: [10, 20, 50, 100],
                    columns: [
                        { title: "Date", field: "date", headerFilter: true, frozen: true },
                        <?php foreach ($enrollmentStats['datasets'] as $datasetKey => $dataset): ?>
                            {
                                title: "<?php echo htmlspecialchars($dataset['label']); ?>",
                                field: "dept_<?php echo $datasetKey; ?>",
                                formatter: function(cell) {
                                    return cell.getValue().toLocaleString();
                                },
                                headerFilter: "number",
                                headerFilterFunc: "=",
                                bottomCalc: "sum",
                                bottomCalcFormatter: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                        <?php endforeach; ?>
                        {
                            title: "Total",
                            field: "total",
                            formatter: function(cell) {
                                return cell.getValue().toLocaleString();
                            },
                            bottomCalc: "sum",
                            bottomCalcFormatter: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    ],
                    <?php if (!$enrollmentStats['has_data']): ?>
                    placeholder: "No enrollment data available for the selected date range.",
                    <?php endif; ?>
                });
            <?php endif; ?>

            // =============================================
            // FINANCIAL TAB CHARTS
            // =============================================
            <?php if ($tab === 'financial'): ?>
                // Detailed Revenue Chart
                const detailedRevenueCtx = document.getElementById('detailedRevenueChart');
                if (detailedRevenueCtx) {
                    const detailedRevenueData = {
                        labels: JSON.parse(detailedRevenueCtx.dataset.labels),
                        datasets: JSON.parse(detailedRevenueCtx.dataset.datasets)
                    };

                    new Chart(detailedRevenueCtx, {
                        type: 'bar',
                        data: {
                            labels: detailedRevenueData.labels,
                            datasets: detailedRevenueData.datasets.map(dataset => ({
                                ...dataset,
                                type: 'line',
                                fill: false,
                                borderWidth: 2
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                title: {
                                    display: true,
                                    text: 'Daily Revenue by Department',
                                    font: {
                                        size: 16
                                    }
                                },
                                datalabels: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '$' + parseFloat(context.raw).toFixed(2);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Revenue ($)'
                                    }
                                }
                            }
                        }
                    });
                }

                // Initialize Revenue Table
                const revenueTableData = [];
                const revenueLabels = <?php echo json_encode($revenueStats['labels']); ?>;

                <?php if ($revenueStats['has_data']): ?>
                    <?php foreach ($revenueStats['labels'] as $index => $date): ?>
                        const row = {
                            date: '<?php echo date('M j, Y', strtotime($date)); ?>'
                        };
                        let total = 0;

                        <?php foreach ($revenueStats['datasets'] as $datasetKey => $dataset): ?>
                            row['dept_<?php echo $datasetKey; ?>'] = <?php echo $dataset['data'][$index] ?? 0; ?>;
                            total += <?php echo $dataset['data'][$index] ?? 0; ?>;
                        <?php endforeach; ?>

                        row.total = total;
                        revenueTableData.push(row);
                    <?php endforeach; ?>

                    // Add totals row
                    const totalsRow = { date: 'Total' };
                    let grandTotal = 0;

                    <?php foreach ($revenueStats['datasets'] as $datasetKey => $dataset): ?>
                        const datasetTotal = <?php echo array_sum($dataset['data']); ?>;
                        totalsRow['dept_<?php echo $datasetKey; ?>'] = datasetTotal;
                        grandTotal += datasetTotal;
                    <?php endforeach; ?>

                    totalsRow.total = grandTotal;
                    revenueTableData.push(totalsRow);
                <?php endif; ?>

                const revenueTable = new Tabulator("#revenueTable", {
                    data: revenueTableData,
                    layout: "fitColumns",
                    responsiveLayout: "collapse",
                    tooltips: true,
                    addRowPos: "top",
                    pagination: "local",
                    paginationSize: 10,
                    paginationSizeSelector: [10, 20, 50, 100],
                    columns: [
                        { title: "Date", field: "date", headerFilter: true, frozen: true },
                        <?php foreach ($revenueStats['datasets'] as $datasetKey => $dataset): ?>
                            {
                                title: "<?php echo htmlspecialchars($dataset['label']); ?>",
                                field: "dept_<?php echo $datasetKey; ?>",
                                formatter: function(cell) {
                                    return '$' + parseFloat(cell.getValue()).toFixed(2);
                                },
                                headerFilter: "number",
                                headerFilterFunc: "=",
                                bottomCalc: "sum",
                                bottomCalcFormatter: function(value) {
                                    return '$' + parseFloat(value).toFixed(2);
                                }
                            },
                        <?php endforeach; ?>
                        {
                            title: "Total",
                            field: "total",
                            formatter: function(cell) {
                                return '$' + parseFloat(cell.getValue()).toFixed(2);
                            },
                            bottomCalc: "sum",
                            bottomCalcFormatter: function(value) {
                                return '$' + parseFloat(value).toFixed(2);
                            }
                        }
                    ],
                    <?php if (!$revenueStats['has_data']): ?>
                    placeholder: "No revenue data available for the selected date range.",
                    <?php endif; ?>
                });

                // Financial Payment Chart
                const financialPaymentCtx = document.getElementById('financialPaymentChart');
                if (financialPaymentCtx) {
                    const financialPaymentData = {
                        labels: JSON.parse(financialPaymentCtx.dataset.labels),
                        totals: JSON.parse(financialPaymentCtx.dataset.totals)
                    };

                    new Chart(financialPaymentCtx, {
                        type: 'pie',
                        data: {
                            labels: financialPaymentData.labels,
                            datasets: [{
                                data: financialPaymentData.totals,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                title: {
                                    display: true,
                                    text: 'Revenue by Payment Method',
                                    font: {
                                        size: 16
                                    }
                                },
                                datalabels: {
                                    formatter: function(value, ctx) {
                                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return '$' + parseFloat(value).toFixed(2) + '\n' + percentage + '%';
                                    },
                                    color: '#ffffff',
                                    font: {
                                        weight: 'bold',
                                        size: 12
                                    }
                                }
                            }
                        }
                    });
                }
            <?php endif; ?>

            // Change tab function
            function changeTab(tabName) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.location.href = url.toString();
            }

            // Apply filters function
            function applyFilters() {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const department = document.getElementById('department').value;

                const url = new URL(window.location.href);
                url.searchParams.set('start_date', startDate);
                url.searchParams.set('end_date', endDate);
                url.searchParams.set('department', department);

                window.location.href = url.toString();
            }

            // Export CSV function
            function exportCSV(type) {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const department = document.getElementById('department').value;

                window.location.href = `?export=csv&type=${type}&start_date=${startDate}&end_date=${endDate}&department=${department}`;
            }

            // Export chart as image
            function exportChart(chartId, filename) {
                const chart = Chart.getChart(chartId);
                if (chart) {
                    const link = document.createElement('a');
                    link.download = filename + '.png';
                    link.href = chart.toBase64Image();
                    link.click();
                }
            }

            // Set export button href based on current tab
            document.getElementById('export-btn').addEventListener('click', function() {
                exportCSV('<?php echo $tab; ?>');
            });
        });
    </script>
</body>
</html>
