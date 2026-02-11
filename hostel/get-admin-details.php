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

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
    exit();
}

$adminId = intval($_GET['id']);

$stmt = $mysqli->prepare("SELECT id, username, email, reg_date, status, is_superadmin, last_login FROM admins WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'admin' => $row]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
}

$stmt->close();
?>