<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ============================================
// SECURITY HEADERS - HAKUNA NEW LINES!
// ============================================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ============================================
// CSP HEADER - LINE MOJA TU! HAKUNA NEW LINES!
// ============================================
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self' https://cdn.jsdelivr.net;");

// Check if already logged in and redirect to appropriate dashboard
if (isset($_SESSION['id'])) {
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
        header("Location: superadmin-dashboard.php");
        exit();
    } else {
        header("Location: dashboard.php");
        exit();
    }
}

require_once('includes/config.php');
require_once('includes/auth.php');

$error = '';

// Function to verify admin credentials (both superadmin and regular)
function verifyAdminCredentials($mysqli, $username, $password) {
    $stmt = $mysqli->prepare("SELECT id, username, email, password, reg_date, is_superadmin FROM admins 
                            WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
    if (!$stmt) {
        error_log("Database preparation error: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("ss", $username, $username);
    
    if (!$stmt->execute()) {
        error_log("Database execution error: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

// Function to log failed login attempts
function logFailedLoginAttempt($mysqli, $username, $ip) {
    try {
        // First check if login_attempts table exists
        $tableCheck = $mysqli->query("SHOW TABLES LIKE 'login_attempts'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $stmt = $mysqli->prepare("INSERT INTO login_attempts (username, ip_address, attempt_time) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $ip);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Input validation
    if (empty($username) || empty($password)) {
        $error = "Username/Email and password are required";
    } elseif (strlen($username) > 50 || strlen($password) > 100) {
        $error = "Invalid input length";
    } else {
        $user = verifyAdminCredentials($mysqli, $username, $password);
        
        if ($user) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_superadmin'] = $user['is_superadmin'];
            $_SESSION['reg_date'] = $user['reg_date'];
            $_SESSION['last_login'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Set secure cookie parameters
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + 3600, // 1 hour
                    'path' => $cookieParams['path'],
                    'domain' => $cookieParams['domain'],
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
            
            // Redirect based on user type
            if ($user['is_superadmin'] == 1) {
                header("Location: superadmin-dashboard.php");
                exit();
            } else {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid credentials or account not active";
            logFailedLoginAttempt($mysqli, $username, $_SERVER['REMOTE_ADDR']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Admin Portal Login">
    <meta name="author" content="Your Name">
    <title>Admin Portal Login</title>
    
    <!-- 
        ====================================================
        SECURITY HEADERS ZIKO KWENYE HTTP - HAPA HAKUNA META
        ====================================================
    -->
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #3A0CA3;
            --secondary-color: #4361EE;
            --danger-color: #EF233C;
            --light-color: #F8F9FA;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 700;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            padding-left: 45px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 13px;
            color: #6c757d;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            height: 50px;
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 12, 163, 0.3);
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 13px;
            cursor: pointer;
            color: #6c757d;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        
        .show-password:hover {
            color: var(--secondary-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 35, 60, 0.1);
            border-color: rgba(239, 35, 60, 0.2);
            color: var(--danger-color);
            border-radius: 8px;
        }
        
        @media (max-width: 576px) {
            .login-card {
                border-radius: 0;
            }
            
            body {
                padding: 0;
            }
        }
    </style>
    
    <!-- Disable source map fetching -->
    <script>
        // DISABLE SOURCE MAPS - FIXED VERSION
        (function() {
            // Prevent source map requests
            if (window.fetch) {
                const originalFetch = window.fetch;
                window.fetch = function(url, options) {
                    if (url && typeof url === 'string' && 
                        (url.includes('.map') || url.includes('sourcemap'))) {
                        console.log('Blocked source map request:', url);
                        return Promise.reject(new Error('Source maps disabled'));
                    }
                    return originalFetch.call(this, url, options);
                };
            }
            
            if (window.XMLHttpRequest) {
                const originalOpen = XMLHttpRequest.prototype.open;
                XMLHttpRequest.prototype.open = function(method, url) {
                    if (url && typeof url === 'string' && 
                        (url.includes('.map') || url.includes('sourcemap'))) {
                        console.log('Blocked source map XHR:', url);
                        return;
                    }
                    return originalOpen.apply(this, arguments);
                };
            }
        })();
    </script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-card">
                    <div class="login-header">
                        <h2><i class="bi bi-shield-lock"></i> Admin Portal</h2>
                        <p class="mb-0">Restricted access only</p>
                    </div>
                    <div class="login-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="" autocomplete="off">
                            <div class="mb-3 position-relative">
                                <i class="bi bi-person-fill input-icon"></i>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required autofocus>
                            </div>
                            <div class="mb-3 position-relative">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <i class="bi bi-eye-fill show-password" id="togglePassword" title="Show Password"></i>
                            </div>
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" name="login" class="btn btn-login btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </div>
                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript - FIXED EVENT LISTENERS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DOM Content Loaded - ALL EVENT LISTENERS HERE
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const password = document.getElementById('password');
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.classList.toggle('bi-eye-fill');
                    this.classList.toggle('bi-eye-slash-fill');
                });
            }

            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            // Auto-focus on username field
            const username = document.getElementById('username');
            if (username) {
                setTimeout(function() {
                    username.focus();
                }, 100);
            }
        });

        // REMOVE HIVI KWENYE CONSOLE IKIWA HAUHITAJI
        // console.log('%cSECURITY WARNING', 'color: red; font-size: 24px; font-weight: bold;');
        // console.log('%cThis is a restricted admin console. Unauthorized access is prohibited.', 'font-size: 16px;');
        
        // UKIWA NAHAJA YA CONSOLE WARNING - FANYA HIVI:
        (function() {
            if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                console.log('%cSECURITY WARNING', 'color: red; font-size: 24px; font-weight: bold;');
                console.log('%cThis is a restricted admin console. Unauthorized access is prohibited.', 'font-size: 16px;');
            }
        })();
    </script>
</body>
</html>