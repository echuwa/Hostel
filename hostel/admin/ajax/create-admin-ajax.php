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

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$status = $_POST['status'] ?? 'active';

// Validation
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit();
}

// Check if username exists
$checkStmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ?");
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Check if email exists
$checkStmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$reg_date = date('Y-m-d H:i:s');

// Insert new admin
$insertStmt = $mysqli->prepare("INSERT INTO admins (username, email, password, status, reg_date, is_superadmin) VALUES (?, ?, ?, ?, ?, 0)");
$insertStmt->bind_param("sssss", $username, $email, $hashedPassword, $status, $reg_date);

if ($insertStmt->execute()) {
    $newAdminId = $insertStmt->insert_id;
    
    // ============================================
    // FIXED: Log the activity - TUMIA user_id na action_type
    // ============================================
    $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address, status) VALUES (?, 'admin_created', ?, ?, 'success')");
    $details = "Created new admin: $username (ID: $newAdminId, Email: $email)";
    $ip = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("iss", $_SESSION['id'], $details, $ip);
    $logStmt->execute();
    $logStmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Admin created successfully', 'admin_id' => $newAdminId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create admin: ' . $mysqli->error]);
}

$insertStmt->close();
?>