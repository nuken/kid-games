<?php
// admin/delete_user.php
session_start();
require_once 'auth_check.php';

// 1. CSRF Security Check
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security Error: Invalid CSRF Token. Please go back and try again.");
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

if ($id == $_SESSION['user_id']) { die("You cannot delete yourself."); }

try {
    $pdo->beginTransaction();

    // A. Unlink Children (if deleting a Parent)
    $stmt = $pdo->prepare("UPDATE users SET parent_id = NULL WHERE parent_id = ?");
    $stmt->execute([$id]);

    // B. Delete Game Data
    $pdo->prepare("DELETE FROM progress WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?")->execute([$id]);
    
    // --- NEW: MISSING TABLES ADDED BELOW ---
    $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$id]);
    
    // Delete messages where user is SENDER or RECEIVER
    $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$id, $id]);

    // C. Delete the User
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    header("Location: index.php?msg=deleted");

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error deleting user: " . $e->getMessage());
}
?>