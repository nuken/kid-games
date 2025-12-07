<?php
// api/reset_stats.php
require_once '../includes/db.php';
session_start();

// 1. Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If this is a browser redirect request, send to login
    if (isset($_GET['redirect'])) {
        header("Location: ../login.php");
        exit;
    }
    // Otherwise return JSON error
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$requester_id = $_SESSION['user_id'];
$requester_role = $_SESSION['role'] ?? 'student';

// 2. Get Target User ID (Supports both JSON POST and GET URL params)
$target_user_id = null;
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['user_id'])) {
    $target_user_id = $data['user_id'];
} elseif (isset($_GET['user_id'])) {
    $target_user_id = $_GET['user_id'];
}

if (!$target_user_id) {
    die(json_encode(['status' => 'error', 'message' => 'No User ID provided']));
}

// 3. Authorization Check (RBAC)
$allowed = false;

if ($target_user_id == $requester_id) {
    // A. User is resetting their own stats
    $allowed = true;
} elseif ($requester_role === 'admin') {
    // B. Admin can reset anyone
    $allowed = true;
} elseif ($requester_role === 'parent') {
    // C. Parent can ONLY reset their own children
    // Verify the relationship in the database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND parent_id = ?");
    $stmt->execute([$target_user_id, $requester_id]);
    if ($stmt->fetchColumn() > 0) {
        $allowed = true;
    }
}

if (!$allowed) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden: Permission denied']));
}

try {
    // 4. Delete data
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM progress WHERE user_id = ?");
    $stmt->execute([$target_user_id]);

    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$target_user_id]);

    $pdo->commit();

    // 5. Redirect if requested (for Parent Dashboard)
    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {
        header("Location: ../parent.php?student_id=" . $target_user_id);
        exit;
    }

    // 6. Return JSON (for Games)
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>