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

// Regular request - redirect with Modern UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | Hostel Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/auth-modern.css">
    
    <style>
        .logout_status_circle {
            width: 80px;
            height: 80px;
            background: #f0fdf4;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(22, 163, 74, 0.1);
        }
        
        .countdown_bar {
            height: 4px;
            background: var(--gray-light);
            border-radius: 2px;
            margin-top: 30px;
            overflow: hidden;
            width: 100%;
        }
        
        .countdown_progress {
            height: 100%;
            background: var(--gradient-primary);
            width: 100%;
            transition: width 5s linear;
        }

        .farewell_text {
            font-size: 1.1rem;
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .auth_hero p {
            max-width: 350px !important;
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="auth_wrapper">
        <div class="auth_card" data-aos="zoom-in" data-aos-duration="800">
            <!-- Left Panel (Hero) -->
            <div class="auth_hero">
                <div data-aos="fade-up" data-aos-delay="200">
                    <h2>See you soon!</h2>
                    <p>Your session has been securely closed. We've ensured all your data is saved and protected.</p>
                </div>
                <img src="assets/img/login_hero.png" alt="Logout Illustration" data-aos="fade-up" data-aos-delay="400">
            </div>

            <!-- Right Panel (Content) -->
            <div class="auth_content">
                <div class="auth_header text-center" data-aos="fade-down" data-aos-delay="300">
                    <div class="logout_status_circle">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="auth_title">Logged Out Securely</h1>
                    <p class="auth_subtitle">Thank you for using HostelMS</p>
                </div>

                <div class="text-center" data-aos="fade-up" data-aos-delay="500">
                    <p class="farewell_text">
                        You have been successfully signed out of your account. To maintain security, please close this browser tab if you are on a public computer.
                    </p>

                    <a href="index.php" class="btn-primary-modern">
                        <i class="fas fa-sign-in-alt"></i> Login Again
                    </a>

                    <div class="countdown_bar">
                        <div class="countdown_progress" id="progressBar"></div>
                    </div>
                    <p class="mt-2 small text-muted">Redirecting to home in <span id="timer">5</span> seconds...</p>
                </div>

                <div class="auth_footer" data-aos="fade-up" data-aos-delay="700">
                    &copy; <?php echo date('Y'); ?> <span class="text-primary fw-800">HostelMS</span>. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        // Countdown logic
        let count = 5;
        const timerText = document.getElementById('timer');
        const progressBar = document.getElementById('progressBar');

        // Start progress bar animation after small delay
        setTimeout(() => {
            progressBar.style.width = '0%';
        }, 100);

        const countdown = setInterval(() => {
            count--;
            timerText.innerText = count;
            if (count <= 0) {
                clearInterval(countdown);
                window.location.href = 'index.php';
            }
        }, 1000);
    </script>
</body>
</html>
<?php
exit();
?>
