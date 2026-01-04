<?php
// play.php
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php'; 

// CSRF Security Setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function auto_version($file) {
    if (file_exists($file)) return $file . '?v=' . filemtime($file);
    return $file;
}

// 1. Get Game Details
$game_id = $_GET['game_id'] ?? null;
if (!$game_id) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];

// Get Theme Overrides
$stmt = $pdo->prepare("SELECT theme_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$theme_id = $stmt->fetchColumn();

$sql = "SELECT g.*, COALESCE(ov.display_name, g.default_title) as final_title, COALESCE(ov.display_icon, g.default_icon) as final_icon
    FROM games g LEFT JOIN game_theme_overrides ov ON g.id = ov.game_id AND ov.theme_id = :tid WHERE g.id = :gid";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $theme_id, ':gid' => $game_id]);
$game = $stmt->fetch();

if (!$game) die("Game not found.");
$game['default_title'] = $game['final_title'];

// 2. Load Language
require_once 'includes/lang/default.php';
$default_lang = $LANG;
$theme_css = basename($theme_path);
$lang_file = str_replace('.css', '.php', $theme_css);
$lang_path = 'includes/lang/' . $lang_file;
if ($lang_file !== 'default.php' && file_exists($lang_path)) {
    include $lang_path;
    $LANG = array_merge($default_lang, $LANG);
}

// 3. Prepare JS Config (Now with CSRF!)
$jsConfig = [
    'userId'    => $_SESSION['user_id'],
    'gameId'    => $game['id'],
    'themePath' => $theme_path,
    'confetti'  => (bool)($current_user['confetti_enabled'] ?? true),
    'csrfToken' => $_SESSION['csrf_token'],
    'root'      => './'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($game['default_title']); ?></title>
    
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <link rel="icon" type="image/x-icon" href="assets/icons/favicon.ico">
    <meta name="theme-color" content="#2c3e50">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <link rel="stylesheet" href="<?php echo auto_version('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version($theme_path); ?>">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/play.css'); ?>">

    <?php
        $game_css = $game['folder_path'] . '/style.css';
        if (file_exists($game_css)) echo '<link rel="stylesheet" href="' . auto_version($game_css) . '">';
    ?>
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
            <p id="overlay-desc"><?php echo $LANG['loading']; ?></p>
            <div id="level-select"></div>
        </div>
    </div>

    <div id="game-stage">
        <?php
            $view_file = $game['folder_path'] . '/view.php';
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                echo "<div class='alert error'><h3>Error</h3><p>Game view not found: $view_file</p></div>";
            }
        ?>
    </div>

    <script>
        window.gameConfig = <?php echo json_encode($jsConfig); ?>;
        window.LANG = <?php echo json_encode($LANG); ?>;
    </script>

    <script src="<?php echo auto_version('assets/js/speech-module.js'); ?>"></script>
    <script src="<?php echo auto_version('assets/js/game-bridge.js'); ?>"></script>
    <script src="<?php echo auto_version($game['folder_path'] . '/game.js'); ?>"></script>

</body>
</html>