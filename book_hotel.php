<?php
session_start();
require 'config.php';

// If they aren't logged in or didn't click a button, send them home
if (!isset($_SESSION['tourist_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$tourist_id = $_SESSION['tourist_id'];
$hotel_id = intval($_POST['hotel_id']);
$message = "";

// Insert the booking
$stmt = $conn->prepare("INSERT INTO bookings (tourist_id, hotel_id) VALUES (?, ?)");
$stmt->bind_param("ii", $tourist_id, $hotel_id);

if ($stmt->execute()) {
    $message = "Your stay has been confirmed!";
} else {
    $message = "Something went wrong. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmed | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
    </nav>

    <div class="hero-section" style="height: 80vh;">
        <div class="search-glass-panel" style="width: 100%; max-width: 500px; padding: 3rem; text-align: center;">
            <h2 style="font-family: 'Playfair Display'; margin-bottom: 1rem; color: var(--accent);">Booking Status</h2>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;"><?php echo $message; ?></p>
            <a href="index.php" class="btn-search" style="text-decoration: none; display: inline-block;">Return to Home</a>
        </div>
    </div>

</body>
</html>