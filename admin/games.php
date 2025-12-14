<?php
// admin/games.php
session_start();
require_once '../includes/db.php'; 
require_once 'auth_check.php';

$message = "";

// 1. DEFINE SUBJECTS (Centralized List)
$subjects = ['Math', 'Reading', 'Logic', 'Creativity', 'Science', 'General'];

// 2. HANDLE ADD GAME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_game') {
    $title       = trim($_POST['title']);
    $folder      = trim($_POST['folder']);
    $icon        = $_POST['icon'];
    $subject     = $_POST['subject']; // New Field
    $min_grade   = $_POST['min_grade'];
    $max_grade   = $_POST['max_grade'];

    // Basic Validation
    if (empty($title) || empty($folder)) {
        $message = "<div class='alert error'>Title and Folder Path are required.</div>";
    } elseif (!preg_match('/^[a-zA-Z0-9\-\_\/]+$/', $folder)) {
        $message = "<div class='alert error'>Invalid Folder Path. Use only letters, numbers, dashes, underscores, and slashes.</div>";
    } else {
        try {
            // Updated SQL to include 'subject'
            $stmt = $pdo->prepare("INSERT INTO games (default_title, folder_path, default_icon, min_grade, max_grade, active, subject) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$title, $folder, $icon, $min_grade, $max_grade, $subject]);
            $message = "<div class='alert success'>Game '$title' added successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// 3. FETCH GAMES
$games = $pdo->query("SELECT * FROM games ORDER BY subject, default_title ASC")->fetchAll();

// Icon Options for Dropdown
$icons = ['🚀', '🤖', '⏰', '📡', '🎨', '🧩', '🎲', '🦁', '🚗', '🏰', '🦄', '🎸', '⚽', '📚', '🕷️', '🧪', '🥚', '🎈', '🚦', '🚂'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Games</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        /* ADMIN THEME OVERRIDES */
        body { background-color: #f4f6f8; background-image: none; color: #333 !important; font-family: sans-serif; }
        .admin-container { max-width: 1200px; margin: 30px auto; display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); color: #333; }

        /* Typography */
        h2 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        label { color: #2c3e50; font-weight: bold; display: block; margin-top: 10px; }

        /* Inputs */
        input, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; background: #fff; color: #333; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f8f9fa; color: #555; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #333; }

        /* Buttons */
        .btn-submit { width:100%; background:#27ae60; color:white; border:none; padding:12px; border-radius:5px; cursor:pointer; font-weight:bold; margin-top: 15px; }
        .btn-submit:hover { background: #219150; }
        .btn-edit { background: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }

        /* Navigation Bar */
        .nav-bar { max-width: 1200px; margin: 0 auto 20px; display: flex; gap: 10px; }
        .nav-item { padding: 10px 20px; text-decoration: none; color: #555; background: #e0e0e0; border-radius: 5px; font-weight: bold; }
        .nav-item.active { background: #3498db; color: white; }
        .nav-item:hover { background: #ccc; }
        .logout { margin-left: auto; background: #e74c3c; color: white; }

        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; color: white; text-align: center; font-weight: bold; }
        .success { background: #2ecc71; } .error { background: #e74c3c; }
        
        .badge-subj { padding: 3px 8px; border-radius: 10px; font-size: 0.8em; background: #eee; border: 1px solid #ccc; }
    </style>
</head>
<body>

<div class="nav-bar">
    <a href="index.php" class="nav-item">👥 Users</a>
    <a href="games.php" class="nav-item active">🎮 Games</a>
    <a href="../logout.php" class="nav-item logout">Log Out</a>
</div>

<div class="admin-container">

    <div class="card">
        <h2>Add New Game</h2>
        <?php echo $message; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add_game">

            <label>Game Title</label>
            <input type="text" name="title" required placeholder="e.g. Super Math">

            <label>Folder Path</label>
            <input type="text" name="folder" required placeholder="games/folder-name">
            <small style="color:#666; font-size: 0.8em;">Relative to site root (e.g. games/rocket-shop)</small>

            <label>Subject</label>
            <select name="subject">
                <?php foreach($subjects as $s): ?>
                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>

            <label>Default Icon</label>
            <select name="icon">
                <?php foreach($icons as $i): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endforeach; ?>
            </select>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Min Grade</label>
                    <select name="min_grade">
                        <option value="0">Preschool (0)</option>
                        <option value="1">Kindergarten (1)</option>
                        <option value="2">1st Grade (2)</option>
                        <option value="3">2nd Grade (3)</option>
                        <option value="4">3rd Grade (4)</option>
                        <option value="5">4th Grade (5)</option>
                        <option value="6">5th Grade (6)</option>
                    </select>
                </div>
                <div>
                    <label>Max Grade</label>
                    <select name="max_grade">
                        <option value="6" selected>5th Grade (6)</option>
                        <option value="5">4th Grade (5)</option>
                        <option value="4">3rd Grade (4)</option>
                        <option value="3">2nd Grade (3)</option>
                        <option value="2">1st Grade (2)</option>
                        <option value="1">Kindergarten (1)</option>
                        <option value="0">Preschool (0)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-submit">Add Game</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Games</h2>
        <table>
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Title / Path</th>
                    <th>Subject</th>
                    <th>Grades</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $g): ?>
                <tr>
                    <td style="font-size: 1.5em; text-align: center;"><?php echo $g['default_icon']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($g['default_title']); ?></strong><br>
                        <span style="font-family:monospace; font-size:0.8em; color:#777;"><?php echo htmlspecialchars($g['folder_path']); ?></span>
                    </td>
                    <td>
                        <span class="badge-subj"><?php echo htmlspecialchars($g['subject'] ?? 'General'); ?></span>
                    </td>
                    <td><?php echo $g['min_grade']; ?> - <?php echo $g['max_grade']; ?></td>
                    <td>
                        <?php echo ($g['active']) ? '<span style="color:green; font-weight:bold;">Active</span>' : '<span style="color:red;">Hidden</span>'; ?>
                    </td>
                    <td>
                        <a href="edit_game.php?id=<?php echo $g['id']; ?>" class="btn-edit">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>