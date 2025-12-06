<?php
session_start();
require_once '../includes/db.php'; //

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: games.php"); exit; }

$message = "";

// 2. HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']);
    $folder     = trim($_POST['folder']);
    $icon       = $_POST['icon'];
    $min        = $_POST['min_grade'];
    $max        = $_POST['max_grade'];
    $active     = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE games SET default_title=?, folder_path=?, default_icon=?, min_grade=?, max_grade=?, active=? WHERE id=?");
    if ($stmt->execute([$title, $folder, $icon, $min, $max, $active, $id])) {
        $message = "<div class='alert success'>Game updated! <a href='games.php' style='color:white;'>Go Back</a></div>";
    } else {
        $message = "<div class='alert error'>Update failed.</div>";
    }
}

// 3. FETCH GAME DATA
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$id]);
$game = $stmt->fetch();

$icons = ['🚀', '🤖', '⏰', '📡', '🎨', '🧩', '🎲', '🦁', '🚗', '🏰', '🦄', '🎸', '⚽', '📚'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Game</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <style>
        /* ADMIN THEME OVERRIDES */
        body { background: #f4f6f8; color: #333 !important; font-family: sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; background-image: none; }
        .edit-card { background: white; padding: 30px; border-radius: 10px; width: 450px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); color: #333; }
        
        h2 { margin-top: 0; color: #2c3e50; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #2c3e50; }
        input[type="text"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; background: #fff; color: #333; }
        
        .alert { padding:15px; margin-bottom:20px; border-radius:5px; text-align:center; color:white; font-weight: bold; }
        .success { background:#2ecc71; } .error { background:#e74c3c; }
        
        .btn-save { width:100%; background:#f39c12; color:white; font-weight:bold; border:none; padding:12px; border-radius:5px; margin-top:20px; cursor:pointer; font-size: 16px; }
        .btn-save:hover { background: #d68910; }
        .cancel-link { display: block; text-align:center; margin-top:15px; color:#7f8c8d; text-decoration:none; }
    </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit Game</h2>
    <?php echo $message; ?>

    <form method="POST">
        <label>Game Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($game['default_title']); ?>" required>

        <label>Folder Path</label>
        <input type="text" name="folder" value="<?php echo htmlspecialchars($game['folder_path']); ?>" required>

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
            Game is Active (Visible to Students)
        </label>

        <button type="submit" class="btn-save">Save Changes</button>
        <a href="games.php" class="cancel-link">Cancel</a>
    </form>
</div>

</body>
</html>