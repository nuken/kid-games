<?php
// api/reset_badges.php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

// 1. Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

// 2. Get the Raw JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No User ID provided']);
    exit;
}

$target_id = $data['user_id'];
$requester_id = $_SESSION['user_id'];
$requester_role = $_SESSION['role'] ?? 'student';

// 3. Authorization Check
$allowed = false;

if ($target_id == $requester_id) {
    // User modifying self
    $allowed = true;
} elseif ($requester_role === 'admin') {
    // Admin modifying anyone
    $allowed = true;
} elseif ($requester_role === 'parent') {
    // Parent modifying child - Verify relationship
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND parent_id = ?");
    $stmt->execute([$target_id, $requester_id]);
    if ($stmt->fetchColumn() > 0) {
        $allowed = true;
    }
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

try {
    // 4. Delete badges ONLY for that specific user
    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$target_id]);

    echo json_encode(['status' => 'success', 'message' => 'Badges reset for User ID ' . $target_id]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>