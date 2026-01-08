<?php
// admin/verify.php
session_start();
require_once '../includes/db.php';

// Security: If not logged in as admin at all, kick them out
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

// Get current User ID
$user_id = $_SESSION['user_id'];

// 1. Fetch User & Check Lockout Status immediately
$stmt = $pdo->prepare("SELECT admin_pin, failed_attempts, locked_until FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If user not found (rare edge case), log them out
if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// CHECK LOCKOUT: Is the user currently locked out?
if ($user['locked_until'] && new DateTime($user['locked_until']) > new DateTime()) {
    $remaining = (new DateTime($user['locked_until']))->diff(new DateTime());
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#e74c3c;'>
            <h2>🚫 Locked Out</h2>
            <p>Too many incorrect attempts.</p>
            <p>Please wait " . $remaining->i . " minutes.</p>
            <a href='../logout.php'>Log Out</a>
         </div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh.";
    } else {
        $entered_pin = $_POST['admin_pin'];

        if (password_verify($entered_pin, $user['admin_pin'])) {
            // SUCCESS:
            // 1. Reset failed attempts to 0
            // 2. Clear any lockout time
            $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user_id]);

            // 3. Mark session as unlocked
            $_SESSION['admin_unlocked'] = true;
            header("Location: index.php");
            exit;
        } else {
            // FAILURE:
            // 1. Increment failed attempts
            $attempts = $user['failed_attempts'] + 1;

            if ($attempts >= 5) {
                // Lock for 15 minutes
                $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?")->execute([$attempts, $lock_time, $user_id]);
                $error = "Account locked for 15 minutes!";
            } else {
                // Just update count
                $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $user_id]);
                $remaining = 5 - $attempts;
                $error = "Incorrect PIN. $remaining attempts remaining.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Verification</title>
    <link rel="stylesheet" href="<?php echo '../assets/css/admin.css'; // Removed auto_version for simplicity here ?>">
    <style>
        body { display:flex; justify-content:center; align-items:center; height:100vh; background:#2c3e50; }
        .verify-box {
            background:white; padding:30px; border-radius:10px;
            text-align:center; width:300px; color: #333;
        }
        input { font-size:24px; letter-spacing:5px; text-align:center; margin: 15px 0; width: 100%; box-sizing: border-box; padding: 10px; }
    </style>
</head>
<body>
    <div class="verify-box">
        <h2>🔒 Security Check</h2>
        <p>Please enter secondary Admin PIN</p>
        <?php if($error) echo "<p style='color:#e74c3c; font-weight:bold;'>$error</p>"; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="password" name="admin_pin" autofocus required autocomplete="off">
            <button type="submit" class="btn-submit" style="background: #e74c3c;">Unlock</button>
        </form>
        <br>
        <a href="../logout.php" style="color:#7f8c8d; text-decoration:none;">Cancel</a>
    </div>
</body>
</html>
