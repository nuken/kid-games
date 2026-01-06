<?php
// install.php
// Setup script with Auto-Destruct and Pre-Flight Checks

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";
$is_locked = false;

// 1. Check for Config File
if (!file_exists('includes/config.php')) {
    die("<div style='color:red; font-family:sans-serif; padding:20px; text-align:center;'>
            <h2>Configuration Missing</h2>
            <strong>Error:</strong> 'includes/config.php' not found.<br><br>
            Please rename <code>includes/config.sample.php</code> to <code>config.php</code><br>
            and edit it with your database credentials before running this installer.
         </div>");
}

require_once 'includes/config.php';

// 2. PRE-FLIGHT CHECK: Is the system already installed?
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    $check_pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Check if critical 'users' table exists
    $stmt = $check_pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $is_locked = true;
        $message = "<div class='alert error'>
                        <strong>⚠️ System Already Installed</strong><br><br>
                        The database tables already exist.<br>
                        For security reasons, this installer has been disabled.<br><br>
                        <strong>PLEASE DELETE 'install.php' MANUALLY.</strong><br><br>
                        <a href='index.php' style='color:white; text-decoration:underline;'>Go to Home</a>
                    </div>";
    }
} catch (Exception $e) {
    // Connection failed or DB doesn't exist yet. 
    // This is fine, we will let the installation logic handle the error details if the user proceeds.
}

// 3. Handle Installation
if (!$is_locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $admin_user = trim($_POST['username']);
    $admin_pin  = trim($_POST['pin']);
    $secret_pin = trim($_POST['admin_pin']);

    if (empty($admin_user) || empty($admin_pin) || empty($secret_pin)) {
        $message = "<div class='alert error'>All fields are required.</div>";
    } else {
        try {
            // Connect
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // ---------------------------------------------------------
            // A. CREATE TABLES
            // ---------------------------------------------------------
            $sql = "
            SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

            CREATE TABLE IF NOT EXISTS `badges` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `description` text DEFAULT NULL,
              `icon` varchar(10) NOT NULL,
              `criteria_game_id` int(11) DEFAULT NULL,
              `criteria_score` int(11) DEFAULT 0,
              `slug` varchar(50) DEFAULT NULL UNIQUE,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `games` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `default_title` varchar(100) NOT NULL,
              `folder_path` varchar(255) NOT NULL,
              `default_icon` varchar(10) DEFAULT '?',
              `min_grade` int(11) DEFAULT 0,
              `max_grade` int(11) DEFAULT 5,
              `active` tinyint(1) DEFAULT 1,
              `subject` varchar(50) NOT NULL DEFAULT 'General',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `game_theme_overrides` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `game_id` int(11) NOT NULL,
              `theme_id` int(11) NOT NULL,
              `display_name` varchar(100) DEFAULT NULL,
              `display_icon` varchar(10) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_override` (`game_id`,`theme_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `progress` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `game_id` int(11) NOT NULL,
              `score` int(11) DEFAULT 0,
              `duration_seconds` int(11) DEFAULT 0,
              `played_at` datetime DEFAULT current_timestamp(),
              `mistakes` int(11) DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL,
              `value` varchar(255) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `themes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL,
              `css_file` varchar(255) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `pin_code` varchar(10) NOT NULL,
              `role` enum('student','parent','admin') DEFAULT 'student',
              `parent_id` int(11) DEFAULT NULL,
              `grade_level` int(11) DEFAULT 0,
              `theme_id` int(11) DEFAULT 1,
              `avatar` varchar(255) DEFAULT '👤',
              `confetti_enabled` tinyint(1) DEFAULT 1,
              `admin_pin` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `parent_id` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `user_badges` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `badge_id` int(11) NOT NULL,
              `earned_at` datetime DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `user_favorites` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `game_id` int(11) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `user_game_unique` (`user_id`,`game_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `user_tokens` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `selector` varchar(255) NOT NULL,
              `hashed_validator` varchar(255) NOT NULL,
              `user_id` int(11) NOT NULL,
              `expiry` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `selector_idx` (`selector`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";

            $pdo->exec($sql);

            // ---------------------------------------------------------
            // B. INSERT DEFAULT DATA
            // ---------------------------------------------------------
            
            // 1. Themes
            $pdo->exec("TRUNCATE TABLE themes");
            $pdo->exec("INSERT INTO `themes` (`id`, `name`, `css_file`) VALUES
                (1, 'Default', 'default.css'),
                (2, 'Space Commander', 'space.css'),
                (3, 'Fairy Tale', 'princess.css');");

            // 2. Games
            $pdo->exec("TRUNCATE TABLE games");
            $sql_games = "INSERT INTO `games` (`id`, `default_title`, `folder_path`, `default_icon`, `min_grade`, `max_grade`, `active`, `subject`) VALUES
                (1, 'Robo-Sorter', 'games/robo-sorter', '🤖', 2, 3, 1, 'Math'),
                (2, 'Rocket Shop', 'games/rocket-shop', '🚀', 2, 3, 1, 'Math'),
                (3, 'Launch Time', 'games/launch-time', '⏰', 2, 3, 1, 'General'),
                (4, 'Cosmic Signal', 'games/cosmic-signal', '📡', 0, 2, 1, 'Reading'),
                (7, 'Spell It!', 'games/spell-it', '🔤', 0, 2, 1, 'Reading'),
                (8, 'Read & Match', 'games/read-match', '📖', 1, 3, 1, 'Reading'),
                (9, 'Spider Web', 'games/spider-web', '🕷️', 0, 2, 1, 'Logic'),
                (10, 'Color Lab', 'games/color-mix', '🧪', 0, 3, 1, 'Creativity'),
                (11, 'Fiesta Piñata', 'games/fiesta-pinata', '🪅', 0, 3, 1, 'Creativity'),
                (12, 'Egg-dition', 'games/egg-dition', '🥚', 0, 3, 1, 'Math'),
                (13, 'Balloon Pop', 'games/balloon-pop', '🎈', 0, 3, 1, 'Math'),
                (14, 'Wild World', 'games/wild-world', '🦁', 0, 3, 1, 'Reading'),
                (15, 'Lava Bridge', 'games/lava-bridge', '🌋', 0, 3, 1, 'Math'),
                (16, 'Traffic Control', 'games/red-light', '🚦', 0, 3, 1, 'Logic'),
                (17, 'Robot Commander', 'games/simon-says', '🤖', 0, 3, 1, 'Logic'),
                (18, 'Pattern Train', 'games/pattern-train', '🚂', 0, 2, 1, 'Logic'),
                (19, 'Alphabet Fun', 'games/alphabet', '🎡', 0, 1, 1, 'Reading'),
                (20, 'Coloring Book', 'games/coloring', '🎨', 0, 6, 1, 'Creativity'),
                (21, 'Number Tracing', 'games/number-tracing', '✏️', 0, 1, 1, 'General'),
                (22, 'Shape Detective', 'games/shape-detective', '🕵️', 0, 3, 1, 'General'),
                (23, 'The Cat and Rat', 'games/cat-rat-reader', '🐱', 0, 1, 1, 'General'),
                (24, 'Sight Word Adventures', 'games/sight-word-reader', '🦜', 1, 2, 1, 'Reading');";
            $pdo->exec($sql_games);

            // 3. Badges
            // 3. Badges
            $pdo->exec("TRUNCATE TABLE badges");
            // FIXED: Added `slug` to the column list and added NULL to all rows that don't have a slug.
            $sql_badges = "INSERT INTO `badges` (`id`, `name`, `description`, `icon`, `criteria_game_id`, `criteria_score`, `slug`) VALUES
                (1, 'First Sparkle', 'Played your first game!', '✨', NULL, 0, NULL),
                (2, 'Sorting Master', 'Scored 100% in Robo-Sorter', '🤖', 1, 100, NULL),
                (3, 'Shopkeeper', 'Scored 100% in Rocket Shop', '💰', 2, 100, NULL),
                (4, 'Time Traveler', 'Scored 100% in Launch Time', '⏳', 3, 100, NULL),
                (5, 'Signal Decoder', 'Scored 100% in Cosmic Signal', '📡', 4, 100, NULL),
                (6, 'Word Wizard', 'Scored 100 points in Spelling Bee', '🧙‍♂️', 7, 100, NULL),
                (7, 'Art Director', 'Scored 100 points in Shapes & Colors', '🖌️', 6, 100, NULL),
                (8, 'Book Scout', 'Scored 100% in Read & Match', '📖', 8, 100, NULL),
                (9, 'Web Weaver', 'Scored 100% in Spider Web', '🕸️', 9, 100, NULL),
                (10, 'Master Chemist', 'Scored 100% in Color Lab', '👨‍🔬', 10, 100, NULL),
                (11, 'Spanish Dancer', 'Mastered new words in Spanish!', '💃', 11, 100, NULL),
                (12, 'Math Farmer', 'Mastered Egg-dition!', '🚜', 12, 100, NULL),
                (13, 'Popper Pro', 'Popped your way to victory!', '📌', 13, 100, NULL),
                (14, 'Safari Guide', 'Expert on animals and habitats!', '🧭', 14, 100, NULL),
                (15, 'Bridge Master', 'Built a safe path across the lava!', '🌉', 15, 100, NULL),
                (16, 'Traffic Cop', 'Kept the traffic moving smoothly!', '👮', 16, 100, NULL),
                (17, 'Good Listener', 'Followed the Robot\'s commands perfectly!', '👂', 17, 100, NULL),
                (18, 'Conductor', 'Completed the Pattern Train route!', '🧢', 18, 100, NULL),
                (19, 'Alphabet Master', 'Found every letter in the alphabet!', '🎓', 19, 100, NULL),
                (20, 'Tracing Titan', 'Practiced writing 10 numbers!', '🖍️', 21, 100, NULL),
                (21, 'Shape Sherlock', 'Solved 10 shape mysteries!', '🔍', 22, 100, NULL),
                (22, 'Story Reader', 'Read a whole story in The Cat and Rat!', '📚', 23, 100, NULL),
                (23, 'Sight Word Explorer', 'Read a story in Sight Word Adventures!', '🔭', 24, 100, NULL),
                (25, 'Daily Star', 'Completed the Daily Quest!', '⭐', NULL, 0, 'daily_star'),
                (26, 'Streak Master', 'Completed quests 3 days in a row!', '🔥', NULL, 0, 'streak_master');";
            $pdo->exec($sql_badges);
            // 4. Overrides
            $pdo->exec("TRUNCATE TABLE game_theme_overrides");
            $pdo->exec("INSERT INTO `game_theme_overrides` (`id`, `game_id`, `theme_id`, `display_name`, `display_icon`) VALUES
                (6, 1, 3, 'Unicorn Sorter', '🦄'),
                (7, 2, 3, 'Castle Shop', '🏰'),
                (8, 3, 3, 'Magic Time', '🕰️'),
                (9, 4, 3, 'Crystal Ball', '🔮');");

             // 5. Settings (Default Invite Code & Debug Mode)
            $pdo->exec("TRUNCATE TABLE settings");
            $default_invite_hash = password_hash('FamilyGames', PASSWORD_DEFAULT);
            
            // Prepare insert for multiple rows
            $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
            
            // Invite Code
            $stmt->execute(['invite_code', $default_invite_hash]);
            
            // Debug Mode (Default to 0/Off)
            $stmt->execute(['display_errors', '0']);
            // ---------------------------------------------------------
            // C. CREATE ADMIN USER
            // ---------------------------------------------------------
            
            // Check if admin exists first
            $check = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
            $check->execute();
            
            if ($check->fetch()) {
                // Admin exists, but we successfully ran the SQL updates.
                $is_locked = true;
                // Try to delete installer anyway
                @unlink(__FILE__);
                $message = "<div class='alert success'>
                                <strong>Updated!</strong> Database structure updated.<br>
                                Admin account already existed.<br>
                                Please delete install.php now.
                            </div>";
            } else {
                $hashed_login = password_hash($admin_pin, PASSWORD_DEFAULT);
                $hashed_secret = password_hash($secret_pin, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, pin_code, role, avatar, admin_pin) VALUES (?, ?, 'admin', '🔰', ?)");
                if ($stmt->execute([$admin_user, $hashed_login, $hashed_secret])) {
                    
                    // ---------------------------------------------------------
                    // D. AUTO-DESTRUCT
                    // ---------------------------------------------------------
                    $deleted = unlink(__FILE__);
                    $is_locked = true;

                    if ($deleted) {
                        $message = "<div class='alert success'>
                                        <strong>SUCCESS!</strong><br><br>
                                        Installation is complete.<br>
                                        Admin User '<strong>" . htmlspecialchars($admin_user) . "</strong>' created.<br>
                                        <br>
                                        <em>♻️ install.php has been automatically deleted.</em><br><br>
                                        <a href='login.php' style='color:white; text-decoration:underline; font-size:1.2em;'>Go to Login</a>
                                    </div>";
                    } else {
                        $message = "<div class='alert success'>
                                        <strong>SUCCESS!</strong><br><br>
                                        Installation is complete.<br>
                                        Admin User '<strong>" . htmlspecialchars($admin_user) . "</strong>' created.<br>
                                        <br>
                                        <span style='color:#ffeb3b; font-size:1.1em;'>⚠️ Could not auto-delete this file.</span><br>
                                        <strong>PLEASE DELETE install.php MANUALLY NOW.</strong><br><br>
                                        <a href='login.php' style='color:white; text-decoration:underline;'>Go to Login</a>
                                    </div>";
                    }
                }
            }

        } catch (PDOException $e) {
            $message = "<div class='alert error'>Database Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install Kids Game Hub</title>
    <style>
        body { background: #2c3e50; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; color: #333; }
        .card { background: white; padding: 40px; border-radius: 10px; width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); text-align: center; }
        h1 { margin-top: 0; color: #2c3e50; }
        label { display: block; text-align: left; margin-top: 15px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 5px; font-size: 1.1em; margin-top: 25px; cursor: pointer; font-weight: bold; }
        button:hover { background: #219150; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .success { background: #2ecc71; color: #0d5026; }
        .error { background: #e74c3c; color: white; }
        a { color: inherit; }
    </style>
</head>
<body>

<div class="card">
    <h1>🚀 Installer</h1>
    
    <?php echo $message; ?>

    <?php if (!$is_locked): ?>
        <p>This will install the database tables and create your main Admin account.</p>
        
        <form method="POST">
            <label>Admin Username</label>
            <input type="text" name="username" placeholder="e.g. Admin" required>

            <label>Main PIN (for login)</label>
            <input type="number" name="pin" placeholder="e.g. 1234" required>

            <label>Admin Secret PIN (for protected areas)</label>
            <input type="number" name="admin_pin" placeholder="e.g. 9999" required>

            <button type="submit">Install Now</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
