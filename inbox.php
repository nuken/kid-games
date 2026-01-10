<?php
// inbox.php
require_once 'includes/header.php';

// Security: Ensure user is logged in
if (!isset($current_user)) { header("Location: login.php"); exit; }
$user_id = $current_user['id'];

// 1. Verify Access (Badge Check)
$hasMessenger = false;
if ($current_user['messaging_enabled']) {
    $checkBadge = $pdo->prepare("
        SELECT COUNT(*)
        FROM user_badges b
        JOIN badges d ON b.badge_id = d.id
        WHERE b.user_id = ?
        AND d.slug = 'messenger_unlock'
        AND DATE(b.earned_at) = CURDATE()
    ");
    $checkBadge->execute([$user_id]);

    if ($checkBadge->fetchColumn() > 0) {
        $hasMessenger = true;
    }
}

// Redirect if not unlocked
if (!$hasMessenger) {
    header("Location: index.php");
    exit;
}

// 2. Mark messages as read (Since we are now viewing the inbox)
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ?")->execute([$user_id]);

// 3. Fetch Inbox
$stmt = $pdo->prepare("SELECT m.*, u.username as sender_name, u.avatar as sender_avatar FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 20");
$stmt->execute([$user_id]);
$myMessages = $stmt->fetchAll();

// 4. Fetch Friends (AND PARENT)
// UPDATED QUERY: Select students OR the specific parent of this user
$stmt = $pdo->prepare("
    SELECT id, username, avatar, role 
    FROM users 
    WHERE (
        (role = 'student' AND id != :uid1) 
        OR 
        (id = (SELECT parent_id FROM users WHERE id = :uid2))
    )
    AND messaging_enabled = 1 
    ORDER BY role ASC, username
");
$stmt->execute(['uid1' => $user_id, 'uid2' => $user_id]);
$friendList = $stmt->fetchAll();

// Theme Setup (for background colors)
$theme_css = $current_user['css_file'] ?? 'default.css';
$theme_path = "assets/themes/" . $theme_css;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inbox</title>
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version($theme_path); ?>">
</head>
<body>

    <div class="profile-bar">
        <div style="flex: 1; display: flex; align-items: center;">
            <a href="index.php" class="logout-btn" style="background: var(--nebula-green, #2ecc71); margin: 0;">&larr; Back to Games</a>
        </div>

        <div class="details" style="flex: 1; text-align: center; white-space: nowrap;">
            <h2 style="margin: 0;">Message Center</h2>
        </div>

        <div style="flex: 1;"></div>
    </div>

    <div class="container">
        <div class="messenger-box" style="display:block;">
            <h2>📬 My Inbox</h2>

            <div id="messenger-content" style="display:block;">
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px dashed #eee;">
                    <select id="receiver_id" style="padding: 10px; font-size: 1.1rem; border-radius: 10px; border: 2px solid #3498db;">
                        <option value="">Choose a person...</option>
                        <?php foreach($friendList as $f): ?>
                            <?php 
                                // Add a star or label if it's the parent
                                $label = htmlspecialchars($f['username']);
                                if ($f['role'] === 'parent' || $f['role'] === 'admin') {
                                    $label .= " (Parent)";
                                }
                            ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo $f['avatar'] . " " . $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top:10px;">
                        <button onclick="pickEmoji('👋')" class="emoji-btn">👋</button>
                        <button onclick="pickEmoji('😀')" class="emoji-btn">😀</button>
						<button onclick="pickEmoji('😁')" class="emoji-btn">😁</button>
						<button onclick="pickEmoji('😎')" class="emoji-btn">😎</button>
                        <button onclick="pickEmoji('💖')" class="emoji-btn">💖</button>
                        <button onclick="pickEmoji('🦄')" class="emoji-btn">🦄</button>
						<button onclick="pickEmoji('🎈')" class="emoji-btn">🎈</button>
						<button onclick="pickEmoji('🚀')" class="emoji-btn">🚀</button>
                        <button onclick="pickEmoji('🦖')" class="emoji-btn">🦖</button>
                        <button onclick="pickEmoji('🍕')" class="emoji-btn">🍕</button>
                    </div>
                    <p id="status-msg" style="height:20px; color:#27ae60; font-weight:bold;"></p>
                </div>

                <div class="inbox-window">
                    <?php if(count($myMessages) > 0): ?>
                        <?php foreach($myMessages as $msg): ?>
                            <div class="msg-bubble">
                                <strong><?php echo $msg['sender_avatar']; ?> <?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
                                <span style="font-size:1.5rem; margin-left:10px;"><?php echo $msg['message']; ?></span>
                                <small style="color:#ccc; float:right;"><?php echo date('M j', strtotime($msg['sent_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#bdc3c7; padding: 20px;">No messages yet!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
function pickEmoji(emoji) {
    const friendId = document.getElementById('receiver_id').value;
    const status = document.getElementById('status-msg');
    if (!friendId) { alert("Pick a person first!"); return; }

    status.innerText = "Sending...";

    fetch('api/send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: friendId, message: emoji })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            status.innerText = "Sent! ✅";
            setTimeout(() => location.reload(), 1000);
        } else if (data.status === 'limit') {
            status.style.color = 'orange';
            status.innerText = data.message;
        } else {
            status.style.color = 'red';
            status.innerText = data.message;
        }
    });
}
</script>
</body>
</html>