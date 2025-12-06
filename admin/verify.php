<?php
// admin/verify.php
session_start();
require_once '../includes/db.php';

// Security: If not logged in as admin at all, kick them out
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_pin = $_POST['admin_pin'];
    $user_id = $_SESSION['user_id'];

    // Fetch the stored admin_pin
    $stmt = $pdo->prepare("SELECT admin_pin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Check if PIN matches
    // Note: In a real app, use password_verify() here if hashing
    if ($user && $entered_pin === $user['admin_pin']) {
        $_SESSION['admin_unlocked'] = true; // <--- The Key
        header("Location: index.php");
        exit;
    } else {
        $error = "Incorrect Admin PIN.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Verification</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { 
            display:flex; justify-content:center; align-items:center; 
            height:100vh; background:#2c3e50; 
        }
        .verify-box { 
            background:white; 
            padding:30px; 
            border-radius:10px; 
            text-align:center; 
            width:300px; 
            /* FIX: Force text to be dark so it shows up on white */
            color: #333333 !important; 
        }
        
        /* Ensure headers are also dark */
        .verify-box h2 {
            color: #2c3e50 !important;
            margin-top: 0;
        }

        input { 
            font-size:24px; letter-spacing:5px; text-align:center; 
            width:100%; padding:10px; margin:15px 0; 
            border: 2px solid #ddd; border-radius: 5px;
            color: #333; /* Input text color */
        }
        
        button { 
            background:#e74c3c; color:white; border:none; 
            padding:10px 20px; width:100%; font-size:18px; 
            cursor:pointer; border-radius:5px; font-weight:bold;
        }
        button:hover { background: #c0392b; }
        
        a { color: #bdc3c7; text-decoration: none; font-size: 0.9em; }
        a:hover { color: white; }
    </style>
</head>
<body>
    <div class="verify-box">
        <h2>🔒 Security Check</h2>
        <p>Please enter secondary Admin PIN</p>
        <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="admin_pin" autofocus required>
            <button type="submit">Unlock</button>
        </form>
        <br>
        <a href="../logout.php">Cancel</a>
    </div>
</body>
</html>