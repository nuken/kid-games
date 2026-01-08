<?php
// login.php
session_start();
require_once 'includes/db.php';

// If already logged in
if (isset($_SESSION['user_id'])) {
    $dest = ($_SESSION['role'] === 'admin') ? 'admin/index.php' : (($_SESSION['role'] === 'parent') ? 'parent.php' : 'index.php');
    header("Location: $dest");
    exit;
}

// Generate Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

// --- HANDLE LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Check CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh.";
    } else {
        $user = null;
        $password_input = "";

        // Determine Login Type
        if (isset($_POST['login_type']) && $_POST['login_type'] === 'staff') {
            // STAFF/PARENT LOGIN (Username + Password)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role != 'student'");
            $stmt->execute([trim($_POST['staff_username'])]);
            $user = $stmt->fetch();
            $password_input = $_POST['staff_password'];
        } else {
            // KID LOGIN (ID + PIN)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$_POST['user_id']]);
            $user = $stmt->fetch();
            $password_input = $_POST['pin'];
        }

        // 2. Process User
        if ($user) {
            // Check Lockout
            if ($user['locked_until'] && new DateTime($user['locked_until']) > new DateTime()) {
                $error = "Account locked! Try again in 15 minutes.";
            }
            // Verify Credential
            elseif (password_verify($password_input, $user['pin_code'])) {
                // SUCCESS
                $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Handle "Remember Me"
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

                $dest = ($user['role'] === 'admin') ? 'admin/index.php' : (($user['role'] === 'parent') ? 'parent.php' : 'index.php');
                header("Location: $dest"); exit;

            } else {
                // FAILURE - Track attempts
                $attempts = $user['failed_attempts'] + 1;

                if ($attempts >= 5) {
                    $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?")->execute([$attempts, $lock_time, $user['id']]);
                    $error = "Account locked for 15 minutes.";
                } else {
                    $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $user['id']]);
                    $error = "Wrong password/PIN.";
                }
            }
        } else {
            $error = "User not found.";
        }
    }
}

// FETCH ONLY STUDENTS FOR GRID
$students = $pdo->query("SELECT id, username, avatar FROM users WHERE role = 'student' ORDER BY username ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kids Hub Login</title>
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/login.css'); ?>">
    <style>
        .staff-link { margin-top: 30px; display: block; color: #bdc3c7; cursor: pointer; text-decoration: underline; font-size: 0.9rem; }
        .staff-link:hover { color: white; }
        .staff-form { display: none; margin-top: 20px; max-width: 300px; margin-left: auto; margin-right: auto; }
        .staff-input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; box-sizing: border-box;}
        .btn-staff { width: 100%; background: #3498db; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 1.1rem; cursor: pointer; margin-top: 10px; }
        .btn-staff:hover { background: #2980b9; }
        .register-link { display: block; margin-top: 15px; color: #f1c40f; text-decoration: none; font-weight: bold; }
        .register-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">

    <?php if(isset($error) && $error) echo "<div class='error-msg' style='display:block'>$error</div>"; ?>

    <div id="grid-screen">
        <h1 id="page-title">Who is playing?</h1>

        <div class="user-grid">
            <?php foreach($students as $u): ?>
                <div class="user-card" onclick="selectStudent(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                    <span class="avatar"><?php echo (strpos($u['avatar'], '.') !== false) ? '👤' : $u['avatar']; ?></span>
                    <div class="username"><?php echo htmlspecialchars($u['username']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <a onclick="toggleStaffLogin()" class="staff-link" id="toggle-btn">Parent or Admin Login</a>

        <div id="staff-login" class="staff-form">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="login_type" value="staff">

                <input type="text" name="staff_username" class="staff-input" placeholder="Username" required>
                <input type="password" name="staff_password" class="staff-input" placeholder="Password" required>

                <label style="color:#bdc3c7; font-size:0.9rem; display:flex; align-items:center; gap:5px; margin: 10px 0;">
                    <input type="checkbox" name="remember" value="1"> Stay Signed In
                </label>

                <button type="submit" class="btn-staff">Login</button>
            </form>

            <a href="register.php" class="register-link">✨ New Family? Register Here</a>
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
            <button class="key key-enter" onclick="submitStudentLogin()">GO!</button>
        </div>

        <form method="POST" id="studentForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="login_type" value="student">
            <input type="hidden" name="user_id" id="input-user-id">
            <input type="hidden" name="pin" id="input-pin">
        </form>
    </div>

</div>

<script>
    let currentPin = "";

    function toggleStaffLogin() {
        const form = document.getElementById('staff-login');
        const grid = document.querySelector('.user-grid');
        const title = document.getElementById('page-title');
        const btn = document.getElementById('toggle-btn');

        if (form.style.display === 'block') {
            // GOING BACK TO KIDS (Reset)
            form.style.display = 'none';
            grid.style.display = 'flex'; // FIXED: Uses flex instead of grid
            title.innerText = "Who is playing?";
            btn.innerText = "Parent or Admin Login";
        } else {
            // GOING TO PARENTS
            form.style.display = 'block';
            grid.style.display = 'none';
            title.innerText = "Parent Access";
            btn.innerText = "⬅ Back to Kids";
        }
    }

    function selectStudent(id, name) {
        document.getElementById('input-user-id').value = id;
        document.getElementById('display-name').innerText = name;
        currentPin = "";
        updateDots();
        document.getElementById('grid-screen').style.display = 'none';
        document.getElementById('pin-screen').style.display = 'block';
    }

    function resetView() {
        // Hides PIN screen, shows Grid screen
        document.getElementById('pin-screen').style.display = 'none';
        document.getElementById('grid-screen').style.display = 'block';

        // Ensure we are in "Kids" mode visually
        document.getElementById('staff-login').style.display = 'none';
        document.querySelector('.user-grid').style.display = 'flex'; // FIXED: Ensures cards are horizontal
        document.getElementById('page-title').innerText = "Who is playing?";
        document.getElementById('toggle-btn').innerText = "Parent or Admin Login";

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

    function submitStudentLogin() {
        if (currentPin.length > 0) {
            document.getElementById('input-pin').value = currentPin;
            document.getElementById('studentForm').submit();
        }
    }
</script>
</body>
</html>
