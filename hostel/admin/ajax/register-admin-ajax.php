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

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
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

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit();
}

// Check if username or email already exists
$checkStmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
$checkStmt->bind_param("ss", $username, $email);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$reg_date = date('Y-m-d H:i:s');

// Insert new admin (pending by default)
$insertStmt = $mysqli->prepare("INSERT INTO admins (username, email, password, reg_date, is_superadmin, status) VALUES (?, ?, ?, ?, 0, 'pending')");
$insertStmt->bind_param("ssss", $username, $email, $password_hash, $reg_date);

if ($insertStmt->execute()) {
    $newAdminId = $insertStmt->insert_id;
    
    // Log the activity
    $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address, status) VALUES (?, 'admin_registered', ?, ?, 'success')");
    $details = "Registered new admin: $username (ID: $newAdminId, Email: $email)";
    $ip = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("iss", $_SESSION['id'], $details, $ip);
    $logStmt->execute();
    $logStmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Admin registered successfully! Account is pending approval.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $mysqli->error]);
}

$insertStmt->close();
?>