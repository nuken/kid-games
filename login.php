<?php
// login.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
if (isset($_SESSION['user_id'])) {
    // If already logged in, go to the correct dashboard
    $dest = ($_SESSION['role'] === 'admin') ? 'admin/index.php' : (($_SESSION['role'] === 'parent') ? 'parent.php' : 'index.php');
    header("Location: $dest");
    exit;
}
require_once 'includes/db.php';

// Generate Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. HANDLE LOGIN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh.";
    } 
    // Rate Limit
    elseif (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $error = "Too many tries. Wait 5 minutes.";
    } else {
        $user_id = $_POST['user_id'];
        $pin     = $_POST['pin'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $user['pin_code'] === $pin) {
            // SUCCESS
            $_SESSION['failed_attempts'] = 0;
            unset($_SESSION['lockout_time']);
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // "Remember Me"
            if (isset($_POST['remember']) && $_POST['remember'] == '1') {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $hashed = password_hash($validator, PASSWORD_DEFAULT);
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 30));
                
                $stmt = $pdo->prepare("INSERT INTO user_tokens (selector, hashed_validator, user_id, expiry) VALUES (?, ?, ?, ?)");
                $stmt->execute([$selector, $hashed, $user['id'], $expiry]);

                $opts = ['expires'=>time()+(86400*30), 'path'=>'/', 'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax'];
                setcookie('remember_me', $selector.':'.$validator, $opts);
            }

            // Redirect
            $dest = ($user['role'] === 'admin') ? 'admin/index.php' : (($user['role'] === 'parent') ? 'parent.php' : 'index.php');
            header("Location: $dest"); exit;

        } else {
            // FAILURE
            $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
            if ($_SESSION['failed_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + 300;
                $error = "Locked for 5 minutes.";
            } else {
                $error = "Wrong PIN.";
            }
        }
    }
}

// 2. FETCH USERS
$users = $pdo->query("SELECT id, username, avatar, role FROM users ORDER BY role DESC, username ASC")->fetchAll();
function auto_version($file) { return file_exists($file) ? $file . '?v=' . filemtime($file) : $file; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kids Hub Login</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/login.css'); ?>">
</head>
<body>

<div class="container">
    
    <div id="grid-screen">
        <h1>Who is playing?</h1>
        <a href="register.php" class="join-btn">✨ New Family? Join Here!</a>
        
        <?php if(isset($error)) echo "<div class='error-msg'>$error</div>"; ?>

        <div class="user-grid">
            <?php foreach($users as $u): ?>
                <div class="user-card" onclick="selectUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                    <?php if ($u['role'] === 'admin'): ?>
                        <div class="role-badge" title="Admin">🛡️</div>
                    <?php elseif ($u['role'] === 'parent'): ?>
                        <div class="role-badge" title="Parent">🔑</div>
                    <?php endif; ?>
                    <span class="avatar"><?php echo (strpos($u['avatar'], '.') !== false) ? '👤' : $u['avatar']; ?></span>
                    <div class="username"><?php echo htmlspecialchars($u['username']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="pin-screen">
        <div class="selected-user-display">
            <h3>Hello, <span id="display-name">User</span>!</h3>
            <p style="color:#777; margin-top:-5px;">Enter your secret code</p>
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
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="user_id" id="input-user-id">
            <input type="hidden" name="pin" id="input-pin">
            
            <div style="margin: 20px 0;">
                <label style="color:#7f8c8d; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px;">
                    <input type="checkbox" name="remember" value="1" style="transform: scale(1.3);"> 
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
        document.getElementById('pin-dots').innerText = "•".repeat(currentPin.length);
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