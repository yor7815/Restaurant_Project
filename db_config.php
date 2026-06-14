<?php
// Database Configuration
$servername = "140.122.184.121";
$port = 3306; // Local MariaDB port
$username = "team14";
$password = "#$9!hKfbfR2n";
$dbname = "team14";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set UTF-8 encoding
if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}
?>
