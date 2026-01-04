<?php
// admin/verify.php
session_start();
require_once '../includes/db.php';

// Security: If not logged in as admin at all, kick them out
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// CSRF Token Generation if missing (just in case they land here directly)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired or invalid token.";
    } else {
        $entered_pin = $_POST['admin_pin'];
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT admin_pin FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

       if ($user && password_verify($entered_pin, $user['admin_pin'])) {
            $_SESSION['admin_unlocked'] = true; 
            header("Location: index.php");
            exit;
        } else {
            $error = "Incorrect Admin PIN.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Verification</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { display:flex; justify-content:center; align-items:center; height:100vh; background:#2c3e50; }
        .verify-box { 
            background:white; padding:30px; border-radius:10px; 
            text-align:center; width:300px; color: #333; 
        }
        input { font-size:24px; letter-spacing:5px; text-align:center; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="verify-box">
        <h2>🔒 Security Check</h2>
        <p>Please enter secondary Admin PIN</p>
        <?php if($error) echo "<p style='color:#e74c3c; font-weight:bold;'>$error</p>"; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="password" name="admin_pin" autofocus required>
            <button type="submit" class="btn-submit" style="background: #e74c3c;">Unlock</button>
        </form>
        <br>
        <a href="../logout.php" style="color:#7f8c8d; text-decoration:none;">Cancel</a>
    </div>
</body>
</html>