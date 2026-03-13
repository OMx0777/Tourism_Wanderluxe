<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Only look for tourists
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? AND role = 'tourist'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Here is the magic function that checks the hash!
        if (password_verify($password, $row['password'])) {
            $_SESSION['tourist_id'] = $row['id'];
            $_SESSION['tourist_username'] = $username;
            header("Location: index.php"); // Send them back to the beautiful search page
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Tourist account not found. Please register first.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tourist Login | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe.</div>
        <div class="nav-links">
            <a href="index.php">Back to Home</a>
            <a href="register.php">Sign Up</a>
        </div>
    </nav>

    <div class="hero-section" style="height: 80vh;">
        <div class="search-glass-panel" style="width: 100%; max-width: 400px; padding: 3rem;">
            <h2 style="font-family: 'Playfair Display'; margin-bottom: 2rem; color: var(--accent);">Welcome Back</h2>
            
            <?php if($error) echo "<p style='color: #ff6b6b; margin-bottom: 1rem;'>$error</p>"; ?>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                </div>
                <button type="submit" class="btn-search" style="margin-top: 1rem;">Login</button>
            </form>
        </div>
    </div>

</body>
</html>