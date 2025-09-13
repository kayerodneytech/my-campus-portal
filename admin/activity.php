<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin and logged in
if (!isAdmin() || !isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$query = "SELECT al.*, u.first_name, u.last_name, u.email 
          FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = '';

if ($type_filter !== 'all') {
    $query .= " AND al.activity_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if ($user_filter > 0) {
    $query .= " AND al.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if (!empty($search_query)) {
    $query .= " AND (al.description LIKE ? OR al.ip_address LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= str_repeat('s', 4);
}

$query .= " ORDER BY al.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$activities = $result->fetch_all(MYSQLI_ASSOC);

// Get unique activity types for filter dropdown
$activity_types = $conn->query("SELECT DISTINCT activity_type FROM activity_logs ORDER BY activity_type")->fetch_all(MYSQLI_ASSOC);

// Get users for filter dropdown
$users = $conn->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_activities = $conn->query("SELECT COUNT(*) as total FROM activity_logs")->fetch_assoc()['total'];
$today_activities = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];
$unique_users = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM activity_logs WHERE user_id IS NOT NULL")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Portal</title>
    
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="../1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                    <h1 class="text-xl font-bold text-gray-800">Admin Portal</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard
                    </a>
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
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Activity Logs</h1>
                <p class="text-gray-600">Monitor system activities and user actions</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="activity.php?export=csv" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>
                    Export CSV
                </a>
                <button onclick="clearOldLogs()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>
                    Clear Old Logs
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-history text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Activities</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_activities); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-day text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Today's Activities</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($today_activities); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Unique Users</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($unique_users); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach ($activity_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['activity_type']); ?>" 
                                <?php echo $type_filter === $type['activity_type'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $type['activity_type'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="0" <?php echo $user_filter === 0 ? 'selected' : ''; ?>>All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                <?php echo $user_filter === $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search description, IP address, or user name..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="flex items-end space-x-2 md:col-span-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-filter mr-2"></i>
                        Apply Filters
                    </button>
                    <a href="activity.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        <i class="fas fa-times mr-2"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Activities Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($activities) > 0): ?>
                            <?php foreach ($activities as $activity): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo date('M j, Y', strtotime($activity['created_at'])); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('g:i A', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($activity['user_id']): ?>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['email']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo getActivityTypeClass($activity['activity_type']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        <?php if (!empty($activity['additional_data'])): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php echo htmlspecialchars($activity['additional_data']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($activity['ip_address']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-500 max-w-xs truncate">
                                            <?php echo htmlspecialchars($activity['user_agent']); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No activities found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing <?php echo count($activities); ?> of <?php echo number_format($total_activities); ?> activities
            </div>
            <!-- Add pagination links here if needed -->
        </div>
    </div>

    <script>
    function clearOldLogs() {
        if (confirm('Are you sure you want to clear activity logs older than 90 days? This action cannot be undone.')) {
            fetch('../includes/async/clear_activity_logs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Old logs cleared successfully!');
                    location.reload();
                } else {
                    alert('Error clearing logs: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error clearing logs: ' + error);
            });
        }
    }

    function getActivityTypeClass(type) {
        switch(type) {
            case 'login': return 'bg-green-100 text-green-800';
            case 'logout': return 'bg-blue-100 text-blue-800';
            case 'registration': return 'bg-purple-100 text-purple-800';
            case 'payment': return 'bg-yellow-100 text-yellow-800';
            case 'error': return 'bg-red-100 text-red-800';
            case 'update': return 'bg-indigo-100 text-indigo-800';
            case 'delete': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }
    </script>
</body>
</html>

<?php
function getActivityTypeClass($type) {
    switch($type) {
        case 'login': return 'bg-green-100 text-green-800';
        case 'logout': return 'bg-blue-100 text-blue-800';
        case 'registration': return 'bg-purple-100 text-purple-800';
        case 'payment': return 'bg-yellow-100 text-yellow-800';
        case 'error': return 'bg-red-100 text-red-800';
        case 'update': return 'bg-indigo-100 text-indigo-800';
        case 'delete': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>