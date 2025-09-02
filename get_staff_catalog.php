<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

try {
    // Check if staff_catalog table exists, if not use fallback data
    $check_table = $conn->query("SHOW TABLES LIKE 'staff_catalog'");
    
    if ($check_table->num_rows > 0) {
        // Table exists, fetch from database
        $stmt = $conn->prepare("SELECT * FROM staff_catalog WHERE status = 'active' ORDER BY department, name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
        $stmt->close();
    } else {
        // Table doesn't exist, use fallback data
        $staff = [
            [
                'id' => 1,
                'name' => 'John Langa',
                'title' => 'Senior Lecturer',
                'department' => 'Computer Science',
                'email' => 'john.langa@mycamp.edu',
                'phone' => '0782-123-001',
                'office_location' => 'CS Building Room 201',
                'biography' => 'John Langa is an experienced web developer and educator with over 10 years in the industry.',
                'qualifications' => 'BSc Computer Science, MSc Software Engineering',
                'specializations' => 'Web Design, Frontend Development, UI/UX',
                'image_path' => null,
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Anna Sibanda',
                'title' => 'Lecturer',
                'department' => 'Computer Science',
                'email' => 'anna.sibanda@mycamp.edu',
                'phone' => '0782-123-002',
                'office_location' => 'CS Building Room 203',
                'biography' => 'Anna specializes in database systems and has worked with major corporations on data management solutions.',
                'qualifications' => 'BSc Information Systems, MSc Database Systems',
                'specializations' => 'Database Design, SQL, Data Analytics',
                'image_path' => null,
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => 'Zodwa Moyo',
                'title' => 'Senior Lecturer',
                'department' => 'Computer Science',
                'email' => 'zodwa.moyo@mycamp.edu',
                'phone' => '0782-123-003',
                'office_location' => 'CS Building Room 205',
                'biography' => 'Zodwa is a cybersecurity expert with certifications in ethical hacking and network security.',
                'qualifications' => 'BSc Computer Science, MSc Cybersecurity, CEH Certification',
                'specializations' => 'Cyber Security, Network Security, Ethical Hacking',
                'image_path' => null,
                'status' => 'active'
            ],
            [
                'id' => 4,
                'name' => 'Thabo Ncube',
                'title' => 'Professor',
                'department' => 'Business Studies',
                'email' => 'thabo.ncube@mycamp.edu',
                'phone' => '0782-123-004',
                'office_location' => 'Business Building Room 101',
                'biography' => 'Professor Ncube has extensive experience in business management and entrepreneurship.',
                'qualifications' => 'MBA Business Administration, PhD Management Studies',
                'specializations' => 'Business Management, Entrepreneurship, Strategic Planning',
                'image_path' => null,
                'status' => 'active'
            ],
            [
                'id' => 5,
                'name' => 'Linda Moyo',
                'title' => 'Lecturer',
                'department' => 'Business Studies',
                'email' => 'linda.moyo@mycamp.edu',
                'phone' => '0782-123-005',
                'office_location' => 'Business Building Room 103',
                'biography' => 'Linda is a certified accountant with experience in both public and private sector accounting.',
                'qualifications' => 'BCom Accounting, CA(Z), CPA',
                'specializations' => 'Accounting, Financial Management, Auditing',
                'image_path' => null,
                'status' => 'active'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'count' => count($staff)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading staff catalog.',
        'staff' => []
    ]);
    error_log("Staff catalog error: " . $e->getMessage());
}

$conn->close();
?>
