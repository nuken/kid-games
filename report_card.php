<?php
// report_card.php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'parent' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php"); exit;
}

$student_id = $_GET['student_id'] ?? 0;
// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) die("Student not found.");

// 1. Fetch Deep Stats (Avg Mistakes & Duration)
// We group by Game to get subject-specific data
$stmt = $pdo->prepare("
    SELECT g.default_title,
           COUNT(p.id) as plays,
           AVG(p.mistakes) as avg_mistakes,
           AVG(p.duration_seconds) as avg_time,
           MIN(p.mistakes) as best_mistakes,
           MIN(p.duration_seconds) as best_time
    FROM games g
    JOIN progress p ON g.id = p.game_id
    WHERE p.user_id = ?
    GROUP BY g.id
");
$stmt->execute([$student_id]);
$game_stats = $stmt->fetchAll();

// 2. History for Charts
// We fetch the last 30 sessions to show trends over time
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

// Prepare Data for Charts
$labels = [];
$mistake_data = [];
$time_data = [];

foreach ($history as $h) {
    // Label format: Date + Game Name
    $labels[] = date('m/d', strtotime($h['played_at'])) . "\n" . substr($h['default_title'], 0, 8);
    $mistake_data[] = $h['mistakes'];
    $time_data[] = $h['duration_seconds'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Report: <?php echo htmlspecialchars($student['username']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f6f8; color: #333; font-family: 'Segoe UI', sans-serif; }
        .report-wrap { max-width: 900px; margin: 30px auto; background: white; padding: 40px; border-radius: 10px; }

        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #2c3e50; }

        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .chart-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 10px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #eee; padding: 10px; text-align: left; }
        td { border-bottom: 1px solid #eee; padding: 10px; }

        .mastery-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; }
        .mastery-high { background: #d4edda; color: #155724; } /* Low mistakes */
        .mastery-med  { background: #fff3cd; color: #856404; }
        .mastery-low  { background: #f8d7da; color: #721c24; }

        @media print {
            .btn-print, .btn-back { display: none; }
            body { background: white; }
            .report-wrap { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

<div class="report-wrap">
    <div class="header">
        <h1>Progress Report</h1>
        <p>Student: <strong><?php echo htmlspecialchars($student['username']); ?></strong></p>
    </div>

    <div class="stat-grid">
        <div class="chart-box">
            <h3 style="text-align:center; color:#e74c3c;">Accuracy (Mistakes)</h3>
            <p style="text-align:center; font-size:0.8em;">Lower is better</p>
            <canvas id="mistakeChart"></canvas>
        </div>
        <div class="chart-box">
            <h3 style="text-align:center; color:#3498db;">Speed (Seconds)</h3>
            <p style="text-align:center; font-size:0.8em;">Lower is better</p>
            <canvas id="timeChart"></canvas>
        </div>
    </div>

    <h3>Mastery Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Game</th>
                <th>Avg Mistakes</th>
                <th>Avg Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($game_stats as $g):
                // Logic to determine Mastery Status based on mistakes
                $m = $g['avg_mistakes'];
                if ($m <= 1) {
                    $status = "Mastered"; $cls = "mastery-high";
                } elseif ($m <= 4) {
                    $status = "Improving"; $cls = "mastery-med";
                } else {
                    $status = "Needs Help"; $cls = "mastery-low";
                }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($g['default_title']); ?></td>
                <td><?php echo number_format($g['avg_mistakes'], 1); ?></td>
                <td><?php echo round($g['avg_time']); ?>s</td>
                <td><span class="mastery-badge <?php echo $cls; ?>"><?php echo $status; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 40px;">
        <button class="btn-print" onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #2c3e50; color: white; border: none; border-radius: 5px;">Print Report</button>
        <a href="parent.php" class="btn-back" style="padding: 10px 20px; text-decoration: none; color: #555;">Back</a>
    </div>
</div>

<script>
    // 1. MISTAKE CHART
    new Chart(document.getElementById('mistakeChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Mistakes',
                data: <?php echo json_encode($mistake_data); ?>,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                tension: 0.3, fill: true
            }]
        }
    });

    // 2. TIME CHART
    new Chart(document.getElementById('timeChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Duration (Seconds)',
                data: <?php echo json_encode($time_data); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.3, fill: true
            }]
        }
    });
</script>

</body>
</html>
