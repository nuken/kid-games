<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    // We use a relative path trick to handle being in subfolders
    $root = ($path_depth > 0) ? str_repeat("../", $path_depth) : "./";
    header("Location: " . $root . "login.php");
    exit;
}

// 2. Database Connection
// Adjust path based on where this file is included from
$db_path = ($path_depth > 0) ? str_repeat("../", $path_depth) . "includes/db.php" : "includes/db.php";
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Error: Database connection file not found at $db_path");
}

// 3. Get Current User Theme
// This allows us to load the specific CSS file for this child
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, t.css_file, t.name as theme_name
    FROM users u
    JOIN themes t ON u.theme_id = t.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// 4. Set Theme Path
// This variable will be used by the HTML <link> tag
$theme_css = $current_user['css_file'] ?? 'default.css';
$theme_path = ($path_depth > 0) ? str_repeat("../", $path_depth) . "assets/themes/" . $theme_css : "assets/themes/" . $theme_css;
?>
