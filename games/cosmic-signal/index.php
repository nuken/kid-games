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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Signal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            text-align: center;
            user-select: none;
            overflow: hidden;
        }

        /* Signal Screen (The "Question") */
        #signal-screen {
            background: rgba(0, 0, 0, 0.6);
            border: 4px solid var(--hologram-cyan);
            border-radius: 15px;
            width: 80%;
            max-width: 700px;
            height: 150px;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--hologram-cyan);
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 10px var(--hologram-cyan);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
            position: relative;
            transition: border-color 0.2s;
            cursor: pointer;
        }
        
        #signal-screen:active {
            border-color: white;
        }

        /* Scanlines effect */
        #signal-screen::after {
            content: " ";
            display: block;
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 2;
            background-size: 100% 2px, 3px 100%;
            pointer-events: none;
        }

        /* Frequency Buttons (The "Answers") */
        #frequency-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .freq-btn {
            background: linear-gradient(145deg, #34495e, #2c3e50);
            color: white;
            font-size: 28px;
            padding: 20px 40px;
            border: 2px solid #7f8c8d;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.2s;
            min-width: 150px;
            font-weight: bold;
        }
        .freq-btn:hover {
            transform: translateY(-5px);
            border-color: var(--star-gold);
            box-shadow: 0 0 15px var(--star-gold);
            color: var(--star-gold);
        }
        .freq-btn:active {
            transform: scale(0.95);
        }

        /* Feedback Message */
        #message {
            height: 50px;
            margin-top: 20px;
            font-size: 32px;
            font-weight: bold;
            text-shadow: 2px 2px 4px black;
        }

        /* Start Overlay */
        #start-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.95); z-index: 2000;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        
        .nav-home {
            position: absolute; top: 15px; left: 15px;
            background: var(--card-bg); color: white; text-decoration: none;
            padding: 10px 15px; border-radius: 30px; font-weight: bold;
            border: 2px solid #ecf0f1; z-index: 1000;
            display: flex; align-items: center; gap: 5px;
            transition: all 0.2s;
        }
        .nav-home:hover { background: var(--planet-red); transform: scale(1.05); }

    </style>
</head>
<body>

<div id="start-overlay">
    <h1 style="color:var(--hologram-cyan); font-size:50px; margin-bottom:10px; text-shadow: 0 0 15px var(--hologram-cyan);">Cosmic Signal</h1>
    <p style="color:white; font-size:20px; margin-bottom:40px;">Decode the alien messages!</p>
    
    <div style="display:flex; gap:30px;">
        <button onclick="initGame(1)" style="
            background: var(--nebula-green); color: white; font-size: 24px; font-weight: bold;
            padding: 20px 40px; border: 4px solid #27ae60; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #1e8449; transition: transform 0.1s;
        ">Level 1: Cadet<br><span style="font-size:16px">(Watch & Find)</span></button>
        
        <button onclick="initGame(2)" style="
            background: var(--planet-red); color: white; font-size: 24px; font-weight: bold;
            padding: 20px 40px; border: 4px solid #c0392b; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #922b21; transition: transform 0.1s;
        ">Level 2: Commander<br><span style="font-size:16px">(Complete Sentence)</span></button>
    </div>
</div>

<a href="../../index.php" class="nav-home">🏠 Base</a>

<button onclick="playInstructions()" style="position:absolute; top:15px; right:15px; font-size:24px; background:none; border:none; cursor:pointer; color: white;">
    🔊 Help
</button>

<h1 style="margin-top: 20px; color: white; text-shadow: 0 0 10px var(--space-blue);">Cosmic Signal</h1>

<div id="signal-screen" title="Click to replay audio">
    <span id="question-text">WAITING FOR SIGNAL...</span>
</div>

<div style="color: #7f8c8d; font-size: 12px; margin-top: 5px;">(Click the screen to hear the signal again)</div>

<div id="message"></div>

<div id="frequency-controls">
    </div>

<div style="margin-top:40px; opacity: 0.8; font-weight: bold; color: white;">
    Score: <span id="score">0</span>
</div>

<script>
    window.gameData = {
        userId: <?php echo $_SESSION['user_id']; ?>,
        gradeLevel: <?php echo $_SESSION['grade_level'] ?? 2; ?>
    };
</script>
<script src="../../assets/js/speech-module.js?v=<?php echo filemtime('../../assets/js/speech-module.js'); ?>"></script>
<script src="game.js?v=<?php echo filemtime('game.js'); ?>"></script>
</body>
</html>