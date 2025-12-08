<?php
// PRODUCTION SECURITY: Turn off error display to users
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

session_start();
require_once 'includes/db.php';

// --- 1. HANDLE LOGIN ATTEMPT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $pin     = $_POST['pin'];

    // Rate Limiting (Security)
    if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $error = "Too many tries. Please wait a few minutes.";
    } else {
        // Fetch specific user by ID
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Verify PIN
        if ($user && $user['pin_code'] === $pin) {
            
            // 1. STANDARD SESSION SETUP
            $_SESSION['failed_attempts'] = 0;
            unset($_SESSION['lockout_time']);
            session_regenerate_id(true);
            
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // 2. "REMEMBER ME" LOGIC
            // Ensure we check for the value '1'
            if (isset($_POST['remember']) && $_POST['remember'] == '1') {
                // Generate tokens
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $hashed_validator = password_hash($validator, PASSWORD_DEFAULT);
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 Days

                // Store in DB
               $stmt = $pdo->prepare("INSERT INTO user_tokens (selector, hashed_validator, user_id, expiry) VALUES (?, ?, ?, ?)");
                $stmt->execute([$selector, $hashed_validator, $user['id'], $expiry]);

                // --- NEW SECURE COOKIE SETTING ---
                $cookie_options = [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'domain' => '', // Leave empty for current domain
                    'secure' => true, // TRUE because you are using HTTPS
                    'httponly' => true,
                    'samesite' => 'Lax' // Helps prevent CSRF
                ];
                setcookie('remember_me', $selector . ':' . $validator, $cookie_options);
                
            }
            // 3. REDIRECT
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } elseif ($user['role'] === 'parent') {
                header("Location: parent.php");
            } else {
                header("Location: index.php");
            }
            exit;

        } else {
            // FAILURE
            if (!isset($_SESSION['failed_attempts'])) $_SESSION['failed_attempts'] = 0;
            $_SESSION['failed_attempts']++;
            
            if ($_SESSION['failed_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + 300; // 5 min lockout
                $error = "Account locked for 5 minutes.";
            } else {
                $error = "Wrong PIN. Try again.";
            }
        }
    }
}

// --- 2. FETCH USERS FOR GRID ---
// Only showing active accounts
$stmt = $pdo->query("SELECT id, username, avatar, role FROM users ORDER BY role DESC, username ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kids Hub Login</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <link rel="icon" type="image/x-icon" href="assets/icons/favicon.ico">
    <meta name="theme-color" content="#2c3e50">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #2c3e50; /* Space Blue Background */
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; 
        }

        .container {
            width: 90%; max-width: 800px;
            text-align: center;
        }

        h1 { color: #f1c40f; text-transform: uppercase; letter-spacing: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }

        /* --- USER GRID STYLES --- */
        .user-grid {
            display: flex; justify-content: center; flex-wrap: wrap; gap: 20px;
            margin-top: 30px;
        }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            width: 140px; padding: 20px 10px;
            cursor: pointer; transition: transform 0.2s, background 0.2s;
            backdrop-filter: blur(5px);
        }
        .user-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.2); border-color: #f1c40f; }

        .avatar { font-size: 60px; margin-bottom: 10px; display: block; }
        .username { color: white; font-weight: bold; font-size: 1.2em; }
        .role-badge { 
            font-size: 0.7em; text-transform: uppercase; 
            background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 10px;
            color: #ccc; margin-top: 5px; display: inline-block;
        }

        /* --- PIN PAD STYLES (Initially Hidden) --- */
        #pin-screen { display: none; background: white; padding: 30px; border-radius: 20px; max-width: 350px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .selected-user-display { margin-bottom: 20px; color: #333; }
        #pin-dots { font-size: 40px; letter-spacing: 5px; color: #333; background: #eee; border-radius: 10px; margin-bottom: 20px; min-height: 50px; line-height: 50px; }
        
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .key {
            background: #e0f7fa; border: none; border-radius: 10px;
            font-size: 24px; padding: 15px 0; font-weight: bold; color: #0277bd;
            cursor: pointer; transition: background 0.1s;
            touch-action: manipulation;
        }
        .key:active { background: #b3e5fc; transform: scale(0.95); }
        .key-back { background: #ffcdd2; color: #c62828; }
        .key-enter { background: #c8e6c9; color: #2e7d32; grid-column: span 3; }

        .error-msg { color: #ffeb3b; background: rgba(231, 76, 60, 0.8); padding: 10px; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    
    <div id="grid-screen">
        <h1>Who is playing?</h1>
        
        <?php if(isset($error)) echo "<div class='error-msg'>$error</div>"; ?>

        <div class="user-grid">
            <?php foreach($users as $u): ?>
                <div class="user-card" onclick="selectUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                    <span class="avatar">
                        <?php 
                            echo (strpos($u['avatar'], '.') !== false) ? '👤' : $u['avatar']; 
                        ?>
                    </span>
                    <div class="username"><?php echo htmlspecialchars($u['username']); ?></div>
                    <?php if($u['role'] !== 'student'): ?>
                        <div class="role-badge"><?php echo $u['role']; ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="pin-screen">
        <div class="selected-user-display">
            <h3>Hello, <span id="display-name">User</span>!</h3>
            <p style="color:#777; margin-top:-10px; font-size:0.9em;">Enter your secret code</p>
        </div>

        <div id="pin-dots"></div>

        <div class="keypad">
            <button class="key" onclick="pressKey(1)">1</button>
            <button class="key" onclick="pressKey(2)">2</button>
            <button class="key" onclick="pressKey(3)">3</button>
            <button class="key" onclick="pressKey(4)">4</button>
            <button class="key" onclick="pressKey(5)">5</button>
            <button class="key" onclick="pressKey(6)">6</button>
            <button class="key" onclick="pressKey(7)">7</button>
            <button class="key" onclick="pressKey(8)">8</button>
            <button class="key" onclick="pressKey(9)">9</button>
            <button class="key key-back" onclick="clearPin()">⬅</button>
            <button class="key" onclick="pressKey(0)">0</button>
            <button class="key key-back" onclick="resetView()">❌</button> 
            <button class="key key-enter" onclick="submitLogin()">GO!</button>
        </div>

        <form method="POST" id="loginForm">
            <input type="hidden" name="user_id" id="input-user-id">
            <input type="hidden" name="pin" id="input-pin">
            
            <div style="margin: 15px 0;">
                <label style="color:#7f8c8d; font-size:1.1em; cursor:pointer;">
                    <input type="checkbox" name="remember" value="1" style="transform: scale(1.5); margin-right:10px;"> 
                    Stay Signed In
                </label>
            </div>
        </form>
    </div>

</div>

<script>
    let currentPin = "";

    function selectUser(id, name) {
        document.getElementById('input-user-id').value = id;
        document.getElementById('display-name').innerText = name;
        currentPin = "";
        updateDots();
        document.getElementById('grid-screen').style.display = 'none';
        document.getElementById('pin-screen').style.display = 'block';
    }

    function resetView() {
        document.getElementById('pin-screen').style.display = 'none';
        document.getElementById('grid-screen').style.display = 'block';
        currentPin = "";
    }

    function pressKey(num) {
        if (currentPin.length < 4) {
            currentPin += num;
            updateDots();
        }
    }

    function clearPin() {
        currentPin = currentPin.slice(0, -1);
        updateDots();
    }

    function updateDots() {
        const dots = document.getElementById('pin-dots');
        dots.innerText = "•".repeat(currentPin.length);
    }

    function submitLogin() {
        if (currentPin.length > 0) {
            document.getElementById('input-pin').value = currentPin;
            document.getElementById('loginForm').submit();
        }
    }
</script>

</body>
</html>