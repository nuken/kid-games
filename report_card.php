<?php
// report_card.php
session_start();
require_once 'includes/db.php';

// Security Check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'parent' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php"); exit;
}

$student_id = $_GET['student_id'] ?? 0;

// Fetch Student Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) die("Student not found.");

// ---------------------------------------------------------
// 1. FETCH GAME-SPECIFIC STATS (Original Table Data)
// ---------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT g.default_title,
           g.subject, -- Added subject to the table fetch
           COUNT(p.id) as plays,
           AVG(p.mistakes) as avg_mistakes,
           AVG(p.duration_seconds) as avg_time
    FROM games g
    JOIN progress p ON g.id = p.game_id
    WHERE p.user_id = ?
    GROUP BY g.id
");
$stmt->execute([$student_id]);
$game_stats = $stmt->fetchAll();

// ---------------------------------------------------------
// 2. FETCH HISTORY (For Timeline Charts)
// ---------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT p.mistakes, p.duration_seconds, p.played_at, g.default_title
    FROM progress p
    JOIN games g ON p.game_id = g.id
    WHERE p.user_id = ?
    ORDER BY p.played_at ASC
    LIMIT 30
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

// Prepare Timeline Data
$labels = [];
$mistake_data = [];
$time_data = [];

foreach ($history as $h) {
    $labels[] = date('m/d', strtotime($h['played_at'])) . "\n" . substr($h['default_title'], 0, 8);
    $mistake_data[] = $h['mistakes'];
    $time_data[] = $h['duration_seconds'];
}

// ---------------------------------------------------------
// 3. FETCH SUBJECT PERFORMANCE (For Radar Chart & Insights)
// ---------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT 
        g.subject,
        COUNT(p.id) as sessions,
        AVG(p.score) as avg_score,
        AVG(p.mistakes) as avg_mistakes
    FROM progress p
    JOIN games g ON p.game_id = g.id
    WHERE p.user_id = ?
    GROUP BY g.subject
");
$stmt->execute([$student_id]);
$subject_data = $stmt->fetchAll();

// Process Data for Radar Chart
$radar_labels = [];
$radar_values = [];
$weakest_subject = null;
$strongest_subject = null;
$lowest_score = 100;
$highest_score = -1;

foreach ($subject_data as $row) {
    // Only include if subject is valid (not empty)
    $subj = $row['subject'] ? $row['subject'] : 'General';
    
    // Calculate "Mastery Score" (0 to 100)
    // Score (0-100) minus heavy penalty for mistakes
    // Logic: If avg score is 100 but avg mistakes is 2, mastery drops to 90.
    $mastery = max(0, $row['avg_score'] - ($row['avg_mistakes'] * 5));
    
    $radar_labels[] = $subj;
    $radar_values[] = round($mastery);

    // Determine Strengths/Weaknesses
    if ($mastery < $lowest_score) { 
        $lowest_score = $mastery; 
        $weakest_subject = $subj; 
    }
    if ($mastery > $highest_score) { 
        $highest_score = $mastery; 
        $strongest_subject = $subj; 
    }
}

// Fallback if no data
if (empty($radar_labels)) {
    $radar_labels = ['Math', 'Reading', 'Logic', 'Creativity'];
    $radar_values = [0, 0, 0, 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics: <?php echo htmlspecialchars($student['username']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f6f8; color: #333; font-family: 'Segoe UI', sans-serif; }
        .report-wrap { max-width: 1000px; margin: 30px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }

        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #2c3e50; font-size: 2.5em; }
        .header p { color: #7f8c8d; font-size: 1.1em; }

        /* Grid Layouts */
        .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 40px; }
        
        .card-box { 
            background: #fff; 
            border: 1px solid #e0e0e0; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .highlight-card {
            background: #f8f9fa;
            border-left: 5px solid #3498db;
            display: flex; justify-content: space-around; align-items: center;
            padding: 25px;
        }

        /* Typography */
        h3 { margin-top: 0; color: #34495e; text-transform: uppercase; font-size: 0.9em; letter-spacing: 1px; }
        .big-stat { font-size: 1.4em; font-weight: bold; margin-top: 5px; }
        .stat-green { color: #27ae60; }
        .stat-red { color: #e74c3c; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f2f6; padding: 12px; text-align: left; color: #555; font-size: 0.9em; }
        td { border-bottom: 1px solid #eee; padding: 12px; font-size: 0.95em; }

        .mastery-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .mastery-high { background: #d4edda; color: #155724; }
        .mastery-med  { background: #fff3cd; color: #856404; }
        .mastery-low  { background: #f8d7da; color: #721c24; }

        /* Recommendation Box */
        .teacher-note {
            background: #e8f4fd; 
            border: 2px solid #b6d4fe; 
            border-radius: 10px; 
            padding: 20px; 
            margin-top: 30px;
        }

        @media print {
            .btn-print, .btn-back { display: none; }
            body { background: white; }
            .report-wrap { box-shadow: none; margin: 0; width: 100%; max-width: 100%; }
            .analytics-grid { grid-template-columns: 1fr 1fr; } 
        }
        @media (max-width: 768px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="report-wrap">
    <div class="header">
        <h1>Student Analytics</h1>
        <p>Report for: <strong><?php echo htmlspecialchars($student['username']); ?></strong></p>
    </div>

    <div class="card-box highlight-card" style="margin-bottom: 30px;">
        <div style="text-align: center;">
            <div style="font-size: 30px;">🏆</div>
            <h3>Superpower</h3>
            <div class="big-stat stat-green">
                <?php echo $strongest_subject ? htmlspecialchars($strongest_subject) : 'None yet'; ?>
            </div>
        </div>
        <div style="text-align: center; border-left: 1px solid #ddd; padding-left: 40px;">
            <div style="font-size: 30px;">🎯</div>
            <h3>Needs Focus</h3>
            <div class="big-stat stat-red">
                <?php echo $weakest_subject ? htmlspecialchars($weakest_subject) : 'Keep Playing!'; ?>
            </div>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="card-box">
            <h3>Skill Balance</h3>
            <canvas id="skillRadar"></canvas>
            <p style="font-size: 0.8em; color: #777; text-align: center; margin-top: 10px;">
                Shows mastery (0-100) across different subjects.
            </p>
        </div>

        <div class="card-box">
            <h3>Recent Performance</h3>
            <canvas id="trendChart"></canvas>
            <p style="font-size: 0.8em; color: #777; text-align: center; margin-top: 10px;">
                Blue line: Speed (sec) | Red bars: Mistakes
            </p>
        </div>
    </div>

    <div class="teacher-note">
        <h3 style="margin-bottom: 10px; color: #0d6efd;">💡 Recommendation</h3>
        <?php
        if (!$weakest_subject) {
            echo "Play more games to unlock recommendations!";
        } elseif ($weakest_subject === 'Math') {
            echo "<strong>Focus on Math:</strong> Try playing <em>Egg-dition</em> or <em>Balloon Pop</em> to improve number confidence.";
        } elseif ($weakest_subject === 'Reading') {
            echo "<strong>Focus on Reading:</strong> Daily practice with <em>Cosmic Signal</em> or <em>Spell It</em> will help word recognition.";
        } elseif ($weakest_subject === 'Logic') {
            echo "<strong>Focus on Logic:</strong> <em>Pattern Train</em> and <em>Red Light</em> are excellent for building sequencing skills.";
        } elseif ($weakest_subject === 'Creativity') {
            echo "<strong>Focus on Creativity:</strong> Relax with <em>Coloring Book</em> or <em>Color Lab</em> to practice recognition.";
        } else {
            echo "Great job! Keep playing a variety of games to maintain a balanced skill set.";
        }
        ?>
    </div>

    <h3 style="margin-top: 40px;">Detailed Game History</h3>
    <table>
        <thead>
            <tr>
                <th>Game</th>
                <th>Subject</th>
                <th>Plays</th>
                <th>Avg Mistakes</th>
                <th>Avg Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($game_stats as $g):
                $m = $g['avg_mistakes'];
                if ($m <= 1) {
                    $status = "Mastered"; $cls = "mastery-high";
                } elseif ($m <= 4) {
                    $status = "Improving"; $cls = "mastery-med";
                } else {
                    $status = "Needs Help"; $cls = "mastery-low";
                }
                
                // Handle subject display (if null, show General)
                $subjDisplay = $g['subject'] ?? 'General';
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($g['default_title']); ?></strong></td>
                <td><span style="color:#777; font-size:0.9em;"><?php echo htmlspecialchars($subjDisplay); ?></span></td>
                <td><?php echo $g['plays']; ?></td>
                <td><?php echo number_format($g['avg_mistakes'], 1); ?></td>
                <td><?php echo round($g['avg_time']); ?>s</td>
                <td><span class="mastery-badge <?php echo $cls; ?>"><?php echo $status; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 40px;">
        <button class="btn-print" onclick="window.print()" style="padding: 12px 25px; cursor: pointer; background: #2c3e50; color: white; border: none; border-radius: 8px; font-weight: bold;">Print Report</button>
        <a href="parent.php" class="btn-back" style="padding: 12px 25px; text-decoration: none; color: #555; margin-left: 10px; font-weight: bold;">Back to Dashboard</a>
    </div>
</div>

<script>
    // --- 1. SKILL RADAR CHART ---
    const radarCtx = document.getElementById('skillRadar').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: <?php echo json_encode($radar_labels); ?>,
            datasets: [{
                label: 'Mastery Score (0-100)',
                data: <?php echo json_encode($radar_values); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: '#3498db',
                pointBackgroundColor: '#2c3e50',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    angleLines: { display: true },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { backdropColor: 'transparent', stepSize: 20 }
                }
            }
        }
    });

    // --- 2. TREND CHART (Mixed: Line for Time, Bar for Mistakes) ---
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    type: 'line',
                    label: 'Speed (Seconds)',
                    data: <?php echo json_encode($time_data); ?>,
                    borderColor: '#3498db',
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    type: 'bar',
                    label: 'Mistakes',
                    data: <?php echo json_encode($mistake_data); ?>,
                    backgroundColor: 'rgba(231, 76, 60, 0.6)',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Seconds' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false }, // only want the grid lines for one axis to show up
                    title: { display: true, text: 'Mistakes' },
                    suggestedMax: 10
                }
            }
        }
    });
</script>

</body>
</html>