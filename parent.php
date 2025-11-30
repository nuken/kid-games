<?php
require_once 'includes/db.php';

// 1. GET ALL STUDENTS (For the dropdown menu)
$stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
$students = $stmt->fetchAll();

// 2. DETERMINE CURRENT STUDENT
// If URL has ?student_id=X, use that. Otherwise, default to the first student in the list.
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
} else {
    // If no students exist yet, handle gracefully
    if (count($students) > 0) {
        $student_id = $students[0]['id'];
    } else {
        die("No students found. Please create a user in the database first.");
    }
}

// Get the specific name of the current student
$current_student_name = "Unknown";
foreach($students as $s) {
    if ($s['id'] == $student_id) {
        $current_student_name = $s['username'];
        break;
    }
}

// 3. GET OVERALL STATS (Dynamic user_id)
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_sessions, 
    SUM(duration_seconds) as total_time,
    AVG(score) as global_average
    FROM progress WHERE user_id = ?");
$stmt->execute([$student_id]);
$stats = $stmt->fetch();

$total_minutes = floor(($stats['total_time'] ? $stats['total_time'] : 0) / 60);

// 4. GET STATS PER GAME (Dynamic user_id)
$stmt = $pdo->prepare("SELECT 
    g.title, 
    COUNT(p.id) as times_played, 
    MAX(p.score) as best_score, 
    AVG(p.score) as avg_score, 
    AVG(p.duration_seconds) as avg_duration
    FROM games g
    LEFT JOIN progress p ON g.id = p.game_id AND p.user_id = ?
    GROUP BY g.id");
$stmt->execute([$student_id]);
$game_stats = $stmt->fetchAll();

// 5. GET RECENT HISTORY (Dynamic user_id)
$stmt = $pdo->prepare("SELECT p.*, g.title 
    FROM progress p 
    JOIN games g ON p.game_id = g.id 
    WHERE p.user_id = ? 
    ORDER BY p.played_at DESC 
    LIMIT 10");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f4f6f7; color: #333; background-image: none; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* Header Card */
        .header-card {
            background: white; padding: 20px; border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .stat-box { text-align: center; }
        .stat-num { font-size: 30px; font-weight: bold; color: var(--space-blue); }
        .stat-label { font-size: 14px; color: #7f8c8d; text-transform: uppercase; }

        /* Student Selector */
        .student-select {
            padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #ddd;
            background: #f9f9f9; font-weight: bold; color: var(--space-blue); cursor: pointer;
        }

        /* Game Performance Cards */
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; color: #333; }
        h2 { margin-top: 0; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; color: var(--space-blue); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #7f8c8d; font-size: 14px; }
        
        /* Progress Bars */
        .progress-bg { background: #eee; height: 10px; border-radius: 5px; width: 100px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 5px; }
        
        .back-btn { text-decoration: none; color: #3498db; font-weight: bold; }
        
        /* Simple Chart Bar */
        .chart-bar-container {
            display: flex; align-items: flex-end; height: 150px; gap: 10px; margin-top: 20px;
            border-bottom: 1px solid #ccc; padding-bottom: 5px;
        }
        .chart-bar {
            width: 40px; background: var(--nebula-green); border-radius: 5px 5px 0 0;
            position: relative; transition: height 0.5s;
        }
        .chart-bar:hover { opacity: 0.8; }
        .chart-label {
            text-align: center; font-size: 12px; margin-top: 5px; color: #777;
        }
    </style>
</head>
<body>

<div class="container">
    <div style="margin-bottom: 15px; display:flex; justify-content:space-between; align-items:center;">
        <a href="index.php" class="back-btn">← Back to Kid's Dashboard</a>
        
        <form method="GET" action="parent.php">
            <select name="student_id" class="student-select" onchange="this.form.submit()">
                <?php foreach($students as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php if($s['id'] == $student_id) echo 'selected'; ?>>
                        👤 <?php echo htmlspecialchars($s['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="header-card">
        <div>
            <h1 style="margin:0; color: var(--space-blue);">Progress Report</h1>
            <p style="margin:5px 0 0; color:#7f8c8d;">Student: <strong><?php echo htmlspecialchars($current_student_name); ?></strong></p>
            
            <button onclick="confirmReset(<?php echo $student_id; ?>, '<?php echo $current_student_name; ?>')" style="
                margin-top: 10px;
                background: var(--planet-red); color: white; border: none; padding: 8px 15px; 
                border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold;">
                🗑️ RESET THIS STUDENT
            </button>
        </div>
        <div style="display:flex; gap: 40px;">
            <div class="stat-box">
                <div class="stat-num"><?php echo $stats['total_sessions']; ?></div>
                <div class="stat-label">Games Played</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo $total_minutes; ?>m</div>
                <div class="stat-label">Total Time</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo round($stats['global_average']); ?>%</div>
                <div class="stat-label">Avg Score</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Skill Mastery</h2>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Times Played</th>
                    <th>Best Score</th>
                    <th>Average Score</th>
                    <th>Avg Time (Speed)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($game_stats as $game): 
                    $avg = round($game['avg_score']);
                    $color = ($avg >= 80) ? '#2ecc71' : (($avg >= 50) ? '#f1c40f' : '#e74c3c');
                    $status = ($avg >= 80) ? 'Mastered' : (($avg >= 50) ? 'Improving' : 'Needs Help');
                ?>
                <tr>
                    <td style="font-weight:bold;"><?php echo htmlspecialchars($game['title']); ?></td>
                    <td><?php echo $game['times_played']; ?></td>
                    <td><?php echo $game['best_score']; ?>%</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="progress-bg">
                                <div class="progress-fill" style="width:<?php echo $avg; ?>%; background:<?php echo $color; ?>;"></div>
                            </div>
                            <?php echo $avg; ?>%
                        </div>
                    </td>
                    <td><?php echo round($game['avg_duration']); ?>s</td>
                    <td style="color:<?php echo $color; ?>; font-weight:bold;"><?php echo $status; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- New Visual Chart Section -->
    <div class="card">
        <h2>Performance Overview</h2>
        <div style="display:flex; justify-content: space-around; align-items: flex-end;">
             <?php foreach($game_stats as $game): 
                $height = round($game['avg_score']) * 1.5; // Scale for visual
                $color = ($game['avg_score'] >= 80) ? '#2ecc71' : (($game['avg_score'] >= 50) ? '#f1c40f' : '#e74c3c');
             ?>
             <div style="text-align:center;">
                 <div class="chart-bar" style="height: <?php echo $height; ?>px; background: <?php echo $color; ?>; width: 50px; margin: 0 auto;"></div>
                 <div class="chart-label" style="margin-top:10px; font-weight:bold;"><?php echo htmlspecialchars($game['title']); ?></div>
                 <div style="font-size:12px; color:#777;"><?php echo round($game['avg_score']); ?>%</div>
             </div>
             <?php endforeach; ?>
        </div>
    </div>

    <!-- Recommendations Section -->
    <div class="card">
        <h2>Recommendations</h2>
        <ul style="list-style: none; padding: 0;">
            <?php foreach($game_stats as $game): 
                $avg = round($game['avg_score']);
                $msg = "";
                $icon = "";
                $style = "";

                if ($avg >= 80) {
                    $icon = "🏆";
                    $msg = "<strong>" . htmlspecialchars($game['title']) . "</strong> is mastered! Challenge " . htmlspecialchars($current_student_name) . " to beat their high score.";
                    $style = "color: #27ae60;";
                } elseif ($avg >= 50) {
                    $icon = "⭐";
                    $msg = "Doing well in <strong>" . htmlspecialchars($game['title']) . "</strong>. Keep practicing to reach Master status!";
                    $style = "color: #f39c12;";
                } else {
                    $icon = "💡";
                    $msg = "Needs practice with <strong>" . htmlspecialchars($game['title']) . "</strong>. Try playing 3 times this week.";
                    $style = "color: #c0392b;";
                }
            ?>
            <li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; gap: 10px; align-items: center; font-size: 16px;">
                <span style="font-size: 24px;"><?php echo $icon; ?></span>
                <span style="<?php echo $style; ?>"><?php echo $msg; ?></span>
            </li>
            <?php endforeach; ?>
            
            <?php if(count($game_stats) == 0): ?>
                <li style="padding: 10px; color: #7f8c8d;">No data available yet. Have the student play some games!</li>
            <?php endif; ?>
        </ul>
        </ul>
    </div>

    <!-- Badges Section -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #ecf0f1; margin-bottom: 10px; padding-bottom: 10px;">
            <h2 style="border:none; margin:0; padding:0;">Mission Patches Earned</h2>
            <button onclick="confirmResetBadges(<?php echo $student_id; ?>, '<?php echo $current_student_name; ?>')" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px;">Reset Patches</button>
        </div>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <?php
            // Fetch earned badges for this student
            $stmt = $pdo->prepare("
                SELECT b.name, b.icon, b.description, ub.earned_at 
                FROM user_badges ub 
                JOIN badges b ON ub.badge_id = b.id 
                WHERE ub.user_id = ?
                ORDER BY ub.earned_at DESC
            ");
            $stmt->execute([$student_id]);
            $earned_badges = $stmt->fetchAll();

            if (count($earned_badges) > 0) {
                foreach ($earned_badges as $badge) {
                    echo '<div style="text-align:center; width: 100px;">';
                    echo '<div style="font-size: 40px; background: #f9f9f9; border-radius: 50%; width: 80px; height: 80px; display:flex; align-items:center; justify-content:center; margin: 0 auto; border: 2px solid var(--star-gold);">' . $badge['icon'] . '</div>';
                    echo '<div style="font-weight:bold; margin-top:5px; font-size:12px;">' . htmlspecialchars($badge['name']) . '</div>';
                    echo '<div style="font-size:10px; color:#7f8c8d;">' . date("M j", strtotime($badge['earned_at'])) . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="color:#7f8c8d;">No mission patches earned yet.</p>';
            }
            ?>
        </div>
    </div>

    <div class="card">
        <h2>Recent Activity Log</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Game</th>
                    <th>Score</th>
                    <th>Time Spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history as $row): 
                    $scoreClass = ($row['score'] >= 80) ? 'score-high' : (($row['score'] >= 50) ? 'score-med' : 'score-low');
                ?>
                <tr>
                    <td><?php echo date("M j, g:i a", strtotime($row['played_at'])); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td style="<?php echo ($row['score'] >= 80) ? 'color:#27ae60; font-weight:bold;' : (($row['score'] >= 50) ? 'color:#f39c12; font-weight:bold;' : 'color:#c0392b; font-weight:bold;'); ?>">
                        <?php echo $row['score']; ?>%
                    </td>
                    <td><?php echo $row['duration_seconds']; ?>s</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Updated JavaScript to accept Student ID and Name
function confirmReset(studentId, studentName) {
    if (confirm("WARNING: Are you sure you want to reset stats for " + studentName + "?\n\nThis will delete their high scores and history permanently.")) {
        
        fetch('api/reset_stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: studentId }) // Send dynamic ID
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Data cleared for " + studentName + "!");
                location.reload(); 
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function confirmResetBadges(studentId, studentName) {
    if (confirm("Are you sure you want to reset mission patches for " + studentName + "?")) {
        fetch('api/reset_badges.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: studentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Badges reset for " + studentName + "!");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>

</body>
</html>