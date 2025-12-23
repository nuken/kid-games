<?php
// includes/quest_logic.php

/**
 * Returns the Game ID for the "Daily Quest" suitable for a specific grade level.
 * This ensures a 4-year-old gets a Pre-K game and a 7-year-old gets a 1st/2nd-grade game.
 */
function getDailyGameId($pdo, $grade_level) {
    // 1. Get active games THAT FIT THIS CHILD'S GRADE
    $stmt = $pdo->prepare("
        SELECT id FROM games 
        WHERE active = 1 
        AND min_grade <= ? 
        AND max_grade >= ? 
        ORDER BY id ASC
    ");
    $stmt->execute([$grade_level, $grade_level]);
    $game_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($game_ids)) return null;

    // 2. Deterministic Shuffle: Seed with the day of the year
    // This ensures that even though the list is personalized, 
    // the selection remains constant for the whole day.
    $dayOfYear = (int)date('z'); 
    srand($dayOfYear); 
    shuffle($game_ids);

    // 3. Return the ID for today
    return $game_ids[$dayOfYear % count($game_ids)];
}

/**
 * Counts how many unique days the user has earned the "Daily Star" badge (ID 25)
 * within the last 3 days.
 */
function getStreakCount($pdo, $user_id) {
    // Count distinct days we earned a Star (25)
    // BUT only look at Stars earned AFTER our last Streak Badge (26)
    // AND within the last 3 days window.
    $sql = "
        SELECT COUNT(DISTINCT DATE(earned_at))
        FROM user_badges
        WHERE user_id = ?
        AND badge_id = 25
        AND earned_at >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
        AND earned_at > (
            SELECT COALESCE(MAX(earned_at), '1970-01-01')
            FROM user_badges
            WHERE user_id = ?
            AND badge_id = 26
        )
    ";

    $stmt = $pdo->prepare($sql);
    // We pass user_id twice now because of the subquery
    $stmt->execute([$user_id, $user_id]);
    return (int)$stmt->fetchColumn();
}
?>
