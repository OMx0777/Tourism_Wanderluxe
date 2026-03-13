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
    
    // Security check: Ensure the booking belongs to this manager's hotel
    $check_stmt = $conn->prepare("SELECT b.id FROM bookings b JOIN hotels h ON b.hotel_id = h.id WHERE b.id = ? AND h.manager_id = ?");
    $check_stmt->bind_param("ii", $cancel_id, $manager_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $del_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $del_stmt->bind_param("i", $cancel_id);
        if ($del_stmt->execute()) {
            $success_msg = "Booking successfully cancelled and removed from records.";
        }
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
        $success_msg = "Hotel listing published successfully!";
    }
}

// Fetch all places for the dropdown
$places_result = $conn->query("SELECT p.id, p.name as place_name, d.name as district_name FROM places p JOIN districts d ON p.district_id = d.id");

// --- FETCH DETAILED RESERVATIONS ---
$bookings_query = "SELECT b.id as booking_id, b.booking_date, b.check_in, b.check_out, b.rooms, b.total_price, b.payment_method, 
                   u.username as tourist_name, h.name as hotel_name 
                   FROM bookings b 
                   JOIN users u ON b.tourist_id = u.id 
                   JOIN hotels h ON b.hotel_id = h.id 
                   WHERE h.manager_id = ? 
                   ORDER BY b.check_in ASC"; // Sorted by soonest arrival
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
        <div class="logo">WanderLuxe. Manager</div>
        <div class="nav-links">
            <a href="index.php">Main Site</a>
            <a href="logout.php" class="btn-manager">Logout</a>
        </div>
    </nav>

    <main class="results-container">
        
        <?php if($success_msg) echo "<div style='background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #2ecc71; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;'>✔️ $success_msg</div>"; ?>

        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 3rem;">
            
            <div>
                <h2 style="font-family: 'Playfair Display'; font-size: 2rem; color: var(--accent); margin-bottom: 1rem;">Property Management</h2>
                <div class="search-glass-panel" style="padding: 2rem;">
                    <form action="manager_dashboard.php" method="POST" style="display: flex; flex-direction: column; gap: 1.2rem;">
                        <div class="input-group">
                            <label>Nearby Attraction</label>
                            <select name="place_id" required style="width: 100%;">
                                <?php 
                                $places_result->data_seek(0);
                                while($row = $places_result->fetch_assoc()) echo "<option value='{$row['id']}'>{$row['place_name']} ({$row['district_name']})</option>"; 
                                ?>
                            </select>
                        </div>
                        <div class="input-group"><label>Hotel Name</label><input type="text" name="hotel_name" required></div>
                        <div class="input-group"><label>Address</label><input type="text" name="location" required></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="input-group"><label>Price/Night</label><input type="number" name="price" required></div>
                            <div class="input-group"><label>Capacity</label><input type="number" name="capacity" required></div>
                        </div>
                        <div class="input-group"><label>Type</label><select name="ac_status"><option value="AC">AC</option><option value="Non-AC">Non-AC</option></select></div>
                        <button type="submit" class="btn-search">Add Listing</button>
                    </form>
                </div>
            </div>

            <div>
                <h2 style="font-family: 'Playfair Display'; font-size: 2rem; color: var(--accent); margin-bottom: 1rem;">Guest Arrivals</h2>
                <div class="search-glass-panel" style="padding: 2rem; max-height: 750px; overflow-y: auto;">
                    
                    <?php if ($reservations->num_rows > 0): ?>
                        <?php while ($res = $reservations->fetch_assoc()): ?>
                            <div style="background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid var(--accent);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="color: #fff; font-size: 1.2rem;"><?php echo htmlspecialchars($res['hotel_name']); ?></h3>
                                        <p style="color: var(--accent); font-weight: 500;">Guest: <?php echo htmlspecialchars($res['tourist_name']); ?></p>
                                    </div>
                                    <span style="font-size: 0.8rem; background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 4px;">ID: #BK-<?php echo $res['booking_id']; ?></span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; font-size: 0.85rem; color: rgba(255,255,255,0.8); margin-bottom: 1rem;">
                                    <div>📅 Check-in: <strong><?php echo date("d M Y", strtotime($res['check_in'])); ?></strong></div>
                                    <div>📅 Check-out: <strong><?php echo date("d M Y", strtotime($res['check_out'])); ?></strong></div>
                                    <div>🏨 Rooms: <strong><?php echo $res['rooms']; ?></strong></div>
                                    <div>💰 Collection: <strong>₹<?php echo number_format($res['total_price'], 2); ?></strong></div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                                    <span style="font-size: 0.8rem; color: #4cd137;">Payment: <?php echo $res['payment_method']; ?></span>
                                    <form action="manager_dashboard.php" method="POST" onsubmit="return confirm('Cancel this guest reservation?');">
                                        <input type="hidden" name="cancel_booking_id" value="<?php echo $res['booking_id']; ?>">
                                        <button type="submit" style="background: none; border: 1px solid #ff6b6b; color: #ff6b6b; padding: 5px 12px; border-radius: 15px; cursor: pointer; font-size: 0.8rem;">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.5); text-align: center;">No upcoming guests found.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>
</body>
</html>