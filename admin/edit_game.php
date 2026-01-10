<?php
// admin/edit_game.php
session_start();
require_once 'auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: games.php"); exit; }

$message = "";
$subjects = ['Math', 'Reading', 'Logic', 'Creativity', 'Science', 'General'];

// ---------------------------------------------------------
// 2. HANDLE REQUESTS (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    $action = $_POST['action'] ?? 'update';

    // --- A. DELETE GAME ---
    if ($action === 'delete') {
        try {
            $pdo->beginTransaction();

            // 1. Delete Progress History
            $stmt = $pdo->prepare("DELETE FROM progress WHERE game_id = ?");
            $stmt->execute([$id]);

            // 2. Delete User Favorites (if exists)
            // Wrapping in try-catch in case table doesn't exist yet, though it should
            try {
                $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE game_id = ?");
                $stmt->execute([$id]);
            } catch (Exception $e) { /* Ignore if table missing */ }

            // 3. Delete Theme Overrides
            try {
                $stmt = $pdo->prepare("DELETE FROM game_theme_overrides WHERE game_id = ?");
                $stmt->execute([$id]);
            } catch (Exception $e) { /* Ignore */ }

            // 4. Detach Badges (Don't delete the badge, just remove the game requirement)
            $stmt = $pdo->prepare("UPDATE badges SET criteria_game_id = NULL WHERE criteria_game_id = ?");
            $stmt->execute([$id]);

            // 5. Delete the Game Record
            $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            header("Location: games.php?msg=deleted");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert error'>Error deleting game: " . $e->getMessage() . "</div>";
        }
    }
    
    // --- B. UPDATE GAME ---
    else {
        $title      = trim($_POST['title']);
        $folder     = trim($_POST['folder']);
        $icon       = $_POST['icon'];
        $subject    = $_POST['subject']; 
        $min        = $_POST['min_grade'];
        $max        = $_POST['max_grade'];
        $active     = isset($_POST['active']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE games SET default_title=?, folder_path=?, default_icon=?, min_grade=?, max_grade=?, active=?, subject=? WHERE id=?");
        
        if ($stmt->execute([$title, $folder, $icon, $min, $max, $active, $subject, $id])) {
            $message = "<div class='alert success'>Game updated! <a href='games.php' style='color:#155724;'>Go Back</a></div>";
        } else {
            $message = "<div class='alert error'>Update failed.</div>";
        }
    }
}

// 3. FETCH GAME DATA
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$id]);
$game = $stmt->fetch();

if (!$game) { die("Game not found."); }

$icons = $game_icons_list;
if (!empty($game['default_icon']) && !in_array($game['default_icon'], $icons)) {
    array_unshift($icons, $game['default_icon']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css"> 
    <style> 
        body { display:flex; justify-content:center; align-items:center; min-height:100vh; padding: 20px; box-sizing: border-box; } 
        .edit-card { max-width: 500px; width: 100%; }
        .btn-delete { 
            background-color: #e74c3c; 
            margin-top: 20px; 
            width: 100%; 
            padding: 10px; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 1rem;
        }
        .btn-delete:hover { background-color: #c0392b; }
        .divider { border-top: 1px solid #ddd; margin: 20px 0; }
    </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit Game</h2>
    <?php echo $message; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="update">

        <label>Game Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($game['default_title']); ?>" required>

        <label>Folder Path</label>
        <input type="text" name="folder" value="<?php echo htmlspecialchars($game['folder_path']); ?>" required>

        <label>Subject</label>
        <select name="subject">
            <?php foreach($subjects as $s): ?>
                <option value="<?php echo $s; ?>" <?php if(($game['subject'] ?? 'General') == $s) echo 'selected'; ?>>
                    <?php echo $s; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Default Icon</label>
        <select name="icon">
            <?php foreach($icons as $i): ?>
                <option value="<?php echo $i; ?>" <?php if($game['default_icon'] == $i) echo 'selected'; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endforeach; ?>
        </select>

       <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
                <label>Min Grade</label>
                <select name="min_grade">
                    <?php 
                    $grades = [0=>'Preschool', 1=>'Kindergarten', 2=>'1st Grade', 3=>'2nd Grade', 4=>'3rd Grade', 5=>'4th Grade', 6=>'5th Grade'];
                    foreach($grades as $val => $label) {
                        $sel = ($game['min_grade'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $sel>$label ($val)</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label>Max Grade</label>
                <select name="max_grade">
                    <?php 
                    foreach($grades as $val => $label) {
                        $sel = ($game['max_grade'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $sel>$label ($val)</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <label style="display:flex; align-items:center; margin-top:20px; cursor:pointer;">
            <input type="checkbox" name="active" style="width:auto; margin-right:10px;" <?php if($game['active']) echo 'checked'; ?>>
            Game is Active
        </label>

        <button type="submit" class="btn-save">Save Changes</button>
        <a href="games.php" class="cancel-link">Cancel</a>
    </form>

    <div class="divider"></div>

    <form method="POST" onsubmit="return confirm('⚠️ DANGER: Are you sure you want to delete this game?\n\nThis will remove all student progress and favorite links associated with it. This cannot be undone.');">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn-delete">🗑️ Delete Game Permanently</button>
    </form>

</div>

</body>
</html>