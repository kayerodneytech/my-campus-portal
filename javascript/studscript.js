// Ensure this script runs after the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get references to the new and existing elements by their IDs
    const studentNameDisplay = document.getElementById('studentNameDisplay');
    const userAvatarInitial = document.getElementById('userAvatarInitial');
    const studentCourseNameSpan = document.getElementById('studentCourseName');
    const settingsIcon = document.getElementById('settingsIcon');
    const mainContentArea = document.getElementById('mainContentArea');
    const comingSoonMessageDiv = document.getElementById('comingSoonMessage');

    // Quick Action Buttons
    const viewCoursesBtn = document.getElementById('viewCoursesBtn');
    const assignmentsBtn = document.getElementById('assignmentsBtn');
    const viewGradesBtn = document.getElementById('viewGradesBtn');
    const timetableBtn = document.getElementById('timetableBtn');
    const libraryBtn = document.getElementById('libraryBtn');
    const paymentsBtn = document.getElementById('paymentsBtn');
    const accommodationBtn = document.getElementById('accommodationBtn');
    const srcVotingBtn = document.getElementById('srcVotingBtn');
    const messagesBtn = document.getElementById('messagesBtn');

    // Stat Cards
    const pendingPaymentsCard = document.getElementById('pendingPaymentsCard');
    const borrowedBooksCard = document.getElementById('borrowedBooksCard');

    // Dynamic Content Element IDs (for updating values)
    const enrolledCoursesCount = document.getElementById('enrolledCoursesCount');
    const pendingAssignmentsCount = document.getElementById('pendingAssignmentsCount');
    const averageGradeValue = document.getElementById('averageGradeValue');
    const attendanceRateValue = document.getElementById('attendanceRateValue');
    const pendingPaymentsCount = document.getElementById('pendingPaymentsCount');
    const borrowedBooksCount = document.getElementById('borrowedBooksCount');
    const recentActivitiesContent = document.getElementById('recentActivitiesContent');
    const notificationsContent = document.getElementById('notificationsContent');
    const lecturerList = document.getElementById('lecturerList'); // For the right menu

    // --- Simulated Student Data (Replace with data from PHP after login) ---
    // In a real application, PHP would pass this data to JavaScript (e.g., via a JSON endpoint)
    const studentData = {
        fullName: "Fungai Maonza",
        role: "Student",
        courseName: "Information Technology",
        enrolledCourses: 3,
        pendingAssignments: 2,
        averageGrade: 78,
        attendanceRate: 92,
        pendingPayments: 1, // Number of pending payments
        pendingPaymentAmount: 150.00, // Example amount
        isPaymentOverdue: true, // Set to true for red/amber color
        borrowedBooks: 0,
        idStatus: 'unpaid', // 'unpaid', 'paid', 'generated'
        generatedId: null,
        recentActivities: [
            "Logged in to dashboard (2 hours ago)",
            "Submitted Web Design Assignment 1 (yesterday)",
            "Viewed Database Design module details (3 days ago)"
        ],
        notifications: [
            "New assignment for Cyber Security posted!",
            "Your average grade has been updated.",
            "Reminder: Tuition fee due on 2025-08-15."
        ],
        lecturers: [
            { name: "John Langa", module: "Web Design", avatarInitial: "JL" },
            { name: "Anna Sibanda", module: "Database Design", avatarInitial: "AS" },
            { name: "Zodwa Moyo", module: "Cyber Security", avatarInitial: "ZM" }
        ]
    };

    // --- Populate Dashboard with Simulated Data ---
    if (studentNameDisplay) studentNameDisplay.textContent = studentData.fullName;
    if (userAvatarInitial) userAvatarInitial.textContent = studentData.fullName.charAt(0);
    if (studentCourseNameSpan) studentCourseNameSpan.textContent = studentData.courseName;

    // Update stat cards
    if (enrolledCoursesCount) enrolledCoursesCount.textContent = studentData.enrolledCourses;
    if (pendingAssignmentsCount) pendingAssignmentsCount.textContent = studentData.pendingAssignments;
    if (averageGradeValue) averageGradeValue.textContent = studentData.averageGrade + '%';
    if (attendanceRateValue) attendanceRateValue.textContent = studentData.attendanceRate + '%';
    if (pendingPaymentsCount) pendingPaymentsCount.textContent = studentData.pendingPayments;
    if (borrowedBooksCount) borrowedBooksCount.textContent = studentData.borrowedBooks;

    // Apply overdue payment style
    if (pendingPaymentsCard && studentData.isPaymentOverdue) {
        pendingPaymentsCard.classList.add('overdue-payment');
    }

    // Populate Recent Activities
    if (recentActivitiesContent) {
        if (studentData.recentActivities.length > 0) {
            recentActivitiesContent.innerHTML = '<ul>' + studentData.recentActivities.map(activity => `<li>${activity}</li>`).join('') + '</ul>';
            recentActivitiesContent.classList.remove('empty-state');
            recentActivitiesContent.style.textAlign = 'left';
        } else {
            recentActivitiesContent.textContent = "No recent activities";
            recentActivitiesContent.classList.add('empty-state');
            recentActivitiesContent.style.textAlign = 'center';
        }
    }

    // Populate Notifications
    if (notificationsContent) {
        if (studentData.notifications.length > 0) {
            notificationsContent.innerHTML = '<ul>' + studentData.notifications.map(notification => `<li>${notification}</li>`).join('') + '</ul>';
            notificationsContent.classList.remove('empty-state');
            notificationsContent.style.textAlign = 'left';
        } else {
            notificationsContent.textContent = "No new notifications";
            notificationsContent.classList.add('empty-state');
            notificationsContent.style.textAlign = 'center';
        }
    }

    // Populate Lecturers for right-side menu
    if (lecturerList) {
        lecturerList.innerHTML = ''; // Clear existing dummy items
        if (studentData.lecturers.length > 0) {
            studentData.lecturers.forEach(lecturer => {
                const li = document.createElement('li');
                li.classList.add('lecturer-item');
                li.innerHTML = `
                    <div class="lecturer-avatar">${lecturer.avatarInitial}</div>
                    <div class="lecturer-details">
                        <span class="lecturer-name">${lecturer.name}</span>
                        <span class="lecturer-module">${lecturer.module}</span>
                    </div>
                `;
                li.addEventListener('click', () => {
                    // Placeholder for lecturer messaging
                    alert(`Opening chat with ${lecturer.name} (${lecturer.module})`);
                });
                lecturerList.appendChild(li);
            });
        } else {
            lecturerList.innerHTML = '<p class="empty-state" style="margin-top:0;">No lecturers assigned yet.</p>';
        }
    }

    // --- Helper function to load content into mainContentArea ---
    async function loadContent(pageIdentifier, title = '') {
        // Clear previous messages / content
        if (comingSoonMessageDiv) comingSoonMessageDiv.style.display = 'none'; // Hide "coming soon" messages
        mainContentArea.innerHTML = '<div style="text-align:center; padding:50px;">Loading...</div>'; // Show loading

        let contentHtml = '';
        let sectionTitle = title || pageIdentifier.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase()); // Convert camelCase to Title Case

        // Hide stats grid, quick actions, bottom section if loading a specific page
        document.querySelector('.stats-grid').style.display = 'none';
        document.querySelector('.quick-actions').style.display = 'none';
        document.querySelector('.bottom-section').style.display = 'none';

        // Simulate fetching different content
        switch (pageIdentifier) {
            case 'settings':
                contentHtml = `
                    <h2 class="section-title">Settings</h2>
                    <div class="settings-content" style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; width: 100%; max-width: 500px; text-align: left;">
                        <h4>Personal Information</h4>
                        <p>Name: ${studentData.fullName}</p>
                        <p>Course: ${studentData.courseName}</p>
                        <button style="margin-top: 15px; padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer;">Edit Personal Info</button>

                        <h4 style="margin-top: 30px;">Change Password</h4>
                        <form id="changePasswordForm">
                            <label for="currentPassword" style="display:block; margin-bottom: 5px;">Current Password:</label>
                            <input type="password" id="currentPassword" style="width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: rgba(255,255,255,0.2); color:white;" required><br>
                            <label for="newPassword" style="display:block; margin-bottom: 5px;">New Password:</label>
                            <input type="password" id="newPassword" style="width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: rgba(255,255,255,0.2); color:white;" required><br>
                            <label for="confirmNewPassword" style="display:block; margin-bottom: 5px;">Confirm New Password:</label>
                            <input type="password" id="confirmNewPassword" style="width: 100%; padding: 8px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; background-color: rgba(255,255,255,0.2); color:white;" required><br>
                            <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer;">Change Password</button>
                        </form>

                        <h4 style="margin-top: 30px;">Generate Student ID</h4>
                        <p>A $5 payment is required to generate your official student ID.</p>
                        <button id="generateIdBtn" style="padding: 10px 20px; background-color: #17a2b8; color: white; border: none; border-radius: 8px; cursor: pointer;">
                            ${studentData.idStatus === 'generated' ? 'View Generated ID' : (studentData.idStatus === 'paid' ? 'Generate ID' : 'Pay $5 & Generate ID')}
                        </button>
                        <p id="generatedIdDisplay" style="margin-top: 10px; font-weight: bold; color: #ffd700;">
                            ${studentData.idStatus === 'generated' ? `Your ID: ${studentData.generatedId}` : ''}
                        </p>
                    </div>
                `;
                break;
            case 'courses':
                // Simulate fetching modules for the enrolled course
                const modules = [
                    { name: "Database Design", requirements: "120hr practical, 2 theory assignments, 2 practical assignments, 2 in-class tests." },
                    { name: "Web Design and Development", requirements: "150hr practical, 3 theory assignments, 3 practical assignments, 1 final project." },
                    { name: "Data Structures", requirements: "80hr practical, 1 theory assignment, 1 practical assignment, 1 final exam." },
                    { name: "CQM", requirements: "60hr theory, 2 theory assignments, 1 final exam." },
                    { name: "Cyber Security", requirements: "100hr practical, 2 theory assignments, 2 practical assignments, 1 final project." },
                    { name: "Practical Project", requirements: "Capstone project, ongoing mentorship, final presentation." }
                ];
                contentHtml = `<h2 class="section-title">${sectionTitle} - ${studentData.courseName}</h2>`;
                contentHtml += `<ul style="list-style: none; padding: 0; width: 100%; max-width: 700px; text-align: left;">`;
                modules.forEach(module => {
                    contentHtml += `
                        <li style="background: rgba(255,255,255,0.1); padding: 15px; margin-bottom: 10px; border-radius: 10px;">
                            <h4 style="margin-bottom: 5px; color: #a78bfa;">${module.name}</h4>
                            <p style="font-size: 0.95em;">Requirements: ${module.requirements}</p>
                        </li>
                    `;
                });
                contentHtml += `</ul>`;
                break;
            case 'assignments':
                contentHtml = `
                    <h2 class="section-title">${sectionTitle}</h2>
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; width: 100%; max-width: 600px; text-align: left;">
                        <p style="font-weight: bold; margin-bottom: 10px;">Pending Assignments:</p>
                        <ul>
                            <li>Web Design - Assignment 2 (Due: 2025-08-01) <button style="float:right; background-color:#28a745; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">Submit</button></li>
                            <li>Database Design - Case Study (Due: 2025-08-10) <button style="float:right; background-color:#28a745; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">Submit</button></li>
                        </ul>
                        <p style="font-weight: bold; margin-top: 20px; margin-bottom: 10px;">Completed Assignments:</p>
                        <ul>
                            <li>Web Design - Assignment 1 (Submitted: 2025-07-20) - Grade: 85%</li>
                        </ul>
                    </div>
                `;
                break;
            case 'grades':
                contentHtml = `
                    <h2 class="section-title">${sectionTitle}</h2>
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; width: 100%; max-width: 700px;">
                        <p style="font-weight: bold; font-size: 1.1em; margin-bottom: 20px; text-align: center;">Overall Average Grade: ${studentData.averageGrade}%</p>
                        <h4 style="margin-bottom: 10px; color: #a78bfa;">Final Exam Results:</h4>
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.15);">
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Module</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Lecturer</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Mark (%)</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Database Design</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Anna Sibanda</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">75</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1); color: #8bc34a;">Passed</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Web Design and Development</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">John Langa</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">68</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1); color: #8bc34a;">Passed</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Cyber Security</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Zodwa Moyo</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">--</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1); color: #ffeb3b;">Pending</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
                break;
            case 'timetable':
                contentHtml = `
                    <h2 class="section-title">${sectionTitle}</h2>
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; width: 100%; max-width: 800px; text-align: left;">
                        <h4 style="color: #a78bfa; margin-bottom: 10px;">Information Technology Timetable:</h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.15);">
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Day</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Time</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Module</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Room</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Lecturer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Monday</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">09:00 - 11:00</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Database Design</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Lab 1</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">A. Sibanda</td></tr>
                                <tr><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Tuesday</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">14:00 - 16:00</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Web Design</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Lecture Hall 3</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">J. Langa</td></tr>
                                <tr><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Wednesday</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">10:00 - 12:00</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Cyber Security</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Lab 2</td><td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Z. Moyo</td></tr>
                                </tbody>
                        </table>
                        <p style="margin-top: 20px; font-style: italic; font-size: 0.9em;">Note: Timetables may vary for students with multiple enrolled courses across different departments. Consult your department head for specific multi-course timetables.</p>
                    </div>
                `;
                break;
            case 'payments':
                contentHtml = `
                    <h2 class="section-title">${sectionTitle}</h2>
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; width: 100%; max-width: 700px; text-align: left;">
                        <h4 style="color: #a78bfa; margin-bottom: 15px;">Payment History</h4>
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.15);">
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Date</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Description</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Amount</th>
                                    <th style="padding: 10px; border: 1px solid rgba(255,255,255,0.2);">Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">2025-01-10</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Registration Fee</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">$500.00</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);"><a href="#" style="color:#a78bfa;">View Slip</a></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">2025-02-15</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">Tuition - Q1</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">$1200.00</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);"><a href="#" style="color:#a78bfa;">View Slip</a></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1); color: #f44336;">2025-07-25</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1); color: #f44336;">Late Library Book Fine</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1); color: #f44336;">$15.00</td>
                                    <td style="padding: 10px; border: 1px solid rgba(255,255,255,0.1);">N/A</td>
                                </tr>
                            </tbody>
                        </table>

                        <h4 style="color: #a78bfa; margin-bottom: 15px;">Outstanding Fines:</h4>
                        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span>Total Fines: $${studentData.isPaymentOverdue ? studentData.pendingPaymentAmount : '0.00'}</span>
                            <button style="padding: 10px 20px; background-color: #ff9800; color: white; border: none; border-radius: 8px; cursor: pointer;">Pay Fine</button>
                        </div>

                        <h4 style="color: #a78bfa; margin-bottom: 15px;">Upload Payment Proof:</h4>
                        <form style="display: flex; flex-direction: column; gap: 10px;">
                            <input type="file" style="padding: 10px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; color: white;">
                            <input type="text" placeholder="Payment Description (e.g., Tuition, Reg. Fee)" style="padding: 10px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; color: white;" required>
                            <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer;">Upload Proof</button>
                        </form>
                    </div>
                `;
                break;
            case 'messages':
                contentHtml = `
                    <h2 class="section-title">${sectionTitle}</h2>
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 15px; width: 100%; max-width: 800px; display: flex; gap: 20px; min-height: 300px;">
                        <div style="flex: 1; border-right: 1px solid rgba(255,255,255,0.2); padding-right: 20px;">
                            <h4 style="color: #a78bfa; margin-bottom: 15px;">Inbox</h4>
                            <ul style="list-style: none; padding: 0;">
                                <li style="padding: 10px; background: rgba(255,255,255,0.1); margin-bottom: 8px; border-radius: 5px; cursor: pointer;">
                                    <strong style="color: #fff;">Admin - New Results Available</strong><br>
                                    <small style="color: rgba(255,255,255,0.7);">2 hours ago</small>
                                </li>
                                <li style="padding: 10px; background: rgba(255,255,255,0.05); margin-bottom: 8px; border-radius: 5px; cursor: pointer;">
                                    <strong style="color: rgba(255,255,255,0.8);">Lecturer John Langa - Assignment Feedback</strong><br>
                                    <small style="color: rgba(255,255,255,0.6);">Yesterday</small>
                                </li>
                                <li style="padding: 10px; background: rgba(255,255,255,0.05); margin-bottom: 8px; border-radius: 5px; cursor: pointer;">
                                    <strong style="color: rgba(255,255,255,0.8);">Study Group IT-201 - Meeting Reminder</strong><br>
                                    <small style="color: rgba(255,255,255,0.6);">3 days ago</small>
                                </li>
                            </ul>
                            <button style="margin-top: 20px; padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer;">New Message</button>
                        </div>
                        <div style="flex: 2; padding-left: 20px;">
                            <h4 style="color: #a78bfa; margin-bottom: 15px;">Selected Message</h4>
                            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; min-height: 200px;">
                                <p style="font-weight: bold; margin-bottom: 5px;">From: Admin</p>
                                <p style="font-size: 0.9em; color: rgba(255,255,255,0.7); margin-bottom: 10px;">Subject: New Semester Results Available</p>
                                <p>Dear Fungai, your final semester results for all modules are now available on the "View Grades" section of your dashboard. Please review them at your earliest convenience.</p>
                                <p style="margin-top: 20px; font-style: italic;">Best regards,<br>The Administration Team</p>
                            </div>
                        </div>
                    </div>
                `;
                break;
            default: // For "coming soon" features
                contentHtml = `
                    <div id="comingSoonMessage" style="display:block; max-width: 100%; margin: 0;">
                        <h3>${sectionTitle} - Feature Coming Soon!</h3>
                        <p>We are working hard to bring you this feature. Please check back later.</p>
                    </div>
                `;
                // To hide the general dashboard elements if a "coming soon" page is displayed
                document.querySelector('.stats-grid').style.display = 'none';
                document.querySelector('.quick-actions').style.display = 'none';
                document.querySelector('.bottom-section').style.display = 'none';
                break;
        }

        mainContentArea.innerHTML = contentHtml; // Update content
        // Re-attach event listeners if elements are replaced
        attachDynamicEventListeners(); // Call this function after content is loaded
    }

    // Function to attach event listeners to dynamically loaded content
    function attachDynamicEventListeners() {
        const generateIdBtn = document.getElementById('generateIdBtn');
        const generatedIdDisplay = document.getElementById('generatedIdDisplay');
        const changePasswordForm = document.getElementById('changePasswordForm');

        if (generateIdBtn) {
            generateIdBtn.addEventListener('click', function() {
                if (studentData.idStatus === 'generated') {
                    alert('Your student ID has already been generated: ' + studentData.generatedId);
                    return;
                }
                if (studentData.idStatus === 'paid') {
                    // Simulate ID generation
                    const newId = 'STD' + Math.floor(100000 + Math.random() * 900000); // Simple random ID
                    studentData.generatedId = newId;
                    studentData.idStatus = 'generated';
                    alert('Student ID generated: ' + newId);
                    if (generatedIdDisplay) generatedIdDisplay.textContent = `Your ID: ${newId}`;
                    generateIdBtn.textContent = 'View Generated ID';
                } else {
                    if (confirm('A $5 payment is required. Simulate payment now?')) {
                        studentData.idStatus = 'paid';
                        alert('Payment simulated! You can now generate your ID.');
                        generateIdBtn.textContent = 'Generate ID';
                    }
                }
            });
        }

        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmNewPassword = document.getElementById('confirmNewPassword').value;

                if (newPassword !== confirmNewPassword) {
                    alert('New password and confirm password do not match.');
                    return;
                }
                // In a real app, send to server for validation and update
                alert('Password change simulated for ' + studentData.fullName + '! (Current: ' + currentPassword + ', New: ' + newPassword + ')');
                changePasswordForm.reset();
            });
        }
    }


    // --- Global Navigation Functions ---
    // Function to navigate back to the main dashboard view
    function showDashboard() {
        if (mainContentArea) {
            mainContentArea.innerHTML = ''; // Clear loaded content
            // Restore visibility of dashboard sections
            document.querySelector('.stats-grid').style.display = 'grid';
            document.querySelector('.quick-actions').style.display = 'block';
            document.querySelector('.bottom-section').style.display = 'grid';
            // You might also need to re-render the initial dashboard elements if they were completely removed
            // For now, assume they are always in the HTML and just hidden/shown
        }
    }

    // --- Attach Event Listeners ---
    // Logout button (already has onclick)
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            alert('Logging out...');
            // Redirect to login page
            // window.location.href = 'login.html';
        }
    }
    window.logout = logout; // Make it globally accessible for onclick attribute

    // Settings Icon
    if (settingsIcon) {
        settingsIcon.addEventListener('click', () => loadContent('settings', 'Student Settings'));
    }

    // Quick Action Buttons
    if (viewCoursesBtn) viewCoursesBtn.addEventListener('click', () => loadContent('courses', 'My Enrolled Courses'));
    if (assignmentsBtn) assignmentsBtn.addEventListener('click', () => loadContent('assignments', 'My Assignments'));
    if (viewGradesBtn) viewGradesBtn.addEventListener('click', () => loadContent('grades', 'My Grades'));
    if (timetableBtn) timetableBtn.addEventListener('click', () => loadContent('timetable', 'My Timetable'));
    if (paymentsBtn) paymentsBtn.addEventListener('click', () => loadContent('payments', 'My Payments'));
    if (messagesBtn) messagesBtn.addEventListener('click', () => loadContent('messages', 'My Messages'));

    // "Feature Coming Soon" buttons/cards
    if (accommodationBtn) accommodationBtn.addEventListener('click', () => loadContent('accommodation', 'Accommodation'));
    if (srcVotingBtn) srcVotingBtn.addEventListener('click', () => loadContent('voting', 'SRC Voting'));
    if (libraryBtn) libraryBtn.addEventListener('click', () => loadContent('library', 'Library Services'));
    // If the borrowedBooksCard itself is clickable for "coming soon"
    if (borrowedBooksCard) borrowedBooksCard.addEventListener('click', () => loadContent('borrowedBooks', 'Borrowed Books'));

    // Initial load for dashboard content (if you want to load it dynamically on page load)
    // For now, the main content is already in the HTML, so we just set values.
    // If you plan to load ALL dashboard content dynamically later, this is where you'd call loadContent('dashboard');

    // Add hover animations for stat cards (already in your original script)
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px) scale(1.02)';
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Make the content area revert to dashboard if clicked on the title
    document.querySelector('.header h1').addEventListener('click', showDashboard);
});
