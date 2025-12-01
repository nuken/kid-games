<?php
// index.php
session_start();
require_once 'includes/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 2. Get High Scores (Logic remains the same for stars)
function getScore($pdo, $uid, $gid) {
    $stmt = $pdo->prepare("SELECT MAX(score) as high_score FROM progress WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$uid, $gid]);
    $res = $stmt->fetch();
    return $res['high_score'] ? $res['high_score'] : 0;
}

function getStars($score) {
    if ($score >= 90) return 3;
    if ($score >= 50) return 2;
    if ($score > 0) return 1;
    return 0;
}

$robo_score = getScore($pdo, $user_id, 1);
$robo_stars = getStars($robo_score);

$shop_score = getScore($pdo, $user_id, 2);
$shop_stars = getStars($shop_score);

$time_score = getScore($pdo, $user_id, 3);
$time_stars = getStars($time_score);

$signal_score = getScore($pdo, $user_id, 4);
$signal_stars = getStars($signal_score);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Control</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* New Style for Stacking Badges */
        .badge-count {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: var(--planet-red);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 10;
        }
    </style>
</head>
<body>

<header>
    <div class="user-profile">
        👨‍🚀 Captain <?php echo htmlspecialchars($username); ?>
    </div>
    <a href="logout.php" class="logout-btn">Log Out</a>
</header>
 
<div class="mission-grid">
    <a href="games/robo-sorter/index.php" class="mission-card">
        <div class="mission-icon">🤖</div>
        <div class="mission-title">Robo-Sorter</div>
        <div class="mission-desc">Odd vs Even Numbers</div>
        <div class="stars active-<?php echo $robo_stars; ?>">
            <span>★</span><span>★</span><span>★</span>
        </div>
        <?php if($robo_score > 0): ?>
            <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">High Score: <?php echo $robo_score; ?></div>
        <?php endif; ?>
    </a>

   <a href="games/rocket-shop/index.php" class="mission-card">
        <div class="mission-icon">🚀</div>
        <div class="mission-title">Rocket Shop</div>
        <div class="mission-desc">Counting Money</div>
        <div class="stars active-<?php echo $shop_stars; ?>">
            <span>★</span><span>★</span><span>★</span>
        </div>
        <?php if($shop_score > 0): ?>
            <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">High Score: <?php echo $shop_score; ?></div>
        <?php endif; ?>
    </a>
    
    <a href="games/launch-time/index.php" class="mission-card">
      <div class="mission-icon">⏰</div>
      <div class="mission-title">Launch Time</div>
      <div class="mission-desc">Master the Clock</div>
      <div class="stars active-<?php echo $time_stars; ?>">
        <span>★</span><span>★</span><span>★</span>
      </div>
      <?php if($time_score > 0): ?>
        <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">High Score: <?php echo $time_score; ?></div>
      <?php endif; ?>
    </a>

    <a href="games/cosmic-signal/index.php" class="mission-card">
      <div class="mission-icon">📡</div>
      <div class="mission-title">Cosmic Signal</div>
      <div class="mission-desc">Decode Messages</div>
      <div class="stars active-<?php echo $signal_stars; ?>">
        <span>★</span><span>★</span><span>★</span>
      </div>
      <?php if($signal_score > 0): ?>
        <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">High Score: <?php echo $signal_score; ?></div>
      <?php endif; ?>
    </a>
</div>

<div style="max-width: 800px; margin: 40px auto; text-align: center;">
    <h2 style="color: var(--star-gold); text-transform: uppercase; letter-spacing: 2px;">Mission Patches</h2>
    <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <?php
        try {
            // Fetch all badges definition
            $stmt = $pdo->query("SELECT * FROM badges");
            $all_badges = $stmt->fetchAll();

            if (empty($all_badges)) {
                echo "<p style='color: #888;'>Mission Control is setting up badges...</p>";
            } else {
                // Fetch COUNTS of user's earned badges
                $stmt = $pdo->prepare("SELECT badge_id, COUNT(*) as cnt FROM user_badges WHERE user_id = ? GROUP BY badge_id");
                $stmt->execute([$user_id]);
                // Create an array where Key = badge_id, Value = count
                $earned_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                foreach ($all_badges as $badge):
                    $count = isset($earned_counts[$badge['id']]) ? $earned_counts[$badge['id']] : 0;
                    $is_earned = $count > 0;
                    
                    $opacity = $is_earned ? '1' : '0.3';
                    $filter = $is_earned ? 'none' : 'grayscale(100%)';
                    $title = $is_earned ? $badge['name'] . " - " . $badge['description'] : "Locked: " . $badge['description'];
                ?>
                    <div title="<?php echo htmlspecialchars($title); ?>" style="
                        position: relative;
                        background: rgba(255,255,255,0.1); 
                        border: 2px solid <?php echo $is_earned ? 'var(--star-gold)' : '#555'; ?>;
                        border-radius: 50%; 
                        width: 80px; height: 80px; 
                        display: flex; align-items: center; justify-content: center;
                        font-size: 40px; 
                        opacity: <?php echo $opacity; ?>;
                        filter: <?php echo $filter; ?>;
                        cursor: help;
                        transition: transform 0.2s;
                    ">
                        <?php echo $badge['icon']; ?>
                        
                        <?php if($count > 1): ?>
                            <div class="badge-count">x<?php echo $count; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; 
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Comms Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</div>

<script src="assets/js/speech-module.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const username = "<?php echo htmlspecialchars($username); ?>";
        setTimeout(() => {
            if (window.speakText) window.speakText("Welcome, Captain " + username);
        }, 1000);
    });
</script>
</body>
</html>