<?php
// index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db.php'; 

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 2. GET USER & THEME
    $stmt = $pdo->prepare("
        SELECT u.*, t.css_file, t.name as theme_name 
        FROM users u 
        LEFT JOIN themes t ON u.theme_id = t.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // Force logout if user deleted
        header("Location: logout.php");
        exit;
    }

    // Set Theme Path
    $theme_css = $user['css_file'] ?? 'default.css';
    $theme_path = "assets/themes/" . $theme_css;
	
	// --- NEW: LOAD LANGUAGE ---
    $lang_file = 'default.php';
    if (strpos($theme_css, 'princess') !== false) {
        $lang_file = 'princess.php';
    }
    
    $lang_path = "includes/lang/" . $lang_file;
    
    if (file_exists($lang_path)) {
        require_once $lang_path;
    } else {
        require_once "includes/lang/default.php";
    }

    // 3. GET GAMES
    // We fetch overrides (custom names/icons) based on the user's theme
    $sql = "
        SELECT 
            g.id, 
            g.folder_path,
            COALESCE(ov.display_name, g.default_title) as title,
            COALESCE(ov.display_icon, g.default_icon) as icon,
            (SELECT MAX(score) FROM progress p WHERE p.user_id = :uid AND p.game_id = g.id) as high_score
        FROM games g
        LEFT JOIN game_theme_overrides ov 
            ON g.id = ov.game_id AND ov.theme_id = :tid
        WHERE 
            g.active = 1 
            AND :grade_min >= g.min_grade 
            AND :grade_max <= g.max_grade
        ORDER BY g.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid'       => $user_id,
        ':tid'       => $user['theme_id'],
        ':grade_min' => $user['grade_level'],
        ':grade_max' => $user['grade_level']
    ]);
    
    $games = $stmt->fetchAll();

    // 4. GET BADGES (Grouped by Badge ID)
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
        .avatar { font-size: 40px; }
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
            cursor: help; transition: transform 0.2s;
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
    </style>
</head>
<body>

    <div class="profile-bar">
        <div class="user-info">
            <div class="avatar">👨‍🚀</div>
            <div class="details">
                <h2><?php echo $LANG['profile_title']; ?> <?php echo htmlspecialchars($user['username']); ?></h2>
                <p>Grade Level: <?php echo $user['grade_level']; ?></p>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>

    <div class="container">
        
        <div class="badge-section">
            <h3 style="margin:0; text-transform: uppercase; letter-spacing: 2px; color: var(--text-light); opacity:0.8;">
                Mission Patches
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
                        No patches yet. Complete missions to earn them!
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

                    // --- SMART LINKING LOGIC ---
                    // Check if the game has been converted to the new View system
                    $view_path = $game['folder_path'] . '/view.php';
                    
                    if (file_exists($view_path)) {
                        // Converted Game -> Use Unified Wrapper
                        $link = "play.php?game_id=" . $game['id'];
                    } else {
                        // Legacy Game (Shapes & Colors) -> Use direct folder link
                        $link = $game['folder_path'] . "/index.php";
                    }
                ?>
                    <a href="<?php echo $link; ?>" class="mission-card">
                        <div class="mission-icon"><?php echo $game['icon']; ?></div>
                        <div class="mission-title"><?php echo htmlspecialchars($game['title']); ?></div>
                        
                        <div class="stars active-<?php echo $stars; ?>">
                            <span>★</span><span>★</span><span>★</span>
                        </div>

                        <?php if ($score > 0): ?>
                            <div style="margin-top:5px; font-weight:bold; font-size: 0.9em; opacity: 0.8;">
                                Best: <?php echo $score; ?>
                            </div>
                        <?php else: ?>
                            <div style="margin-top:5px; font-size: 0.8em; opacity: 0.6;">
                                New Mission
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 40px; background: rgba(0,0,0,0.2); border-radius: 10px;">
                    <h2>No missions available!</h2>
                    <p>Current Grade Level: <strong><?php echo $user['grade_level']; ?></strong></p>
                    <p>Ask a parent to assign games for this grade level.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/speech-module.js"></script>
    <script src="assets/js/game-bridge.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const username = "<?php echo htmlspecialchars($user['username']); ?>";
            
            // USE PHP VARIABLE FOR WELCOME MESSAGE
            const welcomeText = "<?php echo $LANG['welcome']; ?>"; 

            // Only speak welcome if they haven't been here in the last 5 minutes
            const lastVisit = localStorage.getItem('klh_last_visit');
            const now = Date.now();
            
            // Initializing Bridge (Optional, but good practice)
            if (typeof GameBridge !== 'undefined') GameBridge.init();

            if (!lastVisit || (now - lastVisit) > 300000) {
                setTimeout(() => {
                    if (window.speakText) {
                        // Combine the localized welcome with the username
                        window.speakText(welcomeText + " " + username);
                    }
                }, 1000);
            }
            localStorage.setItem('klh_last_visit', now);
        });
    </script>

</body>
</html>