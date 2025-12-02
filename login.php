<?php
// Add these lines for debugging:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db.php';

// Handle the Login Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pin = $_POST['pin'];
    
    // Find user with this PIN
    $stmt = $pdo->prepare("SELECT * FROM users WHERE pin_code = ?");
    $stmt->execute([$pin]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php"); // Send to Dashboard
        exit;
    } else {
        $error = "Uh oh! Wrong Code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kids Hub Login</title>
    <style>
        /* "Clean Desk" Design */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #e0f7fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        
        .login-box { 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            text-align: center;
            /* FIX: Responsive Width */
            width: 90%;
            max-width: 400px; 
        }
        
        /* The Display Screen */
        #pin-display {
            font-size: 40px; 
            letter-spacing: 10px; 
            margin-bottom: 20px;
            background: #eee; 
            padding: 15px; 
            border-radius: 10px; 
            min-height: 50px;
            color: #333;
            font-weight: bold;
        }

        /* The Keypad Grid */
        .keypad { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px; /* More space between buttons */
        }
        
        /* Big Buttons for easy clicking */
        .btn {
            background: #4fc3f7; 
            border: none; 
            padding: 20px 0; /* Vertical padding only, let grid handle width */
            font-size: 28px;
            border-radius: 15px; 
            cursor: pointer; 
            transition: transform 0.1s;
            color: white; 
            font-weight: bold;
            box-shadow: 0 4px 0 #0288d1;
            touch-action: manipulation; /* Removes mobile tap delay */
        }
        
        .btn:active { 
            transform: translateY(4px); 
            box-shadow: none; 
        }
        
        .btn-go { 
            background: #66bb6a; 
            grid-column: span 3; 
            box-shadow: 0 4px 0 #388e3c;
        } 
        
        .btn-clear { 
            background: #ef5350; 
            box-shadow: 0 4px 0 #d32f2f;
        } 
    </style>
</head>
<body>

<div class="login-box">
    <h2 style="color:#0277bd; margin-top:0;">Who are you?</h2>
    
    <form method="POST" id="loginForm">
        <input type="hidden" name="pin" id="real-pin">
    </form>

    <div id="pin-display"></div>
    <?php if(isset($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>

    <div class="keypad">
        <button class="btn" onclick="addNum(1)">1</button>
        <button class="btn" onclick="addNum(2)">2</button>
        <button class="btn" onclick="addNum(3)">3</button>
        <button class="btn" onclick="addNum(4)">4</button>
        <button class="btn" onclick="addNum(5)">5</button>
        <button class="btn" onclick="addNum(6)">6</button>
        <button class="btn" onclick="addNum(7)">7</button>
        <button class="btn" onclick="addNum(8)">8</button>
        <button class="btn" onclick="addNum(9)">9</button>
        <button class="btn btn-clear" onclick="clearPin()">X</button>
        <button class="btn" onclick="addNum(0)">0</button>
        <button class="btn" onclick="addNum('<')">⬅</button>
        <button class="btn btn-go" onclick="submitForm()">LET'S GO!</button>
    </div>
</div>

<script>
    let currentPin = "";
    const display = document.getElementById('pin-display');

    function addNum(num) {
        if (num === '<') {
            currentPin = currentPin.slice(0, -1);
        } else if (currentPin.length < 4) {
            currentPin += num;
        }
        updateDisplay();
    }

    function clearPin() {
        currentPin = "";
        updateDisplay();
    }

    function updateDisplay() {
        // Show stars instead of numbers for "Spy Mode" fun
        display.innerText = "*".repeat(currentPin.length); 
    }

    function submitForm() {
        if (currentPin.length > 0) {
            document.getElementById('real-pin').value = currentPin;
            document.getElementById('loginForm').submit();
        }
    }
</script>

<div style="position: fixed; bottom: 10px; right: 10px; opacity: 0.5;">
    <a href="parent.php" style="text-decoration: none; color: #aaa; font-size: 12px;">Parent Dashboard</a>
</div>
</body>
</html>
