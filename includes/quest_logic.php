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
    srand(); // Reset random seed

    // 3. Return the ID for today
    return $game_ids[$dayOfYear % count($game_ids)];
}

/**
 * Helper function to fetch a Badge ID using its unique slug.
 * This prevents hardcoding IDs like 25 or 26.
 */
function getBadgeIdBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT id FROM badges WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();

    // Return null if not found
    return $id ? (int)$id : null;
}

/**
 * Counts how many unique days the user has earned the "Daily Star" badge
 * within the last 3 days.
 * * Uses 'daily_star' and 'streak_master' slugs instead of hardcoded IDs.
 */
function getStreakCount($pdo, $user_id) {
    // 1. Dynamically get the IDs for our target badges
    $dailyStarId = getBadgeIdBySlug($pdo, 'daily_star');
    $streakMasterId = getBadgeIdBySlug($pdo, 'streak_master');

    // Safety Check: If these badges don't exist in the DB (or lack slugs),
    // we cannot calculate a streak. Return 0 to prevent SQL errors.
    if (!$dailyStarId || !$streakMasterId) {
        return 0;
    }

    // 2. Count distinct days we earned a Star
    // BUT only look at Stars earned AFTER our last Streak Badge
    // AND within the last 3 days window.
    $sql = "
        SELECT COUNT(DISTINCT DATE(earned_at))
        FROM user_badges
        WHERE user_id = ?
        AND badge_id = ?
        AND earned_at >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
        AND earned_at > (
            SELECT COALESCE(MAX(earned_at), '1970-01-01')
            FROM user_badges
            WHERE user_id = ?
            AND badge_id = ?
        )
    ";

    $stmt = $pdo->prepare($sql);

    // Pass parameters in the exact order they appear in the query:
    // 1. user_id (for the count check)
    // 2. dailyStarId (the badge we are counting)
    // 3. user_id (for the subquery check)
    // 4. streakMasterId (the badge that resets the counter)
    $stmt->execute([$user_id, $dailyStarId, $user_id, $streakMasterId]);

    return (int)$stmt->fetchColumn();
}
?>
