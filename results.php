<?php
session_start();
require 'config.php';

// Get the district ID from the URL (e.g., results.php?district=1)
$district_id = isset($_GET['district']) ? intval($_GET['district']) : 0;
$district_name = "Unknown Destination";

// Fetch the name of the district for the header
if ($district_id > 0) {
    $d_stmt = $conn->prepare("SELECT name FROM districts WHERE id = ?");
    $d_stmt->bind_param("i", $district_id);
    $d_stmt->execute();
    $d_result = $d_stmt->get_result();
    if ($row = $d_result->fetch_assoc()) {
        $district_name = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore <?php echo htmlspecialchars($district_name); ?> | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Search</a>
            
            <?php if(isset($_SESSION['tourist_id'])): ?>
                <a href="my_bookings.php">My Bookings</a>
                <a href="#" style="color: var(--accent);">Hi, <?php echo htmlspecialchars($_SESSION['tourist_username']); ?></a>
                <a href="logout.php">Logout</a>
            <?php elseif(isset($_SESSION['manager_id'])): ?>
                <a href="manager_dashboard.php" style="color: var(--accent);">Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="register.php">Sign Up</a>
                <a href="login.php">Login</a>
                <a href="manager_login.php" class="btn-manager">Manager Portal</a>
            <?php endif; ?>

        </div>
    </nav>

    <main class="results-container">
        <h1 class="results-title">Discover <?php echo htmlspecialchars($district_name); ?></h1>
        <p class="results-subtitle">Top places to visit and places to stay.</p>

        <div class="places-grid">
            <?php
            if ($district_id > 0) {
                // Fetch all top places in this district
                $p_stmt = $conn->prepare("SELECT id, name, description FROM places WHERE district_id = ?");
                $p_stmt->bind_param("i", $district_id);
                $p_stmt->execute();
                $places_result = $p_stmt->get_result();

                if ($places_result->num_rows > 0) {
                    while ($place = $places_result->fetch_assoc()) {
                        echo '<div class="place-card glass-panel">';
                        echo '<h2>' . htmlspecialchars($place['name']) . '</h2>';
                        echo '<p class="place-desc">' . htmlspecialchars($place['description']) . '</p>';
                        
                        echo '<div class="hotels-section">';
                        echo '<h3>Nearby Stays</h3>';
                        
                        // Fetch hotels for this specific place (Now including 'id')
                        $h_stmt = $conn->prepare("SELECT id, name, location, price, ac_status, capacity FROM hotels WHERE place_id = ?");
                        $h_stmt->bind_param("i", $place['id']);
                        $h_stmt->execute();
                        $hotels_result = $h_stmt->get_result();

                        if ($hotels_result->num_rows > 0) {
                            while ($hotel = $hotels_result->fetch_assoc()) {
                                echo '<div class="hotel-item">';
                                echo '<div><strong>' . htmlspecialchars($hotel['name']) . '</strong> <span class="tag">' . $hotel['ac_status'] . '</span></div>';
                                echo '<div class="hotel-details">📍 ' . htmlspecialchars($hotel['location']) . ' | 👥 Max ' . $hotel['capacity'] . ' guests</div>';
                                echo '<div class="hotel-price">₹' . number_format($hotel['price'], 2) . ' / night</div>';
                                
                                // --- BOOKING BUTTON LOGIC ---
                                if (isset($_SESSION['tourist_id'])) {
                                    echo '<form action="book_hotel.php" method="POST" style="margin-top: 15px;">';
                                    echo '<input type="hidden" name="hotel_id" value="' . $hotel['id'] . '">';
                                    echo '<button type="submit" class="btn-search" style="padding: 0.5rem 1.5rem; font-size: 0.85rem; margin-top: 0;">Book Now</button>';
                                    echo '</form>';
                                } else {
                                    echo '<p style="font-size: 0.85rem; margin-top: 10px;"><a href="login.php" style="color: var(--accent); text-decoration: none; border-bottom: 1px solid var(--accent);">Login to book this stay</a></p>';
                                }
                                // ----------------------------

                                echo '</div>'; // End hotel-item
                            }
                        } else {
                            echo '<p class="no-hotels">No stays listed by managers yet. Check back soon!</p>';
                        }
                        echo '</div>'; // End hotels-section
                        echo '</div>'; // End place-card
                    }
                } else {
                    echo '<p>We are still adding beautiful destinations for this district.</p>';
                }
            } else {
                echo '<p>Please select a valid destination from the home page.</p>';
            }
            ?>
        </div>
    </main>

</body>
</html>