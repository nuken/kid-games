<?php
// admin/delete_user.php
session_start();
require_once '../includes/db.php';

require_once 'auth_check.php';

// 2. Get User ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// 3. Prevent Admin Self-Deletion
if ($id == $_SESSION['user_id']) {
    die("You cannot delete yourself.");
}

try {
    $pdo->beginTransaction();

    // A. Unlink Children (if deleting a Parent)
    // If we delete a parent, we set their children's parent_id to NULL
    $stmt = $pdo->prepare("UPDATE users SET parent_id = NULL WHERE parent_id = ?");
    $stmt->execute([$id]);

    // B. Delete Game Progress & Badges
    $stmt = $pdo->prepare("DELETE FROM progress WHERE user_id = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$id]);

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