<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('includes/config.php');
// require_once('includes/functions.php');

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Check if email exists
            $stmt = $mysqli->prepare("SELECT id, username FROM admin WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // Generate secure reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 minutes')); // Shorter expiry time
                
                // Store token in database
                $update = $mysqli->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE email = ?");
                $update->bind_param('sss', $token, $expires, $email);
                
                if ($update->execute()) {
                    // Prepare reset link
                    $reset_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . urlencode($token);
                    
                    // Send email using PHPMailer or similar
                    if (sendResetEmail($email, $reset_link)) {
                        $success = "Password reset link has been sent to your email. The link will expire in 30 minutes.";
                    } else {
                        $error = "Failed to send reset email. Please try again later.";
                        // Remove the token if email failed
                        $mysqli->query("UPDATE admin SET reset_token = NULL, reset_expires = NULL WHERE email = '$email'");
                    }
                } else {
                    $error = "Database error. Please try again.";
                }
            } else {
                // Don't reveal whether email exists for security
                $success = "If your email exists in our system, you will receive a password reset link.";
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Email sending function (would be in functions.php)
function sendResetEmail($to, $reset_link) {
    // In a real implementation, use PHPMailer or similar
    $subject = "Password Reset Request";
    $message = "
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <p>You requested a password reset. Click the link below to reset your password:</p>
            <p><a href='$reset_link'>Reset Password</a></p>
            <p>If you didn't request this, please ignore this email.</p>
            <p><small>This link will expire in 30 minutes.</small></p>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: no-reply@yourdomain.com' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Password reset page">
    <title>Forgot Password | Your System Name</title>
    
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Security headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; img-src 'self' data:;">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
</head>
<body>
    <!-- <div class="login-page bk-img" style="background-image: url(img/login-bg.jpg);"> -->
        <div class="form-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card shadow-lg mt-5">
                            <div class="card-body p-4">
                                <h1 class="text-center text-bold mb-4">Forgot Password</h1>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <?php echo htmlspecialchars($error); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php elseif (!empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <?php echo htmlspecialchars($success); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="post" id="resetForm" novalidate>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   required autocomplete="email" autofocus
                                                   placeholder="Enter your registered email">
                                        </div>
                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 mb-3">
                                        <button type="submit" name="reset" class="btn btn-primary btn-lg">
                                            <i class="fas fa-key me-2"></i> Reset Password
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="superadmin-login.php" class="text-decoration-none">
                                            <i class="fas fa-arrow-left me-1"></i> Back to Login
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="text-center text-light mt-3">
                            <small>Need help? Contact <a href="mailto:support@yourdomain.com" class="text-light">support</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            
            const form = document.getElementById('resetForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>
</html>