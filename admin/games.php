<?php
// admin/games.php
session_start();
require_once 'auth_check.php';

$message = "";
$subjects = ['Math', 'Reading', 'Logic', 'Creativity', 'Science', 'General'];

// 2. HANDLE ADD GAME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_game') {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    $title       = trim($_POST['title']);
    $folder      = trim($_POST['folder']);
    $icon        = $_POST['icon'];
    $subject     = $_POST['subject'];
    $min_grade   = $_POST['min_grade'];
    $max_grade   = $_POST['max_grade'];

    if (empty($title) || empty($folder)) {
        $message = "<div class='alert error'>Title and Folder Path are required.</div>";
    } elseif (!preg_match('/^[a-zA-Z0-9\-\_\/]+$/', $folder)) {
        $message = "<div class='alert error'>Invalid Folder Path. Use only letters, numbers, dashes, underscores, and slashes.</div>";
    } else {
        try {
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
$icons = ['🚀', '🤖', '⏰', '📡', '🎨', '🧩', '🎲', '🦁', '🚗', '🏰', '🦄', '🎸', '⚽', '📚', '🕷️', '🧪', '🥚', '🎈', '🚦', '🚂'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Games</title>
    <link rel="stylesheet" href="<?php echo auto_version('../assets/css/admin.css'); ?>"> 
    <style> .badge-subj { padding: 3px 8px; border-radius: 10px; font-size: 0.8em; background: #eee; border: 1px solid #ccc; } </style>
</head>
<body>

<div class="nav-bar">
    <a href="index.php" class="nav-item">👥 Users</a>
    <a href="games.php" class="nav-item active">🎮 Games</a>
	<a href="badges.php" class="nav-item">🏆 Badges</a> 
	<a href="settings.php" class="nav-item">⚙️ Settings</a>
    <a href="../logout.php" class="nav-item logout">Log Out</a>
</div>

<div class="admin-container">

    <div class="card">
        <h2>Add New Game</h2>
        <?php echo $message; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add_game">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

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
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="🔍 Search games..." style="margin-bottom: 15px;">

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

<script>
    function filterTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.querySelector("table");
        tr = table.getElementsByTagName("tr");

        for (i = 1; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1]; // Index 1 is the Title column
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }       
        }
    }
</script>
</body>
</html>
