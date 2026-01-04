<?php
// api/reset_stats.php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['redirect'])) { header("Location: ../login.php"); exit; }
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

// CSRF CHECK (Handle GET and POST)
$token = $_GET['csrf_token'] ?? (json_decode(file_get_contents('php://input'), true)['csrf_token'] ?? '');
if ($token !== $_SESSION['csrf_token']) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token']));
}

$requester_id = $_SESSION['user_id'];
$requester_role = $_SESSION['role'] ?? 'student';

// Get Target ID
$target_user_id = null;
$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['user_id'])) $target_user_id = $data['user_id'];
elseif (isset($_GET['user_id'])) $target_user_id = $_GET['user_id'];

if (!$target_user_id) die(json_encode(['status' => 'error', 'message' => 'No User ID']));

// Auth Logic
$allowed = false;
if ($target_user_id == $requester_id) $allowed = true;
elseif ($requester_role === 'admin') $allowed = true;
elseif ($requester_role === 'parent') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND parent_id = ?");
    $stmt->execute([$target_user_id, $requester_id]);
    if ($stmt->fetchColumn() > 0) $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden']));
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM progress WHERE user_id = ?")->execute([$target_user_id]);
    $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?")->execute([$target_user_id]);
    $pdo->commit();

    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {
        header("Location: ../parent.php?student_id=" . $target_user_id);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>