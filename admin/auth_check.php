<?php
// admin/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Basic Login Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

// 2. Secondary PIN Check
if (!isset($_SESSION['admin_unlocked']) || $_SESSION['admin_unlocked'] !== true) {
    header("Location: verify.php");
    exit;
}

// 3. CSRF Token Generation (Security)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>