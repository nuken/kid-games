<?php
// parent.php
require_once 'includes/header.php';

// --------------------------------------------------------
// 1. SECURITY & CSRF SETUP
// --------------------------------------------------------
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'parent' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php"); exit;
}

// Generate CSRF Token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

// --------------------------------------------------------
// 2. HANDLE UPDATES (POST)
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("<div class='alert error'>Security Error: Invalid Session. Please refresh.</div>");
    }

    // --- A. ADD CHILD ---
    if ($_POST['action'] === 'add_child') {
        $child_name = trim($_POST['child_name']);
        $child_pin  = trim($_POST['child_pin']);
        $child_grade = $_POST['child_grade'];
        $safe_name = htmlspecialchars($child_name);

        if (!empty($child_name) && !empty($child_pin)) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$child_name]);
            if ($checkStmt->fetch()) {
                $message = "<div class='alert error'>Name '$safe_name' is taken. Try adding an initial.</div>";
            } else {
                // Default: Confetti ON, Messaging ON
                $stmt = $pdo->prepare("INSERT INTO users (username, pin_code, role, grade_level, parent_id, avatar, confetti_enabled, messaging_enabled) VALUES (?, ?, 'student', ?, ?, '👤', 1, 1)");

                $hashed_pin = password_hash($child_pin, PASSWORD_DEFAULT);
                if ($stmt->execute([$child_name, $hashed_pin, $child_grade, $_SESSION['user_id']])) {
                    $message = "<div class='alert success'>Added $safe_name successfully!</div>";
                } else {
                    $message = "<div class='alert error'>Database Error.</div>";
                }
            }
        }
    }

    // --- B. UPDATE CHILD SETTINGS ---
    elseif ($_POST['action'] === 'update_settings') {
        $u_id = $_POST['student_id'];
        $u_grade = $_POST['grade_level'];
        $u_theme = $_POST['theme_id'];
        $u_avatar = $_POST['avatar'];
        $u_confetti = isset($_POST['confetti']) ? 1 : 0;
        $u_messaging = isset($_POST['messaging']) ? 1 : 0; // NEW FIELD

        // Verify Ownership
        if ($_SESSION['role'] === 'parent') {
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND parent_id = ?");
            $check->execute([$u_id, $_SESSION['user_id']]);
            if (!$check->fetch()) die("Permission Denied.");
        }

        $stmt = $pdo->prepare("UPDATE users SET grade_level = ?, theme_id = ?, avatar = ?, confetti_enabled = ?, messaging_enabled = ? WHERE id = ?");
        if ($stmt->execute([$u_grade, $u_theme, $u_avatar, $u_confetti, $u_messaging, $u_id])) {
            $message = "<div class='alert success'>Settings Updated!</div>";
        }
    }

    // --- C. UPDATE PARENT PROFILE ---
    elseif ($_POST['action'] === 'update_parent_profile') {
        $p_avatar = $_POST['parent_avatar'];
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$p_avatar, $_SESSION['user_id']]);
        $message = "<div class='alert success'>Profile Updated!</div>";
    }
}

// --------------------------------------------------------
// 3. FETCH DATA
// --------------------------------------------------------
// Get Children
if ($_SESSION['role'] === 'admin') {
    $students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY username ASC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND parent_id = ? ORDER BY username ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $students = $stmt->fetchAll();
}

// Get Parent Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch();

$themes = $pdo->query("SELECT * FROM themes ORDER BY name ASC")->fetchAll();

// Determine Selected Student
$student_id = $_GET['student_id'] ?? ($_POST['student_id'] ?? ($students[0]['id'] ?? 0));

$current_student = null;
foreach ($students as $s) {
    if ($s['id'] == $student_id) {
        $current_student = $s;
        break;
    }
}
// Fallback
if (!$current_student && !empty($students)) {
    $current_student = $students[0];
    $student_id = $current_student['id'];
}

// --------------------------------------------------------
// 4. STATS LOGIC
// --------------------------------------------------------
$stats = ['total' => 0, 'time' => 0, 'avg' => 0];
$game_stats = [];

if ($current_student) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(duration_seconds) as time, AVG(score) as avg FROM progress WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $db_stats = $stmt->fetch();
    if ($db_stats) $stats = $db_stats;

    $stmt = $pdo->prepare("
        SELECT g.default_title as title, COUNT(p.id) as plays, MAX(p.score) as best, AVG(p.score) as avg
        FROM games g
        LEFT JOIN progress p ON g.id = p.game_id AND p.user_id = ?
        GROUP BY g.id HAVING plays > 0
    ");
    $stmt->execute([$student_id]);
    $game_stats = $stmt->fetchAll();
}

// AVATAR LISTS
$kid_avatars = $kid_avatar_list;
$adult_avatars = $adult_avatar_list;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Control Center</title>
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/parent.css'); ?>">
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1>Parent Controls</h1>
        <a href="logout.php" class="back-link">⬅ Log Out</a>
    </div>

    <?php echo $message; ?>

    <div class="dashboard-grid">
        <div class="left-col">

            <div class="card" style="border-left: 5px solid #8e44ad;">
                <h2>👤 My Profile</h2>
                <div class="profile-header">
                    <div class="big-avatar"><?php echo ($me['avatar'] && strpos($me['avatar'], '.')===false) ? $me['avatar'] : '👤'; ?></div>
                    <div>
                        <strong><?php echo htmlspecialchars($me['username']); ?></strong><br>
                        <small style="color:#777;">Parent Account</small>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_parent_profile">
                    <label>Change My Avatar:</label>
                    <select name="parent_avatar" onchange="this.form.submit()">
                        <?php foreach ($adult_avatars as $emoji => $label): ?>
                            <option value="<?php echo $emoji; ?>" <?php echo ($me['avatar'] == $emoji) ? 'selected' : ''; ?>>
                                <?php echo $emoji . " " . $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="card">
                <h2>Select Child</h2>
                <form method="GET">
                    <select name="student_id" onchange="this.form.submit()">
                        <?php if(count($students) > 0): ?>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == $student_id) ? 'selected' : ''; ?>>
                                    👤 <?php echo htmlspecialchars($s['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No children found</option>
                        <?php endif; ?>
                    </select>
                </form>
            </div>

            <div class="card" style="background: #e3f2fd; border-color: #90caf9;">
                <h2>➕ Add a Child</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="add_child">

                    <label>Name</label>
                    <input type="text" name="child_name" placeholder="e.g. Timmy" required>

                    <label>PIN Code</label>
                    <input type="text" name="child_pin" placeholder="e.g. 1111" required>

                    <label>Grade Level</label>
                    <select name="child_grade">
                        <option value="0">Preschool</option>
                        <option value="1">Kindergarten</option>
                        <option value="2">1st Grade</option>
                        <option value="3">2nd Grade</option>
                        <option value="4">3rd Grade</option>
                        <option value="5">4th Grade</option>
                    </select>

                    <button type="submit" class="save-btn" style="background: #3498db;">Add Child</button>
                </form>
            </div>

            <?php if ($current_student): ?>
            <div class="card" style="background: #fdfefe; border-top: 4px solid #f1c40f;">
                <h2>⚙️ Settings for <?php echo htmlspecialchars($current_student['username']); ?></h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="student_id" value="<?php echo $current_student['id']; ?>">

                    <label>Grade Level</label>
                    <select name="grade_level">
                        <?php for($i=0; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($current_student['grade_level'] == $i) ? 'selected' : ''; ?>>
                                Grade <?php echo $i == 0 ? 'PK' : ($i == 1 ? 'K' : $i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <label>Theme</label>
                    <select name="theme_id">
                        <?php foreach ($themes as $theme): ?>
                            <option value="<?php echo $theme['id']; ?>" <?php echo ($current_student['theme_id'] == $theme['id']) ? 'selected' : ''; ?>>
                                🎨 <?php echo htmlspecialchars($theme['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Avatar</label>
                    <div style="display: flex; gap: 10px;">
                        <div id="avatar-preview" class="big-avatar" style="width: 50px; height: 50px; font-size: 25px;">
                            <?php echo $current_student['avatar'] ?: '👤'; ?>
                        </div>
                        <select name="avatar" id="avatar-select" onchange="document.getElementById('avatar-preview').innerText = this.value;">
                            <?php foreach ($kid_avatars as $emoji => $label): ?>
                                <option value="<?php echo $emoji; ?>" <?php echo ($current_student['avatar'] == $emoji) ? 'selected' : ''; ?>>
                                    <?php echo $emoji . " " . $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin: 20px 0; background: #fafafa; padding: 10px; border-radius: 8px;">
                        <label style="display:flex; align-items:center; cursor:pointer; margin-bottom: 10px;">
                            <input type="checkbox" name="confetti" value="1" style="width:auto; margin-right:10px;"
                                <?php if($current_student['confetti_enabled']) echo 'checked'; ?>>
                            Enable Confetti 🎉
                        </label>

                        <label style="display:flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="messaging" value="1" style="width:auto; margin-right:10px;"
                                <?php if($current_student['messaging_enabled']) echo 'checked'; ?>>
                            Allow Messaging 📬
                        </label>
                        <small style="display:block; color:#777; margin-left: 25px;">If disabled, child cannot send/receive messages.</small>
                    </div>

                    <button type="submit" class="save-btn">📝 Save Changes</button>
                </form>

                <div style="margin-top: 5px; text-align: center; display: flex; flex-direction: column; gap: 5px;">
                    <form method="POST" action="api/reset_badges.php?user_id=<?php echo $current_student['id']; ?>&redirect=true&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onsubmit="return confirm('Remove all badges? (Scores will stay)')">
                        <button type="submit" class="save-btn" style="background: #e67e22; width: 100%;">↺ Reset Badges Only</button>
                    </form>

                    <form method="POST" action="api/reset_stats.php?user_id=<?php echo $current_student['id']; ?>&redirect=true&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onsubmit="return confirm('WARNING: This deletes ALL history, scores, and badges. Continue?')">
                        <button type="submit" class="save-btn" style="background: #e74c3c; width: 100%;">🗑️ Reset All Progress</button>
                    </form>
                </div>

                <a href="report_card.php?student_id=<?php echo $current_student['id']; ?>" class="save-btn" style="background:#34495e; margin-top: 30px; display:block; text-align:center; text-decoration:none; box-sizing:border-box;">
                    📄 View Full Report Card
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="right-col">
            <?php if ($current_student): ?>
                <div class="card">
                     <h2>📬 Last 5 Messages</h2>
                     <?php
                     $msgs = $pdo->prepare("SELECT m.*, u.username as friend FROM messages m JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ? ORDER BY m.sent_at DESC LIMIT 5");
                     $msgs->execute([$student_id, $student_id, $student_id]);
                     $recent_msgs = $msgs->fetchAll();
                     ?>
                     <?php if(count($recent_msgs) > 0): ?>
                        <ul style="list-style:none; padding:0; font-size:0.9rem;">
                        <?php foreach($recent_msgs as $m): ?>
                            <?php $is_me = ($m['sender_id'] == $student_id); ?>
                            <li style="padding:5px; border-bottom:1px solid #eee;">
                                <?php echo $is_me ? "➡️ Sent to" : "⬅️ From"; ?>
                                <strong><?php echo htmlspecialchars($m['friend']); ?></strong>:
                                <?php echo $m['message']; ?>
                                <span style="color:#aaa; font-size:0.8em;"><?php echo date('M j', strtotime($m['sent_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                     <?php else: ?>
                        <p style="color:#999;">No messages found.</p>
                     <?php endif; ?>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-around; text-align:center;">
                        <div>
                            <div style="font-size:2em; font-weight:bold; color:#3498db;"><?php echo $stats['total']; ?></div>
                            <div style="color:#777;">Missions</div>
                        </div>
                        <div>
                            <div style="font-size:2em; font-weight:bold; color:#3498db;"><?php echo floor($stats['time'] / 60); ?>m</div>
                            <div style="color:#777;">Minutes</div>
                        </div>
                        <div>
                            <div style="font-size:2em; font-weight:bold; color:#3498db;"><?php echo round($stats['avg'] ?? 0); ?>%</div>
                            <div style="color:#777;">Avg Score</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Game Performance</h2>
                    <?php if (count($game_stats) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Game</th><th>Plays</th><th>Best</th><th>Avg</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($game_stats as $game): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($game['title']); ?></td>
                                <td><?php echo $game['plays']; ?></td>
                                <td style="color:#27ae60; font-weight:bold;"><?php echo $game['best']; ?>%</td>
                                <td><?php echo round($game['avg']); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="color:#999; text-align:center;">No games played yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card"><p>Welcome! Add a child on the left to get started.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
