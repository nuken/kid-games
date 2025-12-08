<?php
// logout.php
session_start();
require_once 'includes/db.php';

// 1. DELETE TOKEN FROM DATABASE (If cookie exists)
if (isset($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) === 2) {
        $selector = $parts[0];
        // Delete the specific token associated with this browser
        $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE selector = ?");
        $stmt->execute([$selector]);
    }
    
    // 2. DELETE THE COOKIE
    // Set expiry time to the past
    setcookie('remember_me', '', time() - 3600, '/', '', true, true); 
    unset($_COOKIE['remember_me']);
}

// 3. DESTROY SESSION
$_SESSION = []; // Clear array
session_destroy(); // Destroy session data

// 4. REDIRECT
header("Location: ./login.php"); 
exit;
?>