<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['tourist_id'])) {
    header("Location: login.php");
    exit();
}

$tourist_id = $_SESSION['tourist_id'];
$msg = '';

// --- HANDLE BOOKING CANCELLATION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking_id'])) {
    $cancel_id = intval($_POST['cancel_booking_id']);
    
    // Security check: Ensure they can only delete THEIR OWN booking
    $del_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND tourist_id = ?");
    $del_stmt->bind_param("ii", $cancel_id, $tourist_id);
    
    if ($del_stmt->execute()) {
        $msg = "Your reservation has been successfully cancelled.";
    } else {
        $msg = "Error cancelling reservation. Please try again.";
    }
}

// --- FETCH ALL BOOKINGS FOR THIS TOURIST ---
$query = "SELECT b.id as booking_id, b.booking_date, b.check_in, b.check_out, b.rooms, b.total_price, b.payment_method, 
                 h.name AS hotel_name, h.location, p.name AS place_name 
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
    <title>My Itinerary | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Search Destinations</a>
            <a href="#" style="color: var(--accent);">Hi, <?php echo htmlspecialchars($_SESSION['tourist_username']); ?></a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="results-container">
        <h1 class="results-title">My Itinerary</h1>
        <p class="results-subtitle">Manage your upcoming stays and adventures.</p>

        <div class="search-glass-panel" style="max-width: 900px; margin: 0 auto; padding: 2rem;">
            
            <?php if($msg) echo "<div style='background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #2ecc71; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;'>✔️ $msg</div>"; ?>

            <?php
            if ($bookings_result->num_rows > 0) {
                while ($booking = $bookings_result->fetch_assoc()) {
                    // Format dates beautifully
                    $booked_on = date("d M Y", strtotime($booking['booking_date']));
                    $check_in = date("D, d M Y", strtotime($booking['check_in']));
                    $check_out = date("D, d M Y", strtotime($booking['check_out']));
                    
                    echo '<div style="background: rgba(0,0,0,0.3); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid var(--accent); position: relative;">';
                    
                    echo '<h3 style="font-family: \'Playfair Display\', serif; font-size: 1.8rem; margin-bottom: 0.5rem; color: #fff;">' . htmlspecialchars($booking['hotel_name']) . '</h3>';
                    echo '<p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; margin-bottom: 1.5rem;">📍 ' . htmlspecialchars($booking['location']) . ' (Near ' . htmlspecialchars($booking['place_name']) . ')</p>';
                    
                    // Trip Details Grid
                    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">';
                    echo '<div><strong style="color: var(--accent); font-size: 0.85rem; text-transform: uppercase;">Check-in</strong><br><span style="color: #fff;">' . $check_in . '</span></div>';
                    echo '<div><strong style="color: var(--accent); font-size: 0.85rem; text-transform: uppercase;">Check-out</strong><br><span style="color: #fff;">' . $check_out . '</span></div>';
                    echo '<div><strong style="color: var(--accent); font-size: 0.85rem; text-transform: uppercase;">Rooms</strong><br><span style="color: #fff;">' . $booking['rooms'] . ' Room(s)</span></div>';
                    echo '<div><strong style="color: var(--accent); font-size: 0.85rem; text-transform: uppercase;">Payment Info</strong><br><span style="color: #fff;">' . htmlspecialchars($booking['payment_method']) . '</span></div>';
                    echo '</div>';

                    // Bottom Row: Price & Cancel Button
                    echo '<div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">';
                    echo '<div><span style="font-size: 0.85rem; color: rgba(255,255,255,0.5);">Total Amount</span><br><strong style="font-size: 1.6rem; color: #fff;">₹' . number_format($booking['total_price'], 2) . '</strong></div>';
                    
                    // CANCEL BUTTON FORM
                    echo '<form action="my_bookings.php" method="POST" onsubmit="return confirm(\'Are you absolutely sure you want to cancel this trip?\');">';
                    echo '<input type="hidden" name="cancel_booking_id" value="' . $booking['booking_id'] . '">';
                    echo '<button type="submit" style="background: transparent; border: 1px solid #ff6b6b; color: #ff6b6b; padding: 0.6rem 1.5rem; border-radius: 30px; cursor: pointer; font-family: \'Poppins\'; font-weight: 500; transition: 0.3s;" onmouseover="this.style.background=\'#ff6b6b\'; this.style.color=\'#fff\'" onmouseout="this.style.background=\'transparent\'; this.style.color=\'#ff6b6b\'">Cancel Trip</button>';
                    echo '</form>';
                    
                    echo '</div>'; // End bottom row
                    echo '<div style="position: absolute; top: 2rem; right: 2rem; font-size: 0.75rem; color: rgba(255,255,255,0.3);">Booked on ' . $booked_on . '</div>';
                    echo '</div>'; // End card
                }
            } else {
                echo '<div style="text-align: center; padding: 4rem 0;">';
                echo '<h3 style="font-family: \'Playfair Display\'; color: #fff; margin-bottom: 1rem; font-size: 1.5rem;">No Upcoming Trips</h3>';
                echo '<p style="font-size: 1rem; color: rgba(255,255,255,0.6); margin-bottom: 2rem;">You haven\'t booked any stays yet. Let\'s fix that!</p>';
                echo '<a href="index.php" class="btn-search" style="text-decoration: none; padding: 0.8rem 2rem;">Start Exploring</a>';
                echo '</div>';
            }
            ?>
        </div>
    </main>

</body>
</html>