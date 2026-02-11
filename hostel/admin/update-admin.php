<?php
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; connect-src 'self' https://cdn.jsdelivr.net;");
// Check if super admin is logged in
if (!isset($_SESSION['is_superadmin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$adminId = intval($_POST['id']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$status = $_POST['status'];
$changePassword = isset($_POST['change_password']);

// Validation
if (empty($username) || empty($email) || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Check if email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists (excluding current admin)
$checkStmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
$checkStmt->bind_param("si", $email, $adminId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Start transaction
$mysqli->begin_transaction();

try {
    // Update admin details
    $updateStmt = $mysqli->prepare("UPDATE admins SET username = ?, email = ?, status = ? WHERE id = ?");
    $updateStmt->bind_param("sssi", $username, $email, $status, $adminId);
    $updateStmt->execute();
    
    // Update password if requested
    if ($changePassword && !empty($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $passwordStmt = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $passwordStmt->bind_param("si", $hashedPassword, $adminId);
        $passwordStmt->execute();
        $passwordStmt->close();
    }
    
    // Log the activity
    $logStmt = $mysqli->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $action = 'admin_updated';
    $details = "Updated admin #$adminId ($username)";
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("isss", $_SESSION['admin_id'], $action, $details, $ipAddress);
    $logStmt->execute();
    $logStmt->close();
    
    $mysqli->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
    
} catch (Exception $e) {
    $mysqli->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating admin: ' . $e->getMessage()]);
}

$updateStmt->close();
?>