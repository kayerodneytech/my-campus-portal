document.addEventListener('DOMContentLoaded', function() {
    // Fix navigation - prevent default behavior for menu items that have onclick handlers
    document.querySelectorAll('.menu-item').forEach(link => {
        link.addEventListener('click', function(e) {
            // If the link has an onclick attribute, don't prevent default
            if (this.getAttribute('onclick')) {
                return; // Let the onclick handler work
            }
            
            // For other links, prevent default and show a message
            e.preventDefault();
            const linkText = this.textContent.trim();
            
            // Show appropriate message based on the link
            if (linkText.includes('Welcome Board')) {
                // Already on welcome board, do nothing
                return;
            } else if (linkText.includes('Student Login')) {
                if (typeof openLoginModal === 'function') {
                    openLoginModal();
                }
            } else if (linkText.includes('Apply Now')) {
                if (typeof openRegisterModal === 'function') {
                    openRegisterModal();
                }
            } else {
                // For other menu items, show a coming soon message
                alert(`${linkText} feature is coming soon! This section is under development.`);
            }
        });
    });

    // Function to set active menu item
    function setActiveMenuItem(clickedLink) {
        document.querySelectorAll('.menu-item').forEach(link => {
            link.classList.remove('active');
        });
        clickedLink.classList.add('active');
    }

    // Default content on load
    if (welcomeBoardLink) {
        welcomeBoardLink.addEventListener('click', function(event) {
            event.preventDefault();
            setActiveMenuItem(this);
            // You would load your default welcome content here if it's dynamic
            // For now, it's static HTML in index.html, so we'll just show it if hidden.
            // Or you could load a 'welcome_board.html' via fetch.
            mainContentArea.innerHTML = `
                <header class="header">
                    <h1>Welcome to MyCamp Portal - Your Gateway to Success</h1>
                    <p>Navigate through comprehensive resources, stay updated with important announcements, and access all the tools you need for your academic journey. Our goal is to provide a seamless experience for all students and faculty members.</p>
                </header>

                <section class="quick-actions">
                    <div class="action-card">
                        <div class="icon">📚</div>
                        <h3>Course Materials</h3>
                        <p>Access your online courses and materials</p>
                    </div>
                    <div class="action-card">
                        <div class="icon">📋</div>
                        <h3>Academic Records</h3>
                        <p>View grades and transcripts</p>
                    </div>
                </section>

                <div class="content-grid">
                    <div class="content-section">
                        <h2 class="section-title">📅 Important Dates and Deadlines</h2>
                        <div class="date-item">
                            <span class="date-label">Spring Semester Begins</span>
                            <span class="date-value">January 15, 2025</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Course Registration Deadline</span>
                            <span class="date-value">January 30, 2025</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Mid-term Exams</span>
                            <span class="date-value">March 10-14, 2025</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Spring Break</span>
                            <span class="date-value">March 24-28, 2025</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Final Exams</span>
                            <span class="date-value">May 5-9, 2025</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Graduation Ceremony</span>
                            <span class="date-value">May 18, 2025</span>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2 class="section-title">📢 Campus News & Events</h2>
                        <div class="news-item">
                            <div class="news-title">Annual Science Fair 2025</div>
                            <div class="news-date">Posted: July 20, 2025</div>
                            <div class="news-summary">Join us for the most exciting science fair featuring innovative projects from students across all departments.</div>
                        </div>
                        <div class="news-item">
                            <div class="news-title">Guest Lecture: AI in Education</div>
                            <div class="news-date">Posted: July 18, 2025</div>
                            <div class="news-summary">Renowned AI expert Dr. Sarah Johnson will discuss the future of artificial intelligence in educational systems.</div>
                        </div>
                        <div class="news-item">
                            <div class="news-title">Career Fair Registration Open</div>
                            <div class="news-date">Posted: July 15, 2025</div>
                            <div class="news-summary">Register now for the upcoming career fair featuring top employers from various industries.</div>
                        </div>
                    </div>
                </div>

                <div class="resources-grid">
                    <div class="resource-card">
                        <h3 class="section-title">🎓 Academic Support</h3>
                        <ul class="resource-list">
                            <li><a href="#">Tutoring Services</a></li>
                            <li><a href="#">Writing Center</a></li>
                            <li><a href="#">Study Groups</a></li>
                            <li><a href="#">Academic Advising</a></li>
                            <li><a href="#">Research Support</a></li>
                        </ul>
                    </div>
                    <div class="resource-card">
                        <h3 class="section-title">🏥 Student Wellness</h3>
                        <ul class="resource-list">
                            <li><a href="#">Counseling Services</a></li>
                            <li><a href="#">Health Center</a></li>
                            <li><a href="#">Mental Health Resources</a></li>
                            <li><a href="#">Wellness Programs</a></li>
                            <li><a href="#">Crisis Support</a></li>
                        </ul>
                    </div>
                    <div class="resource-card">
                        <h3 class="section-title">🎯 Student Organizations</h3>
                        <ul class="resource-list">
                            <li><a href="#"  id="student-government-link" class="menu-item-link">Student Government</a></li>
                            <li><a href="#"  id="academic-clubs-link" class="menu-item-link">Academic Clubs</a></li>
                            <li><a href="#"  id="cultural-orgs-link" class="menu-item-link">Cultural Organizations</a></li>
                            <li><a href="#"  id="sports-recreation-link" class="menu-item-link">Sports & Recreation</a></li>
                            <li><a href="#"  id="volunteer-groups-link" class="menu-item-link">Volunteer Groups</a></li>
                        </ul>
                    </div>
                    <div class="resource-card">
                        <h3 class="section-title">🌐 Digital Resources</h3>
                        <ul class="resource-list">
                            <li><a href="#">Online Learning Platform</a></li>
                            <li><a href="#">Digital Library</a></li>
                            <li><a href="#">Research Databases</a></li>
                            <li><a href="#">Student Portal Mobile App</a></li>
                            <li><a href="#">Tech Support</a></li>
                        </ul>
                    </div>
                </div>

                <div class="testimonial">
                    <div class="testimonial-text">"MyCamp Portal has transformed my academic experience. The integrated resources and user-friendly interface make it easy to stay organized and connected with the campus community."</div>
                    <div class="testimonial-author">- Santi Mundere, Class of 2024, Computer Science</div>
                </div>

                <footer class="footer">
                    <h3 style="color: #2c3e50; margin-bottom: 20px;">📞 Contact Information</h3>
                    <div class="contact-grid">
                        <div class="contact-item">
                            <h4>Admissions Office</h4>
                            <p>Phone: (555) 123-4567<br>
                            Email: admissions@mycamp.edu<br>
                            Hours: Mon-Fri 8AM-5PM</p>
                        </div>
                        <div class="contact-item">
                            <h4>Registrar</h4>
                            <p>Phone: (555) 123-4568<br>
                            Email: registrar@mycamp.edu<br>
                            Hours: Mon-Fri 9AM-4PM</p>
                        </div>
                        <div class="contact-item">
                            <h4>Student Support</h4>
                            <p>Phone: (555) 123-4569<br>
                            Email: support@mycamp.edu<br>
                            Hours: 24/7 Online Chat</p>
                        </div>
                        <div class="contact-item">
                            <h4>Campus Location</h4>
                            <p>123 University Drive<br>
                            Education City, EC 12345<br>
                            <a href="#" style="color: #4a90e2;">View Campus Map</a></p>
                        </div>
                    </div>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666;">
                        <p>© 2025 MyCamp Portal. All rights reserved. | <a href="#" style="color: #4a90e2;">Privacy Policy</a> | <a href="#" style="color: #4a90e2;">Terms of Service</a></p>
                    </div>
                </footer>
            `;
        });
    }

    // Student Login Link Handler (existing logic from previous responses)
    if (studentLoginLink && mainContentArea) {
        studentLoginLink.addEventListener('click', function(event) {
            event.preventDefault();
            setActiveMenuItem(this);
            const loginFormHtml = `
                <h1>Student Login</h1>
                <p>Enter your credentials to log in to the portal.</p>
                <form id="studentLoginForm" class="enrollment-form">
                    <label for="loginEmail">Email:</label>
                    <input type="email" id="loginEmail" name="email" required>

                    <label for="loginPassword">Password:</label>
                    <input type="password" id="loginPassword" name="password" required>

                    <button type="submit">Login</button>
                    <div id="loginMessage" class="form-message"></div>
                    <p><a href="#" id="forgotPasswordLink">Forgot Password?</a></p>
                </form>
            `;
            mainContentArea.innerHTML = loginFormHtml;

            const studentLoginForm = document.getElementById('studentLoginForm');
            const loginMessage = document.getElementById('loginMessage');
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');

            if (studentLoginForm) {
                studentLoginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    loginMessage.style.display = 'none';

                    const email = document.getElementById('loginEmail').value;
                    const password = document.getElementById('loginPassword').value;

                    try {
                        const response = await fetch('../process_login.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ email: email, password: password })
                        });
                        const result = await response.json();

                        if (result.success) {
                            loginMessage.textContent = result.message;
                            loginMessage.className = 'form-message success';
                            setTimeout(() => {
                                if (result.redirect_url) {
                                    window.location.href = result.redirect_url;
                                } else {
                                    window.location.href = 'student_dashboard.php';
                                }
                            }, 1500);
                        } else {
                            loginMessage.textContent = result.message || 'Login failed. Please check your credentials.';
                            loginMessage.className = 'form-message error';
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        loginMessage.textContent = 'Network error during login. Please try again.';
                        loginMessage.className = 'form-message error';
                    }
                    loginMessage.style.display = 'block';
                });
            }

            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    loadForgotPasswordForm();
                });
            }
        });
    }

    // Apply Now/Enroll Link Handler (previously New Enrollments)
    if (applyNowLink && mainContentArea) {
        applyNowLink.addEventListener('click', function(event) {
            event.preventDefault();
            setActiveMenuItem(this);
            const enrollmentFormHtml = `
                <h1>Student Enrollment Form</h1>
                <p>Please fill out the form below to enroll. All fields are required.</p>
                <form id="enrollmentForm" class="enrollment-form" enctype="multipart/form-data">
                    <label for="fullName">Full Name:</label>
                    <input type="text" id="fullName" name="fullName" required>

                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Password (for future login):</label>
                    <input type="password" id="password" name="password" required>
                    <small>Choose a strong password for your portal access.</small>

                    <label for="confirmPassword">Confirm Password:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>

                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" required>

                    <label for="idNumber">National ID/Passport Number:</label>
                    <input type="text" id="idNumber" name="idNumber"  placeholder="22-2345L 12" required>

                    <label for="dob">D.O.B</label>
                    <input type="date" id="dob" name="dob"  placeholder="23/08/2000" required>

                    <div class="form-group">
                    <label for="courseOfInterest">Course of Choice:</label>
                    <select id="courseOfInterest" name="courseOfChoice" required>
                    <option value="">-- Select a Course --</option>
                    <optgroup label="Day Classes">
                    <option value="ND In Information Technology">ND In Information Technology</option>
                    <option value="NC In Brick and Block Laying">NC In Brick and Block Laying</option>
                    <option value="NC In Wood Machining And Manufacturing Technology">NC In Wood Machining And Manufacturing Technology</option>
                    <option value="NC In Information Technology (IT)">NC In Information Technology (IT)</option>
                    <option value="NC In Beauty Therapy">NC In Beauty Therapy</option>
                    <option value="NC In Hairdressing">NC In Hairdressing</option>
                    <option value="NC In Cosmetology">NC In Cosmetology</option>
                    <option value="NFC In Brick and Block Laying">NFC In Brick and Block Laying</option>
                    <option value="NFC In Plumbing and Drain Laying">NFC In Plumbing and Drain Laying</option>
                    <option value="NFC In Information Technology">NFC In Information Technology</option>
                    <option value="NFC In Food Preparation">NFC In Food Preparation</option>
                    <option value="NFC In Tourism and Hospitality">NFC In Tourism and Hospitality</option>
                </optgroup>
                <optgroup label="Day and Evening Classes">
                    <option value="ND In Culinary Arts">ND In Culinary Arts</option>
                    <option value="ND In Tourism and Hospitality">ND In Tourism and Hospitality</option>
                    <option value="NC In Machineshop Engineering (Fitting and Turning)">NC In Machineshop Engineering (Fitting and Turning)</option>
                    <option value="NC In Draughtsmanship Design Technology">NC In Draughtsmanship Design Technology</option>
                    <option value="NC In Carpentry and Joinery">NC In Carpentry and Joinery</option>
                    <option value="NC In Tourism and Hospitality">NC In Tourism and Hospitality</option>
                    <option value="NFC In Professional Cookery">NFC In Professional Cookery</option>
                    <option value="NFC In Basic Machineshop Engineering">NFC In Basic Machineshop Engineering</option>
                    <option value="NFC In Car Maintenance">NFC In Car Maintenance</option>
                    <option value="NFC In Welding Techniques">NFC In Welding Techniques</option>
                    <option value="NFC In Carpentry">NFC In Carpentry</option>
                    <option value="NFC In Cabinet Making">NFC In Cabinet Making</option>
                    <option value="NFC In Joinery">NFC In Joinery</option>
                </optgroup>
                <optgroup label="Evening Classes">
                    <option value="NC In Diesel Plant Fitting">NC In Diesel Plant Fitting</option>
                    <option value="NC In Auto Electrics and Electronics">NC In Auto Electrics and Electronics</option>
                    <option value="NC In Motor Vehicle Mechanics">NC In Motor Vehicle Mechanics</option>
                    <option value="NC In Accountancy">NC In Accountancy</option>
                    <option value="NC In Auto Electrics and Electronics">NC In Auto Electrics and Electronics</option>
                    <option value="NFC In Electrical Installation">NFC In Electrical Installation</option>
                    <option value="NFC In Diesel Plant Fitting">NFC In Diesel Plant Fitting</option>
                </optgroup>
                <optgroup label="Short Courses">
                    <option value="Entrepreneurship">Entrepreneurship</option>
                    <option value="Welding Techniques">Welding Techniques</option>
                    <option value="Lathe and Milling Work">Lathe and Milling Work</option>
                    <option value="Carpentry and Joinery">Carpentry and Joinery</option>
                    <option value="Brick and Block Laying">Brick and Block Laying</option>
                    <option value="Plumbing and Drain Laying">Plumbing and Drain Laying</option>
                    <option value="Wheel Alignment">Wheel Alignment</option>
                    <option value="Hydraulics and Pneumatics">Hydraulics and Pneumatics</option>
                    <option value="Solar Systems Installation">Solar Systems Installation</option>
                    <option value="Tiling">Tiling</option>
                    <option value="Manicure and Pedicure">Manicure and Pedicure</option>
                    <option value="Facials">Facials</option>
                    <option value="Braiding and Weaving">Braiding and Weaving</option>
                    <option value="Closure Making, Wig Installation and Wig Making">Closure Making, Wig Installation and Wig Making</option>
                    <option value="Hairdressing">Hairdressing</option>
                    <option value="Massage Therapy">Massage Therapy</option>
                    <option value="Cake Making and Decorating">Cake Making and Decorating</option>
                    <option value="Baking Techniques">Baking Techniques</option>
                    <option value="Food Preparation">Food Preparation</option>
                    <option value="Basic Front Office Operations">Basic Front Office Operations</option>
                    <option value="Housekeeping">Housekeeping</option>
                    <option value="Food and Beverages Services">Food and Beverages Services</option>
                    <option value="Interior Decor">Interior Decor</option>
                    <option value="African Attire">African Attire</option>
                    <option value="Global Uniform Projects">Global Uniform Projects</option>
                    <option value="Work Wear Production">Work Wear Production</option>
                    <option value="Children's Wear and Baby Wear">Children's Wear and Baby Wear</option>
                    <option value="Curtain Decor">Curtain Decor</option>
                    <option value="Upholstery">Upholstery</option>
                    <option value="Fashion Design, Pattern Making and Sample Making">Fashion Design, Pattern Making and Sample Making</option>
                    <option value="Dressmaking Bearing Men's and Ladies Executive Suits">Dressmaking Bearing Men's and Ladies Executive Suits</option>
                    <option value="Microsoft Packages (Word, Excel, Access, Powerpoint, Outlook)">Microsoft Packages (Word, Excel, Access, Powerpoint, Outlook)</option>
                    <option value="Programming (C++, C#, Visual Basic, Python)">Programming (C++, C#, Visual Basic, Python)</option>
                    <option value="Graphic Design (Adobe InDesign, Adobe Photoshop, Adobe Illustrator)">Graphic Design (Adobe InDesign, Adobe Photoshop, Adobe Illustrator)</option>
                    <option value="Web Development (HTML, CSS, Javascript) - Front End/ Back End/ Databases /APIs">Web Development (HTML, CSS, Javascript) - Front End/ Back End/ Databases /APIs</option>
                    <option value="Computer Networking">Computer Networking</option>
                    </optgroup>
                    </select>
                    </div>

        
                    <label for="paymentProof">Proof of Payment (PDF/Image):</label>
                    <input type="file" id="paymentProof" name="paymentProof" accept=".pdf, .jpg, .jpeg, .png" required>

                    <label for="qualificationCert">Highest Qualification Certificate (PDF/Image):</label>
                    <input type="file" id="qualificationCert" name="qualificationCert" accept=".pdf, .jpg, .jpeg, .png" required>

                    <label for="birthCert">Birth Certificate (PDF/Image):</label>
                    <input type="file" id="birthCert" name="birthCert" accept=".pdf, .jpg, .jpeg, .png" required>

                    <label for="kinFullName">Next of Kin Full Name:</label>
                    <input type="text" id="kinFullName" name="kinFullName" required>

                    <label for="kinPhone">Next of Kin Phone Number:</label>
                    <input type="tel" id="kinPhone" name="kinPhone" required>

                    <button type="submit">Submit Enrollment</button>
                    <div id="enrollmentMessage" class="form-message"></div>
                </form>
            `;
            mainContentArea.innerHTML = enrollmentFormHtml;

            const enrollmentForm = document.getElementById('enrollmentForm');
            const enrollmentMessage = document.getElementById('enrollmentMessage');

            if (enrollmentForm) {
                enrollmentForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    enrollmentMessage.style.display = 'none';

                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;

                    if (password !== confirmPassword) {
                        enrollmentMessage.textContent = 'Passwords do not match.';
                        enrollmentMessage.className = 'form-message error';
                        enrollmentMessage.style.display = 'block';
                        return;
                    }

                    const formData = new FormData(this); // 'this' refers to the form

                    try {
                        const response = await fetch('../process_enrollment.php', {
                            method: 'POST',
                            body: formData // FormData handles multipart/form-data
                        });
                        const result = await response.json();

                        if (result.success) {
                            enrollmentMessage.textContent = result.message;
                            enrollmentMessage.className = 'form-message success';
                            enrollmentForm.reset(); // Clear form on success
                        } else {
                            enrollmentMessage.textContent = result.message || 'Enrollment failed. Please try again.';
                            enrollmentMessage.className = 'form-message error';
                        }
                    } catch (error) {
                        console.error('Enrollment error:', error);
                        enrollmentMessage.textContent = 'Network error or server unavailable. Please try again.';
                        enrollmentMessage.className = 'form-message error';
                    }
                    enrollmentMessage.style.display = 'block';
                });
            }
        });
    }

    // Staff Catalog Link Handler
    if (staffCatalogLink && mainContentArea) {
        staffCatalogLink.addEventListener('click', function(event) {
            event.preventDefault();
            setActiveMenuItem(this);
            
            // Fetch staff data from the backend
            fetch('../get_staff_catalog.php')
                .then(response => response.json())
                .then(data => {
                    let staffHtml = `
                        <h1>Staff Catalog</h1>
                        <p>Meet our dedicated faculty and staff members who are committed to providing quality education and support.</p>
                        <div class="staff-grid">
                    `;
                    
                    if (data.success && data.staff.length > 0) {
                        data.staff.forEach(staff => {
                            staffHtml += `
                                <div class="staff-card">
                                    <div class="staff-avatar">
                                        ${staff.image_path ? 
                                            `<img src="${staff.image_path}" alt="${staff.name}">` : 
                                            `<div class="staff-initials">${staff.name.split(' ').map(n => n[0]).join('')}</div>`
                                        }
                                    </div>
                                    <div class="staff-info">
                                        <h3>${staff.name}</h3>
                                        <p class="staff-title">${staff.title || 'Staff Member'}</p>
                                        <p class="staff-department"><strong>Department:</strong> ${staff.department || 'N/A'}</p>
                                        ${staff.email ? `<p class="staff-email"><strong>Email:</strong> <a href="mailto:${staff.email}">${staff.email}</a></p>` : ''}
                                        ${staff.phone ? `<p class="staff-phone"><strong>Phone:</strong> ${staff.phone}</p>` : ''}
                                        ${staff.office_location ? `<p class="staff-office"><strong>Office:</strong> ${staff.office_location}</p>` : ''}
                                        ${staff.specializations ? `<p class="staff-specializations"><strong>Specializations:</strong> ${staff.specializations}</p>` : ''}
                                        ${staff.qualifications ? `<p class="staff-qualifications"><strong>Qualifications:</strong> ${staff.qualifications}</p>` : ''}
                                        ${staff.biography ? `<p class="staff-bio">${staff.biography}</p>` : ''}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        staffHtml += `
                            <div class="no-staff-message">
                                <p>Staff information is currently being updated. Please check back later.</p>
                            </div>
                        `;
                    }
                    
                    staffHtml += `
                        </div>
                        <style>
                            .staff-grid {
                                display: grid;
                                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                                gap: 25px;
                                margin-top: 30px;
                            }
                            .staff-card {
                                background: white;
                                border-radius: 15px;
                                padding: 25px;
                                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                                transition: transform 0.3s ease;
                            }
                            .staff-card:hover {
                                transform: translateY(-5px);
                                box-shadow: 0 8px 25px rgba(74, 144, 226, 0.3);
                            }
                            .staff-avatar {
                                text-align: center;
                                margin-bottom: 20px;
                            }
                            .staff-avatar img {
                                width: 80px;
                                height: 80px;
                                border-radius: 50%;
                                object-fit: cover;
                            }
                            .staff-initials {
                                width: 80px;
                                height: 80px;
                                border-radius: 50%;
                                background: #4a90e2;
                                color: white;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 24px;
                                font-weight: bold;
                                margin: 0 auto;
                            }
                            .staff-info h3 {
                                color: #2c3e50;
                                margin-bottom: 10px;
                                font-size: 20px;
                            }
                            .staff-title {
                                color: #4a90e2;
                                font-weight: 600;
                                margin-bottom: 15px;
                                font-size: 16px;
                            }
                            .staff-info p {
                                margin-bottom: 8px;
                                font-size: 14px;
                                line-height: 1.5;
                            }
                            .staff-email a {
                                color: #4a90e2;
                                text-decoration: none;
                            }
                            .staff-email a:hover {
                                text-decoration: underline;
                            }
                            .staff-bio {
                                margin-top: 15px;
                                padding-top: 15px;
                                border-top: 1px solid #e9ecef;
                                font-style: italic;
                                color: #666;
                            }
                            .no-staff-message {
                                text-align: center;
                                padding: 60px 20px;
                                color: #666;
                                font-size: 18px;
                            }
                        </style>
                    `;
                    
                    mainContentArea.innerHTML = staffHtml;
                })
                .catch(error => {
                    console.error('Error loading staff catalog:', error);
                    mainContentArea.innerHTML = `
                        <h1>Staff Catalog</h1>
                        <div class="error-message">
                            <p>Unable to load staff information at this time. Please try again later.</p>
                        </div>
                    `;
                });
        });
    }
   
    //document.addEventListener('DOMContentLoaded', function() {
       // const mainContentArea = document.getElementById('main-content-area');
    
        // ... (rest of your existing menu item handlers and other functions) ...
    
        // Courses Offered Link Handler (CORRECTED to show detailed courses and requirements)
        if (coursesOfferedLink && mainContentArea) {
            coursesOfferedLink.addEventListener('click', function(event) {
                event.preventDefault();
                setActiveMenuItem(this);
                const coursesContentHtml = `
                    <h1>Courses Offered - August 2025 Intake</h1>
                    <p>Explore our diverse range of programs designed to prepare you for success. Each course has specific entry requirements.</p>
    
                    <div class="course-section">
                        <h2>General Entry Requirements</h2>
                        <ul>
                            <li><strong>Minimum Age:</strong> 18+ years.</li>
                            <li><strong>O'Levels:</strong> A minimum of 5 O'Levels including Mathematics, at least one language (e.g., English), and other relevant subjects specific to the course.</li>
                            <li><strong>A'Levels:</strong> Relevant A'Levels are an added advantage for higher-level courses.</li>
                        </ul>
                    </div>
    
                    <div class="course-section">
                        <h2>Course Levels & Preceding Qualifications</h2>
                        <ul>
                            <li><strong>Degree Programs:</strong>
                                <ul>
                                    <li>2 years after obtaining a Higher National Diploma (HND) in a relevant field.</li>
                                    <li>3 years after obtaining a National Diploma in a relevant field.</li>
                                    <li>4 years after obtaining a National Certificate in a relevant field.</li>
                                </ul>
                            </li>
                            <li><strong>Higher National Diploma (HND):</strong>
                                <ul>
                                    <li>2 years after obtaining a National Diploma in a relevant field.</li>
                                    <li>3 years after obtaining a National Certificate in a relevant field.</li>
                                </ul>
                            </li>
                            <li><strong>National Diploma:</strong> Requires a preceding National Certificate in a relevant field.</li>
                            <li><strong>National Certificate (NC):</strong> Requires standard O'Level qualifications as specified below.</li>
                            <li><strong>National Foundation Certificate (NFC):</strong> Generally requires standard O'Level qualifications or demonstrated aptitude.</li>
                        </ul>
                    </div>
    
                    <div class="course-section">
                        <h2>Day Classes</h2>
                        <p>Refer to specific requirements outlined above and general requirements.</p>
                        <ul>
                            <li>ND In Information Technology</li>
                            <li>NC In Brick and Block Laying</li>
                            <li>NC In Wood Machining And Manufacturing Technology</li>
                            <li>NC In Information Technology (IT)</li>
                            <li>NC In Beauty Therapy</li>
                            <li>NC In Hairdressing</li>
                            <li>NC In Cosmetology</li>
                            <li>NFC In Brick and Block Laying</li>
                            <li>NFC In Plumbing and Drain Laying</li>
                            <li>NFC In Information Technology</li>
                            <li>NFC In Food Preparation</li>
                            <li>NFC In Tourism and Hospitality</li>
                        </ul>
                    </div>
    
                    <div class="course-section">
                        <h2>Day and Evening Classes</h2>
                        <p>Refer to specific requirements outlined above and general requirements.</p>
                        <ul>
                            <li>ND In Culinary Arts</li>
                            <li>ND In Tourism and Hospitality</li>
                            <li>NC In Machineshop Engineering (Fitting and Turning)</li>
                            <li>NC In Draughtsmanship Design Technology</li>
                            <li>NC In Carpentry and Joinery</li>
                            <li>NC In Tourism and Hospitality</li>
                            <li>NFC In Professional Cookery</li>
                            <li>NFC In Basic Machineshop Engineering</li>
                            <li>NFC In Car Maintenance</li>
                            <li>NFC In Welding Techniques</li>
                            <li>NFC In Carpentry</li>
                            <li>NFC In Cabinet Making</li>
                            <li>NFC In Joinery</li>
                        </ul>
                    </div>
    
                    <div class="course-section">
                        <h2>Evening Classes</h2>
                        <p>Refer to specific requirements outlined above and general requirements.</p>
                        <ul>
                            <li>NC In Diesel Plant Fitting</li>
                            <li>NC In Auto Electrics and Electronics</li>
                            <li>NC In Motor Vehicle Mechanics</li>
                            <li>NC In Accountancy</li>
                            <li>NC In Auto Electrics and Electronics</li>
                            <li>NFC In Electrical Installation</li>
                            <li>NFC In Diesel Plant Fitting</li>
                        </ul>
                    </div>
    
                    <div class="course-section">
                        <h2>Short Courses</h2>
                        <p>Typically require basic literacy and numeracy. Specific prerequisites may apply.</p>
                        <ul>
                            <li>Entrepreneurship</li>
                            <li>Welding Techniques</li>
                            <li>Lathe and Milling Work</li>
                            <li>Carpentry and Joinery</li>
                            <li>Brick and Block Laying</li>
                            <li>Plumbing and Drain Laying</li>
                            <li>Wheel Alignment</li>
                            <li>Hydraulics and Pneumatics</li>
                            <li>Solar Systems Installation</li>
                            <li>Tiling</li>
                            <li>Manicure and Pedicure</li>
                            <li>Facials</li>
                            <li>Braiding and Weaving</li>
                            <li>Closure Making, Wig Installation and Wig Making</li>
                            <li>Hairdressing</li>
                            <li>Massage Therapy</li>
                            <li>Cake Making and Decorating</li>
                            <li>Baking Techniques</li>
                            <li>Food Preparation</li>
                            <li>Basic Front Office Operations</li>
                            <li>Housekeeping</li>
                            <li>Food and Beverages Services</li>
                            <li>Interior Decor</li>
                            <li>African Attire</li>
                            <li>Global Uniform Projects</li>
                            <li>Work Wear Production</li>
                            <li>Children's Wear and Baby Wear</li>
                            <li>Curtain Decor</li>
                            <li>Upholstery</li>
                            <li>Fashion Design, Pattern Making and Sample Making</li>
                            <li>Dressmaking Bearing Men's and Ladies Executive Suits</li>
                            <li>Microsoft Packages (Word, Excel, Access, Powerpoint, Outlook)</li>
                            <li>Programming (C++, C#, Visual Basic, Python)</li>
                            <li>Graphic Design (Adobe InDesign, Adobe Photoshop, Adobe Illustrator)</li>
                            <li>Web Development (HTML, CSS, Javascript) - Front End/ Back End/ Databases /APIs</li>
                            <li>Computer Networking</li>
                        </ul>
                    </div>
    
                    <div class="course-section" style="margin-top: 30px; text-align: center;">
                        <h2>August 2025 Intake & Contact</h2>
                        <img src="../1000174122.jpg" alt="August 2025 Intake Courses" style="max-width: 100%; height: auto; display: block; margin: 20px auto;">
                        <p style="text-align: center; margin-top: 15px; font-size: 1.1em; font-weight: bold;">
                            ENROL TODAY!!!
                        </p>
                        <p style="text-align: center; font-size: 0.9em; color: #666;">
                            For more information on enrolment and courses contact us on:
                        </p>
                        <p style="text-align: center; font-size: 1em; font-weight: bold;">
                            📞 086 4428 3755 | 086 4428 3756 | 086 4428 3757
                        </p>
                        <p style="text-align: center; font-size: 0.9em; color: #666;">
                            📧 info@MyCampus_Portal.ac.zw
                        </p>
                        <p style="text-align: center; font-size: 0.9em; color: #666;">
                            🌐 www.MyCamp_WelcomesU.ac.zw
                        </p>
                    </div>
                `;
                mainContentArea.innerHTML = coursesContentHtml;
            });
        }
    
        // ... (rest of your script.js including initial click and loadForgotPasswordForm) ...
    
    }); // End DOMContentLoaded
    

    // Initial load: Simulate click on Welcome Board to show default content
    if (welcomeBoardLink) {
        welcomeBoardLink.click();
    }

    // Functions for forgot password (from previous responses)
    function loadForgotPasswordForm() {
        const forgotPasswordFormHtml = `
            <h1>Forgot Password</h1>
            <p>Enter your email address to receive a password reset code or answer your security question.</p>
            <form id="forgotPasswordForm" class="enrollment-form">
                <label for="fpEmail">Email Address:</label>
                <input type="email" id="fpEmail" name="email" required>

                <div id="securityQuestionArea" style="display:none;">
                    <label for="securityQuestion">Security Question:</label>
                    <p id="displayedSecurityQuestion"></p>
                    <input type="text" id="securityAnswer" name="securityAnswer" placeholder="Your answer" autocomplete="off">
                </div>

                <div id="backupNumberArea" style="display:none;">
                    <label for="backupNumber">Backup Phone Number:</label>
                    <input type="tel" id="backupNumber" name="backupNumber" placeholder="Enter your backup number" required>
                </div>

                <button type="button" id="getResetOptionsBtn">Get Reset Options</button>
                <button type="submit" id="sendResetCodeBtn" style="display:none;">Send Reset Code / Verify</button>
                <div id="forgotPasswordMessage" class="form-message"></div>
                <p><a href="#" id="backToLoginLink">Back to Login</a></p>
            </form>
        `;
        mainContentArea.innerHTML = forgotPasswordFormHtml;

        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const fpEmailInput = document.getElementById('fpEmail');
        const getResetOptionsBtn = document.getElementById('getResetOptionsBtn');
        const sendResetCodeBtn = document.getElementById('sendResetCodeBtn');
        const securityQuestionArea = document.getElementById('securityQuestionArea');
        const displayedSecurityQuestion = document.getElementById('displayedSecurityQuestion');
        const backupNumberArea = document.getElementById('backupNumberArea');
        const forgotPasswordMessage = document.getElementById('forgotPasswordMessage');
        const backToLoginLink = document.getElementById('backToLoginLink');

        if (getResetOptionsBtn) {
            getResetOptionsBtn.addEventListener('click', async function() {
                forgotPasswordMessage.style.display = 'none';
                const email = fpEmailInput.value;
                if (!email) {
                    forgotPasswordMessage.textContent = 'Please enter your email address.';
                    forgotPasswordMessage.className = 'form-message error';
                    forgotPasswordMessage.style.display = 'block';
                    return;
                }

                try {
                    const response = await fetch('../get_reset_options.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email })
                    });
                    const result = await response.json();

                    if (result.success) {
                        if (result.security_question) {
                            displayedSecurityQuestion.textContent = result.security_question;
                            securityQuestionArea.style.display = 'block';
                        }
                        if (result.backup_number_available) {
                            backupNumberArea.style.display = 'block';
                            document.getElementById('backupNumber').value = result.backup_number_masked; // Show masked number
                        }
                        getResetOptionsBtn.style.display = 'none';
                        sendResetCodeBtn.style.display = 'block';
                        forgotPasswordMessage.textContent = 'Please choose a reset option.';
                        forgotPasswordMessage.className = 'form-message success';
                    } else {
                        forgotPasswordMessage.textContent = result.message || 'Email not found or no reset options available.';
                        forgotPasswordMessage.className = 'form-message error';
                    }
                } catch (error) {
                    console.error('Error fetching reset options:', error);
                    forgotPasswordMessage.textContent = 'Network error or server unavailable.';
                    forgotPasswordMessage.className = 'form-message error';
                }
                forgotPasswordMessage.style.display = 'block';
            });
        }

        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                forgotPasswordMessage.style.display = 'none';

                const email = fpEmailInput.value;
                const securityAnswer = document.getElementById('securityAnswer').value;
                const backupNumber = document.getElementById('backupNumber').value;

                try {
                    const response = await fetch('../process_password_reset.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: email,
                            securityAnswer: securityAnswer,
                            backupNumber: backupNumber
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        forgotPasswordMessage.textContent = result.message;
                        forgotPasswordMessage.className = 'form-message success';
                        // If code sent, potentially display a field for code verification
                        // Then another form for new password
                    } else {
                        forgotPasswordMessage.textContent = result.message || 'Password reset failed.';
                        forgotPasswordMessage.className = 'form-message error';
                    }
                } catch (error) {
                    console.error('Password reset error:', error);
                    forgotPasswordMessage.textContent = 'Network error during password reset. Please try again.';
                    forgotPasswordMessage.className = 'form-message error';
                }
                forgotPasswordMessage.style.display = 'block';
            });
        }

        if (backToLoginLink) {
            backToLoginLink.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('student-login-link').click(); // Simulate click on Student Login
            });
        }
    }

// student organization content

function loadContent(pageIdentifier, title = '') {
    const mainContentArea = document.getElementById('main-content-area');
    let contentHtml = '';
    switch (pageIdentifier) {
        case 'student_government':
            contentHtml = `
                <h2>Student Government</h2>
                <p>The Student Government represents student interests, organizes campus-wide events, and acts as a bridge between students and administration. Get involved to make your voice heard and help shape campus life!</p>
            `;
            break;
        case 'academic_clubs':
            contentHtml = `
                <h2>Academic Clubs</h2>
                <p>Academic clubs provide opportunities for students to deepen their knowledge, collaborate on projects, and participate in competitions. Join a club related to your field of study or start a new one!</p>
            `;
            break;
        case 'cultural_organizations':
            contentHtml = `
                <h2>Cultural Organizations</h2>
                <p>Cultural organizations celebrate diversity and promote cultural awareness on campus. Participate in events, festivals, and workshops to learn about and share different cultures.</p>
            `;
            break;
        case 'sports_recreation':
            contentHtml = `
                <h2>Sports & Recreation</h2>
                <p>Stay active and healthy by joining sports teams or recreational clubs. Whether you are interested in competitive sports or just want to have fun, there is something for everyone!</p>
            `;
            break;
        case 'volunteer_groups':
            contentHtml = `
                <h2>Volunteer Groups</h2>
                <p>Volunteer groups offer students a chance to give back to the community, develop leadership skills, and make a positive impact. Find a cause you care about and get involved!</p>
            `;
            break;
        default:
            contentHtml = `<h2>${title}</h2><p>Content coming soon.</p>`;
    }
    mainContentArea.innerHTML = contentHtml;
}







// In script.js

// ... existing menu item handlers ...

// Student Organizations
const studentGovernmentLink = document.getElementById('student-government-link');
const academicClubsLink = document.getElementById('academic-clubs-link');
const culturalOrgsLink = document.getElementById('cultural-orgs-link');
const sportsRecreationLink = document.getElementById('sports-recreation-link');
const volunteerGroupsLink = document.getElementById('volunteer-groups-link');

if (studentGovernmentLink) {
    studentGovernmentLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('student_government', 'Student Government');
        setActiveMenuItem(this);
    });
}
if (academicClubsLink) {
    academicClubsLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('academic_clubs', 'Academic Clubs');
        setActiveMenuItem(this);
    });
}
if (culturalOrgsLink) {
    culturalOrgsLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('cultural_organizations', 'Cultural Organizations');
        setActiveMenuItem(this);
    });
}
if (sportsRecreationLink) {
    sportsRecreationLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('sports_recreation', 'Sports & Recreation');
        setActiveMenuItem(this);
    });
}
if (volunteerGroupsLink) {
    volunteerGroupsLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('volunteer_groups', 'Volunteer Groups');
        setActiveMenuItem(this);
    });
}

// ... rest of your script.js ...



; // End DOMContentLoaded
