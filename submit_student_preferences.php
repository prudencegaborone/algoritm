<?php
// Database connection details
$host = "localhost";
$username = "student_preferences_db";        
$password = "D3D9.DLL";            
$dbname = "student_preferences_db";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $student_ID = htmlspecialchars(trim($_POST['student_ID']));
    $cumulative_grade_point_average = htmlspecialchars(trim($_POST['cumulative_grade_point_average']));
    $academic_program = htmlspecialchars(trim($_POST['academic_program']));
    $location_preference = htmlspecialchars(trim($_POST['location_preference']));
    $domain_preference = htmlspecialchars(trim($_POST['domain_preference']));

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO Organization_Preferences (student_ID, cumulative_grade_point_average, academic_program, location_preference, domain_preference) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsss", $student_ID, $cumulative_grade_point_average, $academic_program, $location_preference, $domain_preference);

    if ($stmt->execute()) {
        echo "Preferences submisson successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>