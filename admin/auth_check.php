<?php
// admin/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Basic Login Check (Existing)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

// 2. Secondary PIN Check (New)
// If we haven't verified the secondary PIN yet, send them to the verify page
if (!isset($_SESSION['admin_unlocked']) || $_SESSION['admin_unlocked'] !== true) {
    header("Location: verify.php");
    exit;
}
?>