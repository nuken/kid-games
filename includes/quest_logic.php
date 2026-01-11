<?php
// includes/quest_logic.php

/**
 * Returns the Game ID for the "Daily Quest" suitable for a specific grade level.
 * Now accepts an optional timestamp to check past days.
 */
function getDailyGameId($pdo, $grade_level, $timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }

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
    // We use the 'z' (day of year) from the provided timestamp
    $dayOfYear = (int)date('z', $timestamp);
    srand($dayOfYear);
    shuffle($game_ids);
    srand(); // Reset random seed

    // 3. Return the ID for that specific day
    return $game_ids[$dayOfYear % count($game_ids)];
}

/**
 * Helper function to fetch a Badge ID using its unique slug.
 */
function getBadgeIdBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT id FROM badges WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

/**
 * Counts streak by checking the PROGRESS table for the last 3 days.
 * This works even if the user_badges table only stores the most recent date.
 */
function getStreakCount($pdo, $user_id) {
    // 1. Get User's Grade (needed to calculate what the daily game was yesterday)
    $stmt = $pdo->prepare("SELECT grade_level FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $grade = $stmt->fetchColumn() ?: 0;

    // 2. Get the date of the LAST Streak Badge earned
    // We only count stars earned AFTER the last streak badge to prevent "overlapping" streaks.
    $streakMasterId = getBadgeIdBySlug($pdo, 'streak_master');
    $lastStreakDate = '1970-01-01'; // Default long ago

    if ($streakMasterId) {
        $stmt = $pdo->prepare("SELECT DATE(earned_at) FROM user_badges WHERE user_id = ? AND badge_id = ?");
        $stmt->execute([$user_id, $streakMasterId]);
        $res = $stmt->fetchColumn();
        if ($res) $lastStreakDate = $res;
    }

    $streakCount = 0;

    // 3. Check Today, Yesterday, and Day Before Yesterday
    for ($i = 0; $i < 3; $i++) {
        $checkTime = strtotime("-$i days");
        $checkDate = date('Y-m-d', $checkTime);

        // If this date is before or same as the last streak badge, don't count it (already used)
        if ($checkDate <= $lastStreakDate) {
            continue;
        }

        // Calculate what the daily game was on that specific date
        $targetGameId = getDailyGameId($pdo, $grade, $checkTime);

        if ($targetGameId) {
            // Check if they actually played it on that date
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND game_id = ? AND DATE(played_at) = ?");
            $stmt->execute([$user_id, $targetGameId, $checkDate]);
            if ($stmt->fetchColumn() > 0) {
                $streakCount++;
            }
        }
    }

    return $streakCount;
}
?>