<?php
// admin/auth_check.php

// 1. ENABLE AUTO-LOGIN (Remember Me)
// We set path_depth to 1 because we are in the 'admin' subfolder.
// This includes the logic to check cookies and restore the session if needed.
$path_depth = 1;
require_once __DIR__ . '/../includes/header.php';

// 2. ADMIN ROLE CHECK
// header.php ensures a user is logged in, but we must explicitly verify they are an Admin.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

// 3. SECONDARY PIN CHECK (Security)
// This ensures the admin has entered the specific admin-PIN recently.
if (!isset($_SESSION['admin_unlocked']) || $_SESSION['admin_unlocked'] !== true) {
    header("Location: verify.php");
    exit;
}

// 4. CSRF TOKEN GENERATION
// Ensure we have a security token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>