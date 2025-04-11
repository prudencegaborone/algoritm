 <?php
// Database connection details
$host = "localhost";
$username = "registration_db";        
$password = "D3D9.DLL";            
$dbname = "registration_db";

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
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $surname = htmlspecialchars(trim($_POST['surname']));
    $mobile_number = htmlspecialchars(trim($_POST['mobile_number']));
    $address = htmlspecialchars(trim($_POST['address']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO Users (student_ID, firstname, surname, mobile_number, address, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississs", $student_ID, $firstname, $surname, $mobile_number, $address, $email, $password);

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