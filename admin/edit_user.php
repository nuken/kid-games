<?php
// admin/edit_user.php
session_start();
require_once 'auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$message = "";

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error");
    }

    // 1. UNLOCK ACCOUNT
    if (isset($_POST['unlock_account'])) {
        $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "<div class='alert success'>Account Unlocked!</div>";
        }
    }
    // 2. UPDATE USER
    else {
        $username = $_POST['username'];
        $role     = $_POST['role'];
        $grade    = $_POST['grade_level'] ?? 0;
        $parent_id = ($role === 'student' && !empty($_POST['parent_id'])) ? $_POST['parent_id'] : null;
        $new_pin = trim($_POST['pin']);

        // Update Logic
        $sql = "UPDATE users SET username=?, role=?, grade_level=?, parent_id=?";
        $params = [$username, $role, $grade, $parent_id];

        // If password/pin provided
        if (!empty($new_pin)) {
            $sql .= ", pin_code=?";
            $params[] = password_hash($new_pin, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = "<div class='alert success'>User updated! <a href='index.php'>Go Back</a></div>";
        } else {
            $message = "<div class='alert error'>Update failed.</div>";
        }
    }
}

// FETCH USER
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

$parents = $pdo->query("SELECT id, username FROM users WHERE role = 'parent'")->fetchAll();

// Check lock status
$is_locked = false;
if ($user['locked_until'] && new DateTime($user['locked_until']) > new DateTime()) {
    $is_locked = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style> body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:#ecf0f1; } </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>
    <?php echo $message; ?>

    <?php if ($is_locked): ?>
        <div style="background:#e74c3c; color:white; padding:10px; border-radius:5px; margin-bottom:15px; text-align:center;">
            <strong>⚠️ Account Locked</strong><br>
            Until: <?php echo $user['locked_until']; ?>
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="unlock_account" value="1" style="background:white; color:#c0392b; border:none; padding:5px 10px; cursor:pointer; font-weight:bold; border-radius:4px;">🔓 Unlock Now</button>
            </form>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

        <label id="pinLabel">Password / PIN</label>
        <input type="text" name="pin" placeholder="Leave blank to keep current" autocomplete="off">
        <small style="color:#7f8c8d; display:block; margin-bottom:10px;">Students use numeric PINs. Parents/Admins can use text passwords.</small>

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

            <label>Assign Parent</label>
            <select name="parent_id">
                <option value="">-- No Parent --</option>
                <?php foreach($parents as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if($user['parent_id'] == $p['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-save">Save Changes</button>
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
