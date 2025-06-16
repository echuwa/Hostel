<?php
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Check if super admin is logged in
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: superadmin-login.php");
    exit();
}

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($admin_id > 0) {
    // Verify admin exists and is not super admin
    $stmt = $mysqli->prepare("SELECT id FROM admins WHERE id = ? AND is_superadmin = 0");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        // Delete admin
        $stmt = $mysqli->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        
        if ($stmt->execute()) {
            header("Location: superadmin-dashboard.php?success=Admin account deleted successfully");
        } else {
            header("Location: superadmin-dashboard.php?error=Error deleting admin account");
        }
    } else {
        header("Location: superadmin-dashboard.php?error=Admin not found");
    }
} else {
    header("Location: superadmin-dashboard.php?error=Invalid admin ID");
}
exit();
?>


<?php
function deleteAdmin($adminId) {
    global $mysqli;
    
    // Get admin details first (for logging)
    $admin = $mysqli->query("SELECT * FROM admins WHERE id = $adminId")->fetch_assoc();
    
    // Perform deletion
    $stmt = $mysqli->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $success = $stmt->execute();
    
    if ($success) {
        // Log the activity
        log_activity(
            'admin_delete',
            "Deleted admin {$admin['username']} (#$adminId)",
            [
                'deleted_admin' => $admin['username'],
                'email' => $admin['email'],
                'role' => $admin['role']
            ],
            'admins',
            $adminId
        );
    }
    
    return $success;
}
?>