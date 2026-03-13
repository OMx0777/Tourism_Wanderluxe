<?php
session_start();
require 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = 'tourist'; // Force this page to only create tourists

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $message = "<span style='color: #ff6b6b;'>Username already taken. Please choose another.</span>";
    } else {
        // HASH THE PASSWORD (Viva points!)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert_stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($insert_stmt->execute()) {
            $message = "<span style='color: #4cd137;'>Registration successful! You can now <a href='login.php' style='color: var(--accent);'>Login here</a>.</span>";
        } else {
            $message = "<span style='color: #ff6b6b;'>Something went wrong. Try again.</span>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tourist Registration | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Back to Home</a>
            <a href="login.php">Login</a>
        </div>
    </nav>

    <div class="hero-section" style="height: 80vh;">
        <div class="search-glass-panel" style="width: 100%; max-width: 400px; padding: 3rem;">
            <h2 style="font-family: 'Playfair Display'; margin-bottom: 2rem; color: var(--accent);">Join WanderLuxe</h2>
            
            <?php if($message) echo "<p style='margin-bottom: 1.5rem; font-size: 0.9rem;'>$message</p>"; ?>

            <form action="register.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="input-group">
                    <label>Choose a Username</label>
                    <input type="text" name="username" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                </div>
                <div class="input-group">
                    <label>Create a Password</label>
                    <input type="password" name="password" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                </div>
                <button type="submit" class="btn-search" style="margin-top: 1rem;">Create Account</button>
            </form>
        </div>
    </div>

</body>
</html>