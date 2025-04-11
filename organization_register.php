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
?>