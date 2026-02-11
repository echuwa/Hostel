<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['is_superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once('../includes/config.php');

try {
    // Total admins (non-super)
    $totalStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM admins WHERE is_superadmin = 0");
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalAdmins = $totalResult->fetch_assoc()['total'];
    $totalStmt->close();
    
    // Active admins
    $activeStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM admins WHERE is_superadmin = 0 AND status = 'active'");
    $activeStmt->execute();
    $activeResult = $activeStmt->get_result();
    $activeAdmins = $activeResult->fetch_assoc()['total'];
    $activeStmt->close();
    
    // Inactive admins
    $inactiveStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM admins WHERE is_superadmin = 0 AND status != 'active'");
    $inactiveStmt->execute();
    $inactiveResult = $inactiveStmt->get_result();
    $inactiveAdmins = $inactiveResult->fetch_assoc()['total'];
    $inactiveStmt->close();
    
    echo json_encode([
        'success' => true,
        'total_admins' => intval($totalAdmins),
        'active_admins' => intval($activeAdmins),
        'inactive_admins' => intval($inactiveAdmins)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
}
?>