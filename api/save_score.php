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
$user_id  = (int)$data['user_id'];
$game_id  = (int)$data['game_id'];
$score    = (int)$data['score'];
$duration = (int)$data['duration'];
$mistakes = isset($data['mistakes']) ? (int)$data['mistakes'] : 0;

// Get Grade & Messaging Status
try {
    $stmt = $pdo->prepare("SELECT grade_level, messaging_enabled FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_row = $stmt->fetch();
    $user_grade = $user_row ? (int)$user_row['grade_level'] : 0;
    $messaging_on = isset($user_row['messaging_enabled']) ? (int)$user_row['messaging_enabled'] : 1;
} catch (Exception $e) {
    $user_grade = 0;
    $messaging_on = 1;
}

// 3. Save Score & Logic
try {
    // A. Insert Progress Record (This counts as the game played)
    $stmt = $pdo->prepare("INSERT INTO progress (user_id, game_id, score, duration_seconds, mistakes) VALUES (:uid, :gid, :score, :duration, :mistakes)");
    $stmt->execute([':uid'=>$user_id, ':gid'=>$game_id, ':score'=>$score, ':duration'=>$duration, ':mistakes'=>$mistakes]);

    $new_badges = [];

    // --- FETCH BADGE IDS ---
    $dailyStarId    = getBadgeIdBySlug($pdo, 'daily_star');
    $streakMasterId = getBadgeIdBySlug($pdo, 'streak_master');
    $firstGameId    = getBadgeIdBySlug($pdo, 'first_sparkle');
    $messengerBadgeId = getBadgeIdBySlug($pdo, 'messenger_unlock');

    // ======================================================
    // LOGIC 1: MESSENGER BADGE (Daily Reset Logic)
    // ======================================================
    if ($messaging_on === 1 && $messengerBadgeId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND DATE(played_at) = CURDATE()");
        $stmt->execute([$user_id]);
        $gamesToday = $stmt->fetchColumn();

        if ($gamesToday == 3) {
            // UPSERT for Messenger Badge
            $sql = "INSERT INTO user_badges (user_id, badge_id, earned_at, count) VALUES (?, ?, NOW(), 1) 
                    ON DUPLICATE KEY UPDATE count = count + 1, earned_at = NOW()";
            $pdo->prepare($sql)->execute([$user_id, $messengerBadgeId]);
            $new_badges[] = ['name'=>'Messenger Unlocked!', 'icon'=>'📬'];
        }
    }

    // ======================================================
    // LOGIC 2: DAILY QUEST STAR
    // ======================================================
    $daily_game_id = getDailyGameId($pdo, $user_grade);
    
    // Check if this is the daily game
    if ($dailyStarId && $game_id == $daily_game_id) {
        
        // UPSERT: If exists, update date/count. If new, insert.
        // This prevents the "Duplicate Entry" crash.
        $sql = "INSERT INTO user_badges (user_id, badge_id, earned_at, count) 
                VALUES (?, ?, NOW(), 1) 
                ON DUPLICATE KEY UPDATE 
                count = count + 1, 
                earned_at = NOW()";
        
        $pdo->prepare($sql)->execute([$user_id, $dailyStarId]);
        
        // We only show the alert if it's the FIRST time today (to avoid spamming the popup)
        // We check if the 'count' logic implies this, but for simplicity, we just show it.
        // Or we can check if they already had it today before the insert? 
        // For now, let's just show it. It's fun validation.
        $new_badges[] = ['name'=>'Daily Star', 'icon'=>'⭐'];

        // Check Streak (Now uses the improved progress-based logic)
        if ($streakMasterId && getStreakCount($pdo, $user_id) >= 3) {
            
            // UPSERT Streak Master Badge
            $sqlStreak = "INSERT INTO user_badges (user_id, badge_id, earned_at, count) 
                          VALUES (?, ?, NOW(), 1) 
                          ON DUPLICATE KEY UPDATE 
                          count = count + 1, 
                          earned_at = NOW()";
                          
            $pdo->prepare($sqlStreak)->execute([$user_id, $streakMasterId]);
            $new_badges[] = ['name'=>'Streak Master', 'icon'=>'🔥'];
        }
    }

    // ======================================================
    // LOGIC 3: FIRST GAME EVER (One time only)
    // ======================================================
    if ($firstGameId) {
        // This one stays as INSERT IGNORE or simple check because we only want it once EVER.
        $check = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = ?");
        $check->execute([$user_id, $firstGameId]);
        if ($check->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)")->execute([$user_id, $firstGameId]);
            $new_badges[] = ['name'=>'First Flight', 'icon'=>'✨'];
        }
    }

    // ======================================================
    // LOGIC 4: GAME BADGES (STACKABLE!)
    // ======================================================
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE criteria_game_id = ?");
    $stmt->execute([$game_id]);
    $game_badges = $stmt->fetchAll();

    foreach ($game_badges as $badge) {
        if ($score >= $badge['criteria_score']) {
            $sql = "INSERT INTO user_badges (user_id, badge_id, earned_at, count) 
                    VALUES (?, ?, NOW(), 1) 
                    ON DUPLICATE KEY UPDATE 
                    count = count + 1, 
                    earned_at = NOW()";
                    
            $pdo->prepare($sql)->execute([$user_id, $badge['id']]);
            $new_badges[] = ['name'=>$badge['name'], 'icon'=>$badge['icon']];
        }
    }

    echo json_encode(['status' => 'success', 'new_badges' => $new_badges]);

} catch (PDOException $e) {
    error_log("Save Score Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database Error']);
}
?>