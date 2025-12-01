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
    <title>Launch Time</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
    body {
        text-align: center;
        /* CHANGE 1: Allow scrolling if the screen is too small */
        overflow-y: auto; 
        margin: 0;
        padding-bottom: 20px; /* Add breathing room at the bottom */
    }

    /* The "Return to Base" Button */
    .nav-home {
        position: absolute;
        top: 15px; left: 15px;
        background: var(--card-bg); color: white;
        text-decoration: none; padding: 10px 15px;
        border-radius: 30px; font-weight: bold;
        border: 2px solid #ecf0f1; z-index: 1000;
        display: flex; align-items: center; gap: 5px;
        transition: all 0.2s;
    }
    .nav-home:hover { background: var(--planet-red); transform: scale(1.05); }

    /* The Mission Order (Digital Time) */
    #mission-board {
        background: rgba(255, 255, 255, 0.1);
        width: 80%; max-width: 400px;
        /* CHANGE 2: Smaller margins */
        margin: 10px auto; 
        padding: 10px;
        border-radius: 10px; border: 2px solid var(--star-gold);
        box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);
        backdrop-filter: blur(5px);
    }
    h2 { margin: 0; font-size: 18px; text-transform: uppercase; color: var(--star-gold); }
    #target-time {
        font-size: 50px; font-weight: bold;
        font-family: 'Courier New', monospace;
        background: #000; color: var(--planet-red);
        padding: 5px 20px; border-radius: 5px;
        display: inline-block; margin-top: 5px;
        border: 2px solid #555;
    }

    /* The Clock Face */
    #clock-face {
        /* CHANGE 3: Smaller Clock (was 300px) */
        width: 250px; height: 250px;
        background: white; border: 12px solid #95a5a6;
        border-radius: 50%; margin: 15px auto;
        position: relative;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }
    
    /* Clock Markers */
    .marker {
        position: absolute; font-weight: bold; color: #333; font-size: 20px;
    }
    /* Removed manual positioning classes in favor of dynamic PHP loop */

    .center-dot {
        width: 20px; height: 20px; background: var(--planet-red);
        border-radius: 50%; position: absolute;
        top: 50%; left: 50%; transform: translate(-50%, -50%);
        z-index: 10;
    }

    /* The Hands */
    .hand {
        position: absolute; bottom: 50%; left: 50%;
        transform-origin: bottom center; 
        border-radius: 10px 10px 0 0;
        transition: transform 0.5s cubic-bezier(0.4, 2.5, 0.5, 0.5); 
        z-index: 5;
    }
    .hand-hour {
        width: 10px; height: 60px; /* Shorter hand */
        background: #2c3e50;
        margin-left: -5px; 
    }
    .hand-minute {
        width: 6px; height: 90px; /* Shorter hand */
        background: var(--planet-red);
        margin-left: -3px; 
    }

    /* Controls */
    #controls {
        display: flex; justify-content: center; gap: 20px;
        margin-top: 15px;
    }
    .control-group {
        display: flex; flex-direction: column; gap: 8px;
    }
    button {
        padding: 8px 15px; font-size: 16px; border-radius: 8px;
        border: none; cursor: pointer; font-weight: bold;
        box-shadow: 0 4px 0 rgba(0,0,0,0.2);
        transition: all 0.1s;
    }
    button:active { transform: translateY(4px); box-shadow: none; }
    
    .btn-hour { background: #3498db; color: white; }
    .btn-min  { background: var(--planet-red); color: white; }
    
    .btn-launch { 
        margin-top: 15px; padding: 15px 40px; 
        font-size: 24px; background: var(--nebula-green); color: white; 
        border-bottom: 5px solid #27ae60;
        cursor: pointer;
        border-radius: 50px;
    }
    .btn-launch:active { transform: translateY(4px); border-bottom: none; margin-bottom: 5px; }

    /* Feedback Message */
    #message { height: 30px; font-size: 20px; font-weight: bold; margin-top: 5px; color: var(--star-gold); text-shadow: 1px 1px 2px black; }
    
/* START SCREEN OVERLAY */
#start-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(44, 62, 80, 0.95); z-index: 2000;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
#start-btn {
    background: var(--nebula-green); color: white; font-size: 30px; font-weight: bold;
    padding: 20px 50px; border: 4px solid #27ae60; border-radius: 50px;
    cursor: pointer; box-shadow: 0 10px 0 #1e8449; animation: pulse 2s infinite;
}
#start-btn:active { transform: translateY(5px); box-shadow: 0 5px 0 #1e8449; }
@keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
</style>
</head>
<body>
<div id="start-overlay">
    <h1 style="color:white; font-size:40px; margin-bottom:20px;">Mission Ready?</h1>
    <div style="display:flex; gap:20px;">
        <button id="start-btn-1" onclick="initGame(1)" style="
            background: var(--nebula-green); color: white; font-size: 24px; font-weight: bold;
            padding: 15px 30px; border: 4px solid #27ae60; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #1e8449; transition: transform 0.1s;
        ">Level 1: Cadet<br><span style="font-size:14px">(5-Min Steps)</span></button>
        
        <button id="start-btn-2" onclick="initGame(2)" style="
            background: var(--planet-red); color: white; font-size: 24px; font-weight: bold;
            padding: 15px 30px; border: 4px solid #c0392b; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #922b21; transition: transform 0.1s;
        ">Level 2: Commander<br><span style="font-size:14px">(1-Min Precision)</span></button>
    </div>
</div>
    <a href="../../index.php" class="nav-home">🏠 Base</a>

    <div id="mission-board">
        <h2>Launch Time:</h2>
        <div id="target-time">--:--</div>
    </div>

    <div id="message"></div>

    <div id="clock-face">
        <?php for($i=1; $i<=12; $i++): 
            $deg = $i * 30;
        ?>
            <div class="marker" style="
                position: absolute;
                left: 50%; top: 50%;
                transform: translate(-50%, -50%) rotate(<?php echo $deg; ?>deg) translate(0, -95px) rotate(-<?php echo $deg; ?>deg);
                font-size: 20px; font-weight: bold; color: #333;
            "><?php echo $i; ?></div>
        <?php endfor; ?>
        
        <div class="center-dot"></div>
        <div class="hand hand-hour" id="hour-hand"></div>
        <div class="hand hand-minute" id="min-hand"></div>
    </div>

    <div id="controls">
        <div class="control-group">
            <button class="btn-hour" onclick="changeHour(1)">+ 1 Hour</button>
            <button class="btn-hour" onclick="changeHour(-1)">- 1 Hour</button>
        </div>
        <div class="control-group">
            <button class="btn-min" onclick="changeMinute(5)">+ 5 Min</button>
            <button class="btn-min" onclick="changeMinute(-5)">- 5 Min</button>
        </div>
        <div class="control-group" id="fine-controls" style="display:none;">
            <button class="btn-min" onclick="changeMinute(1)" style="background:#e67e22;">+ 1 Min</button>
            <button class="btn-min" onclick="changeMinute(-1)" style="background:#e67e22;">- 1 Min</button>
        </div>
    </div>

    <button class="btn-launch" onclick="checkTime()">LAUNCH! 🚀</button>
    <div style="margin-top:10px; opacity: 0.8; font-weight: bold;">Score: <span id="score">0</span></div>

    <button onclick="playInstructions()" style="position:absolute; top:15px; right:15px; font-size:24px; background:none; border:none; cursor:pointer; color: white;">
    🔊 Help
</button>

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