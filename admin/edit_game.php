<?php
// admin/edit_game.php
session_start();
require_once 'auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: games.php"); exit; }

$message = "";
$subjects = ['Math', 'Reading', 'Logic', 'Creativity', 'Science', 'General'];

// 2. HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

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

// 3. FETCH GAME DATA
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$id]);
$game = $stmt->fetch();

$icons = ['🚀', '🤖', '⏰', '📡', '🎨', '🧩', '🎲', '🦁', '🚗', '🏰', '🦄', '🎸', '⚽', '📚', '🕷️', '🧪', '🥚', '🎈', '🚦', '🚂'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css"> 
    <style> body { display:flex; justify-content:center; align-items:center; height:100vh; } </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit Game</h2>
    <?php echo $message; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

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
</div>

</body>
</html>
