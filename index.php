<?php
// index.php
session_start();
require_once 'includes/db.php';

// 1. Security Check: If not logged in, go back to login screen
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 2. Get High Scores for "Robo-Sorter" (Game ID 1)
// We want his BEST score to determine how many stars to show
$stmt = $pdo->prepare("SELECT MAX(score) as high_score FROM progress WHERE user_id = ? AND game_id = 1");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
$robo_score = $result['high_score'] ? $result['high_score'] : 0;

// Calculate Stars (Logic: 0=No stars, <50=1 star, <90=2 stars, >90=3 stars)
$robo_stars = 0;
if ($robo_score > 0) $robo_stars = 1;
if ($robo_score >= 50) $robo_stars = 2;
if ($robo_score >= 90) $robo_stars = 3;

$stmt = $pdo->prepare("SELECT MAX(score) as high_score FROM progress WHERE user_id = ? AND game_id = 2");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
$shop_score = $result['high_score'] ? $result['high_score'] : 0;

$shop_stars = 0;
if ($shop_score > 0) $shop_stars = 1;
if ($shop_score >= 50) $shop_stars = 2;
if ($shop_score >= 90) $shop_stars = 3;

$stmt = $pdo->prepare("SELECT MAX(score) as high_score FROM progress WHERE user_id = ? AND game_id = 3");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
$time_score = $result['high_score'] ? $result['high_score'] : 0;
$time_stars = 0;
if ($time_score > 0) $time_stars = 1;
if ($time_score >= 50) $time_stars = 2;
if ($time_score >= 90) $time_stars = 3;

$stmt = $pdo->prepare("SELECT MAX(score) as high_score FROM progress WHERE user_id = ? AND game_id = 4");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
$signal_score = $result['high_score'] ? $result['high_score'] : 0;
$signal_stars = 0;
if ($signal_score > 0) $signal_stars = 1;
if ($signal_score >= 50) $signal_stars = 2;
if ($signal_score >= 90) $signal_stars = 3;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Control</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">
                High Score: <?php echo $robo_score; ?>
            </div>
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
            <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">
                High Score: <?php echo $shop_score; ?>
            </div>
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
        <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">
            High Score: <?php echo $time_score; ?>
        </div>
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
        <div style="margin-top:5px; font-weight:bold; color:var(--nebula-green);">
            High Score: <?php echo $signal_score; ?>
        </div>
    <?php endif; ?>
</a>
    
</div>

<!-- BADGE SECTION -->
<div style="max-width: 800px; margin: 40px auto; text-align: center;">
    <h2 style="color: var(--star-gold); text-transform: uppercase; letter-spacing: 2px;">Mission Patches</h2>
    <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <?php
        try {
            // Fetch all badges
            $stmt = $pdo->query("SELECT * FROM badges");
            $all_badges = $stmt->fetchAll();

            if (empty($all_badges)) {
                echo "<p style='color: #888;'>Mission Control is setting up badges... (Run setup_badges.php)</p>";
            } else {
                // Fetch user's earned badges
                $stmt = $pdo->prepare("SELECT badge_id FROM user_badges WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $earned_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($all_badges as $badge):
                    $is_earned = in_array($badge['id'], $earned_ids);
                    $opacity = $is_earned ? '1' : '0.3';
                    $filter = $is_earned ? 'none' : 'grayscale(100%)';
                    $title = $is_earned ? $badge['name'] . " - " . $badge['description'] : "Locked: " . $badge['description'];
                ?>
                    <div title="<?php echo htmlspecialchars($title); ?>" style="
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
                    </div>
                <?php endforeach; 
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Comms Error: Badges not found. Please run setup_badges.php</p>";
        }
        ?>
    </div>
</div>

<script src="assets/js/speech-module.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Get the username safely from PHP
            const username = "<?php echo htmlspecialchars($username); ?>";
            
            // 2. Create the message
            const message = "Welcome, Captain " + username;

            // 3. specific delay to allow voices to load
            setTimeout(() => {
                if (window.speakText) {
                    window.speakText(message);
                }
            }, 1000); // 1 second delay ensures the browser is ready
        });
    </script>
</body>
</html>