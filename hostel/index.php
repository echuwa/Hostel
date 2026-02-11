<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require('includes/config.php');

// Pre-fill email if coming from registration
$prefill_email = $_SESSION['email_for_login'] ?? '';
$registration_number = $_SESSION['registration_number'] ?? '';
unset($_SESSION['email_for_login']);
unset($_SESSION['registration_number']);

if(isset($_POST['login'])) {
    // Use null coalescing operator to handle undefined array keys
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    try {
        // Find the user by email only
        $stmt = $mysqli->prepare("SELECT id, email, password FROM userregistration WHERE email=?");
        if(!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        
        $stmt->bind_param('s', $email);
        if(!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->bind_result($id, $db_email, $db_password);
        $rs = $stmt->fetch();
        $stmt->close();
        
        // Verify the password
        if($rs && password_verify($password, $db_password ?? '')) {
            $_SESSION['id'] = $id;
            $_SESSION['login'] = $email;
            
            try {
                $uid = $_SESSION['id'];
                $uemail = $_SESSION['login'];
                $ip = $_SERVER['REMOTE_ADDR'];
                
                // Geo location
                $city = 'Unknown';
                $country = 'Unknown';
                try {
                    $geopluginURL = 'http://www.geoplugin.net/php.gp?ip='.$ip;
                    $addrDetailsArr = unserialize(file_get_contents($geopluginURL));
                    $city = $addrDetailsArr['geoplugin_city'] ?? 'Unknown';
                    $country = $addrDetailsArr['geoplugin_countryName'] ?? 'Unknown';
                } catch (Exception $e) {
                    error_log("GeoPlugin error: " . $e->getMessage());
                }
                
                $log = $mysqli->prepare("INSERT INTO userLog(userId, userEmail, userIp, city, country) VALUES(?, ?, ?, ?, ?)");
                $log->bind_param('issss', $uid, $uemail, $ip, $city, $country);
                if($log->execute()) {
                    header("Location: dashboard.php");
                    exit();
                } else {
                    throw new Exception("Log insert failed: " . $log->error);
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password. Please try again or <a href='registration.php'>register</a> if you don't have an account.";
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error = "An error occurred during login. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Student Hostel Management System">
    <meta name="author" content="">
    <meta name="theme-color" content="#3e454c">
    <title>Student Hostel - Login</title>
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .registration-number-alert {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .registration-number {
            font-size: 1.2em;
            color: #0c5460;
            background-color: #bee5eb;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-5 col-md-7 col-sm-10">
                    <div class="card shadow-lg animate__animated animate__fadeIn">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h3><i class="fas fa-user-circle me-2"></i> Student Hostel Management System</h3>
                        </div>
                        <div class="card-body p-4">
                            <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>
                            
                            <?php if(!empty($registration_number)): ?>
                            <div class="registration-number-alert">
                                <i class="fas fa-id-card me-2"></i> Your registration number:
                                <div class="registration-number"><?php echo htmlspecialchars($registration_number); ?></div>
                                <small class="text-muted d-block mt-2">Please save this number for future reference.</small>
                            </div>
                            <?php endif; ?>
                            
                            <form action="" method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i> Email Address
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="Enter your email" required
                                               value="<?php echo htmlspecialchars($prefill_email); ?>">
                                        <div class="invalid-feedback">
                                            Please enter your email address.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i> Password
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Enter password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Please enter your password.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                    <a href="forgot-password.php" class="float-end">Forgot password?</a>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="login" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-1"></i> Login
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p class="mb-1">Don't have an account?</p>
                                <a href="registration.php" class="btn btn-success">
                                    <i class="fas fa-user-plus me-1"></i> Create Account
                                </a>
                            </div>
                        </div>
                        <div class="card-footer text-center text-muted py-2">
                            <small>&copy; <?php echo date('Y'); ?> Student Hostel Management System</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                const passwordInput = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
        
        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {http://localhost/Hostel-Management-Syste-Updated-Code/hostel/admin/dashboard.php
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>