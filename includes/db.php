<?php
// includes/db.php
require_once 'config.php';

// If config.php is moved outside the web directory, change the file path.
// require_once __DIR__ . '/../../config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

     // --- DEBUG MODE CHECK START ---
     try {
         // Check for Debug Mode setting in DB
         $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'display_errors' LIMIT 1");
         $debugMode = $stmt->fetchColumn();

         if ($debugMode === '1') {
             ini_set('display_errors', 1);
             ini_set('display_startup_errors', 1);
             error_reporting(E_ALL);
         } else {
             ini_set('display_errors', 0);
             ini_set('display_startup_errors', 0);
             error_reporting(E_ALL); // Log errors, but don't show them
         }
     } catch (Exception $e) {
         // Silently ignore if table missing (happens during install)
     }
     // --- DEBUG MODE CHECK END ---

} catch (\PDOException $e) {
     // =============================================================
     // 🛡️ SECURITY FIX: PREVENT CREDENTIAL LEAKAGE
     // =============================================================
     
     // 1. Log the RAW error internally (Check your server's /var/log/apache2/error.log)
     error_log("CRITICAL DB CONNECTION ERROR: " . $e->getMessage());

     // 2. Show a "Nice Page" for the kids/users
     // We set 503 Service Unavailable status code
     http_response_code(503);
     ?>
     <!DOCTYPE html>
     <html lang="en">
     <head>
         <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <title>Be Right Back!</title>
         <style>
             body {
                 background-color: #2c3e50; /* Your App's Dark Blue */
                 color: #ecf0f1;
                 font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                 display: flex;
                 justify-content: center;
                 align-items: center;
                 height: 100vh;
                 margin: 0;
                 padding: 20px;
                 box-sizing: border-box;
             }
             .error-card {
                 background: #34495e;
                 padding: 40px;
                 border-radius: 20px;
                 text-align: center;
                 box-shadow: 0 15px 30px rgba(0,0,0,0.3);
                 max-width: 450px;
                 width: 100%;
                 border-bottom: 6px solid #e74c3c;
             }
             .icon { 
                 font-size: 80px; 
                 margin-bottom: 20px; 
                 display: block; 
                 animation: float 3s ease-in-out infinite; 
             }
             h1 { 
                 color: #f1c40f; 
                 margin: 0 0 15px 0; 
                 font-size: 2rem; 
             }
             p { 
                 font-size: 1.1rem; 
                 line-height: 1.6; 
                 color: #bdc3c7; 
                 margin-bottom: 25px;
             }
             .btn {
                 display: inline-block;
                 padding: 15px 35px;
                 background: #27ae60;
                 color: white;
                 text-decoration: none;
                 border-radius: 50px;
                 font-weight: bold;
                 font-size: 1.1rem;
                 transition: all 0.2s;
                 box-shadow: 0 4px 0 #219150;
             }
             .btn:hover { 
                 transform: translateY(-2px); 
                 background: #2ecc71; 
                 box-shadow: 0 6px 0 #219150;
             }
             .btn:active {
                 transform: translateY(2px);
                 box-shadow: 0 1px 0 #219150;
             }
             /* Floating animation for the robot icon */
             @keyframes float { 
                 0% { transform: translateY(0px) rotate(0deg); } 
                 50% { transform: translateY(-10px) rotate(5deg); } 
                 100% { transform: translateY(0px) rotate(0deg); } 
             }
         </style>
     </head>
     <body>
         <div class="error-card">
             <span class="icon">🤖💤</span>
             
             <h1>Shhh... Robots Napping</h1>
             
             <p>
                 Our database robots are taking a quick power nap.<br>
                 We are working to wake them up!
             </p>
             
             <a href="javascript:location.reload()" class="btn">Wake Up Robots</a>
         </div>
     </body>
     </html>
     <?php
     // 3. Stop script execution immediately so no sensitive data leaks
     exit;
}

// Add this helper function here so it is available globally
function auto_version($file) {
    if (file_exists($file)) {
        return $file . '?v=' . filemtime($file);
    }
    return $file;
}
?>