<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check specifically for the 'admin' role
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['password']) {
            $_SESSION['admin_id'] = $row['id'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin account not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">
    <nav class="glass-nav static-nav">
        <div class="logo">WanderLuxe. System</div>
        <div class="nav-links">
            <a href="index.php">Back to Live Site</a>
        </div>
    </nav>

    <div class="hero-section" style="height: 80vh;">
        <div class="search-glass-panel" style="width: 100%; max-width: 400px; padding: 3rem;">
            <h2 style="font-family: 'Playfair Display'; margin-bottom: 2rem; color: #ff6b6b;">System Override</h2>
            
            <?php if($error) echo "<p style='color: #ff6b6b; margin-bottom: 1rem;'>$error</p>"; ?>

            <form action="admin_login.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="input-group">
                    <label>Admin ID</label>
                    <input type="text" name="username" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                </div>
                <div class="input-group">
                    <label>Master Password</label>
                    <input type="password" name="password" required style="padding: 0.8rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.5); color: white; outline: none; font-family: 'Poppins';">
                </div>
                <button type="submit" class="btn-search" style="margin-top: 1rem; background: #ff6b6b; color: white;">Access Mainframe</button>
            </form>
        </div>
    </div>
</body>
</html>