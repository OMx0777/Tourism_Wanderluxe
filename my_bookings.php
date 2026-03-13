<?php
session_start();
require 'config.php';

// If they are not logged in as a tourist, send them to the login page
if (!isset($_SESSION['tourist_id'])) {
    header("Location: login.php");
    exit();
}

$tourist_id = $_SESSION['tourist_id'];

// Fetch the user's bookings, including hotel details and the tourist place name
$query = "SELECT b.booking_date, h.name AS hotel_name, h.location, h.price, p.name AS place_name 
          FROM bookings b 
          JOIN hotels h ON b.hotel_id = h.id 
          JOIN places p ON h.place_id = p.id 
          WHERE b.tourist_id = ? 
          ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Search</a>
            <a href="#" style="color: var(--accent);">Hi, <?php echo htmlspecialchars($_SESSION['tourist_username']); ?></a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="results-container">
        <h1 class="results-title">My Itinerary</h1>
        <p class="results-subtitle">Your upcoming stays and adventures.</p>

        <div class="search-glass-panel" style="max-width: 900px; margin: 0 auto; padding: 2rem;">
            <?php
            if ($bookings_result->num_rows > 0) {
                while ($booking = $bookings_result->fetch_assoc()) {
                    // Format the date to look nice (e.g., 13 Mar 2026)
                    $date = date("d M Y, h:i A", strtotime($booking['booking_date']));
                    
                    echo '<div style="background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid var(--accent);">';
                    echo '<h3 style="font-family: \'Playfair Display\', serif; font-size: 1.5rem; margin-bottom: 0.5rem; color: #fff;">' . htmlspecialchars($booking['hotel_name']) . '</h3>';
                    echo '<p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.5rem;">📍 ' . htmlspecialchars($booking['location']) . ' (Near ' . htmlspecialchars($booking['place_name']) . ')</p>';
                    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">';
                    echo '<span style="font-size: 0.85rem; color: var(--accent);">Booked on: ' . $date . '</span>';
                    echo '<strong style="font-size: 1.2rem; color: #fff;">₹' . number_format($booking['price'], 2) . '</strong>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div style="text-align: center; padding: 3rem 0;">';
                echo '<p style="font-size: 1.1rem; color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;">You haven\'t booked any stays yet.</p>';
                echo '<a href="index.php" class="btn-search" style="text-decoration: none;">Start Exploring</a>';
                echo '</div>';
            }
            ?>
        </div>
    </main>

</body>
</html>