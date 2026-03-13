<?php
// get_districts.php
$host = 'localhost';
$db = 'tourism_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if (isset($_GET['state_id'])) {
    $state_id = $_GET['state_id'];
    
    // Protect against SQL injection
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE state_id = ?");
    $stmt->bind_param("i", $state_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $districts = array();
    
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    
    // Send data back to JavaScript as JSON
    echo json_encode($districts);
}
?>