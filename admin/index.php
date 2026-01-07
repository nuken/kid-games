<?php
// admin/index.php
session_start();
require_once 'auth_check.php';

$message = "";

// 1. HANDLE ADD USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    $username = trim($_POST['username']);
    $pin      = trim($_POST['pin']);
    $role     = $_POST['role'];
    $grade    = $_POST['grade_level'] ?? 0;
    
    $parent_id = ($role === 'student' && !empty($_POST['parent_id'])) ? $_POST['parent_id'] : null;

    try {
        // Default avatar set to silhouette
        $stmt = $pdo->prepare("INSERT INTO users (username, pin_code, role, grade_level, parent_id, avatar) VALUES (?, ?, ?, ?, ?, '👤')");
        // HASH THE PIN FIRST
$hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
$stmt->execute([$username, $hashed_pin, $role, $grade, $parent_id]);
        $message = "<div class='alert success'>User '$username' created successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// 2. FETCH DATA
$sql = "SELECT u.*, p.username as parent_name 
        FROM users u 
        LEFT JOIN users p ON u.parent_id = p.id 
        ORDER BY u.role DESC, u.username ASC";
$users = $pdo->query($sql)->fetchAll();

$parents = $pdo->query("SELECT id, username FROM users WHERE role = 'parent' ORDER BY username ASC")->fetchAll();

// 3. GET DASHBOARD STATS
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$active_games = $pdo->query("SELECT COUNT(*) FROM games WHERE active=1")->fetchColumn();
$total_plays = $pdo->query("SELECT COUNT(*) FROM progress")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="<?php echo auto_version('../assets/css/admin.css'); ?>">
</head>
<body>

<div class="nav-bar">
    <a href="index.php" class="nav-item active">👥 Users</a>
    <a href="games.php" class="nav-item">🎮 Games</a>
    <a href="badges.php" class="nav-item">🏆 Badges</a>
	<a href="settings.php" class="nav-item">⚙️ Settings</a>
    <a href="../logout.php" class="nav-item logout">Log Out</a>
</div>

<div class="admin-container">
    
    <div class="stats-row" style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 10px;">
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; font-weight: bold; color: #3498db;"><?php echo $total_users; ?></div>
            <div style="color: #7f8c8d; font-size: 0.9rem;">Total Users</div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; font-weight: bold; color: #9b59b6;"><?php echo $total_students; ?></div>
            <div style="color: #7f8c8d; font-size: 0.9rem;">Students</div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; font-weight: bold; color: #2ecc71;"><?php echo $active_games; ?></div>
            <div style="color: #7f8c8d; font-size: 0.9rem;">Active Games</div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; font-weight: bold; color: #f1c40f;"><?php echo $total_plays; ?></div>
            <div style="color: #7f8c8d; font-size: 0.9rem;">Total Missions</div>
        </div>
    </div>

    <div class="card">
        <h2>Add New User</h2>
        <?php echo $message; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label>Username</label>
            <input type="text" name="username" required placeholder="e.g. Bobby">
            
            <label>PIN Code</label>
            <input type="text" name="pin" required placeholder="4-digit PIN">
            
            <label>Role</label>
            <select name="role" id="roleSelect" onchange="toggleFields()">
                <option value="student">Student</option>
                <option value="parent">Parent</option>
                <option value="admin">Admin</option>
            </select>
            
            <div id="studentFields">
                <label>Grade Level</label>
                <select name="grade_level">
                    <option value="0">Preschool</option>
                    <option value="1">Kindergarten</option>
                    <option value="2">1st Grade</option>
                    <option value="3">2nd Grade</option>
                    <option value="4">3rd Grade</option>
                    <option value="5">4th Grade</option>
                    <option value="6">5th Grade</option>
                </select>

                <label style="color:#9b59b6;">Assign to Parent:</label>
                <select name="parent_id" style="border: 2px solid #9b59b6;">
                    <option value="">-- No Parent --</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn-submit">Create User</button>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-bottom: 15px;">All Users</h2>
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="🔍 Search users..." style="margin-bottom: 15px;">
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    
                    <th>Parent / Data</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                    </td>
                    <td>
                        <span class="badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'student' && $u['parent_name']): ?>
                            <span style="color:#9b59b6; font-weight:bold; font-size:0.9em;">Child of <?php echo htmlspecialchars($u['parent_name']); ?></span>
                        <?php elseif ($u['role'] === 'student'): ?>
                            <span style="color:#999; font-size:0.8em; font-style:italic;">No Parent</span>
                        <?php else: ?>
                            <span style="color:#ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn-edit">Edit</a>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    function toggleFields() {
        const role = document.getElementById('roleSelect').value;
        const studentFields = document.getElementById('studentFields');
        studentFields.style.display = (role === 'student') ? 'block' : 'none';
    }
    toggleFields();

    function filterTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.querySelector("table");
        tr = table.getElementsByTagName("tr");

        for (i = 1; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0]; 
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
