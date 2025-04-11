<?php
// Database connection details
$host = "localhost";
$username = "organization_preferences_db";        
$password = "D3D9.DLL";            
$dbname = "organization_preferences_db";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $organization_ID = htmlspecialchars(trim($_POST['organization_ID']));
    $minimum_cpga_equirement = htmlspecialchars(trim($_POST['minimum_cpga_equirement']));
    $preferred_academic_program = htmlspecialchars(trim($_POST['preferred_academic_program']));

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO Organization_Preferences (organization_ID, minimum_cpga_equirement, preferred_academic_program) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $organization_ID, $minimum_cpga_equirement, $preferred_academic_program);

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