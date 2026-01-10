<?php
// admin/edit_badge.php
session_start();
require_once 'auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: badges.php"); exit; }

$message = "";

// 1. FETCH BADGE EARLY
// We do this first so we can check if it's a restricted "System Badge"
// before performing any actions.
$stmt = $pdo->prepare("SELECT * FROM badges WHERE id = ?");
$stmt->execute([$id]);
$badge = $stmt->fetch();

if (!$badge) {
    header("Location: badges.php");
    exit;
}

// Check if this is a protected system badge (has a slug)
$is_system_badge = !empty($badge['slug']);

// 2. HANDLE DELETE
if (isset($_GET['delete']) && $_GET['delete'] == 'true') {
    // SECURITY: CSRF Check
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    // LOGIC: Prevent deletion of system badges
    if ($is_system_badge) {
        $message = "<div class='alert error'>⛔ Action Blocked: This is a System Badge and cannot be deleted.</div>";
    } else {
        // 1. Clean up user history (Prevents Bloat!)
        $pdo->prepare("DELETE FROM user_badges WHERE badge_id = ?")->execute([$id]);

        // 2. Delete the badge definition
        $stmt = $pdo->prepare("DELETE FROM badges WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: badges.php");
        exit;
    }
}

// 3. HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $icon = $_POST['icon'];
    $game_id = !empty($_POST['game_id']) ? $_POST['game_id'] : null;
    $score   = !empty($_POST['score']) ? $_POST['score'] : 0;

    // We do NOT update the slug here, ensuring it remains safe.
    $stmt = $pdo->prepare("UPDATE badges SET name=?, description=?, icon=?, criteria_game_id=?, criteria_score=? WHERE id=?");
    if ($stmt->execute([$name, $desc, $icon, $game_id, $score, $id])) {
        $message = "<div class='alert success'>Badge updated! <a href='badges.php' style='color:#155724;'>Go Back</a></div>";
        // Refresh badge data to show new values
        $stmt = $pdo->prepare("SELECT * FROM badges WHERE id = ?");
        $stmt->execute([$id]);
        $badge = $stmt->fetch();
    } else {
        $message = "<div class='alert error'>Update failed.</div>";
    }
}

// FETCH GAMES (for dropdown)
$games = $pdo->query("SELECT id, default_title FROM games ORDER BY default_title ASC")->fetchAll();
$icons = $badge_icons_list;
// FIX: Ensure the current badge's icon is in the list
if (!in_array($badge['icon'], $icons)) {
    array_unshift($icons, $badge['icon']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Badge</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { display:flex; justify-content:center; align-items:center; height:100vh; }
        .system-badge-notice {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9em;
            border: 1px solid #90caf9;
        }
    </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit Badge</h2>
    <?php echo $message; ?>

    <?php if ($is_system_badge): ?>
        <div class="system-badge-notice">
            <strong>ℹ️ System Badge:</strong> This badge is required for game logic (ID: <code><?php echo htmlspecialchars($badge['slug']); ?></code>). It cannot be deleted, but you can change its name or icon.
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label>Badge Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($badge['name']); ?>" required>

        <label>Description</label>
        <textarea name="description" rows="2"><?php echo htmlspecialchars($badge['description']); ?></textarea>

        <label>Icon</label>
        <select name="icon">
            <?php foreach($icons as $i): ?>
                <option value="<?php echo $i; ?>" <?php if($badge['icon'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Linked Game</label>
        <select name="game_id">
            <option value="">-- Global / No Specific Game --</option>
            <?php foreach($games as $g): ?>
                <option value="<?php echo $g['id']; ?>" <?php if($badge['criteria_game_id'] == $g['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($g['default_title']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Score Criteria</label>
        <input type="number" name="score" value="<?php echo $badge['criteria_score']; ?>">

        <button type="submit" class="btn-save">Save Changes</button>

        <?php if (!$is_system_badge): ?>
            <a href="edit_badge.php?id=<?php echo $badge['id']; ?>&delete=true&csrf_token=<?php echo $_SESSION['csrf_token']; ?>"
               class="btn-delete"
               style="display: block; width: 100%; text-align: center; margin-top: 15px; margin-left: 0; padding: 12px; box-sizing: border-box; font-weight: bold; font-size: 16px;"
               onclick="return confirm('Delete this badge?');">
               Delete Badge
            </a>
        <?php endif; ?>

        <a href="badges.php" class="cancel-link">Cancel</a>
    </form>
</div>

</body>
</html>
