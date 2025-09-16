# MyCamp Portal

**A Comprehensive Educational Management System**

![MyCamp Portal Logo](1753449127073.jpg)

**MyCamp Portal** is a modern, responsive platform designed for **students, lecturers, and administrators** to manage academic operations, elections, payments, and more. Built with **PHP, MySQL, and Tailwind CSS**, it offers a seamless experience for educational institutions.

---

## 🌟 Core Features

### **For Students**

- **Registration & Enrollment**: Multi-step form with payment integration.
- **Profile Management**: Update personal and academic details.
- **Course Enrollment**: Browse and enroll in available courses.
- **Elections**: Participate in SRC elections, view candidates, and cast votes.
- **Assignments**: Submit assignments and view grades/feedback.
- **Attendance**: Track class attendance.
- **Library**: Borrow books and track borrowing history.
- **Clubs & Events**: Join clubs and register for campus events.

### **For Lecturers**

- **Dashboard**: Overview of assigned classes and assignments.
- **Profile Management**: Update professional details.
- **Assignments**: Create, grade, and manage submissions.
- **Announcements**: Communicate with students via class announcements.

### **For Administrators**

- **Dashboard Analytics**: System overview with key metrics.
- **Student Management**: Approve/reject applications and manage records.
- **Course Management**: Full CRUD operations for courses and programs.
- **Election Management**: Create, monitor, and manage elections.
- **Reports**: Generate reports for payments, attendance, and grades.
- **Activity Monitoring**: Comprehensive audit trails and system logs.

---

## 🏗️ System Architecture

### **Tech Stack**

- **Frontend**: HTML5, Tailwind CSS, JavaScript (ES6+), Chart.js
- **Backend**: PHP 8.2+
- **Database**: MySQL 10.4+ (MariaDB)
- **Authentication**: Session-based with bcrypt password hashing
- **Security**: CSRF protection, SQL injection prevention, XSS protection

### **Directory Structure**

```bash
school/
├── admin/                  # Admin portal
│   ├── components/        # Reusable UI components
│   ├── includes/           # Admin includes and utilities
│   │   └── async/          # Async endpoints for dynamic data
│   ├── utils/              # Utility scripts
│   ├── courses.php         # Course management
│   ├── elections.php       # Election management
│   ├── index.php           # Admin dashboard
│   ├── lecturers.php       # Lecturer management
│   ├── login.php           # Admin login
│   ├── reports.php         # Reports and analytics
│   └── students.php        # Student management
│
├── api/                    # API endpoints
│   └── payment.php         # Payment processing
│
├── assets/                 # Static assets
│   ├── fonts/              # Custom fonts (e.g., Poppins)
│   └── images/             # Images and logos
│
├── components/              # Shared UI components
│   ├── footer.php
│   ├── header.php
│   ├── login_modal.php
│   ├── payment_modal.php
│   ├── register_modal.php
│   └── sidebar.php
│
├── css/                    # Custom styles
│   └── style.css
│
├── database/               # Database scripts
│   ├── init_sample_data.php
│   └── missing_tables.sql
│
├── includes/               # Core system files
│   ├── async/              # Async utilities
│   ├── auth.php            # Authentication functions
│   ├── auto_login.php      # Persistent sessions
│   ├── config.php          # Database configuration
│   └── session.php         # Session management
│
├── javascript/             # Client-side scripts
│   ├── script.js
│   ├── studscript.js
│   └── tailwindcss.js
│
├── lecturer/               # Lecturer portal
│   ├── assignments.php     # Assignment management
│   ├── index.php           # Lecturer dashboard
│   ├── profile.php         # Profile management
│   └── sidebar.php
│
├── student/                # Student portal
│   ├── elections.php       # Student elections
│   ├── index.php           # Student dashboard
│   ├── profile.php         # Profile management
│   └── sidebar.php
│
├── .htaccess                # Apache configuration
├── auth_index.php           # Authentication index
├── auth_register.php        # Registration logic
├── courses.php              # Public course catalog
├── index.php                # Main landing page
├── landing.php              # Alternative landing page
├── logout.php               # Logout logic
├── process_enrollment.php   # Enrollment processing
├── process_login.php        # Login processing
└── README.md                # Project documentation

🗄️ Database Schema
Core Tables
TableDescriptionusersBase table for all system users (students, lecturers, admins).studentsExtends users with academic info (e.g., application number, status).lecturer_profilesExtends users with professional info (e.g., department, specialization).coursesAcademic programs (e.g., course name, code, credits).classesScheduled course instances (e.g., class code, academic year, lecturer).electionsStudent elections (e.g., title, start/end date, status).election_positionsElection roles (e.g., President, Vice President).election_candidatesStudents running for positions (e.g., manifesto, photo).election_votesStudent votes (e.g., election ID, candidate ID).election_resultsElection outcomes (e.g., vote count, winners).assignmentsClass assignments (e.g., title, due date, max score).assignment_submissionsStudent submissions (e.g., submission text, attachment).paymentsFinancial transactions (e.g., amount, payment method, status).activity_logsSystem audit trail (e.g., user activity, IP address).

🔌 API Structure
Endpoints
EndpointDescriptionPOST /includes/async/process_login.phpUser authentication.POST /includes/async/process_enrollment.phpStudent registration.GET /includes/async/get_student_details.php?id={id}Fetch student details.GET /includes/async/get_election_results.php?id={id}Fetch election results.POST /api/payment.phpProcess payments.
Response Format
 Copy{
  "success": true,
  "message": "Operation completed successfully",
  "data": {...},
  "redirect_url": "/dashboard.php"
}

🎨 UI/UX Guide
Design System

Typography: Poppins font family.
Colors:

Primary: Blue (#3B82F6)
Success: Green (#10B981)
Error: Red (#EF4444)


Spacing: Tailwind CSS spacing scale.
Components: Modular and reusable (e.g., sidebar.php, login_modal.php).

Responsive Breakpoints
BreakpointLayout< 768pxMobile (single column)768px - 1024pxTablet (adaptive)> 1024pxDesktop (full feature)

🚀 Installation & Setup
Prerequisites

PHP 8.2+
MySQL 10.4+ (MariaDB)
Web server (Apache/Nginx)
Composer (optional)

Steps


Clone the Repository
 Copygit clone https://github.com/your-organization/mycamp-portal.git
cd mycamp-portal


Set Up the Database
 Copymysql -u root -p < database/init_sample_data.php


Configure the App
 Copycp includes/config.example.php includes/config.php
Edit includes/config.php with your database credentials.


Set File Permissions
 Copychmod 755 -R assets/ uploads/
chmod 644 includes/config.php


Start the Server

Use XAMPP, WAMP, or your preferred local server.




🔧 Development Guide
Code Standards

PHP: PSR-12
JavaScript: ES6+
CSS: Tailwind CSS utility classes
SQL: ANSI SQL with proper indexing

Adding Features

Database Changes: Create migration scripts and update documentation.
Backend: Implement logic in /includes/async/.
Frontend: Add components in /components/.
Testing: Test across browsers and devices.


🧪 Testing
Test Cases

User authentication and authorization.
Form validation and submission.
Database operations and transactions.
Mobile responsiveness and cross-browser compatibility.

Testing Environment
 Copy# Set up a testing database
mysql -u root -p -e "CREATE DATABASE mycamp_portal_test"

🔒 Security
Authentication

Bcrypt password hashing (cost 10).
Session regeneration on login.
Secure cookie settings.

Data Security

Prepared statements for all database queries.
Input validation and sanitization.
CSRF protection tokens.

System Security

File upload validation.
Directory traversal prevention.
Secure headers implementation.


📈 Deployment
Production Checklist

 Database backups configured.
 Error logging enabled.
 SSL certificate installed.
 Monitoring and alerting set up.

Deployment Process

Staging
 Copygit checkout staging
git pull origin staging
composer install --no-dev

Production
 Copygit checkout main
git pull origin main
composer install --no-dev



🤝 Contributing
Branch Strategy
BranchPurposemainProduction releases.stagingStaging environment.developDevelopment branch.feature/*New features.hotfix/*Emergency fixes.
Pull Request Process

Create a feature branch from develop.
Implement changes with tests.
Update documentation.
Submit a PR for review.
Merge after approval.


🆘 Support
Common Issues
IssueSolutionDatabase connectionVerify credentials in config.php.Permission errorsCheck file permissions on uploads/.Session issuesEnsure session path is writable.
Getting Help

Check the documentation.
Search existing issues.
Create a new issue with error logs.


📝 License
MIT License. See LICENSE for details.

🏆 Acknowledgments

Tailwind CSS: Styling framework.
Font Awesome: Icon library.
MySQL: Database management.
PHP Community: Backend foundation.

```
