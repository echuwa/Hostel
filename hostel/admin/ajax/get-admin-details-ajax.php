<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['is_superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once('../includes/config.php');

$adminId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($adminId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
    exit();
}

$stmt = $mysqli->prepare("SELECT id, username, email, reg_date, status, is_superadmin, last_login FROM admins WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'admin' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
}

$stmt->close();
?>