<?php
session_start();
require 'config.php';

if (!isset($_SESSION['tourist_id'])) {
    header("Location: login.php");
    exit();
}

$tourist_id = $_SESSION['tourist_id'];
$message = "";

// --- 1. IF THEY SUBMITTED THE BOOKING FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $hotel_id = intval($_POST['hotel_id']);
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $rooms = intval($_POST['rooms']);
    $payment_method = $_POST['payment_method'];
    
    // Calculate total price accurately on the server side
    $h_stmt = $conn->prepare("SELECT price FROM hotels WHERE id = ?");
    $h_stmt->bind_param("i", $hotel_id);
    $h_stmt->execute();
    $hotel = $h_stmt->get_result()->fetch_assoc();
    
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $nights = $date1->diff($date2)->days;
    $nights = ($nights == 0) ? 1 : $nights; // Minimum 1 night
    
    $total_price = $hotel['price'] * $nights * $rooms;

    $stmt = $conn->prepare("INSERT INTO bookings (tourist_id, hotel_id, check_in, check_out, rooms, total_price, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissids", $tourist_id, $hotel_id, $check_in, $check_out, $rooms, $total_price, $payment_method);

    if ($stmt->execute()) {
        $message = "Booking Confirmed! You can pay at the hotel.";
    } else {
        $message = "Something went wrong. Please try again.";
    }
}

// --- 2. IF THEY JUST CLICKED "BOOK NOW" ON THE SEARCH PAGE ---
$hotel_details = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['hotel_id']) && !isset($_POST['confirm_booking'])) {
    $h_id = intval($_POST['hotel_id']);
    $stmt = $conn->prepare("SELECT id, name, location, price, ac_status FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $h_id);
    $stmt->execute();
    $hotel_details = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Cancel & Return</a>
        </div>
    </nav>

    <div class="hero-section" style="height: auto; min-height: 80vh; padding: 4rem 0;">
        <div class="search-glass-panel" style="width: 100%; max-width: 600px; padding: 3rem;">
            
            <?php if($message): ?>
                <div style="text-align: center;">
                    <h2 style="font-family: 'Playfair Display'; color: #4cd137; margin-bottom: 1rem;">✔️ <?php echo $message; ?></h2>
                    <p style="margin-bottom: 2rem;">Your itinerary has been updated.</p>
                    <a href="my_bookings.php" class="btn-search" style="text-decoration: none;">View My Bookings</a>
                </div>
            <?php elseif($hotel_details): ?>
                <h2 style="font-family: 'Playfair Display'; margin-bottom: 0.5rem; color: var(--accent);">Complete Your Booking</h2>
                <p style="font-size: 1.1rem; margin-bottom: 2rem; color: #fff;">
                    <strong><?php echo htmlspecialchars($hotel_details['name']); ?></strong><br>
                    <span style="font-size: 0.9rem; color: rgba(255,255,255,0.7);">📍 <?php echo htmlspecialchars($hotel_details['location']); ?></span>
                </p>

                <form action="book_hotel.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <input type="hidden" name="confirm_booking" value="1">
                    <input type="hidden" name="hotel_id" value="<?php echo $hotel_details['id']; ?>">
                    <input type="hidden" id="base_price" value="<?php echo $hotel_details['price']; ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="input-group">
                            <label>Check-in Date</label>
                            <input type="date" id="check_in" name="check_in" required onchange="calculateTotal()" style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins'; width: 100%;">
                        </div>
                        <div class="input-group">
                            <label>Check-out Date</label>
                            <input type="date" id="check_out" name="check_out" required onchange="calculateTotal()" style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins'; width: 100%;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="input-group">
                            <label>Number of Rooms</label>
                            <input type="number" id="rooms" name="rooms" min="1" value="1" required onchange="calculateTotal()" style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins'; width: 100%;">
                        </div>
                        <div class="input-group">
                            <label>Payment Method</label>
                            <select name="payment_method" required style="width: 100%;">
                                <option value="Pay at Arrival">Pay at Arrival (Cash/Card at Hotel)</option>
                                <option value="UPI / Netbanking" disabled>UPI / Netbanking (Coming Soon)</option>
                            </select>
                        </div>
                    </div>

                    <div style="background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 8px; margin-top: 1rem; border-left: 4px solid var(--accent);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 1.1rem;">Total Amount:</span>
                            <strong id="display_total" style="font-size: 1.5rem; color: var(--accent);">₹0.00</strong>
                        </div>
                        <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5); text-align: right; margin-top: 5px;" id="calculation_details">Select dates to calculate</p>
                    </div>

                    <button type="submit" class="btn-search" style="margin-top: 1rem; font-size: 1.1rem; padding: 1rem;">Confirm Reservation</button>
                </form>

                <script>
                    // Set minimum date for Check-in to today
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('check_in').setAttribute('min', today);

                    document.getElementById('check_in').addEventListener('change', function() {
                        // Make sure checkout is at least 1 day after checkin
                        let checkinDate = new Date(this.value);
                        checkinDate.setDate(checkinDate.getDate() + 1);
                        document.getElementById('check_out').setAttribute('min', checkinDate.toISOString().split('T')[0]);
                    });

                    function calculateTotal() {
                        let checkIn = new Date(document.getElementById('check_in').value);
                        let checkOut = new Date(document.getElementById('check_out').value);
                        let rooms = document.getElementById('rooms').value;
                        let basePrice = document.getElementById('base_price').value;

                        if (!isNaN(checkIn) && !isNaN(checkOut) && checkOut > checkIn) {
                            let timeDifference = checkOut.getTime() - checkIn.getTime();
                            let nights = Math.ceil(timeDifference / (1000 * 3600 * 24));
                            let total = nights * rooms * basePrice;
                            
                            document.getElementById('display_total').innerText = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
                            document.getElementById('calculation_details').innerText = `${nights} Night(s) × ${rooms} Room(s) × ₹${basePrice}`;
                        } else {
                            document.getElementById('display_total').innerText = '₹0.00';
                            document.getElementById('calculation_details').innerText = 'Select valid dates to calculate';
                        }
                    }
                </script>
            <?php else: ?>
                <p>No hotel selected. <a href="index.php" style="color: var(--accent);">Go back</a>.</p>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>