<?php
// index.php
require_once 'includes/header.php';
require_once 'includes/quest_logic.php';

// CSRF Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = $current_user;
/// --- MESSENGER CHECK ---
$hasMessenger = false;
$unreadCount = 0;

if ($current_user['messaging_enabled']) {
    // 1. Check for Badge (Unlock status)
    $checkBadge = $pdo->prepare("
        SELECT COUNT(*)
        FROM user_badges b
        JOIN badges d ON b.badge_id = d.id
        WHERE b.user_id = ?
        AND d.slug = 'messenger_unlock'
        AND DATE(b.earned_at) = CURDATE()
    ");
    $checkBadge->execute([$user_id]);

    if ($checkBadge->fetchColumn() > 0) {
        $hasMessenger = true;

        // 2. Check for UNREAD messages only
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unreadCount = $stmt->fetchColumn();
    }
}

$user_id = $_SESSION['user_id'];

try {
    // 2. RE-FETCH USER & THEME
    $stmt = $pdo->prepare("SELECT u.*, t.css_file FROM users u LEFT JOIN themes t ON u.theme_id = t.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) { header("Location: logout.php"); exit; }

    // Redirect logic
    if ($user['role'] !== 'student') {
        if ($user['role'] === 'parent') header("Location: parent.php");
        elseif ($user['role'] === 'admin') header("Location: admin/index.php");
        exit;
    }

    $grade_map = [0=>'Preschool', 1=>'Kindergarten', 2=>'1st Grade', 3=>'2nd Grade', 4=>'3rd Grade', 5=>'4th Grade', 6=>'5th Grade'];
    $grade_display = $grade_map[$user['grade_level']] ?? $user['grade_level'];

    // Theme & Lang
    $theme_css = $user['css_file'] ?? 'default.css';
    $theme_path = "assets/themes/" . $theme_css;

    require_once 'includes/lang/default.php';
    $default_lang = $LANG;
    $current_lang_file = str_replace('.css', '.php', $theme_css);
    if ($current_lang_file !== 'default.php' && file_exists("includes/lang/" . $current_lang_file)) {
        include "includes/lang/" . $current_lang_file;
        $LANG = array_merge($default_lang, $LANG);
    }

    // Daily Quest
    $daily_id = getDailyGameId($pdo, $user['grade_level']);
    $quest_game = null;
    $is_quest_complete = false;

    if ($daily_id) {
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$daily_id]);
        $quest_game = $stmt->fetch();

        // --- FIXED: Use Slug instead of hardcoded ID 25 ---
        $dailyStarId = getBadgeIdBySlug($pdo, 'daily_star');

        if ($dailyStarId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = ? AND DATE(earned_at) = CURDATE()");
            $stmt->execute([$user_id, $dailyStarId]);
            $is_quest_complete = ($stmt->fetchColumn() > 0);
        }
    }

    // This function was updated in previous steps to use slugs internally
    $streak = getStreakCount($pdo, $user_id);

    // 3. GET GAMES
    $sql = "SELECT g.id, g.folder_path, COALESCE(ov.display_name, g.default_title) as title,
            COALESCE(ov.display_icon, g.default_icon) as icon,
            (SELECT MAX(score) FROM progress p WHERE p.user_id = :uid1 AND p.game_id = g.id) as high_score,
            (SELECT COUNT(*) FROM user_favorites f WHERE f.user_id = :uid2 AND f.game_id = g.id) as is_favorite
            FROM games g LEFT JOIN game_theme_overrides ov ON g.id = ov.game_id AND ov.theme_id = :tid
            WHERE g.active = 1 AND :grade_min >= g.min_grade AND :grade_max <= g.max_grade
            ORDER BY is_favorite DESC, g.default_title ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid1'=>$user_id, ':uid2'=>$user_id, ':tid'=>$user['theme_id'], ':grade_min'=>$user['grade_level'], ':grade_max'=>$user['grade_level']]);
    $games = $stmt->fetchAll();

    // 4. GET BADGES
    $stmt = $pdo->prepare("SELECT b.name, b.icon, COUNT(ub.id) as count FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = ? GROUP BY b.id ORDER BY MAX(ub.earned_at) DESC");
    $stmt->execute([$user_id]);
    $my_badges = $stmt->fetchAll();

    // Greeting
    $greeting_text = "Hello " . $user['username'];
    if (strpos($theme_css, 'princess') !== false) $greeting_text = "Hello Princess " . $user['username'];
    elseif (strpos($theme_css, 'space') !== false) $greeting_text = "Hello Cadet " . $user['username'];

} catch (PDOException $e) { die("System Error"); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Hub Dashboard</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version($theme_path); ?>">
</head>
<body>

    <div class="profile-bar">
        <div class="user-info">
            <div class="avatar" onclick='speakText(<?php echo json_encode($greeting_text); ?>)'>
                <?php echo (strpos($user['avatar'], '.') !== false) ? '👤' : $user['avatar']; ?>
            </div>
            <div class="details">
                <h2><?php echo $LANG['profile_title']; ?> <?php echo htmlspecialchars($user['username']); ?></h2>
                <p>Level: <?php echo $grade_display; ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">

            <?php if($hasMessenger): ?>
                <a href="inbox.php" class="inbox-btn">
                    📬 Inbox
                    <?php if($unreadCount > 0): ?>
                        <span class="notify-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                 <div title="Play 3 games to unlock messenger!" style="opacity:0.5; font-size:1.5rem; cursor: help;">🔒</div>
            <?php endif; ?>

            <a href="sticker_book.php" class="logout-btn" style="background: var(--nebula-green);">Sticker Book</a>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    <div class="container">
            <?php if ($quest_game): ?>
        <div class="daily-quest-container">
            <div class="quest-info">
                <?php if ($is_quest_complete): ?>
                    <h2>⭐ Quest Complete!</h2>
                    <p>You earned the Daily Star! Come back tomorrow.</p>
                <?php else: ?>
                    <h2>🚀 Daily Mission</h2>
                    <p>Play <strong><?php echo htmlspecialchars($quest_game['default_title']); ?></strong> to earn a Star!</p>
                <?php endif; ?>
                <div class="streak-display">
                    Streak: <?php for($i=1; $i<=3; $i++): ?><span class="streak-fire <?php echo ($i <= $streak) ? 'active' : ''; ?>">🔥</span><?php endfor; ?>
                </div>
            </div>
            <div class="quest-action">
                <?php if (!$is_quest_complete):
                     $q_link = (file_exists($quest_game['folder_path'] . '/view.php')) ? "play.php?game_id=" . $quest_game['id'] : $quest_game['folder_path'] . "/index.php";
                ?>
                    <div style="font-size: 40px; margin-bottom: 5px;"><?php echo $quest_game['default_icon']; ?></div>
                    <a href="<?php echo $q_link; ?>" class="quest-btn">PLAY NOW</a>
                <?php else: ?>
                    <div style="font-size: 50px;">⭐</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="badge-section">
            <h3 style="margin:0; text-transform: uppercase; letter-spacing: 2px; color: var(--text-light, #ecf0f1); opacity:0.8;">
                <?php echo $LANG['mission_patches']; ?>
            </h3>
            <div class="badge-grid">
                <?php if (count($my_badges) > 0): foreach ($my_badges as $badge): ?>
                    <div class="badge-item" title="<?php echo htmlspecialchars($badge['name']); ?>">
                        <?php echo $badge['icon']; ?>
                        <?php if ($badge['count'] > 1): ?><div class="badge-count"><?php echo $badge['count']; ?></div><?php endif; ?>
                    </div>
                <?php endforeach; else: ?>
                    <div style="color: #ccc; padding: 10px; font-style: italic;"><?php echo $LANG['no_patches']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mission-grid">
            <?php if (count($games) > 0): foreach ($games as $game):
                $score = $game['high_score'];
                $stars = ($score > 0) ? (($score >= 90) ? 3 : (($score >= 50) ? 2 : 1)) : 0;
                $view_path = $game['folder_path'] . '/view.php';
                $link = (file_exists($view_path)) ? "play.php?game_id=" . $game['id'] : $game['folder_path'] . "/index.php";
                $heartClass = ($game['is_favorite'] > 0) ? 'active' : '';
            ?>
                <div class="mission-card-wrapper">
                    <div class="fav-btn <?php echo $heartClass; ?>" onclick="toggleFavorite(event, <?php echo $game['id']; ?>, this)">&#x2764;&#xfe0e;</div>
                    <a href="<?php echo $link; ?>" class="mission-card">
                        <div class="mission-icon"><?php echo $game['icon']; ?></div>
                        <div class="mission-title"><?php echo htmlspecialchars($game['title']); ?></div>
                        <div class="stars active-<?php echo $stars; ?>"><span>★</span><span>★</span><span>★</span></div>
                        <div style="margin-top:5px; font-size:0.8em; opacity:0.7;">
                            <?php echo ($score > 0) ? "Best: $score" : $LANG['new_mission']; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; else: ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 40px; background: rgba(0,0,0,0.2); border-radius: 10px; color: white;">
                    <h2>No games available!</h2>
                    <p>Current Grade: <strong><?php echo $grade_display; ?></strong></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/speech-module.js"></script>
    <script src="assets/js/game-bridge.js"></script>
    <script>
        const CSRF_TOKEN = "<?php echo $_SESSION['csrf_token']; ?>";

        document.addEventListener('DOMContentLoaded', () => {
            const username = "<?php echo htmlspecialchars($user['username']); ?>";
            const welcomeText = "<?php echo $LANG['welcome']; ?>";
            const lastVisit = localStorage.getItem('klh_last_visit');
            const now = Date.now();

            if (typeof GameBridge !== 'undefined') GameBridge.init();

            if (!lastVisit || (now - lastVisit) > 600000) { // 10 minutes
                setTimeout(() => { if (window.speakText) window.speakText(welcomeText + " " + username); }, 1000);
            }
            localStorage.setItem('klh_last_visit', now);
        });

        function toggleFavorite(e, gameId, btn) {
            e.preventDefault(); e.stopPropagation();
            fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game_id: gameId, csrf_token: CSRF_TOKEN })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    btn.classList.toggle('active');
                    if(data.action === 'added' && window.playSound) window.playSound('correct');
                }
            }).catch(err => console.error(err));
        }
    </script>
</body>
</html>
