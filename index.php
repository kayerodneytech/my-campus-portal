<?php

/**
 * MyCamp Portal - Homepage
 * Redesigned homepage with better user experience and clear navigation
 * Focuses on user journey and role-based access
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

        h1 {
            font-weight: 600;
        }

        .hero-gradient {
            background-image: url("assets/images/abstract-backround.jpg");
            background-size: cover;
            background-position: center;
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
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="1753449127073.jpg" alt="MyCamp Portal" class="h-10 w-10 rounded-full mr-3">
                    <h1 class="text-xl font-bold text-gray-800">MyCamp Portal</h1>
                    <span class="ml-3 text-sm text-gray-500 italic">One stop place for life in college</span>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="#" onclick="openLoginModal()" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">🔐</span>
                        Sign In
                    </a>
                    <a href="courses.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm font-medium">
                        <i class="fas fa-user-plus mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">📝</span>
                        Apply Now
                    </a>
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
                <a href="#" onclick="openLoginModal()" class="block px-3 py-2 text-gray-600 hover:text-blue-600 rounded-md text-base font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span class="fa-fallback mr-2" style="display: none;">🔐</span>
                    Sign In
                </a>
                <a href="courses.php" class="block px-3 py-2 bg-blue-600 text-white rounded-md text-base font-medium">
                    <i class="fas fa-user-plus mr-2"></i>
                    <span class="fa-fallback mr-2" style="display: none;">📝</span>
                    Apply Now
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="fade-in">
                <h1 class="text-3xl md:text-5xl font-extrabold mb-6">
                    Welcome to MyCamp Portal
                </h1>
                <p class="text-lg md:text-xl mb-8 text-blue-100 max-w-3xl mx-auto">
                    Your comprehensive gateway to academic success. Access courses, manage your studies, and connect with your campus community.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="courses.php" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-rocket mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">🚀</span>
                        Enroll Now
                    </a>
                    <button onclick="scrollToFeatures()" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors duration-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span class="fa-fallback mr-2" style="display: none;">ℹ️</span>
                        Learn More
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Quick Access Cards -->
        <section class="mb-12">
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-8 text-center">Quick Access</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="#" onclick="openLoginModal()" class="card-hover bg-white rounded-xl p-6 text-center shadow-md block">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-sign-in-alt text-blue-600"></i>
                        <span class="fa-fallback text-blue-600" style="display: none;">🔐</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Sign In</h3>
                    <p class="text-gray-600 text-sm">Access your dashboard and account</p>
                </a>
                <a href="courses.php" class="card-hover bg-white rounded-xl p-6 text-center shadow-md block">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-user-plus text-green-600"></i>
                        <span class="fa-fallback text-green-600" style="display: none;">📝</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Apply Now</h3>
                    <p class="text-gray-600 text-sm">Start your academic journey</p>
                </a>
                <a href="courses.php" class="card-hover bg-white rounded-xl p-6 text-center shadow-md block">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-book text-purple-600"></i>
                        <span class="fa-fallback text-purple-600" style="display: none;">📚</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Browse Courses</h3>
                    <p class="text-gray-600 text-sm">Explore available programs</p>
                </a>
                <button onclick="showContactInfo()" class="card-hover bg-white rounded-xl p-6 text-center shadow-md w-full text-left">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-phone text-orange-600"></i>
                        <span class="fa-fallback text-orange-600" style="display: none;">📞</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Contact Us</h3>
                    <p class="text-gray-600 text-sm">Get help and support</p>
                </button>
            </div>
        </section>

        <!-- Information Grid -->
        <section id="features" class="mb-12">
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-8 text-center">Campus Information</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Include Important Dates Component -->
                <?php include 'components/important_dates.php'; ?>
                <!-- Include Campus News Component -->
                <?php include 'components/campus_news.php'; ?>
            </div>
            <!-- Resources Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Include Lecturer Catalog Component -->
                <?php include 'components/lecturer_catalog.php'; ?>
                <!-- Include Student Organizations Component -->
                <?php include 'components/student_organizations.php'; ?>
                <!-- Include Digital Resources Component -->
                <?php include 'components/digital_resources.php'; ?>
            </div>
        </section>

        <!-- Include Testimonial Component -->
        <?php include 'components/testimonial.php'; ?>
    </main>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- Include Modal Components -->
    <?php include 'components/login_modal.php'; ?>
    <?php include 'components/register_modal.php'; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        function scrollToFeatures() {
            document.getElementById('features').scrollIntoView({
                behavior: 'smooth'
            });
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