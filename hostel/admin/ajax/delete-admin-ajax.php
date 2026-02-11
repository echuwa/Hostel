<?php
session_start();
header('Content-Type: application/json');

// Check if super admin is logged in
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

$adminId = intval($_GET['id'] ?? 0);

if ($adminId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
    exit();
}

// Get admin details and check if super admin
$checkStmt = $mysqli->prepare("SELECT username, is_superadmin FROM admins WHERE id = ?");
$checkStmt->bind_param("i", $adminId);
$checkStmt->execute();
$checkStmt->bind_result($username, $isSuperadmin);
$checkStmt->fetch();
$checkStmt->close();

if (!$username) {
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit();
}

if ($isSuperadmin == 1) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete super admin']);
    exit();
}

// Start transaction
$mysqli->begin_transaction();

try {
    // ============================================
    // FIXED: Delete related audit logs - TUMIA user_id SI admin_id!
    // ============================================
    $deleteLogsStmt = $mysqli->prepare("DELETE FROM audit_logs WHERE user_id = ?");
    $deleteLogsStmt->bind_param("i", $adminId);
    $deleteLogsStmt->execute();
    $deleteLogsStmt->close();
    
    // Delete the admin
    $deleteStmt = $mysqli->prepare("DELETE FROM admins WHERE id = ?");
    $deleteStmt->bind_param("i", $adminId);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // ============================================
    // FIXED: Log the deletion activity - TUMIA user_id na action_type
    // ============================================
    $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address, status) VALUES (?, 'admin_deleted', ?, ?, 'success')");
    $details = "Deleted admin #$adminId - Username: $username";
    $ip = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("iss", $_SESSION['id'], $details, $ip);
    $logStmt->execute();
    $logStmt->close();
    
    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting admin: ' . $e->getMessage()]);
}
?>