<?php
// Database connection details
$host = "localhost";
$username = "registration_db";        
$password = "D3D9.DLL";            
$dbname = "organization_registration_db";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $organization_id = htmlspecialchars(trim($_POST['organization_id']));
    $organization_name = htmlspecialchars(trim($_POST['organization_name']));
    $organization_phone = htmlspecialchars(trim($_POST['organization_phone']));
    $organization_address = htmlspecialchars(trim($_POST['organization_address']));
    $organization_email = htmlspecialchars(trim($_POST['organization_email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO Organizations (organization_id, organization_name organization_phone, organization_address, organization_email, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $organization_id, $organization_name, $organization_phone, $organization_address, $organization_email, $password);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}

/**
 * Core Matching Algorithm
 */
function findStudentMatches($organization_ID, $db) {
    // Get organization requirements
    $org = $db->query("SELECT * FROM organizations WHERE id = $orgId")->fetch();
    $orgSkills = $db->query("SELECT skill FROM organization_skills WHERE org_id = $orgId")->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all eligible students
    $students = $db->query("
        SELECT s.*, GROUP_CONCAT(sk.skill) as skills 
        FROM students s
        LEFT JOIN student_skills sk ON s.id = sk.student_id
        WHERE s.cgpa >= {$org['min_gpa']}
        GROUP BY s.id
    ")->fetchAll();
    
    $matches = [];
    
    foreach ($students as $student) {
        $studentSkills = explode(',', $student['skills']);
        
        // 1. Calculate skill match (50% weight)
        $skillMatch = count(array_intersect($orgSkills, $studentSkills)) / count($orgSkills);
        
        // 2. Calculate GPA match (20% weight)
        $gpaMatch = ($student['cgpa'] - $org['min_gpa']) / (5.0 - $org['min_gpa']);
        
        // 3. Calculate year of study match (15% weight)
        $yearMatch = in_array($student['year'], explode(',', $org['preferred_years'])) ? 1 : 0;
        
        // 4. Calculate course match (15% weight)
        $studentCourses = $db->query("SELECT course FROM student_courses WHERE student_id = {$student['id']}")->fetchAll(PDO::FETCH_COLUMN);
        $orgCourses = $db->query("SELECT course FROM organization_courses WHERE org_id = $orgId")->fetchAll(PDO::FETCH_COLUMN);
        $courseMatch = count(array_intersect($orgCourses, $studentCourses)) / count($orgCourses);
        
        // Calculate total score (0-100)
        $totalScore = ($skillMatch * 50) + ($gpaMatch * 20) + ($yearMatch * 15) + ($courseMatch * 15);
        
        if ($totalScore >= 60) { // Minimum threshold
            $matches[$student['id']] = $totalScore;
        }
    }
    
    // Sort by highest score first
    arsort($matches);
    return $matches;
}
?>

