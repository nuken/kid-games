<?php
// index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. LOAD HEADER
require_once 'includes/header.php';
// NEW: Load quest logic
require_once 'includes/quest_logic.php';

$user = $current_user;
$user_id = $_SESSION['user_id'];

try {
    // 2. RE-FETCH USER & THEME
    $stmt = $pdo->prepare("
        SELECT u.*, t.css_file, t.name as theme_name
        FROM users u
        LEFT JOIN themes t ON u.theme_id = t.id
        WHERE u.id = ?
    ");
   $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: logout.php");
        exit;
    }

    $grade_map = [
        0 => 'Preschool', 1 => 'Kindergarten', 2 => '1st Grade',
        3 => '2nd Grade', 4 => '3rd Grade', 5 => '4th Grade', 6 => '5th Grade'
    ];
    $grade_display = $grade_map[$user['grade_level']] ?? $user['grade_level'];

    if ($user['role'] !== 'student') {
        if ($user['role'] === 'parent') header("Location: parent.php");
        elseif ($user['role'] === 'admin') header("Location: admin/index.php");
        exit;
    }

    $theme_css = $user['css_file'] ?? 'default.css';
    $theme_path = "assets/themes/" . $theme_css;

    // SMART LANGUAGE LOADER
    require_once 'includes/lang/default.php';
    $default_lang = $LANG;
    $current_lang_file = str_replace('.css', '.php', $theme_css);
    $current_lang_path = "includes/lang/" . $current_lang_file;

    if ($current_lang_file !== 'default.php' && file_exists($current_lang_path)) {
        include $current_lang_path;
        $LANG = array_merge($default_lang, $LANG);
    }

    // --- NEW: FETCH DAILY QUEST INFO ---
    // PASS THE USER'S GRADE LEVEL HERE
    $daily_id = getDailyGameId($pdo, $user['grade_level']);
    
    $quest_game = null;
    $is_quest_complete = false;

    if ($daily_id) {
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$daily_id]);
        $quest_game = $stmt->fetch();

        // Check if completed today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_id = 25 AND DATE(earned_at) = CURDATE()");
        $stmt->execute([$user_id]);
        $is_quest_complete = ($stmt->fetchColumn() > 0);
    }
    
    // Calculate Streak
    $streak = getStreakCount($pdo, $user_id);
    // -----------------------------------

    // 3. GET GAMES (With Favorites Logic)
    $sql = "
        SELECT
            g.id,
            g.folder_path,
            COALESCE(ov.display_name, g.default_title) as title,
            COALESCE(ov.display_icon, g.default_icon) as icon,
            (SELECT MAX(score) FROM progress p WHERE p.user_id = :uid1 AND p.game_id = g.id) as high_score,
            (SELECT COUNT(*) FROM user_favorites f WHERE f.user_id = :uid2 AND f.game_id = g.id) as is_favorite
        FROM games g
        LEFT JOIN game_theme_overrides ov
            ON g.id = ov.game_id AND ov.theme_id = :tid
        WHERE
            g.active = 1
            AND :grade_min >= g.min_grade
            AND :grade_max <= g.max_grade
        ORDER BY is_favorite DESC, g.default_title ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid1'      => $user_id,
        ':uid2'      => $user_id,
        ':tid'       => $user['theme_id'],
        ':grade_min' => $user['grade_level'],
        ':grade_max' => $user['grade_level']
    ]);

    $games = $stmt->fetchAll();

    // 4. GET BADGES
    $stmt = $pdo->prepare("
        SELECT b.name, b.icon, b.description, COUNT(ub.id) as count
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        GROUP BY b.id
        ORDER BY MAX(ub.earned_at) DESC
    ");
    $stmt->execute([$user_id]);
    $my_badges = $stmt->fetchAll();

    // --- EASTER EGG LOGIC ---
    $greeting_text = "Hello " . $user['username']; // Default
    if (strpos($theme_css, 'princess') !== false) {
        $greeting_text = "Hello Princess " . $user['username'];
    } elseif (strpos($theme_css, 'space') !== false) {
        $greeting_text = "Hello Cadet " . $user['username'];
    }

} catch (PDOException $e) {
    die("System Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Hub Dashboard</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($theme_path); ?>">

    <style>
        /* DASHBOARD SPECIFIC STYLES */
        .profile-bar {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: white;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .user-info { display: flex; align-items: center; gap: 15px; }
        .details h2 { margin: 0; font-size: 1.2em; color: var(--star-gold); }
        .details p { margin: 0; font-size: 0.9em; opacity: 0.8; color: white; }

        .logout-btn {
            background: var(--planet-red); color: white; text-decoration: none;
            padding: 8px 15px; border-radius: 20px; font-weight: bold;
            font-size: 0.9em; border: 2px solid rgba(255,255,255,0.2);
        }

        /* Badge Section */
        .badge-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .badge-grid {
            display: flex; justify-content: center; flex-wrap: wrap;
            gap: 15px; margin-top: 15px; min-height: 70px;
        }

        .badge-item {
            background: rgba(0,0,0,0.3);
            border: 2px solid var(--star-gold);
            border-radius: 50%; width: 60px; height: 60px;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; position: relative;
            cursor: pointer; transition: transform 0.2s;
        }
        .badge-item:hover { transform: scale(1.15) rotate(5deg); background: rgba(255,255,255,0.2); }

        .badge-count {
            position: absolute; top: -5px; right: -5px;
            background: var(--planet-red); color: white;
            border-radius: 50%; width: 22px; height: 22px;
            font-size: 12px; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid white;
        }

        /* Mission Grid Layout */
        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        /* --- FAVORITE BUTTON STYLES (Fixes Overlapping) --- */
        .mission-card-wrapper {
            position: relative;
            height: 100%; /* Ensures grid cell is filled */
            display: block;
            transition: transform 0.2s;
        }
        .mission-card-wrapper:hover {
            transform: translateY(-5px);
            z-index: 5;
        }
        .fav-btn {
            position: absolute;
            top: 15px; right: 15px;
            font-size: 30px;
            color: rgba(255, 255, 255, 0.4); /* Faint white */
            cursor: pointer;
            z-index: 20;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: transform 0.2s, color 0.2s;
            user-select: none;
        }
        /* Princess Theme Support (Dark Text Mode) */
        body[style*="princess"] .fav-btn {
            color: rgba(0, 0, 0, 0.2);
        }

        .fav-btn:hover {
            transform: scale(1.2);
            color: white;
        }
        .fav-btn.active {
            color: #e74c3c !important; /* Always Red when active */
            opacity: 1;
            text-shadow: 0 0 15px rgba(231, 76, 60, 0.8);
        }

        /* Ensure card fills wrapper */
        .mission-card { height: 100%; display: block; box-sizing: border-box; }
        .mission-card:hover { transform: none !important; } /* Disable default card hover */
        
        /* NEW QUEST STYLES */
        .daily-quest-container {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            color: #fff;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 3px solid #fff;
            animation: pulse-glow 2s infinite alternate;
        }
        @keyframes pulse-glow { from { box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3); } to { box-shadow: 0 4px 25px rgba(243, 156, 18, 0.6); } }

        .quest-info h2 { margin: 0 0 5px 0; font-size: 1.4em; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .quest-info p { margin: 0; font-size: 1em; }
        
        .quest-action { text-align: right; }
        .quest-btn {
            background: #fff; color: #d35400; text-decoration: none;
            padding: 10px 20px; border-radius: 20px; font-weight: bold;
            display: inline-block; margin-top: 5px; box-shadow: 0 3px 0 #e67e22;
            transition: transform 0.1s;
        }
        .quest-btn:active { transform: translateY(3px); box-shadow: none; }
        
        .streak-display { margin-top: 5px; font-size: 20px; }
        .streak-fire { filter: grayscale(100%) opacity(0.5); transition: all 0.5s; }
        .streak-fire.active { filter: none; transform: scale(1.2); }

        @media (max-width: 600px) {
            .daily-quest-container { flex-direction: column; text-align: center; gap: 15px; }
            .quest-action { text-align: center; }
        }
    </style>
</head>
<body>

    <div class="profile-bar">
        <div class="user-info">
            <div class="avatar" onclick='speakText(<?php echo json_encode($greeting_text); ?>)' style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid white; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 35px; box-shadow: 0 0 10px rgba(255,255,255,0.5); cursor: pointer; user-select: none;">
                <?php
                    $user_avatar = $user['avatar'] ?? '👤';
                    if (strpos($user_avatar, '.') !== false) echo '👤';
                    else echo $user_avatar;
                ?>
            </div>
            <div class="details">
                <h2><?php echo $LANG['profile_title']; ?> <?php echo htmlspecialchars($user['username']); ?></h2>
                <p>Grade Level: <?php echo $grade_display; ?></p>
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
                    <h2>🌟 Quest Complete!</h2>
                    <p>You earned the Daily Star! Come back tomorrow.</p>
                <?php else: ?>
                    <h2>🚀 Daily Mission</h2>
                    <p>Play <strong><?php echo htmlspecialchars($quest_game['default_title']); ?></strong> to earn a Star!</p>
                <?php endif; ?>
                
                <div class="streak-display">
                    Streak: 
                    <?php for($i=1; $i<=3; $i++): ?>
                        <span class="streak-fire <?php echo ($i <= $streak) ? 'active' : ''; ?>">🔥</span>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="quest-action">
                <?php if (!$is_quest_complete): 
                     $q_link = (file_exists($quest_game['folder_path'] . '/view.php')) ? "play.php?game_id=" . $quest_game['id'] : $quest_game['folder_path'] . "/index.php";
                ?>
                    <div style="font-size: 40px; margin-bottom: 5px;"><?php echo $quest_game['default_icon']; ?></div>
                    <a href="<?php echo $q_link; ?>" class="quest-btn">PLAY NOW</a>
                <?php else: ?>
                    <div style="font-size: 50px;">🌟</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="badge-section">
            <h3 style="margin:0; text-transform: uppercase; letter-spacing: 2px; color: var(--text-light); opacity:0.8;">
                <?php echo $LANG['mission_patches']; ?>
            </h3>
            <div class="badge-grid">
                <?php if (count($my_badges) > 0): ?>
                    <?php foreach ($my_badges as $badge): ?>
                        <div class="badge-item" title="<?php echo htmlspecialchars($badge['name']); ?>">
                            <?php echo $badge['icon']; ?>
                            <?php if ($badge['count'] > 1): ?>
                                <div class="badge-count"><?php echo $badge['count']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color: #ccc; padding: 10px; font-style: italic;">
                        <?php echo $LANG['no_patches']; ?>
                        </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="mission-grid">
            <?php if (count($games) > 0): ?>
                <?php foreach ($games as $game):
                    $score = $game['high_score'];
                    $stars = 0;
                    if ($score > 0) $stars = 1;
                    if ($score >= 50) $stars = 2;
                    if ($score >= 90) $stars = 3;

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
                            <?php if ($score > 0): ?>
                                <div style="margin-top:5px; font-weight:bold; font-size: 0.9em; opacity: 0.8;">Best: <?php echo $score; ?></div>
                            <?php else: ?>
                                <div style="margin-top:5px; font-size: 0.8em; opacity: 0.6;"><?php echo $LANG['new_mission']; ?></div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 40px; background: rgba(0,0,0,0.2); border-radius: 10px;">
                    <h2>No games available!</h2>
                    <p>Current Grade Level: <strong><?php echo $grade_display; ?></strong></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/speech-module.js"></script>
    <script src="assets/js/game-bridge.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const username = "<?php echo htmlspecialchars($user['username']); ?>";
            const welcomeText = "<?php echo $LANG['welcome']; ?>";
            const lastVisit = localStorage.getItem('klh_last_visit');
            const now = Date.now();

            if (typeof GameBridge !== 'undefined') GameBridge.init();

            if (!lastVisit || (now - lastVisit) > 300000) {
                setTimeout(() => {
                    if (window.speakText) window.speakText(welcomeText + " " + username);
                }, 1000);
            }
            localStorage.setItem('klh_last_visit', now);
        });

        function toggleFavorite(e, gameId, btn) {
            e.preventDefault();
            e.stopPropagation();

            fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game_id: gameId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    btn.classList.toggle('active');
                    if(data.action === 'added' && window.speakText) {
                        // Optional sound feedback
                    }
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>