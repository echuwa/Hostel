<?php
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Redirect to dashboard if already logged in
if (isset($_SESSION['id'])) {
    header("Location: " . (isSuperAdmin() ? 'superadmin/dashboard.php' : 'admin/dashboard.php'));
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
            $is_superadmin = 0;
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
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Hostel Management System - Admin Registration">
    <meta name="author" content="">
    <title>Admin Registration | Hostel Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.png" type="image/png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #ef233c;
            --success-color: #4cc9f0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .register-container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            background: url('img/register-bg.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            overflow: hidden;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        
        .register-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 5px solid var(--accent-color);
        }
        
        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .register-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 28px;
        }
        
        .register-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding-left: 45px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.15);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #adb5bd;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .form-control:focus + .input-icon {
            color: var(--accent-color);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            border: none;
            height: 50px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(72, 149, 239, 0.3);
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 149, 239, 0.4);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .register-footer a {
            color: var(--accent-color);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .register-footer a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            padding: 12px 15px;
        }
        
        .alert-danger {
            background-color: rgba(239, 35, 60, 0.1);
            border-color: rgba(239, 35, 60, 0.2);
            color: var(--danger-color);
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            border-color: rgba(76, 201, 240, 0.2);
            color: var(--success-color);
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 15px;
            cursor: pointer;
            color: #adb5bd;
            transition: color 0.3s;
        }
        
        .show-password:hover {
            color: var(--accent-color);
        }
        
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            background: #dc3545;
            transition: width 0.3s, background 0.3s;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .register-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .terms-checkbox input {
            margin-top: 3px;
            margin-right: 10px;
        }
        
        .terms-text {
            font-size: 13px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-badge">
                <i class="fas fa-user-plus"></i>
            </div>
            
            <div class="register-header">
                <h2>Admin Registration</h2>
                <p>Create a new admin account</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" autocomplete="off" id="registrationForm">
                    <div class="form-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Email address" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Password (min 8 characters)" required>
                        <i class="fas fa-eye show-password" id="togglePassword"></i>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Confirm Password" required>
                        <i class="fas fa-eye show-password" id="toggleConfirmPassword"></i>
                    </div>
                    
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms" class="terms-text">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> 
                            and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="register" class="btn btn-block btn-register" id="registerButton">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </div>
                </form>
                
                <div class="register-footer">
                    <p>Already have an account? <a href="admin-login.php">Login here</a></p>
                    <p>Super admin? <a href="superadmin-login.php">Switch to super admin portal</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Account Registration</h6>
                    <p>All admin accounts require approval by the super administrator. You must provide accurate information during registration.</p>
                    
                    <h6>2. Account Security</h6>
                    <p>You are responsible for maintaining the confidentiality of your password and account. Notify us immediately of any unauthorized use.</p>
                    
                    <h6>3. Proper Use</h6>
                    <p>You agree to use this system only for its intended purposes of hostel management.</p>
                    
                    <h6>4. Account Termination</h6>
                    <p>The super administrator reserves the right to suspend or terminate accounts that violate these terms.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Information Collection</h6>
                    <p>We collect your username, email address, and password (hashed) for account creation and authentication.</p>
                    
                    <h6>2. Data Usage</h6>
                    <p>Your information will only be used for system administration and will not be shared with third parties.</p>
                    
                    <h6>3. Security Measures</h6>
                    <p>We implement appropriate security measures to protect your personal information.</p>
                    
                    <h6>4. Account Data</h6>
                    <p>You may request access to or deletion of your account data by contacting the super administrator.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const passwordFieldType = passwordField.attr('type');
                passwordField.attr('type', passwordFieldType === 'password' ? 'text' : 'password');
                $(this).toggleClass('fa-eye fa-eye-slash');
            });
            
            $('#toggleConfirmPassword').click(function() {
                const confirmField = $('#confirm_password');
                const confirmFieldType = confirmField.attr('type');
                confirmField.attr('type', confirmFieldType === 'password' ? 'text' : 'password');
                $(this).toggleClass('fa-eye fa-eye-slash');
            });
            
            // Password strength indicator
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrengthBar');
                let strength = 0;
                
                if (password.length > 0) strength += 20;
                if (password.length >= 8) strength += 20;
                if (/[A-Z]/.test(password)) strength += 20;
                if (/[0-9]/.test(password)) strength += 20;
                if (/[^A-Za-z0-9]/.test(password)) strength += 20;
                
                strengthBar.css('width', strength + '%');
                
                // Change color based on strength
                if (strength < 40) {
                    strengthBar.css('background', '#dc3545'); // Red
                } else if (strength < 80) {
                    strengthBar.css('background', '#ffc107'); // Yellow
                } else {
                    strengthBar.css('background', '#28a745'); // Green
                }
            });
            
            // Form validation
            $('#registrationForm').submit(function() {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (password !== confirmPassword) {
                    alert('Passwords do not match');
                    return false;
                }
                
                if (password.length < 8) {
                    alert('Password must be at least 8 characters');
                    return false;
                }
                
                if (!$('#terms').is(':checked')) {
                    alert('You must agree to the terms and conditions');
                    return false;
                }
                
                return true;
            });
            
            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Add animation to form elements
            $('.form-group').each(function(i) {
                $(this).css({
                    'opacity': '0',
                    'animation': `fadeIn 0.5s ease-out forwards ${i * 0.1 + 0.3}s`
                });
            });
        });
    </script>
</body>
</html>