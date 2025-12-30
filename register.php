<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/config.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['invite_code'];
    $username = trim($_POST['username']);
    $pin = trim($_POST['pin']);

    // 1. Verify Invite Code
    if ($code !== INVITE_CODE) {
        $error = "Invalid Invite Code!";
    }
    // 2. Validate Input
    elseif (empty($username) || empty($pin) || strlen($pin) < 4) {
        $error = "Please enter a username and a 4-digit PIN.";
    }
    // 3. Check if Username Taken
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken. Try another.";
        } else {
            // 4. Create Parent Account
            // Role is always 'parent' for self-registration
            $stmt = $pdo->prepare("INSERT INTO users (username, pin_code, role, avatar) VALUES (?, ?, 'parent', 'default_avatar.png')");
            if ($stmt->execute([$username, $pin])) {
                // Auto-Login
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'parent';

                header("Location: parent.php"); // Send them straight to add their kids
                exit;
            } else {
                $error = "Database error. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Family</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #2c3e50; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
        .card { background: rgba(255,255,255,0.1); padding: 40px; border-radius: 20px; text-align: center; backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.2); width: 300px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: none; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #f1c40f; color: #2c3e50; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1.1em; margin-top: 10px; }
        button:hover { background: #f39c12; }
        .error { color: #ff6b6b; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        a { color: #ccc; text-decoration: none; display: block; margin-top: 20px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Join the Fun!</h1>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <input type="text" name="invite_code" placeholder="Enter Invite Code" required autocomplete="off">
            <input type="text" name="username" placeholder="Parent Username (e.g. Mom)" required>
            <input type="number" name="pin" placeholder="Create a 4-Digit PIN" required>
            <button type="submit">Create Account</button>
        </form>
        <a href="login.php">⬅ Back to Login</a>
    </div>
</body>
</html>
