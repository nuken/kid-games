<?php
session_start();
// 1. Redirect if not logged in
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
    <title>Robo-Sorter</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            text-align: center;
            overflow: hidden; /* No scrolling */
        }
        #game-area {
            position: relative;
            height: 70vh;
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            background: #2f4f4f; /* Dark Slate Gray */
            border: 5px solid #000;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        /* The Conveyor Belt */
        #conveyor {
            position: absolute;
            top: 50px;
            left: 0;
            width: 100%;
            height: 10px;
            background: #000;
        }
        /* The Number Box */
        #number-box {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            background: var(--star-gold);
            color: #000;
            font-size: 40px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px #998100;
            transition: top 5s linear; /* The movement speed */
            z-index: 10;
        }
        /* The "Visual Aid" dots (hidden by default) */
        #visual-hint {
            position: absolute;
            top: 120px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            pointer-events: none;
        }
        .dot {
            width: 10px;
            height: 10px;
            background: cyan;
            border-radius: 50%;
            margin: 2px;
            box-shadow: 0 0 5px white;
        }
        .dot.lonely {
            background: #ff4444; /* Red for the odd one out */
        }

        /* The Bins */
        .bin {
            position: absolute;
            bottom: 20px;
            width: 40%;
            height: 150px;
            border: 4px dashed #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            opacity: 0.7;
            cursor: pointer;
            transition: 0.2s;
            border-radius: 10px;
        }
        .bin:hover { opacity: 1; background: rgba(255,255,255,0.1); transform: scale(1.02); }
        #bin-odd { left: 5%; border-color: #ff9999; color: #ff9999; }
        #bin-even { right: 5%; border-color: #99ff99; color: #99ff99; }

        /* Score & Feedback */
        #score-board { font-size: 24px; margin-top: 10px; font-weight: bold; }
        #message {
            position: absolute;
            top: 40%;
            width: 100%;
            font-size: 50px;
            font-weight: bold;
            text-shadow: 2px 2px 0 #000;
            display: none;
            z-index: 20;
        }
        
        /* Combo Text */
        #combo-text {
            font-size: 18px;
            color: var(--star-gold);
            height: 20px;
        }

        /* Navigation Button */
        .nav-home {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--card-bg);
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 30px;
            font-weight: bold;
            border: 2px solid #ecf0f1;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        .nav-home:hover {
            background: var(--planet-red);
            transform: scale(1.05);
        }
        
        /* THE VISUAL LEGEND (Cheat Sheet) */
        #legend-card {
            position: absolute;
            top: 80px; 
            left: 20px; 
            background: var(--card-bg);
            border: 2px solid var(--star-gold);
            border-radius: 10px;
            padding: 10px;
            width: 140px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 50;
            transition: transform 0.2s;
        }
        #legend-card:active { transform: scale(0.95); }

        .legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px;
            border-radius: 5px;
        }
        .legend-even { background: rgba(46, 204, 113, 0.2); border: 1px solid var(--nebula-green); }
        .legend-odd  { background: rgba(231, 76, 60, 0.2); border: 1px solid var(--planet-red); }

        .legend-text {
            font-size: 14px;
            font-weight: bold;
            color: white;
            text-align: left;
        }
        .legend-dots { display: flex; gap: 4px; }

        /* Mini Dots for the Legend */
        .mini-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #3498db;
            border: 1px solid #2980b9;
        }
        .mini-dot.red { background: var(--planet-red); border-color: #c0392b; }

        /* Responsive */
        @media (max-width: 600px) {
            #legend-card {
                position: relative;
                top: 0; left: 0;
                margin: 10px auto;
                width: 80%;
            }
        }
        
        /* START SCREEN OVERLAY */
        #start-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(44, 62, 80, 0.95);
            z-index: 2000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        #start-btn {
            background: var(--nebula-green);
            color: white;
            font-size: 30px;
            font-weight: bold;
            padding: 20px 50px;
            border: 4px solid #27ae60;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 10px 0 #1e8449;
            transition: transform 0.1s;
            animation: pulse 2s infinite;
        }
        #start-btn:active {
            transform: translateY(5px);
            box-shadow: 0 5px 0 #1e8449;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

<div id="start-overlay">
    <h1 style="color:white; font-size:40px; margin-bottom:20px;">Ready to Sort?</h1>
    <div style="display:flex; gap:20px;">
        <button id="start-btn-1" onclick="initGame(1)" style="
            background: var(--nebula-green); color: white; font-size: 24px; font-weight: bold;
            padding: 15px 30px; border: 4px solid #27ae60; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #1e8449; transition: transform 0.1s;
        ">Level 1: Cadet<br><span style="font-size:14px">(With Hints)</span></button>
        
        <button id="start-btn-2" onclick="initGame(2)" style="
            background: var(--planet-red); color: white; font-size: 24px; font-weight: bold;
            padding: 15px 30px; border: 4px solid #c0392b; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #922b21; transition: transform 0.1s;
        ">Level 2: Commander<br><span style="font-size:14px">(No Hints)</span></button>
    </div>
</div>

<a href="../../index.php" class="nav-home">
    🏠 Base
</a>

    <button onclick="playInstructions()" style="position:absolute; top:15px; right:15px; font-size:24px; background:none; border:none; cursor:pointer; color: white;">
        🔊 Help
    </button>

    <h1>Robo-Sorter 3000</h1>
    <div id="combo-text"></div>
    <div id="score-board">Score: <span id="score">0</span></div>

    <div id="legend-card" onclick="explainRules()">
        <div style="font-size:12px; margin-bottom:5px; color:#ccc;">CLICK FOR HELP</div>
        
        <div class="legend-row legend-even">
            <div class="legend-text">EVEN<br><small>Buddies</small></div>
            <div class="legend-dots">
                <div class="mini-dot"></div>
                <div class="mini-dot"></div>
            </div>
        </div>

        <div class="legend-row legend-odd">
            <div class="legend-text">ODD<br><small>Lonely</small></div>
            <div class="legend-dots">
                <div class="mini-dot"></div>
                <div class="mini-dot red"></div>
            </div>
        </div>
    </div>
    
    <div id="game-area">
        <div id="conveyor"></div>
        
        <div id="number-box" onclick="showHint()">?</div>

        <div id="visual-hint"></div>

        <div id="message"></div>

        <div class="bin" id="bin-odd" onclick="checkAnswer('odd')">
            ODD<br>(Lonely)
        </div>
        <div class="bin" id="bin-even" onclick="checkAnswer('even')">
            EVEN<br>(Buddies)
        </div>
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