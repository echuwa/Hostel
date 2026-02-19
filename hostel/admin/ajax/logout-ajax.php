<?php
session_start();
header('Content-Type: application/json');

require_once('../includes/config.php');

try {
    // ============================================
    // DETECT USER TYPE
    // ============================================
    $username = 'Unknown User';
    $user_id = null;
    $user_type = 'unknown';
    $redirect = '../index.php'; // Default redirect to main login page
    
    // Check for Super Admin / Admin (from admins table)
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $username = $_SESSION['username'] ?? 'Admin User';
        $user_type = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1 ? 'superadmin' : 'admin';
        
        // All users redirect to main index.php login page
        $redirect = '../index.php';
    }
    
    // Check for Student (from userregistration table)
    elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['name'] ?? $_SESSION['login'] ?? 'Student User';
        $user_type = 'student';
        $redirect = '../index.php'; // Students always go to main login
    }
    
    // ============================================
    // LOG THE LOGOUT ACTIVITY
    // ============================================
    if (isset($mysqli) && $user_id) {
        
        // Check which table structure exists
        $tableCheck = $mysqli->query("SHOW TABLES LIKE 'audit_logs'");
        
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // Using audit_logs table (for admins/superadmins)
            if ($user_type == 'superadmin' || $user_type == 'admin') {
                $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address, status) VALUES (?, 'logout', ?, ?, 'success')");
                $details = "User logged out from " . ucfirst($user_type) . " panel - Username: $username";
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                
                if ($logStmt) {
                    $logStmt->bind_param("iss", $user_id, $details, $ip_address);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
            
            // For students, check if student_logs table exists
            elseif ($user_type == 'student') {
                // Check if student_logs table exists
                $studentLogTable = $mysqli->query("SHOW TABLES LIKE 'student_logs'");
                if ($studentLogTable && $studentLogTable->num_rows > 0) {
                    $logStmt = $mysqli->prepare("INSERT INTO student_logs (student_id, action, ip_address) VALUES (?, 'logout', ?)");
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                    
                    if ($logStmt) {
                        $logStmt->bind_param("is", $user_id, $ip_address);
                        $logStmt->execute();
                        $logStmt->close();
                    }
                }
            }
        }
    }
    
    // ============================================
    // CLEAR ALL SESSION DATA
    // ============================================
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // ============================================
    // RETURN SUCCESS RESPONSE
    // ============================================
    
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => $redirect,
        'user_type' => $user_type
    ]);
    
} catch (Exception $e) {
    // Log error but still try to logout
    error_log("Logout error: " . $e->getMessage());
    
    // Attempt to destroy session anyway
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out',
        'redirect' => '../index.php'
    ]);
}
?>