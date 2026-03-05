<?php
session_start();
require_once('config.php');

// Security Check
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $block = trim($_POST['block'] ?? '');
    $perms = $_POST['perms'] ?? []; // Receive selected roles

    if (empty($username) || empty($email) || empty($password) || empty($block)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit();
    }

    // Default permissions if none provided
    if (empty($perms)) {
        $perms = [
            'manage_students' => true,
            'manage_rooms' => true,
            'manage_complaints' => true,
            'view_reports' => true
        ];
    }
    $permissionsJson = json_encode($perms);
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert
    $stmt = $mysqli->prepare("INSERT INTO admins (username, email, password, is_superadmin, assigned_block, permissions, status, reg_date) VALUES (?, ?, ?, 0, ?, ?, 'active', NOW())");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $block, $permissionsJson);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Debtor account created and deployed to Block ' . $block]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'System error: ' . $mysqli->error]);
    }
    $stmt->close();
}
?>
