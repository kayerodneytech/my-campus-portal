<?php
/**
 * MyCamp Portal - Homepage
 * Modern responsive homepage using Tailwind CSS
 * Includes reusable components for better maintainability
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyCamp Portal - Welcome</title>
    
    <!-- Tailwind CSS -->
    <script src="javascript/tailwindcss.js"></script>
    
    <!-- Font Awesome Icons with fallback -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Fallback for offline use -->
    <script>
        // Check if Font Awesome loaded, if not, use emoji fallbacks
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.querySelector('link[href*="font-awesome"]')) {
                console.log('Font Awesome not loaded, using emoji fallbacks');
            }
        });
    </script>
    
    <!-- Custom styles for any additional styling -->
    <style>
        /* Custom animations and additional styles */
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Main Container -->
    <div class="flex min-h-screen">
        <!-- Include Sidebar Component -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="flex-1 lg:ml-80 p-4 lg:p-8 overflow-x-hidden">
            <!-- Include Header Component -->
            <?php include 'components/header.php'; ?>
            
            <!-- Include Quick Actions Component -->
            <?php include 'components/quick_actions.php'; ?>
            
            <!-- Content Grid - Important Dates and Campus News -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Include Important Dates Component -->
                <?php include 'components/important_dates.php'; ?>
                
                <!-- Include Campus News Component -->
                <?php include 'components/campus_news.php'; ?>
            </div>
            
            <!-- Resources Grid - Lecturer Catalog, Student Organizations, Digital Resources -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Include Lecturer Catalog Component -->
                <?php include 'components/lecturer_catalog.php'; ?>
                
                <!-- Include Student Organizations Component -->
                <?php include 'components/student_organizations.php'; ?>
                
                <!-- Include Digital Resources Component -->
                <?php include 'components/digital_resources.php'; ?>
            </div>
            
            <!-- Include Testimonial Component -->
            <?php include 'components/testimonial.php'; ?>
            
            <!-- Include Footer Component -->
            <?php include 'components/footer.php'; ?>
        </main>
    </div>
    
    <!-- Include the main JavaScript file -->
    <script src="javascript/script.js"></script>
    
    <!-- Additional JavaScript for enhanced interactivity -->
    <script>
        // Font Awesome fallback detection and handling
        document.addEventListener('DOMContentLoaded', function() {
            // Check if Font Awesome is loaded
            const testIcon = document.createElement('i');
            testIcon.className = 'fas fa-home';
            testIcon.style.position = 'absolute';
            testIcon.style.left = '-9999px';
            document.body.appendChild(testIcon);
            
            const faLoaded = window.getComputedStyle(testIcon, ':before').getPropertyValue('font-family').includes('Font Awesome');
            document.body.removeChild(testIcon);
            
            if (!faLoaded) {
                // Font Awesome not loaded, show emoji fallbacks
                document.querySelectorAll('.fa-fallback').forEach(fallback => {
                    fallback.style.display = 'inline';
                });
                document.querySelectorAll('i[class*="fas"], i[class*="far"], i[class*="fab"]').forEach(icon => {
                    icon.style.display = 'none';
                });
                console.log('Font Awesome not loaded, using emoji fallbacks');
            }
        });
        
        // Add fade-in animation to components
        document.addEventListener('DOMContentLoaded', function() {
            const components = document.querySelectorAll('header, section, div[class*="bg-white"]');
            components.forEach((component, index) => {
                component.classList.add('fade-in');
                component.style.animationDelay = `${index * 0.1}s`;
            });
        });
        
        // Add hover effects to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.bg-white.rounded-xl');
            cards.forEach(card => {
                card.classList.add('hover-lift');
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
