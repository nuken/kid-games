<?php
// api/reset_badges.php
require_once '../includes/db.php';
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['redirect'])) { header("Location: ../login.php"); exit; }
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

// 2. Get Data (Support both POST JSON and GET URL)
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true) ?? [];

$token     = $_GET['csrf_token'] ?? ($data['csrf_token'] ?? '');
$target_id = $_GET['user_id']    ?? ($data['user_id'] ?? null);

// 3. CSRF Check
if ($token !== $_SESSION['csrf_token']) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid Security Token']));
}

if (!$target_id) {
    die(json_encode(['status' => 'error', 'message' => 'No User ID provided']));
}

// 4. Authorization
$requester_id = $_SESSION['user_id'];
$requester_role = $_SESSION['role'] ?? 'student';
$allowed = false;

if ($target_id == $requester_id) $allowed = true;
elseif ($requester_role === 'admin') $allowed = true;
elseif ($requester_role === 'parent') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND parent_id = ?");
    $stmt->execute([$target_id, $requester_id]);
    if ($stmt->fetchColumn() > 0) $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden']));
}

// 5. Execute Delete
try {
    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
    $stmt->execute([$target_id]);

    // Handle Redirect (For Parent Dashboard)
    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {
        header("Location: ../parent.php?student_id=" . $target_id);
        exit;
    }

    // Handle JSON (For API/JS)
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Badges reset']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>