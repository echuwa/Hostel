<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once('includes/config.php');

// Redirect if already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
        header("Location: admin/superadmin-dashboard.php");
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'student') {
        header("Location: dashboard.php");
    } elseif (isset($_SESSION['id'])) {
        header("Location: admin/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Username/Email and password are required";
    } else {
        // FIRST: Check in admins table (for superadmin and admin)
        $adminStmt = $mysqli->prepare("SELECT id, username, email, password, is_superadmin, status FROM admins WHERE (username = ? OR email = ?)");
        $adminStmt->bind_param("ss", $username, $username);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        
        if ($adminRow = $adminResult->fetch_assoc()) {
            // Found in admins table
            if (password_verify($password, $adminRow['password'])) {
                if ($adminRow['status'] !== 'active') {
                    $error = "Invalid credentials"; // Same message for all
                } else {
                    // Set session for admin/superadmin
                    $_SESSION['id'] = $adminRow['id'];
                    $_SESSION['username'] = $adminRow['username'];
                    $_SESSION['email'] = $adminRow['email'];
                    $_SESSION['is_superadmin'] = $adminRow['is_superadmin'];
                    $_SESSION['last_login'] = time();
                    
                    // Update last login
                    $update = $mysqli->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $update->bind_param("i", $adminRow['id']);
                    $update->execute();
                    $update->close();
                    
                    // Log login
                    $log = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address) VALUES (?, 'login', 'User logged in', ?)");
                    $log->bind_param("is", $adminRow['id'], $_SERVER['REMOTE_ADDR']);
                    $log->execute();
                    $log->close();
                    
                    // Redirect based on role (USER DOESN'T SEE THIS)
                    if ($adminRow['is_superadmin'] == 1) {
                        header("Location: admin/superadmin-dashboard.php");
                    } else {
                        header("Location: admin/dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid credentials"; // Same message for wrong password
            }
        } else {
            // SECOND: Check in userregistration table (for students)
            $studentStmt = $mysqli->prepare("SELECT id, email, password, firstName, lastName, status FROM userregistration WHERE email = ?");
            $studentStmt->bind_param("s", $username);
            $studentStmt->execute();
            $studentResult = $studentStmt->get_result();
            
            if ($studentRow = $studentResult->fetch_assoc()) {
                // Found in students table
                if (password_verify($password, $studentRow['password'])) {
                    // Check student status
                    if (strtolower($studentRow['status'] ?? '') === 'pending') {
                        $error = "Your account is pending admin approval. Please wait for verification.";
                    } else if (strtolower($studentRow['status'] ?? '') === 'blocked') {
                        $error = "Your account has been blocked. Please contact administration.";
                    } else {
                        // Set session for student
                        $_SESSION['user_id'] = $studentRow['id'];
                        $_SESSION['login'] = $studentRow['email'];
                        $_SESSION['name'] = $studentRow['firstName'] . ' ' . $studentRow['lastName'];
                        $_SESSION['user_role'] = 'student';
                        
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Invalid credentials"; // Same message for wrong password
                }
            } else {
                $error = "Invalid credentials"; // Same message for user not found
            }
        }
        $adminStmt->close();
        if (isset($studentStmt)) $studentStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management System - Login</title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --secondary: #7209b7;
            --accent: #f72585;
            --success: #06d6a0;
            --danger: #ef233c;
            --warning: #ffb703;
            --info: #4cc9f0;
            --dark: #1e293b;
            --gray-dark: #334155;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --light: #f8fafc;
            --white: #ffffff;
            
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f72585, #b5179e);
            
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--gradient-1);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: rotate 30s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        /* Glass Card Effect */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: var(--transition);
            animation: slideUp 0.6s ease-out forwards;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .login-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 40px 30px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(20px, -20px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-lg);
        }

        .login-header h2 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        /* Body */
        .login-body {
            padding: 40px 30px;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-danger {
            background: rgba(239, 35, 60, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group-custom {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
            z-index: 2;
            transition: var(--transition);
        }

        .form-control {
            height: 56px;
            border-radius: 16px;
            border: 2px solid var(--gray-light);
            padding: 0 20px 0 50px;
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
            background: var(--white);
            transition: var(--transition);
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            outline: none;
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        /* Password Toggle */
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            z-index: 2;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        /* Remember Me & Forgot Password */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .remember-me label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
        }

        .forgot-password:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Login Button */
        .btn-login {
            width: 100%;
            height: 56px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: var(--transition);
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 18px;
        }

        /* Register Link - ONLY STUDENTS SEE THIS */
        .register-section {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
        }

        .register-section p {
            margin-bottom: 15px;
            color: var(--gray);
            font-size: 14px;
        }

        .student-register {
            display: inline-block;
            padding: 12px 30px;
            background: var(--gradient-2);
            color: white !important;
            border-radius: 40px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .student-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Hidden Links - NOT SHOWN ON PAGE */
        .hidden-links {
            display: none;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                margin: 0;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
            
            .form-control {
                height: 50px;
                font-size: 14px;
            }
            
            .btn-login {
                height: 50px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Error Message - SAME FOR ALL -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form - ONLY FORM, NO CHOICES -->
                <form method="POST" action="">
                    <!-- Username/Email Field -->
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Username or Email"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password"
                                   required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" name="login" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <!-- Register Section - ONLY STUDENT REGISTRATION SHOWN -->
                <div class="register-section">
                    <p>Don't have an account?</p>
                    <a href="registration.php" class="student-register">
                        <i class="fas fa-user-plus me-2"></i>
                        Create Student Account
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.
        </div>
    </div>

    <!-- HIDDEN LINKS - ZIPO KWA AJILI YA ACCESS LAKINI HAZIONEKANI -->
    <div class="hidden-links">
        <a href="admin-register.php">Admin Registration</a>
        <a href="superadmin-login.php">Super Admin</a>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                
                const icon = $(this).find('i');
                if (type === 'password') {
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });
            
            // Animate form fields
            $('.form-group').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'animation': `slideIn 0.5s ease-out forwards ${index * 0.1 + 0.3}s`
                });
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>