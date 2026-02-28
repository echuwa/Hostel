<?php
session_start();
include('../includes/config.php');
include('../includes/checklogin.php');
check_login();

header('Content-Type: application/json');

if (!isset($_SESSION['is_superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only Super Admin can perform this.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    $stmt = $mysqli->prepare("DELETE FROM userlog WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Access log deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error. Could not delete log.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
