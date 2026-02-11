<?php
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; connect-src 'self' https://cdn.jsdelivr.net;");
// Check if super admin is logged in
if (!isset($_SESSION['is_superadmin'])) {
    if (isset($_POST['confirm'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    } else {
        header("Location: superadmin-login.php");
    }
    exit();
}

if (!isset($_GET['id'])) {
    if (isset($_POST['confirm'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
    } else {
        header("Location: superadmin-dashboard.php?error=Admin ID is required");
    }
    exit();
}

$adminId = intval($_GET['id']);

// Prevent deleting super admins
$checkStmt = $mysqli->prepare("SELECT username, is_superadmin FROM admins WHERE id = ?");
$checkStmt->bind_param("i", $adminId);
$checkStmt->execute();
$checkStmt->bind_result($username, $isSuperadmin);
$checkStmt->fetch();
$checkStmt->close();

if (!$username) {
    if (isset($_POST['confirm'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
    } else {
        header("Location: superadmin-dashboard.php?error=Admin not found");
    }
    exit();
}

if ($isSuperadmin == 1) {
    if (isset($_POST['confirm'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cannot delete super admin']);
    } else {
        header("Location: superadmin-dashboard.php?error=Cannot delete super admin");
    }
    exit();
}

// Handle AJAX request
if (isset($_POST['confirm'])) {
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Delete admin
        $deleteStmt = $mysqli->prepare("DELETE FROM admins WHERE id = ?");
        $deleteStmt->bind_param("i", $adminId);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // Log the activity
        $logStmt = $mysqli->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $action = 'admin_deleted';
        $details = "Deleted admin #$adminId ($username)";
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("isss", $_SESSION['admin_id'], $action, $details, $ipAddress);
        $logStmt->execute();
        $logStmt->close();
        
        $mysqli->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error deleting admin: ' . $e->getMessage()]);
    }
    
    exit();
}

// Handle regular GET request (fallback)
$deleteStmt = $mysqli->prepare("DELETE FROM admins WHERE id = ?");
$deleteStmt->bind_param("i", $adminId);

if ($deleteStmt->execute()) {
    // Log the activity
    $logStmt = $mysqli->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $action = 'admin_deleted';
    $details = "Deleted admin #$adminId ($username)";
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("isss", $_SESSION['admin_id'], $action, $details, $ipAddress);
    $logStmt->execute();
    $logStmt->close();
    
    header("Location: superadmin-dashboard.php?success=Admin deleted successfully");
} else {
    header("Location: superadmin-dashboard.php?error=Error deleting admin");
}

$deleteStmt->close();
?>