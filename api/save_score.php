<?php
// api/save_score.php
header('Content-Type: application/json');
require_once '../includes/db.php';

// 1. Get the data from the game (sent via JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

// 2. Extract variables
$user_id = $data['user_id'];
$game_id = $data['game_id'];
$score   = $data['score'];
$duration = $data['duration'];

// 3. Insert into Database
try {
    $sql = "INSERT INTO progress (user_id, game_id, score, duration_seconds) 
            VALUES (:uid, :gid, :score, :duration)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $user_id,
        ':gid' => $game_id,
        ':score' => $score,
        ':duration' => $duration
    ]);

    // --- BADGE LOGIC START ---
    $new_badges = [];

    // A. Check for "First Flight" (Badge ID 1) - Play any game
    // If user has no badges yet, or just check specifically for ID 1
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 1");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() == 0) {
        // Award Badge 1
        $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 1)")->execute([$user_id]);
        $new_badges[] = ['name' => 'First Flight', 'icon' => '🚀'];
    }

    // B. Check Game-Specific Badges
    // Fetch potential badges for this game
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE criteria_game_id = ?");
    $stmt->execute([$game_id]);
    $game_badges = $stmt->fetchAll();

    foreach ($game_badges as $badge) {
        // Check if already earned
        $check = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = ?");
        $check->execute([$user_id, $badge['id']]);
        if ($check->fetchColumn() > 0) continue; // Already has it

        // Check Score Criteria
        'new_badges' => $new_badges
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>