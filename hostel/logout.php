<?php
session_start();

// Enable error reporting for debugging (remove in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Load configuration
require_once('includes/config.php');

// ============================================
// DETECT USER TYPE AND LOG ACTIVITY
// ============================================

$user_type = 'unknown';
$user_id = null;
$username = 'Unknown User';

// Check for Super Admin / Admin (from admins table)
if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $username = $_SESSION['username'] ?? 'Admin User';
    $user_type = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1 ? 'superadmin' : 'admin';
}

// Check for Student (from userregistration table)
elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['name'] ?? $_SESSION['login'] ?? 'Student User';
    $user_type = 'student';
}

// ============================================
// LOG LOGOUT ACTIVITY (IF DATABASE EXISTS)
// ============================================

if (isset($mysqli) && $user_id) {
    try {
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
            
            // For students, you might have a different log table
            elseif ($user_type == 'student') {
                // If you have student_logs table, log there
                $logStmt = $mysqli->prepare("INSERT INTO student_logs (student_id, action, ip_address) VALUES (?, 'logout', ?)");
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                
                if ($logStmt) {
                    $logStmt->bind_param("is", $user_id, $ip_address);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail - don't stop logout process
        error_log("Logout logging failed: " . $e->getMessage());
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
// REDIRECT TO LOGIN PAGE
// ============================================

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => 'index.php'
    ]);
    exit();
}

// Regular request - redirect directly to login page
header("Location: index.php");
exit();
?>
