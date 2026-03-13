<?php
// config.php
$host = 'localhost';
$db = 'tourism_project';
$user = 'tour_admin'; // Our new dedicated user
$pass = 'admin123';   // The new password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4"); 
?>