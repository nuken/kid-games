<?php
// api/toggle_favorite.php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// CSRF VERIFICATION
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid Security Token']));
}

$user_id = $_SESSION['user_id'];
$game_id = $data['game_id'] ?? 0;

if (!$game_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Game ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user_id, $game_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$user_id, $game_id]);
        $action = 'removed';
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, game_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $game_id]);
        $action = 'added';
    }
    echo json_encode(['status' => 'success', 'action' => $action]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>