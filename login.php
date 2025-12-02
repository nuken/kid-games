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
    <title>Kids Hub Login</title>
    <style>
        /* "Clean Desk" Design */
        body { font-family: sans-serif; background: #e0f7fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        
        /* The Display Screen */
        #pin-display {
            font-size: 40px; letter-spacing: 10px; margin-bottom: 20px;
            background: #eee; padding: 10px; border-radius: 10px; min-height: 50px;
        }

        /* The Keypad Grid */
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        
        /* Big Buttons for easy clicking */
        .btn {
            background: #4fc3f7; border: none; padding: 20px; font-size: 24px;
            border-radius: 10px; cursor: pointer; transition: transform 0.1s;
            color: white; font-weight: bold;
        }
        .btn:active { transform: scale(0.95); }
        .btn-go { background: #66bb6a; grid-column: span 3; } /* Green GO button */
        .btn-clear { background: #ef5350; } /* Red Clear button */
    </style>
</head>
<body>

<div class="login-box">
    <h2>Who are you?</h2>
    
    <form method="POST" id="loginForm">
        <input type="hidden" name="pin" id="real-pin">
    </form>

    <div id="pin-display"></div>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

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
        <button class="btn" onclick="addNum('<')">Back</button>
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
        document.getElementById('real-pin').value = currentPin;
        document.getElementById('loginForm').submit();
    }
</script>
<div style="position: fixed; bottom: 10px; right: 10px; opacity: 0.5;">
    <a href="parent.php" style="text-decoration: none; color: #aaa; font-size: 12px;">Parent Dashboard</a>
</div>
</body>
</html>