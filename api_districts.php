<?php
// api_districts.php
require 'config.php';

// Check if a state_id was sent in the URL
if (isset($_GET['state_id'])) {
    // intval() is a security measure to ensure the ID is strictly an integer
    $state_id = intval($_GET['state_id']); 
    
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE state_id = ?");
    $stmt->bind_param("i", $state_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $districts = array();
    
    // Fetch all matching districts
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    
    // Set header to JSON and echo the result
    header('Content-Type: application/json');
    echo json_encode($districts);
}
?>