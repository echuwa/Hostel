<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Redirect to dashboard if already logged in
if (isset($_SESSION['id'])) {
    header("Location: " . (isSuperAdmin() ? 'superadmin-dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if username or email already exists
        $username = $mysqli->real_escape_string($username);
        $email = $mysqli->real_escape_string($email);
        
        $query = "SELECT id FROM users WHERE username = '$username' OR email = '$email' LIMIT 1";
        $result = $mysqli->query($query);
        
        if ($result && $result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin (status will be 'pending' by default)
            $query = "INSERT INTO users (username, email, password, reg_date, is_superadmin, status) 
                      VALUES (?, ?, ?, NOW(), 0, 'pending')";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Your account is pending approval by the super administrator.";
                $_POST = array(); // Clear form
            } else {
                $error = "Registration failed: " . $mysqli->error;
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hostel Management System - Admin Registration">
    <title>Admin Registration | Hostel Management System</title>
    
    <!-- ============================================
         ALL CDNS - HAKUNA 404 ERRORS!
         ============================================ -->
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* ============================================
             MODERN COLOR PALETTE - ZINAZOPENDESA
             ============================================ */
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
            
            --gradient-1: linear-gradient(135deg, #4361ee, #7209b7);
            --gradient-2: linear-gradient(135deg, #f72585, #b5179e);
            --gradient-3: linear-gradient(135deg, #06d6a0, #1b9aaa);
            --gradient-4: linear-gradient(135deg, #ffb703, #fb8500);
            
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: var(--gradient-1);
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

        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Glass Card Effect */
        .register-card {
            width: 100%;
            max-width: 500px;
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

        .register-card:hover {
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

        /* Header with Gradient */
        .register-header {
            background: var(--gradient-1);
            padding: 40px 30px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
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

        .register-header::after {
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

        .register-badge {
            width: 60px;
            height: 60px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
            box-shadow: var(--shadow-lg);
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .register-header h2 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .register-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        /* Body */
        .register-body {
            padding: 40px 30px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
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

        /* Show Password Toggle */
        .show-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            z-index: 2;
            transition: var(--transition);
        }

        .show-password:hover {
            color: var(--primary);
        }

        /* Password Strength */
        .password-strength {
            height: 4px;
            background: var(--gray-light);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: var(--transition);
        }

        /* Terms Checkbox */
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 20px 0;
            padding: 10px;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 12px;
        }

        .terms-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .terms-text {
            font-size: 14px;
            color: var(--gray-dark);
            line-height: 1.5;
        }

        .terms-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .terms-text a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Register Button */
        .btn-register {
            width: 100%;
            height: 56px;
            background: var(--gradient-1);
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

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: var(--gradient-1);
            filter: brightness(1.1);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register i {
            font-size: 18px;
        }

        /* Register Footer */
        .register-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
        }

        .register-footer p {
            margin-bottom: 10px;
            color: var(--gray);
            font-size: 14px;
        }

        .register-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .register-footer a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
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

        .alert-success {
            background: rgba(6, 214, 160, 0.1);
            border-left: 4px solid var(--success);
            color: #0b5e42;
        }

        /* Modals */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: var(--gradient-1);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 20px;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: var(--transition);
        }

        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-body h6 {
            color: var(--dark);
            font-size: 16px;
            font-weight: 700;
            margin: 20px 0 10px;
        }

        .modal-body h6:first-child {
            margin-top: 0;
        }

        .modal-body p {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .modal-footer {
            border-top: 1px solid var(--gray-light);
            padding: 20px;
        }

        .modal-footer .btn {
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
        }

        .modal-footer .btn-secondary {
            background: var(--gray-light);
            border: none;
            color: var(--dark);
        }

        .modal-footer .btn-secondary:hover {
            background: var(--gray);
            color: white;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .register-card {
                margin: 0;
            }
            
            .register-header {
                padding: 30px 20px;
            }
            
            .register-header h2 {
                font-size: 24px;
            }
            
            .register-body {
                padding: 30px 20px;
            }
            
            .form-control {
                height: 50px;
                font-size: 14px;
            }
            
            .btn-register {
                height: 50px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="register-badge">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>Admin Registration</h2>
                <p>Create a new admin account to manage the hostel system</p>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                <!-- Alert Messages -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Login
                    </a>
                    <?php if (isset($_SESSION['id'])): ?>
                    <a href="<?php echo isSuperAdmin() ? 'superadmin-dashboard.php' : 'dashboard.php'; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-th-large me-1"></i> Go to Dashboard
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Registration Form -->
                <form action="" method="post" autocomplete="off" id="registrationForm">
                    <!-- Username Field -->
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Choose a username"
                                   required 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   placeholder="Enter your email"
                                   required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Create a password (min 8 characters)"
                                   required>
                            <i class="fas fa-eye show-password" id="togglePassword"></i>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="form-group">
                        <div class="input-group-custom">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   placeholder="Confirm your password"
                                   required>
                            <i class="fas fa-eye show-password" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    
                    <!-- Terms Checkbox -->
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms" class="terms-text">
                            I agree to the 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> 
                            and 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" name="register" class="btn-register" id="registerButton">
                            <i class="fas fa-user-plus"></i>
                            Create Admin Account
                        </button>
                    </div>
                </form>
                
                <!-- Footer Links -->
                <div class="register-footer">
                    <p>Already have an account? <a href="admin-login.php">Login here</a></p>
                    <p>Super admin? <a href="superadmin-login.php">Switch to super admin portal</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-contract me-2"></i>
                        Terms of Service
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Account Registration</h6>
                    <p>All admin accounts require approval by the super administrator. You must provide accurate information during registration. Any false or misleading information may result in immediate account termination.</p>
                    
                    <h6>2. Account Security</h6>
                    <p>You are responsible for maintaining the confidentiality of your password and account. Notify us immediately of any unauthorized use of your account. We are not liable for any loss or damage arising from your failure to protect your account.</p>
                    
                    <h6>3. Proper Use</h6>
                    <p>You agree to use this system only for its intended purposes of hostel management. Any misuse, including but not limited to unauthorized access, data manipulation, or harassment, will result in account termination.</p>
                    
                    <h6>4. Account Termination</h6>
                    <p>The super administrator reserves the right to suspend or terminate accounts that violate these terms at any time without notice. You may also request account deletion by contacting support.</p>
                    
                    <h6>5. Changes to Terms</h6>
                    <p>We reserve the right to modify these terms at any time. Continued use of the system after changes constitutes acceptance of the new terms.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>
                        Privacy Policy
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Information Collection</h6>
                    <p>We collect your username, email address, and password (hashed) for account creation and authentication. This information is necessary for system functionality and security.</p>
                    
                    <h6>2. Data Usage</h6>
                    <p>Your information will only be used for system administration and will not be shared with third parties except as required by law. We do not sell or rent your personal data.</p>
                    
                    <h6>3. Data Security</h6>
                    <p>We implement appropriate security measures including encryption, access controls, and regular security audits to protect your personal information from unauthorized access, alteration, or destruction.</p>
                    
                    <h6>4. Cookies</h6>
                    <p>We use essential cookies to maintain your session and remember your login state. You can disable cookies in your browser, but this may affect system functionality.</p>
                    
                    <h6>5. Your Rights</h6>
                    <p>You may request access to, correction of, or deletion of your account data by contacting the super administrator. We will respond to such requests within 30 days.</p>
                    
                    <h6>6. Data Retention</h6>
                    <p>We retain your account data for as long as your account is active. Upon account deletion, your data will be permanently removed from our systems within 30 days.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts - ALL CDNs -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });
            
            $('#toggleConfirmPassword').click(function() {
                const confirmField = $('#confirm_password');
                const type = confirmField.attr('type') === 'password' ? 'text' : 'password';
                confirmField.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });
            
            // Password strength indicator
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrengthBar');
                let strength = 0;
                
                // Calculate strength
                if (password.length > 0) strength += 20;
                if (password.length >= 8) strength += 20;
                if (/[A-Z]/.test(password)) strength += 20;
                if (/[0-9]/.test(password)) strength += 20;
                if (/[^A-Za-z0-9]/.test(password)) strength += 20;
                
                strengthBar.css('width', strength + '%');
                
                // Set color based on strength
                if (strength < 40) {
                    strengthBar.css('background', '#ef233c');
                } else if (strength < 80) {
                    strengthBar.css('background', '#ffb703');
                } else {
                    strengthBar.css('background', '#06d6a0');
                }
            });
            
            // Form validation
            $('#registrationForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                const termsChecked = $('#terms').is(':checked');
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('❌ Passwords do not match!');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('❌ Password must be at least 8 characters long!');
                    return false;
                }
                
                if (!termsChecked) {
                    e.preventDefault();
                    alert('❌ You must agree to the Terms of Service and Privacy Policy!');
                    return false;
                }
                
                return true;
            });
            
            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Animate form fields
            $('.form-group').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'animation': `slideIn 0.5s ease-out forwards ${index * 0.1 + 0.3}s`
                });
            });
        });
    </script>
</body>
</html>