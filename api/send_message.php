<?php
// api/send_message.php
header('Content-Type: application/json');
session_start();
require_once '../includes/db.php';

// CONFIGURATION
$DAILY_LIMIT = 15; // Max messages a child can send per day
$INBOX_CAP   = 20; // Max messages kept in history per child

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Not logged in']));
}

$input = json_decode(file_get_contents('php://input'), true);
$sender_id   = $_SESSION['user_id'];
$receiver_id = (int)$input['receiver_id'];
$emoji_msg   = $input['message'];

// 2. Validate Message (Security: Ensure it is ONLY emojis/text, no HTML)
$emoji_msg = htmlspecialchars(strip_tags($emoji_msg));
if(mb_strlen($emoji_msg) > 10) { // Limit length just in case
    die(json_encode(['status' => 'error', 'message' => 'Message too long']));
}

try {
    // --- CHECK 1: OPT-OUT STATUS ---
    // Check if Sender OR Receiver has messaging disabled
    $stmt = $pdo->prepare("SELECT id, messaging_enabled, role FROM users WHERE id IN (?, ?)");
    $stmt->execute([$sender_id, $receiver_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
    // Remap to [id => data] for easier access
    $userData = [];
    foreach($users as $u) { $userData[$u['id']] = $u; }

    if (empty($userData[$sender_id]) || $userData[$sender_id]['messaging_enabled'] == 0) {
         die(json_encode(['status' => 'error', 'message' => 'Messaging disabled by parent.']));
    }
    if (empty($userData[$receiver_id]) || $userData[$receiver_id]['messaging_enabled'] == 0) {
         die(json_encode(['status' => 'error', 'message' => 'This friend cannot receive messages right now.']));
    }

    // --- CHECK 2: DAILY LIMIT (Students Only) ---
    $senderRole = $userData[$sender_id]['role'] ?? 'student';
    
    if ($senderRole === 'student') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND DATE(sent_at) = CURDATE()");
        $stmt->execute([$sender_id]);
        $sent_today = $stmt->fetchColumn();

        if ($sent_today >= $DAILY_LIMIT) {
            die(json_encode(['status' => 'limit', 'message' => "You reached your limit of $DAILY_LIMIT messages today!"]));
        }
    }

    // --- EXECUTE: SEND MESSAGE ---
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $emoji_msg]);

    // --- CLEANUP: INBOX CAP ---
    // Count receiver's messages. If > CAP, delete the oldest ones.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ?");
    $stmt->execute([$receiver_id]);
    $count = $stmt->fetchColumn();

    if ($count > $INBOX_CAP) {
        // Delete oldest messages for this receiver, keeping only the newest INBOX_CAP
        $limit = $count - $INBOX_CAP;
        $stmt = $pdo->prepare("DELETE FROM messages WHERE receiver_id = ? ORDER BY id ASC LIMIT $limit");
        $stmt->execute([$receiver_id]);
    }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System error']);
}
?>