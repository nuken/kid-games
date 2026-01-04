<?php
// api/save_score.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/quest_logic.php';

session_start();

// 1. Security Checks
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

// CSRF VERIFICATION
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid Security Token']));
}

if ($data['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'ID Mismatch']));
}

// 2. Extract Data
$user_id  = $data['user_id'];
$game_id  = $data['game_id'];
$score    = $data['score'];
$duration = $data['duration'];
$mistakes = isset($data['mistakes']) ? (int)$data['mistakes'] : 0; 

// Get Grade
try {
    $stmt = $pdo->prepare("SELECT grade_level FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_grade = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) { $user_grade = 0; }

// 3. Save Score & Logic
try {
    $stmt = $pdo->prepare("INSERT INTO progress (user_id, game_id, score, duration_seconds, mistakes) VALUES (:uid, :gid, :score, :duration, :mistakes)");
    $stmt->execute([':uid'=>$user_id, ':gid'=>$game_id, ':score'=>$score, ':duration'=>$duration, ':mistakes'=>$mistakes]);

    $new_badges = [];

    // Daily Quest
    $daily_game_id = getDailyGameId($pdo, $user_grade);
    if ($game_id == $daily_game_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 25 AND DATE(earned_at) = CURDATE()");
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 25)")->execute([$user_id]);
            $new_badges[] = ['name'=>'Daily Star', 'icon'=>'🌟'];
            if (getStreakCount($pdo, $user_id) >= 3) {
                $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 26)")->execute([$user_id]);
                $new_badges[] = ['name'=>'Streak Master', 'icon'=>'🔥'];
            }
        }
    }

    // First Flight
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 1");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, 1)")->execute([$user_id]);
        $new_badges[] = ['name'=>'First Flight', 'icon'=>'✨'];
    }

    // Game Specific Badges
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE criteria_game_id = ?");
    $stmt->execute([$game_id]);
    foreach ($stmt->fetchAll() as $badge) {
        if ($score >= $badge['criteria_score']) {
            $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)")->execute([$user_id, $badge['id']]);
            $new_badges[] = ['name'=>$badge['name'], 'icon'=>$badge['icon']];
        }
    }

    echo json_encode(['status' => 'success', 'new_badges' => $new_badges]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>