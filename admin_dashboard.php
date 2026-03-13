<?php
session_start();
require 'config.php';

// Protect the route! Only admins allowed.
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$msg = '';
$msg_color = '#4cd137'; // Default green for success

// --- HANDLE ALL FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_state'])) {
            $stmt = $conn->prepare("INSERT INTO states (name) VALUES (?)");
            $stmt->bind_param("s", $_POST['state_name']);
            $stmt->execute();
            $msg = "New State added successfully!";
        } elseif (isset($_POST['delete_state'])) {
            $stmt = $conn->prepare("DELETE FROM states WHERE id = ?");
            $stmt->bind_param("i", $_POST['state_id']);
            $stmt->execute();
            $msg = "State deleted successfully!";
        } elseif (isset($_POST['add_district'])) {
            $stmt = $conn->prepare("INSERT INTO districts (state_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $_POST['state_id'], $_POST['district_name']);
            $stmt->execute();
            $msg = "New District added successfully!";
        } elseif (isset($_POST['delete_district'])) {
            $stmt = $conn->prepare("DELETE FROM districts WHERE id = ?");
            $stmt->bind_param("i", $_POST['district_id']);
            $stmt->execute();
            $msg = "District deleted successfully!";
        } elseif (isset($_POST['add_place'])) {
            $stmt = $conn->prepare("INSERT INTO places (district_id, name, description) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $_POST['district_id'], $_POST['place_name'], $_POST['description']);
            $stmt->execute();
            $msg = "New Tourist Place added successfully!";
        } elseif (isset($_POST['delete_place'])) {
            $stmt = $conn->prepare("DELETE FROM places WHERE id = ?");
            $stmt->bind_param("i", $_POST['place_id']);
            $stmt->execute();
            $msg = "Tourist Place deleted successfully!";
        }
    } catch (mysqli_sql_exception $e) {
        // Catch Foreign Key Constraints (e.g., trying to delete a state that has districts in it)
        $msg = "Action Blocked: You cannot delete this item because it contains dependent data (e.g., trying to delete a State that still has Districts, or a Place that has Hotels). Delete the inside items first!";
        $msg_color = '#ff6b6b';
    }
}

// Fetch all data for the dropdowns and lists
$states = $conn->query("SELECT * FROM states ORDER BY name");
$districts = $conn->query("SELECT d.id, d.name, s.name as state_name FROM districts d JOIN states s ON d.state_id = s.id ORDER BY s.name, d.name");
$places = $conn->query("SELECT p.id, p.name, d.name as district_name FROM places p JOIN districts d ON p.district_id = d.id ORDER BY d.name, p.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | WanderLuxe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .admin-card { background: rgba(0, 0, 0, 0.4); padding: 1.5rem; border-radius: 8px; border-top: 3px solid #ff6b6b; }
        .admin-list { margin-top: 1.5rem; max-height: 200px; overflow-y: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; }
        .admin-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; font-size: 0.9rem; }
        .btn-del { background: transparent; color: #ff6b6b; border: 1px solid #ff6b6b; border-radius: 4px; padding: 0.2rem 0.5rem; cursor: pointer; transition: 0.3s; }
        .btn-del:hover { background: #ff6b6b; color: white; }
    </style>
</head>
<body class="results-page">

    <nav class="glass-nav static-nav">
        <div class="logo" style="color: #ff6b6b;">WanderLuxe. Administrator</div>
        <div class="nav-links">
            <a href="index.php">View Live Site</a>
            <a href="logout.php" class="btn-manager" style="background: transparent; border-color: #ff6b6b; color: #ff6b6b;">Logout</a>
        </div>
    </nav>

    <main class="results-container" style="padding-top: 2rem; max-width: 1400px;">
        <h1 class="results-title" style="font-size: 2.5rem; color: #fff;">Database Control Panel</h1>
        
        <?php if($msg) echo "<div style='background: rgba(0,0,0,0.5); border: 1px solid $msg_color; color: $msg_color; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; font-weight: 500;'>$msg</div>"; ?>

        <div class="admin-grid">
            
            <div class="admin-card">
                <h3 style="color: #ff6b6b; margin-bottom: 1rem;">Manage States</h3>
                <form action="admin_dashboard.php" method="POST" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="state_name" placeholder="New State Name" required style="flex: 1; padding: 0.5rem; border-radius: 4px; border: none; outline: none;">
                    <button type="submit" name="add_state" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Add</button>
                </form>
                <div class="admin-list">
                    <?php 
                    $states->data_seek(0); // Reset pointer
                    while($s = $states->fetch_assoc()): 
                    ?>
                        <div class="admin-item">
                            <span><?php echo htmlspecialchars($s['name']); ?></span>
                            <form action="admin_dashboard.php" method="POST" onsubmit="return confirm('Delete this state?');">
                                <input type="hidden" name="state_id" value="<?php echo $s['id']; ?>">
                                <button type="submit" name="delete_state" class="btn-del">Delete</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="admin-card">
                <h3 style="color: #ff6b6b; margin-bottom: 1rem;">Manage Districts</h3>
                <form action="admin_dashboard.php" method="POST" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <select name="state_id" required style="padding: 0.5rem; border-radius: 4px; border: none; outline: none;">
                        <option value="">Select Parent State...</option>
                        <?php 
                        $states->data_seek(0);
                        while($s = $states->fetch_assoc()) echo "<option value='{$s['id']}'>{$s['name']}</option>"; 
                        ?>
                    </select>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="district_name" placeholder="New District Name" required style="flex: 1; padding: 0.5rem; border-radius: 4px; border: none; outline: none;">
                        <button type="submit" name="add_district" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Add</button>
                    </div>
                </form>
                <div class="admin-list">
                    <?php while($d = $districts->fetch_assoc()): ?>
                        <div class="admin-item">
                            <span><?php echo htmlspecialchars($d['name']) . " <small style='color: gray;'>(" . htmlspecialchars($d['state_name']) . ")</small>"; ?></span>
                            <form action="admin_dashboard.php" method="POST" onsubmit="return confirm('Delete this district?');">
                                <input type="hidden" name="district_id" value="<?php echo $d['id']; ?>">
                                <button type="submit" name="delete_district" class="btn-del">Delete</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="admin-card" style="grid-column: 1 / -1;">
                <h3 style="color: #ff6b6b; margin-bottom: 1rem;">Manage Tourist Places</h3>
                <form action="admin_dashboard.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <select name="district_id" required style="padding: 0.5rem; border-radius: 4px; border: none; outline: none;">
                        <option value="">Select Parent District...</option>
                        <?php 
                        $districts->data_seek(0);
                        while($d = $districts->fetch_assoc()) echo "<option value='{$d['id']}'>{$d['name']} ({$d['state_name']})</option>"; 
                        ?>
                    </select>
                    <input type="text" name="place_name" placeholder="Tourist Place Name" required style="padding: 0.5rem; border-radius: 4px; border: none; outline: none;">
                    <textarea name="description" placeholder="A beautiful description of the location..." required style="grid-column: 1 / -1; padding: 0.5rem; border-radius: 4px; border: none; outline: none; min-height: 80px; font-family: 'Poppins';"></textarea>
                    <button type="submit" name="add_place" style="grid-column: 1 / -1; background: #ff6b6b; color: white; border: none; padding: 0.8rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 500;">Add Tourist Place</button>
                </form>
                
                <div class="admin-list" style="max-height: 300px; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <?php while($p = $places->fetch_assoc()): ?>
                        <div class="admin-item" style="background: rgba(255,255,255,0.05); padding: 0.5rem 1rem; border-radius: 4px;">
                            <span><?php echo htmlspecialchars($p['name']) . " <br><small style='color: gray;'>(" . htmlspecialchars($p['district_name']) . ")</small>"; ?></span>
                            <form action="admin_dashboard.php" method="POST" onsubmit="return confirm('Delete this tourist place?');">
                                <input type="hidden" name="place_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="delete_place" class="btn-del">Delete</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>
    </main>

</body>
</html>