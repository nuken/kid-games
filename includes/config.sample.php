<?php
// includes/config.php
// Recommended: Move your config.php file from the includes/ folder to the directory immediately above your main website folder. Modify path in db.php.
define('DB_HOST', 'db'); // Change to actual DB host
define('DB_NAME', 'kidgames'); // Change to actual DB Name
define('DB_USER', 'kid_user'); // Change to actual DB User
define('DB_PASS', 'kid_password'); // Change to actual DB Password
define('DB_CHARSET', 'utf8mb4');
define('SHOW_ERRORS', false); // Change to true when debugging is needed
// --- MESSAGING LIMITS ---
define('MSG_DAILY_LIMIT', 15); // Max messages a child can send per day
define('MSG_INBOX_CAP', 20);   // Max messages kept in history per child
?>
