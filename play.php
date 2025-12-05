<?php
// play.php
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php'; 
require_once 'includes/header.php'; // Checks login & sets $theme_path

// 1. Get Game Details & Apply Theme Overrides
$game_id = $_GET['game_id'] ?? null;
if (!$game_id) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];

// Get user's theme ID to fetch specific game overrides
$stmt = $pdo->prepare("SELECT theme_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$theme_id = $stmt->fetchColumn();

// Fetch game details with overrides
$sql = "
    SELECT 
        g.*, 
        COALESCE(ov.display_name, g.default_title) as final_title,
        COALESCE(ov.display_icon, g.default_icon) as final_icon
    FROM games g
    LEFT JOIN game_theme_overrides ov 
        ON g.id = ov.game_id AND ov.theme_id = :tid
    WHERE g.id = :gid
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $theme_id, ':gid' => $game_id]);
$game = $stmt->fetch();

if (!$game) die("Game not found.");

// Update the array to use the overridden title
$game['default_title'] = $game['final_title'];

// ---------------------------------------------------------
// NEW: LOAD LANGUAGE FILE
// ---------------------------------------------------------
// 1. Load the Default Language first (as a fallback)
require_once 'includes/lang/default.php';
$default_lang = $LANG; // Keep a copy of defaults

// 2. Determine if we need a theme-specific language file
// We assume the theme CSS filename matches the PHP lang filename (e.g., 'princess.css' -> 'princess.php')
$theme_css = basename($theme_path); // e.g., "princess.css"
$lang_file = str_replace('.css', '.php', $theme_css); // "princess.php"
$lang_path = 'includes/lang/' . $lang_file;

// 3. If a specific language file exists, load it and merge with defaults
if ($lang_file !== 'default.php' && file_exists($lang_path)) {
    include $lang_path; // This overwrites $LANG with specific keys
    // Merge: specific theme strings overwrite defaults, missing ones stay default
    $LANG = array_merge($default_lang, $LANG);
}
// ---------------------------------------------------------

// Prepare Config for JS
$jsConfig = [
    'userId' => $_SESSION['user_id'],
    'gameId' => $game['id'],
    'themePath' => $theme_path, 
    'root' => './'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['default_title']); ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $theme_path; ?>">
    
    <?php 
        $game_css = $game['folder_path'] . '/style.css';
        if (file_exists($game_css)) {
            echo '<link rel="stylesheet" href="' . $game_css . '">';
        }
    ?>
    
    <style>
        /* Responsive Game Stage */
        #game-stage {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            min-height: 500px;
            padding: 10px;
        }
        
        /* Global Nav Styles override */
        .game-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.3);
            padding: 10px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
        }
        .nav-home { color: white; text-decoration: none; font-weight: bold; font-size: 1.2em; }
        .game-title { margin: 0; color: var(--star-gold); font-size: 1.5em; text-transform: uppercase; }
        .score-box { font-weight: bold; font-size: 1.2em; color: white; }

        /* Unified Overlay Styles */
        #system-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.95); z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .overlay-content { text-align: center; color: white; }
        .btn-level {
            background: var(--nebula-green); color: white;
            border: 3px solid rgba(255,255,255,0.2);
            padding: 15px 30px; font-size: 1.2em; font-weight: bold;
            border-radius: 50px; cursor: pointer; margin: 10px;
            transition: transform 0.2s;
        }
        .btn-level:hover { transform: scale(1.05); }
    </style>
</head>
<body>

    <nav class="game-nav">
        <a href="index.php" class="nav-home">⬅ <?php echo $LANG['base']; ?></a>
        <h1 class="game-title"><?php echo htmlspecialchars($game['default_title']); ?></h1>
        <div class="score-box"><?php echo $LANG['score']; ?>: <span id="score-display">0</span></div>
    </nav>

    <div id="system-overlay">
        <div class="overlay-content">
            <h1 style="font-size: 3em; margin-bottom: 10px; color: var(--star-gold);">
                <?php echo htmlspecialchars($game['default_title']); ?>
            </h1>
            <p id="overlay-desc" style="font-size: 1.5em; margin-bottom: 30px; color: #ccc;">
                <?php echo $LANG['loading']; ?>
            </p>
            <div id="level-select"></div>
        </div>
    </div>

    <div id="game-stage">
        <?php 
            $view_file = $game['folder_path'] . '/view.php';
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                echo "<div style='text-align:center; color:red;'><h3>Error</h3><p>Game view not found: $view_file</p></div>";
            }
        ?>
    </div>

    <script>
        window.gameConfig = <?php echo json_encode($jsConfig); ?>;
        window.LANG = <?php echo json_encode($LANG); ?>; 
    </script>

    <script src="assets/js/speech-module.js"></script>
    <script src="assets/js/game-bridge.js"></script>
    <script src="<?php echo $game['folder_path']; ?>/game.js"></script>

</body>
</html>