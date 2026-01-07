<?php
// admin/settings.php
session_start();
require_once 'auth_check.php'; // Ensures only logged-in admins can access

$message = "";

/// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid Token");
    }

    // 1. Handle Invite Code
    if (!empty($_POST['invite_code'])) {
        $new_code = trim($_POST['invite_code']);
        if (strlen($new_code) < 4) {
            $message .= "<div class='alert error'>Code must be at least 4 characters.</div>";
        } else {
            $hashed_code = password_hash($new_code, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('invite_code', ?) ON DUPLICATE KEY UPDATE value = ?");
            if ($stmt->execute([$hashed_code, $hashed_code])) {
                $message .= "<div class='alert success'>Invite Code updated!</div>";
            }
        }
    }

    // 2. Handle Debug Mode
    if (isset($_POST['display_errors'])) {
        $debug_val = $_POST['display_errors'] === '1' ? '1' : '0';
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('display_errors', ?) ON DUPLICATE KEY UPDATE value = ?");
        if ($stmt->execute([$debug_val, $debug_val])) {
             // Refresh page to apply setting immediately
             header("Refresh:2");
             $message .= "<div class='alert success'>Debug Mode updated!</div>";
        }
    }
}

// Fetch current setting
$curr_debug = $pdo->query("SELECT value FROM settings WHERE name = 'display_errors'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Settings</title>
    <link rel="stylesheet" href="<?php echo auto_version('../assets/css/admin.css'); ?>">
</head>
<body>

<div class="nav-bar">
    <a href="index.php" class="nav-item">👥 Users</a>
    <a href="games.php" class="nav-item">🎮 Games</a>
    <a href="badges.php" class="nav-item">🏆 Badges</a>
    <a href="settings.php" class="nav-item active">⚙️ Settings</a>
    <a href="../logout.php" class="nav-item logout">Log Out</a>
</div>

<div class="admin-container-centered">
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <h2>System Settings</h2>
        <?php echo $message; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="margin-bottom: 20px;">
                <label>Update Invite Code</label>
                <input type="text" name="invite_code" placeholder="New Invite Code" autocomplete="off">
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

            <div style="margin-bottom: 20px;">
                <label>🐛 Debug Mode</label>
                <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">
                    Show PHP errors on screen? (Keep OFF for the kids)
                </div>
                <select name="display_errors" style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                    <option value="0" <?php echo ($curr_debug === '0') ? 'selected' : ''; ?>>OFF - Hide Errors</option>
                    <option value="1" <?php echo ($curr_debug === '1') ? 'selected' : ''; ?>>ON - Show Errors</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Save Settings</button>
        </form>
    </div>
</div>

</body>
</html>
