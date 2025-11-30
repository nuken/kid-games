<?php
// api/reset_stats.php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

// 1. Get the Raw JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 2. Check for User ID
if (!isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No User ID provided']);
    exit;
}

$user_id = $data['user_id'];

try {
    // 3. Delete progress AND badges for that specific user
    $stmt = $pdo->prepare("DELETE FROM progress WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$user_id]);

    echo json_encode(['status' => 'success', 'message' => 'History and badges erased for User ID ' . $user_id]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>