<?php
session_start();
include('includes/config.php');

$message = '';
$showForm = true;

if(isset($_POST['send_otp'])) {
    // CSRF PROTECTION
    if(!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">Security token mismatch.</div>';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Check if email exists
        $stmt = $mysqli->prepare("SELECT email FROM userregistration WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        
        if($stmt->num_rows > 0) {
            // Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_time'] = time();
            $_SESSION['otp_step'] = 'verify';
            
            // SECURITY NOTE: In production, send this via email.
            // FOR NOW: We attempt mail, if fails we give dev-mode hint
            $to = $email;
            $subject = "Hostel Security Code: $otp";
            $headers = "From: no-reply@hostelms.com";
            $mail_sent = @mail($to, $subject, "Your security code is: $otp", $headers);
            
            if ($mail_sent) {
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>A security code has been sent to your registered email. Please enter it below.</div>';
            } else {
                // If mail fails (common on local servers), we show a dev-mode notification
                $_SESSION['dev_otp'] = $otp; // Temporarily store for Swall demonstration
                $message = '<div class="alert alert-warning"><i class="fas fa-info-circle me-2"></i>Notice: Mail server not responding. (Dev Mode: Check browser console or use our demo tool)</div>';
            }
            $showForm = false;
        } else {
            $message = '<div class="alert alert-danger">This email is not registered in our system.</div>';
        }
        $stmt->close();
    }
} elseif(isset($_POST['verify_otp'])) {
    if(isset($_POST['otp']) && $_POST['otp'] == $_SESSION['otp'] && (time() - $_SESSION['otp_time']) < 300) {
        $_SESSION['otp_step'] = 'reset';
        $message = '<div class="alert alert-success">Identity verified! Please set your new password.</div>';
    } else {
        $message = '<div class="alert alert-danger">Invalid or expired OTP. Please request a new one.</div>';
    }
} elseif(isset($_POST['reset_password'])) {
    // CSRF PROTECTION
    if(!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">Security token mismatch.</div>';
    } elseif($_SESSION['otp_step'] !== 'reset') {
        $message = '<div class="alert alert-danger">Unauthorized password reset attempt.</div>';
    } else {
        $new_pass = $_POST['new_password'];
        $conf_pass = $_POST['conf_password'];
        
        if($new_pass !== $conf_pass) {
            $message = '<div class="alert alert-danger">Passwords do not match!</div>';
        } elseif(strlen($new_pass) < 6) {
            $message = '<div class="alert alert-danger">Password must be at least 6 characters.</div>';
        } else {
            $email = $_SESSION['otp_email'];
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_date = date('Y-m-d H:i:s');
            
            $stmt = $mysqli->prepare("UPDATE userregistration SET password = ?, passUdateDate = ? WHERE email = ?");
            $stmt->bind_param('sss', $hashed_pass, $update_date, $email);
            
            if($stmt->execute()) {
                $message = '<div class="alert alert-success">Password reset successful! You can now <a href="index.php">login</a>.</div>';
                unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time'], $_SESSION['otp_step']);
                $showForm = false;
            } else {
                $message = '<div class="alert alert-danger">System error. Please contact administrative support.</div>';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password | HostelMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Auth Modern CSS -->
    <link rel="stylesheet" href="css/auth-modern.css">
    
    <style>
        .otp-display {
            background: #f0f9ff;
            border: 2px dashed #bae6fd;
            padding: 20px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .otp-code {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 8px;
            display: block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="auth_wrapper" style="max-width: 1000px;">
        <div class="auth_card" data-aos="zoom-in" data-aos-duration="1000">
            <!-- Left Panel - Recovery Illustration -->
            <div class="auth_hero">
                <div class="auth_hero_content" data-aos="fade-right" data-aos-delay="200">
                    <h2>Account Recovery</h2>
                    <p>Don't worry, it happens to the best of us. Let's get you back into your room.</p>
                    <img src="assets/img/recovery_hero.png" alt="Recovery Hero" style="max-width: 320px;">
                </div>
            </div>

            <!-- Right Panel - Form -->
            <div class="auth_content">
                <div class="auth_header" data-aos="fade-up" data-aos-delay="300">
                    <h1 class="auth_title">Forgot Password?</h1>
                    <p class="auth_subtitle">Securely recover your access credentials.</p>
                </div>

                <!-- Messages -->
                <div data-aos="fade-up" data-aos-delay="350">
                    <?php echo $message; ?>
                </div>

                <?php if($showForm && !isset($_SESSION['otp_step'])): ?>
                <!-- Step 1: Email Form -->
                <form method="post">
                    <?php csrf_field(); ?>
                    <div class="input_container" data-aos="fade-up" data-aos-delay="400">
                        <label class="form-label">Registered Email</label>
                        <div class="input-group-modern">
                            <i class="fas fa-envelope-circle-check"></i>
                            <input type="email" name="email" placeholder="email@example.com" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="auth_actions" data-aos="fade-up" data-aos-delay="500">
                        <button type="submit" name="send_otp" class="btn-primary-modern">
                            <span>Get Security OTP</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>

                <?php elseif(isset($_SESSION['otp_step']) && $_SESSION['otp_step'] == 'verify'): ?>
                <!-- Step 2: OTP Verification -->
                <form method="post">
                    <?php csrf_field(); ?>
                    <div class="input_container" data-aos="fade-up" data-aos-delay="450">
                        <label class="form-label">Enter 6-Digit OTP</label>
                        <div class="input-group-modern">
                            <i class="fas fa-shield-keyhole"></i>
                            <input type="text" name="otp" maxlength="6" placeholder="000000" required 
                                   style="text-align: center; letter-spacing: 5px; font-size: 1.2rem;">
                        </div>
                        <p class="small text-muted mt-2 text-center">Check your email for the code.</p>
                    </div>

                    <div class="auth_actions" data-aos="fade-up" data-aos-delay="550">
                        <button type="submit" name="verify_otp" class="btn-primary-modern">
                            <span>Verify Identity</span>
                            <i class="fas fa-shield-check"></i>
                        </button>
                    </div>
                    
                    <div class="text-center mt-3" data-aos="fade-up" data-aos-delay="600">
                        <button type="button" onclick="window.location.href=\'forgot-password.php\'" class="btn-link-modern">
                            <i class="fas fa-rotate me-1"></i> Resend OTP
                        </button>
                    </div>
                </form>

                <?php elseif(isset($_SESSION['otp_step']) && $_SESSION['otp_step'] == 'reset'): ?>
                <!-- Step 3: Reset Password Form -->
                <form method="post">
                    <?php csrf_field(); ?>
                    <div class="input_container" data-aos="fade-up" data-aos-delay="400">
                        <label class="form-label">New Password</label>
                        <div class="input-group-modern">
                            <i class="fas fa-lock text-primary"></i>
                            <input type="password" name="new_password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="input_container" data-aos="fade-up" data-aos-delay="450">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group-modern">
                            <i class="fas fa-lock text-success"></i>
                            <input type="password" name="conf_password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="auth_actions" data-aos="fade-up" data-aos-delay="550">
                        <button type="submit" name="reset_password" class="btn-primary-modern">
                            <span>Reset Password</span>
                            <i class="fas fa-key"></i>
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="auth_footer" data-aos="fade-up" data-aos-delay="700">
                    <a href="index.php" class="auth_back_link">
                        <i class="fas fa-chevron-left"></i>
                        <span>Back to Sign In</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        <?php if(isset($_SESSION['dev_otp'])): ?>
        Swal.fire({
            title: 'Development Access (Demo)',
            html: 'Kwa ajili ya majaribio, tumia code hii: <b><?php echo $_SESSION['dev_otp']; ?></b> (Hii inaonekana hapa tu kwa sababu mail server haijatengenezwa)',
            icon: 'info',
            confirmButtonColor: '#4361ee'
        });
        <?php unset($_SESSION['dev_otp']); endif; ?>
    </script>
</body>
</html>