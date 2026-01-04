<?php
// admin/edit_user.php
session_start();
require_once '../includes/db.php'; 
require_once 'auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$message = "";

// 2. HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token.");
    }

    $username = $_POST['username'];
    $pin      = $_POST['pin'];
    $role     = $_POST['role'];
    $grade    = $_POST['grade_level'] ?? 0;
    $parent_id = ($role === 'student' && !empty($_POST['parent_id'])) ? $_POST['parent_id'] : null;

    $stmt = $pdo->prepare("UPDATE users SET username=?, pin_code=?, role=?, grade_level=?, parent_id=? WHERE id=?");
    if ($stmt->execute([$username, $pin, $role, $grade, $parent_id, $id])) {
        $message = "<div class='alert success'>User updated! <a href='index.php' style='color:#155724; text-decoration:underline;'>Go Back</a></div>";
    } else {
        $message = "<div class='alert error'>Update failed.</div>";
    }
}

// 3. FETCH USER DATA
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

$parents = $pdo->query("SELECT id, username FROM users WHERE role = 'parent'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style> body { display:flex; justify-content:center; align-items:center; height:100vh; } </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>
    <?php echo $message; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

        <label>PIN Code</label>
        <input type="text" name="pin" value="<?php echo htmlspecialchars($user['pin_code']); ?>" required>

        <label>Role</label>
        <select name="role" id="roleSelect" onchange="toggleFields()">
            <option value="student" <?php if($user['role']=='student') echo 'selected'; ?>>Student</option>
            <option value="parent" <?php if($user['role']=='parent') echo 'selected'; ?>>Parent</option>
            <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
        </select>

        <div id="studentFields">
            <label>Grade Level</label>
            <select name="grade_level">
                <option value="0" <?php if($user['grade_level']==0) echo 'selected'; ?>>Preschool</option>
                <option value="1" <?php if($user['grade_level']==1) echo 'selected'; ?>>Kindergarten</option>
                <option value="2" <?php if($user['grade_level']==2) echo 'selected'; ?>>1st Grade</option>
                <option value="3" <?php if($user['grade_level']==3) echo 'selected'; ?>>2nd Grade</option>
                <option value="4" <?php if($user['grade_level']==4) echo 'selected'; ?>>3rd Grade</option>
                <option value="5" <?php if($user['grade_level']==5) echo 'selected'; ?>>4th Grade</option>
                <option value="6" <?php if($user['grade_level']==6) echo 'selected'; ?>>5th Grade</option>
            </select>

            <label style="color:#8e44ad;">Assign Parent</label>
            <select name="parent_id" style="border: 2px solid #9b59b6;">
                <option value="">-- No Parent --</option>
                <?php foreach($parents as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if($user['parent_id'] == $p['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-save">Save Changes</button>

        <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <a href="delete_user.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
               class="btn-delete"
               style="display: block; width: 100%; text-align: center; margin-top: 15px; margin-left: 0; padding: 12px; box-sizing: border-box; font-weight: bold; font-size: 16px; background-color: #e74c3c;"
               onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($user['username']); ?>? This cannot be undone.');">
               Delete User
            </a>
        <?php endif; ?>

        <a href="index.php" class="cancel-link">Cancel</a>
    </form>
</div>

<script>
    function toggleFields() {
        const role = document.getElementById('roleSelect').value;
        const studentFields = document.getElementById('studentFields');
        studentFields.style.display = (role === 'student') ? 'block' : 'none';
    }
    toggleFields();
</script>

</body>
</html>