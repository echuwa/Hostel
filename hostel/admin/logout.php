<?php
session_start();

// Enable error reporting for debugging (remove in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Load configuration
require_once('includes/config.php');

// Get user info before destroying session
$username = $_SESSION['username'] ?? 'Unknown User';
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['id'] ?? null;
$is_superadmin = $_SESSION['is_superadmin'] ?? false;

// Log the logout activity if database connection exists
if (isset($mysqli) && $admin_id) {
    try {
        $logStmt = $mysqli->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, 'admin_logout', ?, ?)");
        $details = "User logged out from " . ($is_superadmin ? 'Super Admin' : 'Admin') . " panel - Username: $username";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if ($logStmt) {
            $logStmt->bind_param("iss", $admin_id, $details, $ip_address);
            $logStmt->execute();
            $logStmt->close();
        }
    } catch (Exception $e) {
        // Silently fail - don't stop logout process
        error_log("Logout logging failed: " . $e->getMessage());
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

// Determine redirect based on user type
if ($is_superadmin) {
    $redirect = 'superadmin-login.php';
} else {
    $redirect = 'index.php';
}

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => $redirect
    ]);
    exit();
}

// Regular request - redirect with SweetAlert message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }
        .logout-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="spinner"></div>
        <h2>Logging out...</h2>
        <p>Please wait while we securely log you out.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Logged Out!',
                text: 'You have been successfully logged out.',
                icon: 'success',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                willClose: () => {
                    window.location.href = '<?php echo $redirect; ?>';
                }
            });
        });
    </script>
</body>
</html>
<?php
exit();
?>