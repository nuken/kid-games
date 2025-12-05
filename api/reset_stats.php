<?php
// api/reset_stats.php
require_once '../includes/db.php';

// 1. Get User ID (Supports both JSON POST and GET URL params)
$user_id = null;

// Check JSON input (from Games)
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (isset($data['user_id'])) {
    $user_id = $data['user_id'];
} 
// Check GET input (from Parent Dashboard)
elseif (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
}

if (!$user_id) {
    die(json_encode(['status' => 'error', 'message' => 'No User ID provided']));
}

try {
    // 2. Delete data
    $stmt = $pdo->prepare("DELETE FROM progress WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // 3. Redirect if requested (for Parent Dashboard)
    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {
        header("Location: ../parent.php?student_id=" . $user_id);
        exit;
    }

    // 4. Return JSON (for Games)
    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>