<?php
// Define path to root (2 levels up)
$path_depth = 2;
require_once '../../includes/header.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spell It!</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $theme_path; ?>">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div id="start-overlay">
        <h1 style="color:white; font-size:50px; margin-bottom:10px; text-shadow: 0 4px 0 rgba(0,0,0,0.2);">Spelling Mission</h1>
        <p style="color:#ecf0f1; font-size:20px; margin-bottom:40px;">Choose your difficulty to begin!</p>
        
        <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:20px;">
            <button class="start-btn btn-l1" onclick="initGame(1)">
                Level 1: Cadet
                <span class="btn-sub">(Easy Letters)</span>
            </button>
            
            <button class="start-btn btn-l2" onclick="initGame(2)">
                Level 2: Commander
                <span class="btn-sub">(Extra Trick Letters)</span>
            </button>
        </div>
    </div>

    <a href="../../index.php" class="nav-home">🏠 Base</a>

    <div id="game-area">
        <img id="word-image" src="" alt="Word">
        
        <div id="word-blanks" class="word-blanks"></div>
        <div id="letter-choices" class="letter-choices"></div>
        
        <button id="next-btn" class="next-btn hidden" onclick="loadLevel()">Next Word ➡</button>

        <div style="margin-top: 20px; font-size: 1.2em; font-weight: bold; opacity: 0.8;">
            Score: <span id="score">0</span>
        </div>
    </div>

    <script>
        window.gameData = { userId: <?php echo $_SESSION['user_id']; ?> };
    </script>
    <script src="../../assets/js/speech-module.js"></script>
    <script src="../../assets/js/game-bridge.js"></script>
    <script src="game.js"></script>
</body>
</html>