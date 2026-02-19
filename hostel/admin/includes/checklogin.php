<?php
function check_login() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ============================================
    // CHECK IF USER ID EXISTS
    // ============================================
    if (empty($_SESSION['id'])) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "login.php";
        $_SESSION = array(); // Clear all session data
        
        // Destroy the session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        header("Location: http://$host$uri/$extra");
        exit();
    }
    
    // ============================================
    // CHECK ACCOUNT STATUS IN DATABASE
    // ============================================
    require_once('config.php');
    global $mysqli;
    
    // Get ALL user data including username and email
    $stmt = $mysqli->prepare("SELECT username, email, status, is_superadmin, permissions FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $stmt->bind_result($username, $email, $status, $is_superadmin, $permissions);
    $stmt->fetch();
    $stmt->close();
    
    // ============================================
    // CHECK IF ACCOUNT EXISTS
    // ============================================
    if (empty($username)) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "login.php?error=account_not_found";
        session_unset();
        session_destroy();
        header("Location: http://$host$uri/$extra");
        exit();
    }
    
    // ============================================
    // CHECK IF ACCOUNT IS ACTIVE
    // ============================================
    if ($status !== 'active') {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "login.php?error=account_inactive";
        session_unset();
        session_destroy();
        header("Location: http://$host$uri/$extra");
        exit();
    }
    
    // ============================================
    // UPDATE SESSION WITH COMPLETE USER DATA
    // ============================================
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['is_superadmin'] = $is_superadmin;
    $_SESSION['permissions'] = $permissions;
    
    // ============================================
    // BACKWARD COMPATIBILITY - Set 'login' for old code
    // ============================================
    $_SESSION['login'] = $username;  // ← HII NDIO SULUHISHO LA ERROR YAKO!
    
    // ============================================
    // CHECK FOR INACTIVITY TIMEOUT (30 minutes)
    // ============================================
    $inactive = 1800; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "login.php?timeout=1";
        session_unset();
        session_destroy();
        header("Location: http://$host$uri/$extra");
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // ============================================
    // CHECK PAGE PERMISSIONS FOR NON-SUPERADMINS
    // ============================================
    if (empty($_SESSION['is_superadmin'])) {
        $current_page = basename($_SERVER['PHP_SELF']);
        $allowed_pages = ['dashboard.php', 'logout.php', 'profile.php', 'change-password.php'];
        
        if (!in_array($current_page, $allowed_pages)) {
            $permissions = json_decode($_SESSION['permissions'] ?? '{}', true);
            
            // Map pages to permission keys
            $page_permissions = [
                'manage-students.php' => 'manage_students',
                'manage-rooms.php' => 'manage_rooms',
                'manage-complaints.php' => 'manage_complaints',
                'view-reports.php' => 'view_reports',
            ];
            
            if (isset($page_permissions[$current_page])) {
                $required_permission = $page_permissions[$current_page];
                if (empty($permissions[$required_permission])) {
                    $host = $_SERVER['HTTP_HOST'];
                    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                    $extra = "dashboard.php?error=no_permission";
                    header("Location: http://$host$uri/$extra");
                    exit();
                }
            }
        }
    }
}

// ============================================
// HELPER FUNCTION TO CHECK SPECIFIC PERMISSIONS
// ============================================
function has_permission($permission) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!empty($_SESSION['is_superadmin'])) {
        return true; // Superadmins have all permissions
    }
    
    $permissions = json_decode($_SESSION['permissions'] ?? '{}', true);
    return !empty($permissions[$permission]);
}

// ============================================
// NEW HELPER FUNCTION TO GET CURRENT USERNAME SAFELY
// ============================================
function get_current_username() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Return username from session with fallback
    return $_SESSION['username'] ?? $_SESSION['login'] ?? 'Admin';
}

// ============================================
// NEW HELPER FUNCTION TO CHECK IF USER IS LOGGED IN
// ============================================
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return !empty($_SESSION['id']);
}
?>