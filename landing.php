<?php
/**
 * MyCamp Portal - Landing Page
 * Clean, focused landing page that guides users to their destination
 * Modern design with clear user journey
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyCamp Portal - Your Gateway to Success</title>
    
    <!-- Tailwind CSS -->
    <script src="javascript/tailwindcss.js"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                        <span class="fa-fallback mr-2" style="display: none;">🔐</span>
                        Sign In
                    </button>
                    <button onclick="openRegisterModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm font-medium">
                        <i class="fas fa-user-plus mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">📝</span>
                        Apply Now
                    </button>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-600 hover:text-blue-600 p-2">
                        <i class="fas fa-bars text-xl"></i>
                        <span class="fa-fallback text-xl" style="display: none;">☰</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <button onclick="openLoginModal()" class="block w-full text-left px-3 py-2 text-gray-600 hover:text-blue-600 rounded-md text-base font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span class="fa-fallback mr-2" style="display: none;">🔐</span>
                    Sign In
                </button>
                <button onclick="openRegisterModal()" class="block w-full text-left px-3 py-2 bg-blue-600 text-white rounded-md text-base font-medium">
                    <i class="fas fa-user-plus mr-2"></i>
                    <span class="fa-fallback mr-2" style="display: none;">📝</span>
                    Apply Now
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="fade-in">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Welcome to MyCamp Portal
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto">
                    Your comprehensive gateway to academic success. Access courses, manage your studies, and connect with your campus community.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button onclick="openRegisterModal()" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200 text-lg">
                        <i class="fas fa-rocket mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">🚀</span>
                        Get Started
                    </button>
                    <button onclick="scrollToFeatures()" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors duration-200 text-lg">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">ℹ️</span>
                        Learn More
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- User Type Selection -->
    <section id="features" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                    Choose Your Path
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Select your role to access the most relevant features and information for your needs.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Student Card -->
                <div class="card-hover bg-white rounded-2xl shadow-lg p-8 border border-gray-100 cursor-pointer" onclick="navigateToStudent()">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-graduation-cap text-blue-600 text-3xl"></i>
                            <span class="fa-fallback text-blue-600 text-3xl" style="display: none;">🎓</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">I'm a Student</h3>
                        <p class="text-gray-600 mb-6">
                            Access your courses, assignments, grades, and campus resources. Manage your academic journey with ease.
                        </p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>View Courses & Assignments</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Track Grades & Attendance</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Make Payments</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lecturer Card -->
                <div class="card-hover bg-white rounded-2xl shadow-lg p-8 border border-gray-100 cursor-pointer" onclick="navigateToLecturer()">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-3xl"></i>
                            <span class="fa-fallback text-green-600 text-3xl" style="display: none;">👨‍🏫</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">I'm a Lecturer</h3>
                        <p class="text-gray-600 mb-6">
                            Manage your classes, create assignments, track student progress, and communicate with your students.
                        </p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Manage Classes & Students</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Create Assignments</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Track Attendance</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Card -->
                <div class="card-hover bg-white rounded-2xl shadow-lg p-8 border border-gray-100 cursor-pointer" onclick="navigateToAdmin()">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-cogs text-purple-600 text-3xl"></i>
                            <span class="fa-fallback text-purple-600 text-3xl" style="display: none;">⚙️</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">I'm an Administrator</h3>
                        <p class="text-gray-600 mb-6">
                            Oversee the entire portal, manage users, courses, and system settings. Access comprehensive reports and analytics.
                        </p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Manage Users & Courses</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>View Reports & Analytics</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>System Configuration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Access Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                    Quick Access
                </h2>
                <p class="text-xl text-gray-600">
                    Common tasks and resources you might need
                </p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl p-6 text-center shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer" onclick="openLoginModal()">
                    <div class="text-3xl mb-3">
                        <i class="fas fa-sign-in-alt text-blue-600"></i>
                        <span class="fa-fallback text-blue-600" style="display: none;">🔐</span>
                    </div>
                    <h3 class="font-semibold text-gray-800">Sign In</h3>
                </div>
                
                <div class="bg-white rounded-xl p-6 text-center shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer" onclick="openRegisterModal()">
                    <div class="text-3xl mb-3">
                        <i class="fas fa-user-plus text-green-600"></i>
                        <span class="fa-fallback text-green-600" style="display: none;">📝</span>
                    </div>
                    <h3 class="font-semibold text-gray-800">Apply Now</h3>
                </div>
                
                <div class="bg-white rounded-xl p-6 text-center shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer" onclick="window.location.href='index.php'">
                    <div class="text-3xl mb-3">
                        <i class="fas fa-home text-purple-600"></i>
                        <span class="fa-fallback text-purple-600" style="display: none;">🏠</span>
                    </div>
                    <h3 class="font-semibold text-gray-800">Portal Home</h3>
                </div>
                
                <div class="bg-white rounded-xl p-6 text-center shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer" onclick="showContactInfo()">
                    <div class="text-3xl mb-3">
                        <i class="fas fa-phone text-orange-600"></i>
                        <span class="fa-fallback text-orange-600" style="display: none;">📞</span>
                    </div>
                    <h3 class="font-semibold text-gray-800">Contact Us</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <img src="1753449127073.jpg" alt="MyCamp Portal" class="h-8 w-8 rounded-full mr-3">
                        <h3 class="text-xl font-bold">MyCamp Portal</h3>
                    </div>
                    <p class="text-gray-300">
                        Your comprehensive gateway to academic success. One stop place for life in college.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" onclick="openLoginModal()" class="hover:text-white transition-colors duration-200">Sign In</a></li>
                        <li><a href="#" onclick="openRegisterModal()" class="hover:text-white transition-colors duration-200">Apply Now</a></li>
                        <li><a href="index.php" class="hover:text-white transition-colors duration-200">Portal Home</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Information</h4>
                    <div class="space-y-2 text-gray-300">
                        <div class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <span>0782 573 049</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>admissions@mycamp.edu</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-300">
                <p>&copy; 2025 MyCamp Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Include Modal Components -->
    <?php include 'components/login_modal.php'; ?>
    <?php include 'components/register_modal.php'; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Navigation functions
        function navigateToStudent() {
            openLoginModal();
        }

        function navigateToLecturer() {
            openLoginModal();
        }

        function navigateToAdmin() {
            openLoginModal();
        }

        function scrollToFeatures() {
            document.getElementById('features').scrollIntoView({ behavior: 'smooth' });
        }

        function showContactInfo() {
            alert('Contact Information:\n\nPhone: 0782 573 049\nEmail: admissions@mycamp.edu\n\nOffice Hours: Mon-Fri 8AM-5PM');
        }

        // Font Awesome fallback detection
        document.addEventListener('DOMContentLoaded', function() {
            const testIcon = document.createElement('i');
            testIcon.className = 'fas fa-home';
            testIcon.style.position = 'absolute';
            testIcon.style.left = '-9999px';
            document.body.appendChild(testIcon);
            
            const faLoaded = window.getComputedStyle(testIcon, ':before').getPropertyValue('font-family').includes('Font Awesome');
            document.body.removeChild(testIcon);
            
            if (!faLoaded) {
                document.querySelectorAll('.fa-fallback').forEach(fallback => {
                    fallback.style.display = 'inline';
                });
                document.querySelectorAll('i[class*="fas"], i[class*="far"], i[class*="fab"]').forEach(icon => {
                    icon.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>
