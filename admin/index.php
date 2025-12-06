<?php
session_start();
require_once '../includes/db.php'; //

// 1. Security Check: Only Admins Allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: Admins Only.");
}

$message = "";

// 2. HANDLE ADD USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username']);
    $pin      = trim($_POST['pin']);
    $role     = $_POST['role'];
    $grade    = $_POST['grade_level'] ?? 0;
    
    // Only students get a parent_id
    $parent_id = ($role === 'student' && !empty($_POST['parent_id'])) ? $_POST['parent_id'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, pin_code, role, grade_level, parent_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $pin, $role, $grade, $parent_id]);
        $message = "<div class='alert success'>User '$username' created successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// 3. FETCH DATA
// Get all users with their parent's name joined
$sql = "SELECT u.*, p.username as parent_name 
        FROM users u 
        LEFT JOIN users p ON u.parent_id = p.id 
        ORDER BY u.role DESC, u.username ASC";
$users = $pdo->query($sql)->fetchAll();

// Get list of Parents for the dropdowns
$parents = $pdo->query("SELECT id, username FROM users WHERE role = 'parent' ORDER BY username ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <style>
        /* --- ADMIN THEME OVERRIDES --- */
        body { 
            background-color: #f4f6f8; 
            background-image: none; /* Remove space stars */
            color: #333 !important; /* Force dark text globally */
            font-family: sans-serif;
        }

        .admin-container { 
            max-width: 1000px; 
            margin: 30px auto; 
            display: grid; 
            grid-template-columns: 1fr 2fr; 
            gap: 20px; 
        }

        .card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            color: #333; /* Ensure text inside card is dark */
        }
        
        /* Force Headings & Labels to be Dark */
        h2 { 
            margin-top: 0; 
            color: #2c3e50; 
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        label { 
            color: #2c3e50; 
            font-weight: bold; 
            display: block; 
            margin-top: 10px;
        }

        /* Form Inputs */
        input, select {
            width: 100%; 
            padding: 8px; 
            margin-top: 5px; 
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            color: #333;
        }

        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        
        th { 
            background: #f8f9fa; 
            color: #555; /* Dark grey header text */
            text-align: left; 
            padding: 12px; 
            border-bottom: 2px solid #ddd;
        }
        
        td { 
            padding: 12px; 
            border-bottom: 1px solid #eee; 
            color: #333; /* Dark text for data */
        }
        
        /* Badges & Buttons */
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; color: white; }
        .role-admin { background: #e74c3c; }
        .role-parent { background: #9b59b6; }
        .role-student { background: #3498db; }

        .btn-edit { background: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
        .btn-submit { width:100%; background:#2ecc71; color:white; border:none; padding:12px; border-radius:5px; cursor:pointer; font-weight:bold; font-size: 16px; margin-top: 10px; }
        .btn-submit:hover { background: #27ae60; }
        
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; color: white; text-align: center; font-weight: bold; }
        .success { background: #2ecc71; } 
        .error { background: #e74c3c; }
    </style>
</head>
<body>
<div class="nav-bar" style="max-width: 1000px; margin: 0 auto 20px; display: flex; gap: 10px;">
    <a href="index.php" style="padding: 10px 20px; text-decoration: none; background: #3498db; color: white; border-radius: 5px; font-weight: bold;">👥 Users</a>
    <a href="games.php" style="padding: 10px 20px; text-decoration: none; color: #555; background: #e0e0e0; border-radius: 5px; font-weight: bold;">🎮 Games</a>
    <a href="../logout.php" style="margin-left: auto; padding: 10px 20px; text-decoration: none; background: #e74c3c; color: white; border-radius: 5px; font-weight: bold;">Log Out</a>
</div>
<div class="admin-container">
    
    <div class="card">
        <h2>Add New User</h2>
        <?php echo $message; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
            <h2 style="margin:0; border:none;">All Users</h2>
            
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>PIN</th>
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
                    <td style="font-family:monospace; font-weight:bold; color:#555;"><?php echo $u['pin_code']; ?></td>
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
        // Only show Grade and Parent options if role is Student
        if (role === 'student') {
            studentFields.style.display = 'block';
        } else {
            studentFields.style.display = 'none';
        }
    }
    // Run on load
    toggleFields();
</script>

</body>
</html>