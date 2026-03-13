<?php
session_start();
require 'config.php';

// Fetch all states from the database
$states_query = "SELECT id, name FROM states";
$states_result = $conn->query($states_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WanderLuxe | Discover India</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="glass-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            
            <?php if(isset($_SESSION['tourist_id'])): ?>
                <a href="my_bookings.php">My Bookings</a>
                <a href="#" style="color: var(--accent);">Hi, <?php echo htmlspecialchars($_SESSION['tourist_username']); ?></a>
                <a href="logout.php">Logout</a>
                
            <?php elseif(isset($_SESSION['manager_id'])): ?>
                <a href="manager_dashboard.php" style="color: var(--accent);">Manager Dashboard</a>
                <a href="logout.php">Logout</a>
                
            <?php elseif(isset($_SESSION['admin_id'])): ?>
                <a href="admin_dashboard.php" style="color: #ff6b6b; font-weight: 500;">System Control Panel</a>
                <a href="logout.php">Logout</a>
                
            <?php else: ?>
                <a href="register.php">Sign Up</a>
                <a href="login.php">Login</a>
                <a href="manager_login.php" class="btn-manager">Manager Portal</a>
                <a href="admin_login.php" class="btn-manager" style="border-color: #ff6b6b; color: #ff6b6b;">Admin Login</a>
            <?php endif; ?>

        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <h1>Find Your Next Escape.</h1>
            <p>Curated stays and unforgettable experiences.</p>
            
            <div class="search-glass-panel">
                <form action="results.php" method="GET" class="search-form">
                    <div class="input-group">
                        <label for="state">State</label>
                        <select id="state" name="state" onchange="fetchDistricts()">
                            <option value="">Select State</option>
                            <?php
                            if ($states_result->num_rows > 0) {
                                while($row = $states_result->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="district">District</label>
                        <select id="district" name="district" disabled>
                            <option value="">Choose District</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-search">Explore</button>
                </form>
            </div>
        </div>
    </header>

    <script src="script.js"></script>
</body>
</html>