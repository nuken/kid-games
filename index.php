<?php
// index.php
require_once 'includes/header.php';
require_once 'includes/quest_logic.php';

// CSRF Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = $current_user;
/// --- MESSENGER LOGIC ---
$myMessages = [];
$friendList = [];
$hasMessenger = false;

// 1. Only run if Messaging is Enabled for this user
if ($current_user['messaging_enabled']) {

    // 2. Check for Badge earned TODAY (Daily Reset Logic)
    // We added: AND DATE(b.earned_at) = CURDATE()
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

        // 3. Mark messages as read
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ?")->execute([$user_id]);

        // 4. Fetch Inbox
        $stmt = $pdo->prepare("SELECT m.*, u.username as sender_name, u.avatar as sender_avatar FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $myMessages = $stmt->fetchAll();

        // 5. Fetch Friends
        $stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE role = 'student' AND id != ? AND messaging_enabled = 1 ORDER BY username");
        $stmt->execute([$user_id]);
        $friendList = $stmt->fetchAll();
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
        <div style="display: flex; gap: 10px;">
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

        <?php if($current_user['messaging_enabled']): ?>
<div class="messenger-box">
    <?php if ($hasMessenger): ?>
        <h2 onclick="toggleMessenger()" style="cursor: pointer; user-select: none;" title="Click to minimize">
            📬 Inbox <span id="msg-toggle-icon" style="font-size: 0.8em; float: right;">▼</span>
        </h2>

        <div id="messenger-content">

            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px dashed #eee;">
                <select id="receiver_id" style="padding: 10px; font-size: 1.1rem; border-radius: 10px; border: 2px solid #3498db;">
                    <option value="">Choose a friend...</option>
                    <?php foreach($friendList as $f): ?>
                        <option value="<?php echo $f['id']; ?>"><?php echo $f['avatar'] . " " . htmlspecialchars($f['username']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="margin-top:10px;">
                    <button onclick="pickEmoji('👋')" class="emoji-btn">👋</button>
                    <button onclick="pickEmoji('😀')" class="emoji-btn">😀</button>
                    <button onclick="pickEmoji('💖')" class="emoji-btn">💖</button>
                    <button onclick="pickEmoji('🚀')" class="emoji-btn">🚀</button>
                    <button onclick="pickEmoji('🦖')" class="emoji-btn">🦖</button>
                    <button onclick="pickEmoji('🍕')" class="emoji-btn">🍕</button>
                </div>
                <p id="status-msg" style="height:20px; color:#27ae60; font-weight:bold;"></p>
            </div>

            <div class="inbox-window">
                <?php if(count($myMessages) > 0): ?>
                    <?php foreach($myMessages as $msg): ?>
                        <div class="msg-bubble">
                            <strong><?php echo $msg['sender_avatar']; ?> <?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
                            <span style="font-size:1.5rem; margin-left:10px;"><?php echo $msg['message']; ?></span>
                            <small style="color:#ccc; float:right;"><?php echo date('M j', strtotime($msg['sent_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#bdc3c7; padding: 20px;">No messages yet!</p>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div style="color: #95a5a6; font-style: italic;">
            <span style="font-size: 3rem;">🔒</span><br>
            <strong>Messenger Locked!</strong><br>
            Play 3 games to unlock!
        </div>
    <?php endif; ?>
</div>

<script>
function pickEmoji(emoji) {
    const friendId = document.getElementById('receiver_id').value;
    const status = document.getElementById('status-msg');
    if (!friendId) { alert("Pick a friend first!"); return; }

    status.innerText = "Sending...";

    fetch('api/send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: friendId, message: emoji })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            status.innerText = "Sent! ✅";
            setTimeout(() => location.reload(), 1000); // Reload to see message in history if we want
        } else if (data.status === 'limit') {
            status.style.color = 'orange';
            status.innerText = data.message; // "You reached your limit..."
        } else {
            status.style.color = 'red';
            status.innerText = data.message;
        }
    });
}

function toggleMessenger() {
    const content = document.getElementById('messenger-content');
    const icon = document.getElementById('msg-toggle-icon');

    if (content.style.display === 'none') {
        // Open it
        content.style.display = 'block';
        icon.innerText = '▼';
    } else {
        // Close it
        content.style.display = 'none';
        icon.innerText = '◀'; // Or use '▲'
    }
}
</script>
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
