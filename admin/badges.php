<?php
// admin/badges.php
session_start();
require_once '../includes/db.php'; 
require_once 'auth_check.php';

$message = "";

// 1. HANDLE ADD BADGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_badge') {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $icon = $_POST['icon'];
    $game_id = !empty($_POST['game_id']) ? $_POST['game_id'] : null;
    $score   = !empty($_POST['score']) ? $_POST['score'] : 0;

    if (empty($name)) {
        $message = "<div class='alert error'>Badge Name is required.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO badges (name, description, icon, criteria_game_id, criteria_score) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $icon, $game_id, $score]);
            $message = "<div class='alert success'>Badge '$name' added successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// 2. FETCH DATA
// Get badges with Game Names
$sql = "SELECT b.*, g.default_title as game_name 
        FROM badges b 
        LEFT JOIN games g ON b.criteria_game_id = g.id 
        ORDER BY b.criteria_game_id DESC, b.name ASC";
$badges = $pdo->query($sql)->fetchAll();

// Get Games for Dropdown
$games = $pdo->query("SELECT id, default_title FROM games ORDER BY default_title ASC")->fetchAll();

// Icon Options
$icons = ['✨', '🏆', '🥇', '🥈', '🥉', '⭐', '🔥', '🤖', '🚀', '🎨', '🧩', '🦁', '🦖', '🦄', '🧙‍♂️', '💰', '💎', '🎓', '📚', '🔬'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Badges</title>
    <link rel="stylesheet" href="<?php echo auto_version('../assets/css/admin.css'); ?>"> 
</head>
<body>

<div class="nav-bar">
    <a href="index.php" class="nav-item">👥 Users</a>
    <a href="games.php" class="nav-item">🎮 Games</a>
    <a href="badges.php" class="nav-item active">🏆 Badges</a>
	<a href="settings.php" class="nav-item">⚙️ Settings</a>
    <a href="../logout.php" class="nav-item logout">Log Out</a>
</div>

<div class="admin-container">

    <div class="card">
        <h2>Add New Badge</h2>
        <?php echo $message; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add_badge">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <label>Badge Name</label>
            <input type="text" name="name" required placeholder="e.g. Math Whiz">

            <label>Description</label>
            <textarea name="description" rows="2" placeholder="e.g. Scored 100% in a Math game"></textarea>

            <label>Icon</label>
            <select name="icon">
                <?php foreach($icons as $i): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endforeach; ?>
            </select>

            <label>Linked Game (Optional)</label>
            <select name="game_id">
                <option value="">-- Global / No Specific Game --</option>
                <?php foreach($games as $g): ?>
                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['default_title']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Score Requirement (if linked to game)</label>
            <input type="number" name="score" value="100" placeholder="0">

            <button type="submit" class="btn-submit">Add Badge</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Badges</h2>
        
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="🔍 Search badges..." style="margin-bottom: 15px;">

        <table>
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Name / Desc</th>
                    <th>Criteria</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($badges as $b): ?>
                <tr>
                    <td style="font-size: 2em; text-align: center;"><?php echo $b['icon']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($b['name']); ?></strong><br>
                        <small style="color:#777;"><?php echo htmlspecialchars($b['description']); ?></small>
                    </td>
                    <td>
                        <?php if ($b['game_name']): ?>
                            Score <strong><?php echo $b['criteria_score']; ?>%</strong> in <br>
                            <span style="color:#2980b9; font-weight:bold;"><?php echo htmlspecialchars($b['game_name']); ?></span>
                        <?php else: ?>
                            <span style="color:#27ae60;">Global / Manual</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_badge.php?id=<?php echo $b['id']; ?>" class="btn-edit">Edit</a>
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
            // Target the 2nd column (Index 1) which contains Name/Desc
            td = tr[i].getElementsByTagName("td")[1]; 
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