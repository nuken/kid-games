<?php
// parent.php
// 1. Error Reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db.php';

// --------------------------------------------------------
// DEFINITIONS
// --------------------------------------------------------
// Define available avatars (Using Emojis)
$avatar_options = [
    '👤' => 'Default',
    '👨‍🚀' => 'Astronaut',
    '👸' => 'Princess',
    '🤖' => 'Robot',
    '🦸' => 'Super Hero',
    '🐱' => 'Cat',
    '🐶' => 'Dog',
    '🦁' => 'Lion',
    '🦄' => 'Unicorn',
    '🧙‍♂️' => 'Wizard',
    '👽' => 'Alien',
    '👾' => 'Space Invader'
];

// --------------------------------------------------------
// 2. HANDLE SETTINGS UPDATES (POST)
// --------------------------------------------------------
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_settings') {
        $u_id = $_POST['student_id'];
        $u_grade = $_POST['grade_level'];
        $u_theme = $_POST['theme_id'];
        $u_avatar = $_POST['avatar']; // Get avatar selection

        $stmt = $pdo->prepare("UPDATE users SET grade_level = ?, theme_id = ?, avatar = ? WHERE id = ?");
        if ($stmt->execute([$u_grade, $u_theme, $u_avatar, $u_id])) {
            $message = "<div class='alert success'>Settings Updated! Go check the dashboard.</div>";
        } else {
            $message = "<div class='alert error'>Error updating settings.</div>";
        }
    }
}

// --------------------------------------------------------
// 3. FETCH DATA (Only Students for THIS Parent)
// --------------------------------------------------------
// Security Check: Ensure user is a parent (or admin)
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'parent' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php"); exit;
}

$students = [];
if ($_SESSION['role'] === 'admin') {
    // Admins see all students
    $students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY username ASC")->fetchAll();
} else {
    // Parents only see their own linked children
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND parent_id = ? ORDER BY username ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $students = $stmt->fetchAll();
}

$themes = $pdo->query("SELECT * FROM themes ORDER BY name ASC")->fetchAll();

// Determine which student we are looking at
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
} elseif (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
} else {
    // Default to the first student found, or 0 if none
    $student_id = count($students) > 0 ? $students[0]['id'] : 0;
}

// Get current student's info
$current_student = null;
foreach ($students as $s) {
    if ($s['id'] == $student_id) {
        $current_student = $s;
        break;
    }
}

// Fallback if ID is invalid but students exist
if (!$current_student && count($students) > 0) {
    $current_student = $students[0];
    $student_id = $current_student['id'];
}

// --------------------------------------------------------
// 4. CALCULATE STATS
// --------------------------------------------------------
$stats = ['total' => 0, 'time' => 0, 'avg' => 0];
$game_stats = [];

if ($current_student) {
    // Overall Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(duration_seconds) as time, AVG(score) as avg FROM progress WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $db_stats = $stmt->fetch();
    if ($db_stats) $stats = $db_stats;

    $total_minutes = floor(($stats['time'] ?? 0) / 60);

    // Per-Game Stats
    $stmt = $pdo->prepare("
        SELECT g.default_title as title, COUNT(p.id) as plays, MAX(p.score) as best, AVG(p.score) as avg
        FROM games g
        LEFT JOIN progress p ON g.id = p.game_id AND p.user_id = ?
        GROUP BY g.id
    ");
    $stmt->execute([$student_id]);
    $game_stats = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Control Center</title>
    <link rel="stylesheet" href="assets/css/style.css"> <style>
        /* PARENT DASHBOARD OVERRIDES */
        body {
            background: #f0f2f5;
            color: #333 !important; /* Force dark text */
            background-image: none;
            font-family: sans-serif;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-link { text-decoration: none; color: #333; font-weight: bold; font-size: 1.1em; }

        .dashboard-grid { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }

        .card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            color: #333; /* Ensure text inside cards is dark */
        }

        .card h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        label { display: block; margin-top: 10px; font-weight: bold; color: #555; }
        select, input[type=text] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            color: #333;
            background: #fff;
        }

        button.save-btn { width: 100%; margin-top: 20px; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 8px; font-size: 1em; cursor: pointer; font-weight: bold; }
        button.save-btn:hover { background: #27ae60; }

        /* TABLE STYLES */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }

        th {
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            background: #f8f9fa;
            color: #555; /* Dark header text */
            font-weight: bold;
        }

        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            color: #333; /* Dark cell text */
        }

        /* Notifications */
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
      <a href="logout.php" class="back-link">⬅ Log Out & Switch to Child</a>
        <h1>Parent Controls</h1>
    </div>

    <?php echo $message; ?>

    <div class="dashboard-grid">

        <div class="left-col">
            <div class="card">
                <h2>Select Child</h2>
                <form method="GET" action="parent.php">
                    <select name="student_id" onchange="this.form.submit()">
                        <?php if(count($students) > 0): ?>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == $student_id) ? 'selected' : ''; ?>>
                                    👤 <?php echo htmlspecialchars($s['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option>No children found</option>
                        <?php endif; ?>
                    </select>
                </form>
            </div>

            <?php if ($current_student): ?>
            <div class="card" style="background: #e8f4fd; border: 1px solid #b6d4fe;">
                <h2>⚙️ Settings</h2>
                <form method="POST" action="parent.php">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="student_id" value="<?php echo $current_student['id']; ?>">

                    <label>Display Name:</label>
                    <input type="text" value="<?php echo htmlspecialchars($current_student['username']); ?>" disabled style="background:#eee; color:#777;">

                    <label>Grade Level:</label>
                    <select name="grade_level">
                        <option value="0" <?php echo ($current_student['grade_level'] == 0) ? 'selected' : ''; ?>>Preschool (Age 3-4)</option>
                        <option value="1" <?php echo ($current_student['grade_level'] == 1) ? 'selected' : ''; ?>>Kindergarten (Age 5-6)</option>
                        <option value="2" <?php echo ($current_student['grade_level'] == 2) ? 'selected' : ''; ?>>1st Grade (Age 6-7)</option>
                        <option value="3" <?php echo ($current_student['grade_level'] == 3) ? 'selected' : ''; ?>>2nd Grade (Age 7-8)</option>
						<option value="4" <?php echo ($current_student['grade_level'] == 4) ? 'selected' : ''; ?>>3rd Grade (Age 8-9)</option>
						<option value="5" <?php echo ($current_student['grade_level'] == 5) ? 'selected' : ''; ?>>4th Grade (Age 9-10)</option>
						<option value="6" <?php echo ($current_student['grade_level'] == 6) ? 'selected' : ''; ?>>5th Grade (Age 10-11)</option>
                    </select>

                    <label>Theme:</label>
                    <select name="theme_id">
                        <?php foreach ($themes as $theme): ?>
                            <option value="<?php echo $theme['id']; ?>" <?php echo ($current_student['theme_id'] == $theme['id']) ? 'selected' : ''; ?>>
                                🎨 <?php echo htmlspecialchars($theme['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Avatar:</label>
                    <div style="display: flex; align-items: center; gap: 15px;">

                        <div id="avatar-preview" style="
                            width: 60px; height: 60px;
                            background: #fff;
                            border: 2px solid #ddd;
                            border-radius: 50%;
                            font-size: 35px;
                            display: flex; align-items: center; justify-content: center;
                            cursor: default;
                        ">
                            <?php
                                // Fallback: If DB has old filename (contains dot), show default emoji
                                $current_av = $current_student['avatar'] ?? '👤';
                                if (strpos($current_av, '.') !== false) $current_av = '👤';
                                echo $current_av;
                            ?>
                        </div>

                        <select name="avatar" id="avatar-select" onchange="updateAvatar()" style="flex: 1; font-size: 1.2em;">
                            <?php foreach ($avatar_options as $emoji => $label): ?>
                                <option value="<?php echo $emoji; ?>" <?php echo ($current_av == $emoji) ? 'selected' : ''; ?>>
                                    <?php echo $emoji . " " . $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="save-btn">Save Changes</button>
                </form>

                <div style="margin-top: 15px; text-align: center;">
                    <button onclick="if(confirm('Are you sure? This deletes ALL history.')) { location.href='api/reset_stats.php?user_id=<?php echo $current_student['id']; ?>&redirect=true'; }"
                            style="background:none; border:none; color: #e74c3c; text-decoration: underline; cursor: pointer;">
                        Reset Progress
                    </button>
                </div>

                <div style="margin-top: 15px; border-top: 1px solid #b6d4fe; padding-top: 15px;">
                    <a href="report_card.php?student_id=<?php echo $current_student['id']; ?>"
                       style="
                           display: block;
                           text-align: center;
                           background: #34495e;
                           color: white;
                           padding: 12px;
                           border-radius: 8px;
                           text-decoration: none;
                           font-weight: bold;
                       ">
                       📄 View Full Report Card
                    </a>
                </div>
            </div>

            <script>
                function updateAvatar() {
                    const select = document.getElementById('avatar-select');
                    const preview = document.getElementById('avatar-preview');
                    // Update text inside the circle
                    preview.innerText = select.value;
                }
            </script>
            <?php endif; ?>
        </div>

        <div class="right-col">
            <?php if ($current_student): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-around; text-align:center;">
                        <div>
                            <div style="font-size:2em; font-weight:bold; color:#3498db;"><?php echo $stats['total']; ?></div>
                            <div style="color:#777;">Missions</div>
                        </div>
                        <div>
                            <div style="font-size:2em; font-weight:bold; color:#3498db;"><?php echo $total_minutes; ?>m</div>
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
                            <?php foreach ($game_stats as $game):
                                $avg = round($game['avg'] ?? 0);
                                $best = $game['best'] ?? 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($game['title']); ?></td>
                                <td><?php echo $game['plays']; ?></td>
                                <td style="font-weight:bold; color:#27ae60;"><?php echo $best; ?>%</td>
                                <td><?php echo $avg; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="color:#999; text-align:center;">No games played yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card"><p>No students found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
