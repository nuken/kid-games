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
}

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

<div class="admin-container-centered" style="margin-bottom: 15px;">
    <div class="card" style="max-width: 750px; margin: 0 auto;">
        <h2>System Settings</h2>
        <?php echo $message; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="margin-bottom: 20px;">
                <label>Update Invite Code</label>
                <input type="text" name="invite_code" placeholder="New Invite Code" autocomplete="off">
            </div>

            <button type="submit" class="btn-submit" style="margin-top: 20px;">Save Settings</button>
        </form>
		<br><br>
		
		          <h2>System Information</h2>   
            <div class="info-row" style="margin-bottom: 15px; font-size: 0.9rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <strong>Debug Mode Status:</strong> 
                <?php 
                if (defined('SHOW_ERRORS') && SHOW_ERRORS === true) {
                    echo '<span style="color: #e74c3c; font-weight: bold;">ON</span>';
                } else {
                    echo '<span style="color: #27ae60; font-weight: bold;">OFF</span>';
                }
                ?>
                <br><br>
                To change the error reporting settings, please modify the <code>config.php</code> file.
            </div>
		<div>
        
        <div class="info-row" style="margin-bottom: 15px; font-size: 0.9rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <strong style="color: #2c3e50;">🕒 Server Time:</strong>
            <div style="font-family: monospace; font-size: 1.1em; margin-top: 5px;">
                <?php echo date('F j, Y'); ?><br>
                <?php echo date('g:i:s a T'); ?>
            </div>
            <small style="color: #7f8c8d;">
                This is the time used for Streaks and Daily Quests.
            </small>
        </div>

        <div class="info-row" style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <strong style="color: #2c3e50;">🐘 PHP Environment:</strong>
            <ul style="list-style: none; padding: 0; margin-top: 5px; color: #555;">
                <li><strong>Version:</strong> <?php echo phpversion(); ?></li>
                <li><strong>Max Upload:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
                <li><strong>Max POST:</strong> <?php echo ini_get('post_max_size'); ?></li>
                <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
            </ul>
        </div>

        <div class="info-row">
            <strong style="color: #2c3e50;">🗄️ Database:</strong>
            <div style="margin-top: 5px; color: #555;">
                Connected to: <strong><?php echo htmlspecialchars(DB_NAME); ?></strong><br>
                <small>Host: <?php echo htmlspecialchars(DB_HOST); ?></small>
            </div>
        </div>
    </div>
    </div>
	
</div>

</body>
</html>