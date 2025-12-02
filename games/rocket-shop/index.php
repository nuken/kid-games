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
    <title>Rocket Shop</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            text-align: center;
            user-select: none; /* Prevent highlighting text while clicking fast */
        }
        
        /* The Item to Buy */
        #shop-counter {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid var(--star-gold);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            backdrop-filter: blur(5px);
        }
        #item-display {
            font-size: 80px;
            margin: 10px 0;
            filter: drop-shadow(0 0 10px rgba(255,255,255,0.5));
            animation: float 3s ease-in-out infinite;
        }
        #price-tag {
            background: var(--planet-red);
            color: white;
            font-size: 40px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 10px;
            display: inline-block;
            transform: rotate(-5deg);
            box-shadow: 3px 3px 5px rgba(0,0,0,0.3);
            border: 2px solid white;
        }

        /* The Payment Slot */
        #payment-slot {
            background: #34495e;
            width: 80%;
            max-width: 500px;
            height: 120px;
            margin: 20px auto;
            border: 5px solid #95a5a6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            font-weight: bold;
            color: var(--star-gold); /* Digital clock color */
            font-family: 'Courier New', monospace;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }

        /* The Wallet (Coin Holder) */
        #wallet {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        /* CSS Coins - No images needed! */
        .coin {
            border-radius: 50%;
            border: 4px solid rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 10px rgba(0,0,0,0.3);
            transition: transform 0.1s;
        }
        .coin:active { transform: scale(0.9); }
        .coin:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.4); }
        
        .quarter { width: 80px; height: 80px; background: #bdc3c7; color: #555; font-size: 24px; border: 4px solid #95a5a6; }
        .dime    { width: 50px; height: 50px; background: #bdc3c7; color: #555; font-size: 18px; border: 3px solid #95a5a6; }
        .nickel  { width: 65px; height: 65px; background: #bdc3c7; color: #555; font-size: 20px; border: 3px solid #95a5a6; }
        .penny   { width: 60px; height: 60px; background: #d35400; color: #fff; font-size: 18px; border: 3px solid #a04000; }

        /* Feedback Buttons */
        #controls { margin-top: 20px; }
        .btn {
            padding: 15px 30px;
            font-size: 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.2s;
        }
        .btn-reset { background: var(--planet-red); color: white; border-bottom: 4px solid #c0392b; }
        .btn-buy   { background: var(--nebula-green); color: white; border-bottom: 4px solid #27ae60; }
        .btn:active { transform: translateY(4px); border-bottom: none; margin-bottom: 4px; }

        #message {
            font-size: 30px;
            font-weight: bold;
            height: 40px;
            margin-top: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
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
            font-size: 16px; 
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
    <h1 style="color:white; font-size:40px; margin-bottom:20px;">Ready to Buy Parts?</h1>
    <div style="display:flex; gap:20px;">
        <button id="start-btn-1" onclick="initGame(1)" style="
            background: var(--nebula-green); color: white; font-size: 24px; font-weight: bold;
            padding: 15px 30px; border: 4px solid #27ae60; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #1e8449; transition: transform 0.1s;
        ">Level 1: Cadet<br><span style="font-size:14px">(Show Total)</span></button>
        
        <button id="start-btn-2" onclick="initGame(2)" style="
            background: var(--planet-red); color: white; font-size: 24px; font-weight: bold;
            padding: 15px 30px; border: 4px solid #c0392b; border-radius: 50px; cursor: pointer;
            box-shadow: 0 10px 0 #922b21; transition: transform 0.1s;
        ">Level 2: Commander<br><span style="font-size:14px">(Use Dollars)</span></button>
    </div>
</div>
<a href="../../index.php" class="nav-home">
    🏠 Base
</a>
    
    <button onclick="playInstructions()" style="position:absolute; top:15px; right:15px; font-size:24px; background:none; border:none; cursor:pointer; color: white;">
        🔊 Help
    </button>

    <h1>Rocket Shop</h1>
    
    <div id="shop-counter">
        <div id="item-display">🚀</div> <div id="price-tag"><span id="target-price">0</span></div>
    </div>

    <div id="message"></div>

    <div id="payment-slot">
        <span id="current-total">0</span>
    </div>

    <div id="wallet">
        <div class="coin dollar" onclick="addCoin(100)" style="
            width: 120px; height: 60px; background: #27ae60; color: #fff; 
            font-size: 24px; border: 4px solid #1e8449; border-radius: 5px;
        ">$1.00</div>
        <div class="coin quarter" onclick="addCoin(25)">25¢</div>
        <div class="coin dime" onclick="addCoin(10)">10¢</div>
        <div class="coin nickel" onclick="addCoin(5)">5¢</div>
        <div class="coin penny" onclick="addCoin(1)">1¢</div>
    </div>

    <div id="controls">
        <button class="btn btn-reset" onclick="resetCoins()">Clear</button>
        <button class="btn btn-buy" onclick="checkPurchase()">BUY PART</button>
    </div>

    <div style="margin-top:20px; opacity: 0.8; font-weight: bold;">
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