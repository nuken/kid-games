<?php
// api/reset_badges.php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// CSRF CHECK
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid Security Token']));
}

if (!isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No User ID']);
    exit;
}

$target_id = $data['user_id'];
$requester_id = $_SESSION['user_id'];
$requester_role = $_SESSION['role'] ?? 'student';

$allowed = false;
if ($target_id == $requester_id) $allowed = true;
elseif ($requester_role === 'admin') $allowed = true;
elseif ($requester_role === 'parent') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND parent_id = ?");
    $stmt->execute([$target_id, $requester_id]);
    if ($stmt->fetchColumn() > 0) $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$target_id]);
    echo json_encode(['status' => 'success', 'message' => 'Badges reset']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>