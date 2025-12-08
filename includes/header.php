<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default path depth if not set
if (!isset($path_depth)) $path_depth = 0;

// Database Connection
$db_path = ($path_depth > 0) ? str_repeat("../", $path_depth) . "includes/db.php" : "includes/db.php";
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Error: Database connection file not found at $db_path");
}

// --- [NEW] AUTO-LOGIN LOGIC ---
// If user is NOT logged in, but HAS the cookie, try to restore session
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    
    // 1. Split the cookie (Selector : Validator)
    $parts = explode(':', $_COOKIE['remember_me']);
    
    if (count($parts) === 2) {
        $selector = $parts[0];
        $validator = $parts[1];

        // 2. Look up the selector in the DB
        $stmt = $pdo->prepare("SELECT * FROM user_tokens WHERE selector = ? AND expiry > NOW()");
        $stmt->execute([$selector]);
        $token_row = $stmt->fetch();

        if ($token_row) {
            // 3. Verify the validator matches the hash
            if (password_verify($validator, $token_row['hashed_validator'])) {
                
                // 4. Valid! Fetch the user info
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$token_row['user_id']]);
                $user = $stmt->fetch();

                if ($user) {
                    // 5. Log them in!
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                }
            } 
            // ---------------------------------------------------------
            // THEFT DETECTION ADDITION STARTS HERE
            // ---------------------------------------------------------
            else {
                // The Selector matched (so the cookie "looked" valid),
                // BUT the Validator failed. This implies a stolen or replayed cookie.
                
                // 1. Delete ALL tokens for this specific user to lock out the thief
                $del = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
                $del->execute([$token_row['user_id']]);

                // 2. Kill the bad cookie in the browser
                setcookie('remember_me', '', time() - 3600, '/');

                // 3. Optional: Redirect to login with a specific error
                // header("Location: login.php?error=session_terminated");
                // exit();
            }
            // ---------------------------------------------------------
            // THEFT DETECTION ADDITION ENDS HERE
            // ---------------------------------------------------------
        }
    }
}
// --- END AUTO-LOGIN LOGIC ---

// 1. Standard Security Check
if (!isset($_SESSION['user_id'])) {
    $root = ($path_depth > 0) ? str_repeat("../", $path_depth) : "./";
    header("Location: " . $root . "login.php");
    exit;
}

// 2. Get User Theme & Settings
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, t.css_file, t.name as theme_name
    FROM users u
    JOIN themes t ON u.theme_id = t.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Security: If user was deleted while logged in, kick them out
if (!$current_user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$theme_css = $current_user['css_file'] ?? 'default.css';
$theme_path = ($path_depth > 0) ? str_repeat("../", $path_depth) . "assets/themes/" . $theme_css : "assets/themes/" . $theme_css;
?>