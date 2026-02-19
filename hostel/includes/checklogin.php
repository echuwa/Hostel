<?php
/**
 * Student Login Check Function
 * Verifies student session and redirects to login if not authenticated
 */

function check_login() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ============================================
    // CHECK IF STUDENT IS LOGGED IN
    // ============================================
    if (empty($_SESSION['user_id']) && empty($_SESSION['id'])) {
        // Not logged in - redirect to login page
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "index.php";
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
    
    // Determine if this is a student or admin
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
    $is_student = !empty($_SESSION['user_id']);
    
    if ($is_student) {
        // Verify student account exists and is active
        $stmt = $mysqli->prepare("SELECT id, firstName, lastName, email FROM userregistration WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($id, $firstName, $lastName, $email);
        $stmt->fetch();
        $stmt->close();
        
        if (empty($id)) {
            // Student account not found
            $host = $_SERVER['HTTP_HOST'];
            $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $extra = "index.php?error=account_not_found";
            session_unset();
            session_destroy();
            header("Location: http://$host$uri/$extra");
            exit();
        }
        
        // Update session with complete user data
        $_SESSION['user_id'] = $id;
        $_SESSION['name'] = $firstName . ' ' . $lastName;
        $_SESSION['login'] = $email;
        $_SESSION['user_role'] = 'student';
    }
    
    // ============================================
    // CHECK FOR INACTIVITY TIMEOUT (30 minutes)
    // ============================================
    $inactive = 1800; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "index.php?timeout=1";
        session_unset();
        session_destroy();
        header("Location: http://$host$uri/$extra");
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return !empty($_SESSION['user_id']);
}

/**
 * Get current user name safely
 */
function get_current_username() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Return name from session with fallback
    return $_SESSION['name'] ?? $_SESSION['login'] ?? 'Student';
}

/**
 * Redirect to login if not authenticated
 */
function require_login() {
    check_login();
}
?>
