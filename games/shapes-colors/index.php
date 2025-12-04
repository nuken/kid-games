<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Shapes & Colors</title>
    
    <script src="https://unpkg.com/konva@9/konva.min.js"></script>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <div id="main-menu" class="game-screen visible">
        <a href="../../index.php" class="nav-home">🏠 Base</a>
        <h1>Shapes & Colors!</h1>
        
        <div class="menu-buttons">
            <button id="start-leaf-sort-btn" class="menu-btn" style="background: linear-gradient(to bottom, #4CAF50, #388E3C);">Leaf Color Sort</button>
            <button id="start-shape-web-btn" class="menu-btn" style="background: linear-gradient(to bottom, #9C27B0, #7B1FA2);">Spider's Shape Web</button>
            <button id="start-shape-puzzle-btn" class="menu-btn" style="background: linear-gradient(to bottom, #F44336, #D32F2F);">Shape Puzzles</button>
            <button id="start-mixing-btn" class="menu-btn" style="background: linear-gradient(to bottom, #E91E63, #C2185B);">Color Mixing</button>
        </div>

        <div style="margin-top:20px; color: #333; font-weight: bold;">
            Session Score: <span id="session-score">0</span>
        </div>
    </div>

    <div id="leaf-sort-game" class="game-screen">
        <button class="back-btn">Menu</button>
        <h2>Sort the Leaves!</h2>
        <div id="leaf-sort-canvas" class="touch-area"></div>
    </div>

    <div id="shape-web-game" class="game-screen">
        <button class="back-btn">Menu</button>
        <h2>Fill the Web!</h2>
        <div id="spider-web-display" class="touch-area">
            <img src="images/friendly-spider.png" alt="Friendly Spider" id="friendly-spider">
        </div>
        <div id="shape-choices-container"></div>
        <button id="next-shape-button" class="hidden next-btn">Next Shape!</button>
    </div>

    <div id="shape-puzzle-game" class="game-screen">
        <button class="back-btn">Menu</button>
        <h2 id="puzzle-prompt">Let's build!</h2>
        <div id="shape-puzzle-canvas" class="touch-area"></div>
        <button id="next-puzzle-button" class="hidden next-btn">Next Puzzle!</button>
    </div>

    <div id="mixing-game" class="game-screen">
        <button class="back-btn">Menu</button>
        <h2>Color Mixing!</h2>
        <div id="mixing-problem-container"></div>
        <div id="mixing-choices"></div>
    </div>

    <script>
        window.gameData = {
            userId: <?php echo $_SESSION['user_id']; ?>,
            gradeLevel: <?php echo $_SESSION['grade_level'] ?? 0; ?>
        };
    </script>

    <script src="../../assets/js/speech-module.js"></script>
    <script src="../../assets/js/sticker-module.js"></script>
    <script src="../../assets/js/game-bridge.js"></script>
    <script src="game.js"></script>
</body>
</html>