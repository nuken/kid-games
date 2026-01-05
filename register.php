<?php
// register.php

session_start();
require_once 'includes/db.php';
require_once 'includes/config.php';

// Generate Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh.";
    } else {
        $code = $_POST['invite_code'];
        $username = trim($_POST['username']);
        $pin = trim($_POST['pin']);

        // Fetch hashed invite code from DB
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'invite_code' LIMIT 1");
$stmt->execute();
$setting = $stmt->fetch();
$hashed_code = $setting ? $setting['value'] : null;

// Verify
if (!$hashed_code || !password_verify($code, $hashed_code)) {
    $error = "Invalid Invite Code!";
        } elseif (empty($username) || empty($pin) || strlen($pin) < 4) {
            $error = "Please enter a username and a 4-digit PIN.";
        } else {
            // Check username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username taken. Try another.";
            } else {
                // Create Parent
                $stmt = $pdo->prepare("INSERT INTO users (username, pin_code, role, avatar) VALUES (?, ?, 'parent', '👤')");
                $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
                if ($stmt->execute([$username, $hashed_pin])) {
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'parent';
                    header("Location: parent.php"); 
                    exit;
                } else {
                    $error = "Database error.";
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Join the Family</title>
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/login.css'); ?>">
</head>
<body>

<div class="container">
    <div id="pin-screen" style="display: block; max-width: 400px;">
        <h1 style="font-size: 2rem; margin-bottom: 20px;">Join the Fun! 🚀</h1>
        
        <?php if($error): ?>
            <div class="error-msg" style="display:block;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="text-align: left; margin-bottom: 15px;">
                <label style="font-weight: bold; color: #2c3e50;">Invite Code</label>
                <input type="text" name="invite_code" required autocomplete="off" 
                       style="width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 8px; font-size: 16px; margin-top: 5px;">
            </div>

            <div style="text-align: left; margin-bottom: 15px;">
                <label style="font-weight: bold; color: #2c3e50;">Parent Name</label>
                <input type="text" name="username" placeholder="e.g. Mom" required 
                       style="width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 8px; font-size: 16px; margin-top: 5px;">
            </div>

            <div style="text-align: left; margin-bottom: 25px;">
                <label style="font-weight: bold; color: #2c3e50;">Create 4-Digit PIN</label>
                <input type="number" name="pin" placeholder="****" required 
                       style="width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 8px; font-size: 16px; margin-top: 5px; text-align: center; letter-spacing: 5px;">
            </div>

            <button type="submit" class="key key-enter" style="width: 100%; border-radius: 8px; box-shadow: 0 4px 0 #27ae60;">Create Account</button>
        </form>
        
        <a href="login.php" style="display: block; margin-top: 20px; color: #7f8c8d; text-decoration: none; font-weight: bold;">
            ⬅ Back to Login
        </a>
    </div>
</div>

</body>
</html>
