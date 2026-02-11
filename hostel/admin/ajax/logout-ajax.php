<?php
session_start();
header('Content-Type: application/json');

require_once('../includes/config.php');

try {
    $username = $_SESSION['username'] ?? 'Unknown User';
    $user_id = $_SESSION['id'] ?? null;
    $is_superadmin = $_SESSION['is_superadmin'] ?? false;
    
    // ============================================
    // FIXED: Log the logout activity - TUMIA user_id na action_type
    // ============================================
    if (isset($mysqli) && $user_id) {
        $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address, status) VALUES (?, 'admin_logout', ?, ?, 'success')");
        $details = "User logged out from " . ($is_superadmin ? 'Super Admin' : 'Admin') . " panel - Username: $username";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if ($logStmt) {
            $logStmt->bind_param("iss", $user_id, $details, $ip_address);
            $logStmt->execute();
            $logStmt->close();
        }
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    $redirect = $is_superadmin ? 'superadmin-login.php' : '../index.php';
    
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => $redirect
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error during logout: ' . $e->getMessage()
    ]);
}
?>