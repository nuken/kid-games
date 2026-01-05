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

// 1. STATS (Table)
$stmt = $pdo->prepare("
    SELECT g.default_title, g.subject, COUNT(p.id) as plays, AVG(p.mistakes) as avg_mistakes, AVG(p.duration_seconds) as avg_time
    FROM games g JOIN progress p ON g.id = p.game_id
    WHERE p.user_id = ? GROUP BY g.id
");
$stmt->execute([$student_id]);
$game_stats = $stmt->fetchAll();

// 2. HISTORY (Timeline)
$stmt = $pdo->prepare("
    SELECT p.mistakes, p.duration_seconds, p.played_at, g.default_title
    FROM progress p JOIN games g ON p.game_id = g.id
    WHERE p.user_id = ? ORDER BY p.played_at ASC LIMIT 30
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

$labels = []; $mistake_data = []; $time_data = [];
foreach ($history as $h) {
    $labels[] = date('m/d', strtotime($h['played_at'])) . "\n" . substr($h['default_title'], 0, 8);
    $mistake_data[] = $h['mistakes'];
    $time_data[] = $h['duration_seconds'];
}

// 3. RADAR DATA
$stmt = $pdo->prepare("
    SELECT g.subject, COUNT(p.id) as sessions, AVG(p.score) as avg_score, AVG(p.mistakes) as avg_mistakes
    FROM progress p JOIN games g ON p.game_id = g.id
    WHERE p.user_id = ? GROUP BY g.subject
");
$stmt->execute([$student_id]);
$subject_data = $stmt->fetchAll();

$radar_labels = []; $radar_values = [];
$weakest_subject = null; $strongest_subject = null;
$lowest_score = 100; $highest_score = -1;

foreach ($subject_data as $row) {
    $subj = $row['subject'] ?: 'General';
    $mastery = max(0, $row['avg_score'] - ($row['avg_mistakes'] * 5));
    $radar_labels[] = $subj;
    $radar_values[] = round($mastery);
    if ($mastery < $lowest_score) { $lowest_score = $mastery; $weakest_subject = $subj; }
    if ($mastery > $highest_score) { $highest_score = $mastery; $strongest_subject = $subj; }
}

if (empty($radar_labels)) {
    $radar_labels = ['Math', 'Reading', 'Logic', 'Creativity'];
    $radar_values = [0, 0, 0, 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card: <?php echo htmlspecialchars($student['username']); ?></title>
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/parent.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="background-color: #f4f6f8;">

<div class="report-wrap">
    <div class="top-bar" style="border-bottom: 2px solid #eee; padding-bottom: 20px;">
        <div>
            <h1>Student Analytics</h1>
            <span style="color:#777;">Report for: <strong><?php echo htmlspecialchars($student['username']); ?></strong></span>
        </div>
        <a href="parent.php" class="back-link">Back to Dashboard</a>
    </div>

    <div class="highlight-card" style="margin-bottom: 30px;">
        <div style="text-align: center;">
            <div style="font-size: 30px;">🏆</div>
            <h3 style="margin: 10px 0;">Superpower</h3>
            <div class="big-stat stat-green">
                <?php echo $strongest_subject ? htmlspecialchars($strongest_subject) : 'None yet'; ?>
            </div>
        </div>
        <div style="text-align: center; border-left: 1px solid #ddd; padding-left: 40px;">
            <div style="font-size: 30px;">🎯</div>
            <h3 style="margin: 10px 0;">Needs Focus</h3>
            <div class="big-stat stat-red">
                <?php echo $weakest_subject ? htmlspecialchars($weakest_subject) : 'Keep Playing!'; ?>
            </div>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="card">
            <h2>Skill Balance</h2>
            <canvas id="skillRadar"></canvas>
            <p style="font-size: 0.8em; color: #777; text-align: center; margin-top: 10px;">Shows mastery (0-100) across subjects.</p>
        </div>
        <div class="card">
            <h2>Recent History</h2>
            <canvas id="trendChart"></canvas>
            <p style="font-size: 0.8em; color: #777; text-align: center; margin-top: 10px;">Blue: Speed | Red: Mistakes</p>
        </div>
    </div>

    <div class="card" style="background: #e8f4fd; border: 2px solid #b6d4fe;">
        <h2 style="color: #0d6efd; border-bottom: none;">💡 Recommendation</h2>
        <?php
        if (!$weakest_subject) echo "Play more games to unlock recommendations!";
        elseif ($weakest_subject === 'Math') echo "<strong>Focus on Math:</strong> Try <em>Egg-dition</em> to improve number confidence.";
        elseif ($weakest_subject === 'Reading') echo "<strong>Focus on Reading:</strong> Daily practice with <em>Cosmic Signal</em> helps word recognition.";
        elseif ($weakest_subject === 'Logic') echo "<strong>Focus on Logic:</strong> <em>Pattern Train</em> is great for sequencing.";
        else echo "Great job! Keep playing a variety of games to maintain a balanced skill set.";
        ?>
    </div>

    <div class="card">
        <h2>Detailed Game Stats</h2>
        <table>
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Subject</th>
                    <th>Plays</th>
                    <th>Mistakes (Avg)</th>
                    <th>Time (Avg)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($game_stats as $g): 
                    $m = $g['avg_mistakes'];
                    $status = ($m <= 1) ? "Mastered" : (($m <= 4) ? "Improving" : "Needs Help");
                    $cls = ($m <= 1) ? "mastery-high" : (($m <= 4) ? "mastery-med" : "mastery-low");
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($g['default_title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($g['subject'] ?? 'General'); ?></td>
                    <td><?php echo $g['plays']; ?></td>
                    <td><?php echo number_format($g['avg_mistakes'], 1); ?></td>
                    <td><?php echo round($g['avg_time']); ?>s</td>
                    <td><span class="mastery-badge <?php echo $cls; ?>"><?php echo $status; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <button class="btn-print" onclick="window.print()">Print Report</button>
    </div>
</div>

<script>
    const radarCtx = document.getElementById('skillRadar').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: <?php echo json_encode($radar_labels); ?>,
            datasets: [{
                label: 'Mastery',
                data: <?php echo json_encode($radar_values); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: '#3498db',
                pointBackgroundColor: '#2c3e50',
            }]
        },
        options: { scales: { r: { suggestedMin: 0, suggestedMax: 100 } } }
    });

    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                { type: 'line', label: 'Speed (s)', data: <?php echo json_encode($time_data); ?>, borderColor: '#3498db', tension: 0.3, yAxisID: 'y' },
                { type: 'bar', label: 'Mistakes', data: <?php echo json_encode($mistake_data); ?>, backgroundColor: 'rgba(231, 76, 60, 0.6)', yAxisID: 'y1' }
            ]
        },
        options: {
            scales: {
                y: { type: 'linear', position: 'left', title: { display: true, text: 'Seconds' } },
                y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, suggestedMax: 10 }
            }
        }
    });
</script>
</body>
</html>