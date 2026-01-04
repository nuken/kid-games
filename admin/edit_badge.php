<?php
// admin/edit_badge.php
session_start();
require_once '../includes/db.php'; 
require_once 'auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: badges.php"); exit; }

$message = "";

// HANDLE DELETE
if (isset($_GET['delete']) && $_GET['delete'] == 'true') {
    // SECURITY: CSRF Check for GET request
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }
    
    $stmt = $pdo->prepare("DELETE FROM badges WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: badges.php"); exit;
}

// HANDLE UPDATE
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

    $stmt = $pdo->prepare("UPDATE badges SET name=?, description=?, icon=?, criteria_game_id=?, criteria_score=? WHERE id=?");
    if ($stmt->execute([$name, $desc, $icon, $game_id, $score, $id])) {
        $message = "<div class='alert success'>Badge updated! <a href='badges.php' style='color:#155724;'>Go Back</a></div>";
    } else {
        $message = "<div class='alert error'>Update failed.</div>";
    }
}

// FETCH BADGE
$stmt = $pdo->prepare("SELECT * FROM badges WHERE id = ?");
$stmt->execute([$id]);
$badge = $stmt->fetch();

// FETCH GAMES
$games = $pdo->query("SELECT id, default_title FROM games ORDER BY default_title ASC")->fetchAll();
$icons = ['✨', '🏆', '🥇', '🥈', '🥉', '⭐', '🔥', '🤖', '🚀', '🎨', '🧩', '🦁', '🦖', '🦄', '🧙‍♂️', '💰', '💎', '🎓', '📚', '🔬'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Badge</title>
    <link rel="stylesheet" href="../assets/css/admin.css"> 
    <style> 
        body { display:flex; justify-content:center; align-items:center; height:100vh; } 
    </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit Badge</h2>
    <?php echo $message; ?>

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
        
        <a href="edit_badge.php?id=<?php echo $badge['id']; ?>&delete=true&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
           class="btn-delete" 
           onclick="return confirm('Delete this badge?');">
           Delete Badge
        </a>
        
        <a href="badges.php" class="cancel-link">Cancel</a>
    </form>
</div>

</body>
</html>