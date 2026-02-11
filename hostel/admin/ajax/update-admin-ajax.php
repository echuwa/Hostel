<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['is_superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once('../includes/config.php');
require_once('../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$adminId = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$status = $_POST['status'] ?? '';
$changePassword = isset($_POST['change_password']);
$newPassword = $_POST['new_password'] ?? '';

if ($adminId <= 0 || empty($username) || empty($email) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Check if trying to edit super admin
$checkSuperStmt = $mysqli->prepare("SELECT is_superadmin FROM admins WHERE id = ?");
$checkSuperStmt->bind_param("i", $adminId);
$checkSuperStmt->execute();
$checkSuperStmt->bind_result($isSuperadmin);
$checkSuperStmt->fetch();
$checkSuperStmt->close();

if ($isSuperadmin == 1) {
    echo json_encode(['success' => false, 'message' => 'Cannot edit super admin']);
    exit();
}

// Check if email exists for other users
$checkStmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
$checkStmt->bind_param("si", $email, $adminId);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists for another admin']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

$mysqli->begin_transaction();

try {
    // Update admin details
    $updateStmt = $mysqli->prepare("UPDATE admins SET username = ?, email = ?, status = ? WHERE id = ?");
    $updateStmt->bind_param("sssi", $username, $email, $status, $adminId);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Update password if requested
    if ($changePassword && !empty($newPassword)) {
        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $passwordStmt = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $passwordStmt->bind_param("si", $hashedPassword, $adminId);
        $passwordStmt->execute();
        $passwordStmt->close();
    }
    
    // ============================================
    // FIXED: Log the activity - TUMIA user_id na action_type
    // ============================================
    $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address, status) VALUES (?, 'admin_updated', ?, ?, 'success')");
    $details = "Updated admin #$adminId - Username: $username, Status: $status" . ($changePassword ? ', Password changed' : '');
    $ip = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("iss", $_SESSION['id'], $details, $ip);
    $logStmt->execute();
    $logStmt->close();
    
    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>