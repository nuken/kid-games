<?php
// admin/settings.php
session_start();
require_once '../includes/db.php';
require_once 'auth_check.php'; // Ensures only logged-in admins can access

$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_code'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid Token");
    }

    $new_code = trim($_POST['invite_code']);
    
    if (strlen($new_code) < 4) {
        $message = "<div class='alert error'>Code must be at least 4 characters.</div>";
    } else {
        // Hash the new code
        $hashed_code = password_hash($new_code, PASSWORD_DEFAULT);

        // Update or Insert into DB
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('invite_code', ?) ON DUPLICATE KEY UPDATE value = ?");
        if ($stmt->execute([$hashed_code, $hashed_code])) {
            $message = "<div class='alert success'>Invite Code updated successfully!</div>";
        } else {
            $message = "<div class='alert error'>Database error.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Settings</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="nav-bar">
    <a href="index.php" class="nav-item">👥 Users</a>
    <a href="games.php" class="nav-item">🎮 Games</a>
    <a href="badges.php" class="nav-item">🏆 Badges</a>
    <a href="settings.php" class="nav-item active">⚙️ Settings</a>
    <a href="../logout.php" class="nav-item logout">Log Out</a>
</div>

<div class="admin-container.centered">
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <h2>System Settings</h2>
        <?php echo $message; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="margin-bottom: 20px;">
                <label>Update Invite Code</label>
                <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">
                    Enter a new code to change the registration key. (Stored securely as a hash)
                </div>
                <input type="text" name="invite_code" placeholder="New Invite Code" required autocomplete="off">
            </div>
            
            <button type="submit" class="btn-submit">Save Settings</button>
        </form>
    </div>
</div>

</body>
</html>