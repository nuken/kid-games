<?php
require_once 'includes/db.php';

// 1. GET ALL STUDENTS
$stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
$students = $stmt->fetchAll();

// 2. DETERMINE CURRENT STUDENT
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
} else {
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

// 3. GET OVERALL STATS
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_sessions, 
    SUM(duration_seconds) as total_time,
    AVG(score) as global_average
    FROM progress WHERE user_id = ?");
$stmt->execute([$student_id]);
$stats = $stmt->fetch();

$total_minutes = floor(($stats['total_time'] ? $stats['total_time'] : 0) / 60);

// 4. GET STATS PER GAME
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

// 5. GET RECENT HISTORY
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
    <title>Mission Report: <?php echo htmlspecialchars($current_student_name); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f4f6f7; color: #333; background-image: none; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
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

        /* Cards */
        .card { 
            background: white; padding: 20px; border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; color: #333;
            page-break-inside: avoid;
        }
        h2 { margin-top: 0; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; color: var(--space-blue); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #7f8c8d; font-size: 14px; }
        
        /* Progress Bars */
        .progress-bg { background: #eee; height: 10px; border-radius: 5px; width: 100px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 5px; }
        
        .back-btn { text-decoration: none; color: #3498db; font-weight: bold; }
        
        /* Chart Bars Container (Web View) */
        .chart-bar-container {
            display: flex; 
            align-items: stretch; 
            height: 140px; 
            gap: 10px; 
            margin-top: 20px;
            border-bottom: 1px solid #ccc; 
            padding-bottom: 5px;
        }
        
        .chart-bar {
            width: 40px; 
            border-radius: 5px 5px 0 0;
            position: relative; 
            transition: height 0.5s;
        }

        /* Badge Counter */
        .badge-count {
            position: absolute; bottom: -5px; right: -5px;
            background: var(--planet-red); color: white; border-radius: 50%;
            width: 24px; height: 24px; font-size: 12px; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); z-index: 10;
        }

        .print-header, .signature-section { display: none; }

        /* --- PRINT STYLES --- */
        @media print {
            @page { margin: 0.5cm; size: auto; }
            
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
                background: white; 
                font-family: 'Courier New', monospace; 
                font-size: 11pt;
            }

            .no-print, .back-btn, button, select, form, .logout-btn { display: none !important; }
            
            .container { 
                width: 98%; max-width: 98%; margin: 0 auto; padding: 10px;
                border: 3px double #2c3e50;
                border-radius: 8px;
            }

            .print-header {
                display: block; text-align: center; margin-bottom: 15px;
                border-bottom: 2px solid #2c3e50; padding-bottom: 5px;
            }
            .print-title { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
            .print-sub   { font-size: 12px; color: #555; }

            .card, .header-card { 
                border: 1px solid #aaa; box-shadow: none; 
                margin-bottom: 10px; padding: 10px; 
                break-inside: avoid; page-break-inside: avoid;
            }
            .header-card { background: #f9f9f9; }
            
            h1 { font-size: 18px; margin: 0; }
            h2 { font-size: 16px; margin: 0 0 5px 0; padding-bottom: 5px; }
            
            table th, table td { padding: 4px; font-size: 10pt; }
            
            /* Chart Adjustments for Print */
            .chart-bar-container { height: 100px; margin-top: 10px; }
            
            .signature-section {
                display: flex; justify-content: space-between;
                margin-top: 25px; padding-top: 10px;
                page-break-inside: avoid;
            }
            .sig-line {
                width: 40%; border-top: 2px solid #333;
                text-align: center; padding-top: 5px; font-weight: bold; font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="print-header">
        <div style="font-size:32px;">🚀</div>
        <div class="print-title">Official Mission Report</div>
        <div class="print-sub">Date: <?php echo date('F j, Y'); ?> | Cadet: <?php echo htmlspecialchars($current_student_name); ?></div>
    </div>

    <div class="no-print" style="margin-bottom: 15px; display:flex; justify-content:space-between; align-items:center;">
        <a href="index.php" class="back-btn">← Back to Kid's Dashboard</a>
        
        <div style="display:flex; gap: 10px;">
            <button onclick="window.print()" style="
                background: #3498db; color: white; border: none; padding: 10px 20px; 
                border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px;">
                🖨️ Print Certificate
            </button>

            <form method="GET" action="parent.php" style="margin:0;">
                <select name="student_id" class="student-select" onchange="this.form.submit()">
                    <?php foreach($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php if($s['id'] == $student_id) echo 'selected'; ?>>
                            👤 <?php echo htmlspecialchars($s['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="header-card">
        <div>
            <h1 style="margin:0; color: var(--space-blue);">Cadet Performance</h1>
            <p style="margin:5px 0 0; color:#7f8c8d;">Status: <span style="color:#27ae60; font-weight:bold;">ACTIVE</span></p>
            
            <button class="no-print" onclick="confirmReset(<?php echo $student_id; ?>, '<?php echo $current_student_name; ?>')" style="
                margin-top: 10px;
                background: var(--planet-red); color: white; border: none; padding: 8px 15px; 
                border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold;">
                🗑️ RESET THIS STUDENT
            </button>
        </div>
        <div style="display:flex; gap: 40px;">
            <div class="stat-box">
                <div class="stat-num"><?php echo $stats['total_sessions']; ?></div>
                <div class="stat-label">Missions</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo $total_minutes; ?>m</div>
                <div class="stat-label">Time</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo round($stats['global_average']); ?>%</div>
                <div class="stat-label">Avg</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Skill Mastery</h2>
        <table>
            <thead>
                <tr>
                    <th>Mission Type</th>
                    <th>Attempts</th>
                    <th>Best Score</th>
                    <th>Average</th>
                    <th>Speed</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($game_stats as $game): 
                    $avg = round($game['avg_score']);
                    // CLAMP VISUALS TO 100%
                    $display_val = ($avg > 100) ? 100 : $avg;
                    
                    $color = ($avg >= 80) ? '#2ecc71' : (($avg >= 50) ? '#f1c40f' : '#e74c3c');
                    $status = ($avg >= 80) ? 'Mastered' : (($avg >= 50) ? 'Improving' : 'Needs Help');
                ?>
                <tr>
                    <td style="font-weight:bold;"><?php echo htmlspecialchars($game['title']); ?></td>
                    <td><?php echo $game['times_played']; ?></td>
                    <td><?php echo $game['best_score']; ?>%</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <div class="progress-bg" style="width:60px;">
                                <div class="progress-fill" style="width:<?php echo $display_val; ?>%; background:<?php echo $color; ?>;"></div>
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

    <div class="card">
        <h2>Performance Overview</h2>
        <div class="chart-bar-container" style="justify-content: space-around;">
             <?php foreach($game_stats as $game): 
                $percentage = round($game['avg_score']);
                // CLAMP VISUALS TO 100%
                $display_val = ($percentage > 100) ? 100 : $percentage;
                
                $color = ($percentage >= 80) ? '#2ecc71' : (($percentage >= 50) ? '#f1c40f' : '#e74c3c');
             ?>
             <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; width: 100px;">
                 
                 <div style="flex-grow: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center;">
                    <div class="chart-bar" style="height: <?php echo $display_val; ?>%; background: <?php echo $color; ?>;"></div>
                 </div>

                 <div class="chart-label" style="margin-top:5px; font-weight:bold; font-size:10px; text-align:center; line-height:1.2;">
                    <?php echo htmlspecialchars($game['title']); ?>
                 </div>
                 <div style="font-size:10px; color:#777;"><?php echo $percentage; ?>%</div>
             </div>
             <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Recommendations</h2>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach($game_stats as $game): 
                $avg = round($game['avg_score']);
                $msg = ""; $icon = ""; $style = "";
                if ($avg >= 80) {
                    $icon = "🏆"; $msg = "<strong>" . htmlspecialchars($game['title']) . "</strong>: Mastered!";
                    $style = "color: #27ae60;";
                } elseif ($avg >= 50) {
                    $icon = "⭐"; $msg = "<strong>" . htmlspecialchars($game['title']) . "</strong>: Good progress.";
                    $style = "color: #f39c12;";
                } else {
                    $icon = "💡"; $msg = "<strong>" . htmlspecialchars($game['title']) . "</strong>: Needs practice.";
                    $style = "color: #c0392b;";
                }
            ?>
            <li style="padding: 5px 0; border-bottom: 1px solid #eee; display: flex; gap: 10px; align-items: center; font-size: 14px;">
                <span><?php echo $icon; ?></span>
                <span style="<?php echo $style; ?>"><?php echo $msg; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #ecf0f1; margin-bottom: 5px; padding-bottom: 5px;">
            <h2 style="border:none; margin:0; padding:0;">Mission Patches</h2>
            <button class="no-print" onclick="confirmResetBadges(<?php echo $student_id; ?>, '<?php echo $current_student_name; ?>')" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px;">Reset</button>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php
            $stmt = $pdo->prepare("
                SELECT b.name, b.icon, MAX(ub.earned_at) as last_earned, COUNT(ub.id) as cnt
                FROM user_badges ub 
                JOIN badges b ON ub.badge_id = b.id 
                WHERE ub.user_id = ?
                GROUP BY b.id
                ORDER BY last_earned DESC
            ");
            $stmt->execute([$student_id]);
            $earned_badges = $stmt->fetchAll();

            if (count($earned_badges) > 0) {
                foreach ($earned_badges as $badge) {
                    echo '<div style="text-align:center; width: 80px;">';
                    echo '<div style="position: relative; font-size: 30px; background: #f9f9f9; border-radius: 50%; width: 60px; height: 60px; display:flex; align-items:center; justify-content:center; margin: 0 auto; border: 2px solid var(--star-gold);">';
                    echo $badge['icon'];
                    if ($badge['cnt'] > 1) {
                        echo '<div class="badge-count" style="width:20px; height:20px; font-size:10px;">x' . $badge['cnt'] . '</div>';
                    }
                    echo '</div>';
                    echo '<div style="font-weight:bold; margin-top:2px; font-size:10px;">' . htmlspecialchars($badge['name']) . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="color:#7f8c8d; font-size:12px;">No patches yet.</p>';
            }
            ?>
        </div>
    </div>

    <div class="card">
        <h2>Recent Activity</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Game</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history as $row): ?>
                <tr>
                    <td><?php echo date("M j, g:i a", strtotime($row['played_at'])); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td style="<?php echo ($row['score'] >= 80) ? 'color:#27ae60; font-weight:bold;' : (($row['score'] >= 50) ? 'color:#f39c12; font-weight:bold;' : 'color:#c0392b; font-weight:bold;'); ?>">
                        <?php echo $row['score']; ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="signature-section">
        <div class="sig-line">Flight Director</div>
        <div class="sig-line">Cadet Signature</div>
    </div>

</div>

<script>
function confirmReset(studentId, studentName) {
    if (confirm("WARNING: Are you sure you want to reset stats for " + studentName + "?\n\nThis will delete their high scores and history permanently.")) {
        fetch('api/reset_stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: studentId }) 
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