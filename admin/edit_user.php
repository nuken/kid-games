<?php
session_start();
require_once '../includes/db.php'; //

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$message = "";

// 2. HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $pin      = $_POST['pin'];
    $role     = $_POST['role'];
    $grade    = $_POST['grade_level'] ?? 0;
    $parent_id = ($role === 'student' && !empty($_POST['parent_id'])) ? $_POST['parent_id'] : null;

    $stmt = $pdo->prepare("UPDATE users SET username=?, pin_code=?, role=?, grade_level=?, parent_id=? WHERE id=?");
    if ($stmt->execute([$username, $pin, $role, $grade, $parent_id, $id])) {
        $message = "<div class='alert success'>User updated! <a href='index.php' style='color:white; text-decoration:underline;'>Go Back</a></div>";
    } else {
        $message = "<div class='alert error'>Update failed.</div>";
    }
}

// 3. FETCH USER DATA
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

// Fetch Parents for Dropdown
$parents = $pdo->query("SELECT id, username FROM users WHERE role = 'parent'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <style>
        /* OVERRIDE MAIN THEME COLORS FOR ADMIN PANEL */
        body { 
            background: #f4f6f8; 
            color: #333; /* Forces dark text */
            font-family: sans-serif; 
            display:flex; 
            justify-content:center; 
            align-items:center; 
            height:100vh;
            background-image: none; /* Removes starry background */
        }
        
        .edit-card { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            width: 400px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }

        /* Specific text colors to ensure readability */
        h2 { 
            margin-top: 0; 
            color: #2c3e50; 
        }
        
        label { 
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold; 
            color: #2c3e50; /* Dark Blue-Grey */
        }

        /* Form Elements */
        input[type="text"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box; 
            font-size: 16px;
            color: #333;
            background: #fff;
        }

        /* Notifications */
        .alert { padding:15px; margin-bottom:20px; border-radius:5px; text-align:center; color:white; font-weight: bold; }
        .success { background:#2ecc71; } 
        .error { background:#e74c3c; }

        /* Buttons */
        .btn-save {
            width:100%; 
            background:#f39c12; 
            color:white; 
            font-weight:bold; 
            border:none; 
            padding:12px; 
            border-radius:5px; 
            margin-top:20px; 
            cursor:pointer;
            font-size: 16px;
        }
        .btn-save:hover { background: #d68910; }
        
        .cancel-link {
            display: block;
            text-align:center; 
            margin-top:15px;
            color:#7f8c8d; 
            text-decoration:none;
        }
        .cancel-link:hover { color: #333; }

    </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>
    <?php echo $message; ?>

    <form method="POST">
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
        <a href="index.php" class="cancel-link">Cancel</a>
    </form>
</div>

<script>
    function toggleFields() {
        const role = document.getElementById('roleSelect').value;
        const studentFields = document.getElementById('studentFields');
        if (role === 'student') {
            studentFields.style.display = 'block';
        } else {
            studentFields.style.display = 'none';
        }
    }
    toggleFields(); // Init on load
</script>

</body>
</html>