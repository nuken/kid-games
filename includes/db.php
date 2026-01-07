<?php
// includes/db.php
require_once 'config.php';
//If config.php is moved outside the web directory, change the file path. Example below.
//require_once __DIR__ . '/../../config.php';
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

     // --- NEW CODE START ---
     // Check for Debug Mode setting
     // We use try/catch here just in case the 'settings' table doesn't exist yet (during install/migration)
     try {
         $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'display_errors' LIMIT 1");
         $debugMode = $stmt->fetchColumn();

         if ($debugMode === '1') {
             ini_set('display_errors', 1);
             ini_set('display_startup_errors', 1);
             error_reporting(E_ALL);
         } else {
             ini_set('display_errors', 0);
             ini_set('display_startup_errors', 0);
             // We still log errors, just don't show them to the kids
             error_reporting(E_ALL);
         }
     } catch (Exception $e) {
         // Silently ignore if table missing (happens during install)
     }
     // --- NEW CODE END ---

} catch (\PDOException $e) {
     die("Database Connection Failed: " . $e->getMessage());
}
// Add this helper function here so it is available globally
function auto_version($file) {
    // Check if file exists relative to the calling script
    if (file_exists($file)) {
        return $file . '?v=' . filemtime($file);
    }
    return $file;
}
?>
