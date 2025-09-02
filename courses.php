<?php
session_start();
require_once 'includes/config.php';

// Get all courses from database
$query = "SELECT c.*, d.name as department_name 
          FROM courses c 
          LEFT JOIN departments d ON c.department_id = d.id 
          ORDER BY c.course_name";
$result = $conn->query($query);

$courses = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - MyCamp Portal</title>
    
    <!-- Local Tailwind CSS -->
    <script src="javascript/tailwindcss.js"></script>
    
    <!-- Font Awesome Icons with fallback -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('assets/fonts/poppins.ttf') format('truetype');
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
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                    <h1 class="text-xl font-bold text-gray-800">MyCamp Portal</h1>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <button onclick="openLoginModal()" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </button>
                    <button onclick="openRegisterModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm font-medium">
                        <i class="fas fa-user-plus mr-2"></i>
                        Apply Now
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Our Courses</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Explore our comprehensive range of programs designed to help you achieve your academic and career goals.
            </p>
        </div>

        <!-- Course Filter -->
        <div class="mb-8 bg-white rounded-xl shadow-md p-6 card-hover">
            <div class="flex flex-wrap gap-4 items-center">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Level:</label>
                    <select id="levelFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="all">All Levels</option>
                        <option value="certificate">Certificate</option>
                        <option value="diploma">Diploma</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Courses:</label>
                    <input type="text" id="courseSearch" placeholder="Search course name..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php foreach ($courses as $course): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover course-card" 
                 data-level="<?= strtolower($course['level']) ?>" 
                 data-name="<?= strtolower($course['course_name']) ?>">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-graduation-cap text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($course['course_name']) ?></h3>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($course['course_code']) ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                            <?= ucfirst($course['level']) ?>
                        </span>
                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium ml-2">
                            <?= $course['credits'] ?> Credits
                        </span>
                    </div>
                    
                    <p class="text-gray-600 mb-4 text-sm"><?= htmlspecialchars($course['description']) ?></p>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">
                            <?= $course['department_name'] ? htmlspecialchars($course['department_name']) : 'General' ?>
                        </span>
                        
                        <button onclick="enrollInCourse('<?= $course['id'] ?>', '<?= htmlspecialchars($course['course_name']) ?>')" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                            Enroll Now
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // Course filtering
    document.getElementById('levelFilter').addEventListener('change', filterCourses);
    document.getElementById('courseSearch').addEventListener('input', filterCourses);
    
    function filterCourses() {
        const levelFilter = document.getElementById('levelFilter').value;
        const searchTerm = document.getElementById('courseSearch').value.toLowerCase();
        
        document.querySelectorAll('.course-card').forEach(card => {
            const cardLevel = card.getAttribute('data-level');
            const cardName = card.getAttribute('data-name');
            
            const levelMatch = levelFilter === 'all' || cardLevel === levelFilter;
            const nameMatch = cardName.includes(searchTerm);
            
            if (levelMatch && nameMatch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function enrollInCourse(courseId, courseName) {
        // Store the selected course for the registration form
        localStorage.setItem('selectedCourse', courseName);
        localStorage.setItem('selectedCourseId', courseId);
        
        // Open the registration modal
        openRegisterModal();
    }
    </script>

    <!-- Include Modal Components -->
    <?php include 'components/login_modal.php'; ?>
    <?php include 'components/register_modal.php'; ?>
</body>
</html>