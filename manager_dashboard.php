<?php
session_start();
require 'config.php';

// Kick out anyone who isn't logged in as a manager
if (!isset($_SESSION['manager_id'])) {
    header("Location: manager_login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];
$success_msg = '';

// --- HANDLE CANCEL BOOKING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking_id'])) {
    $cancel_id = intval($_POST['cancel_booking_id']);
    
    // Security check: Make sure this booking actually belongs to a hotel owned by THIS manager
    $check_stmt = $conn->prepare("SELECT b.id FROM bookings b JOIN hotels h ON b.hotel_id = h.id WHERE b.id = ? AND h.manager_id = ?");
    $check_stmt->bind_param("ii", $cancel_id, $manager_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $del_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $del_stmt->bind_param("i", $cancel_id);
        if ($del_stmt->execute()) {
            $success_msg = "Booking successfully cancelled.";
        }
    } else {
        $success_msg = "Error: Unauthorized cancellation attempt.";
    }
}

// --- HANDLE ADDING A NEW HOTEL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['hotel_name'])) {
    $place_id = intval($_POST['place_id']);
    $name = $_POST['hotel_name'];
    $location = $_POST['location'];
    $price = floatval($_POST['price']);
    $ac_status = $_POST['ac_status'];
    $capacity = intval($_POST['capacity']);

    $stmt = $conn->prepare("INSERT INTO hotels (place_id, manager_id, name, location, price, ac_status, capacity) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissdsi", $place_id, $manager_id, $name, $location, $price, $ac_status, $capacity);

    if ($stmt->execute()) {
        $success_msg = "Hotel successfully listed!";
    }
}

// Fetch all places for the dropdown
$places_result = $conn->query("SELECT p.id, p.name as place_name, d.name as district_name FROM places p JOIN districts d ON p.district_id = d.id");

// --- FETCH RESERVATIONS FOR THIS MANAGER ---
$bookings_query = "SELECT b.id as booking_id, b.booking_date, u.username as tourist_name, h.name as hotel_name, p.name as place_name 
                   FROM bookings b 
                   JOIN users u ON b.tourist_id = u.id 
                   JOIN hotels h ON b.hotel_id = h.id 
                   JOIN places p ON h.place_id = p.id 
                   WHERE h.manager_id = ? 
                   ORDER BY b.booking_date DESC";
$b_stmt = $conn->prepare($bookings_query);
$b_stmt->bind_param("i", $manager_id);
$b_stmt->execute();
$reservations = $b_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe. Admin.</div>
        <div class="nav-links">
            <a href="index.php">View Live Site</a>
            <a href="logout.php" class="btn-manager">Logout</a>
        </div>
    </nav>

    <main class="results-container">
        
        <?php if($success_msg) echo "<div style='background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #2ecc71; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;'>✔️ $success_msg</div>"; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
            
            <div>
                <h2 style="font-family: 'Playfair Display'; font-size: 2rem; color: var(--accent); margin-bottom: 1rem;">Add New Listing</h2>
                <div class="search-glass-panel" style="padding: 2rem;">
                    <form action="manager_dashboard.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                        
                        <div class="input-group">
                            <label>Tourist Destination</label>
                            <select name="place_id" required style="min-width: 100%;">
                                <option value="">Select nearest attraction...</option>
                                <?php
                                if ($places_result->num_rows > 0) {
                                    while($row = $places_result->fetch_assoc()) {
                                        echo "<option value='" . $row['id'] . "'>" . $row['place_name'] . " (" . $row['district_name'] . ")</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Hotel Name</label>
                            <input type="text" name="hotel_name" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                        </div>

                        <div class="input-group">
                            <label>Location / Address</label>
                            <input type="text" name="location" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="input-group">
                                <label>Price (₹)</label>
                                <input type="number" step="0.01" name="price" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins'; min-width: 100%;">
                            </div>
                            <div class="input-group">
                                <label>Capacity</label>
                                <input type="number" name="capacity" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins'; min-width: 100%;">
                            </div>
                        </div>

                        <div class="input-group">
                            <label>AC / Non-AC</label>
                            <select name="ac_status" required style="min-width: 100%;">
                                <option value="AC">AC</option>
                                <option value="Non-AC">Non-AC</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-search" style="margin-top: 1rem;">Publish Listing</button>
                    </form>
                </div>
            </div>

            <div>
                <h2 style="font-family: 'Playfair Display'; font-size: 2rem; color: var(--accent); margin-bottom: 1rem;">Live Reservations</h2>
                <div class="search-glass-panel" style="padding: 2rem; max-height: 700px; overflow-y: auto;">
                    
                    <?php
                    if ($reservations->num_rows > 0) {
                        while ($res = $reservations->fetch_assoc()) {
                            $date = date("d M Y, h:i A", strtotime($res['booking_date']));
                            echo '<div style="background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid var(--accent);">';
                            echo '<h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">' . htmlspecialchars($res['hotel_name']) . '</h3>';
                            echo '<p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.2rem;">👤 Guest: <strong>' . htmlspecialchars($res['tourist_name']) . '</strong></p>';
                            echo '<p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 1rem;">📅 Booked: ' . $date . '</p>';
                            
                            // CANCEL BUTTON FORM
                            echo '<form action="manager_dashboard.php" method="POST" onsubmit="return confirm(\'Are you sure you want to cancel this booking?\');">';
                            echo '<input type="hidden" name="cancel_booking_id" value="' . $res['booking_id'] . '">';
                            echo '<button type="submit" style="background: transparent; border: 1px solid #ff6b6b; color: #ff6b6b; padding: 0.4rem 1rem; border-radius: 20px; cursor: pointer; font-family: \'Poppins\'; transition: 0.3s;" onmouseover="this.style.background=\'#ff6b6b\'; this.style.color=\'#fff\'" onmouseout="this.style.background=\'transparent\'; this.style.color=\'#ff6b6b\'">Cancel Booking</button>';
                            echo '</form>';
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="color: rgba(255,255,255,0.5); text-align: center; font-style: italic;">No reservations yet. Once tourists book your stays, they will appear here.</p>';
                    }
                    ?>

                </div>
            </div>

        </div>
    </main>

</body>
</html>