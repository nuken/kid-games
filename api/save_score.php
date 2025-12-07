<?php
// api/save_score.php
header('Content-Type: application/json');
require_once '../includes/db.php';

session_start();
// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

if ($data['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'ID Mismatch']));
}

// 2. Extract variables
$user_id  = $data['user_id'];
$game_id  = $data['game_id'];
$score    = $data['score'];
$duration = $data['duration'];
$mistakes = isset($data['mistakes']) ? (int)$data['mistakes'] : 0; // Capture mistakes

// 3. Insert into Database
try {
    $sql = "INSERT INTO progress (user_id, game_id, score, duration_seconds, mistakes)
            VALUES (:uid, :gid, :score, :duration, :mistakes)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $user_id,
        ':gid' => $game_id,
        ':score' => $score,
        ':duration' => $duration,
        ':mistakes' => $mistakes
    ]);

    // --- BADGE LOGIC START ---
    $new_badges = [];

    // A. "First Flight" (Badge ID 1) - ONLY AWARD ONCE
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 1");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 1)")->execute([$user_id]);
        $new_badges[] = ['name' => 'First Flight', 'icon' => '🚀'];
    }

    // B. Check Game-Specific Badges
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE criteria_game_id = ?");
    $stmt->execute([$game_id]);
    $game_badges = $stmt->fetchAll();

    foreach ($game_badges as $badge) {
        if ($score >= $badge['criteria_score']) {
            $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)")
                ->execute([$user_id, $badge['id']]);

            $new_badges[] = [
                'name' => $badge['name'],
                'icon' => $badge['icon']
            ];
        }
    }

    // 4. Return Success
    echo json_encode([
        'status' => 'success',
        'new_badges' => $new_badges
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
