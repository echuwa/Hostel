<?php
session_start();
include('includes/config.php');

$message = '';
$showForm = true;

if(isset($_POST['send_otp'])) {
    $email = $_POST['email'];
    
    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Invalid email format</div>';
    } else {
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
            
            // In a real application, you would send the OTP via email
            // For this example, we'll just display it
            $message = '<div class="alert alert-success">OTP has been sent to your email</div>';
            $showForm = false;
        } else {
            $message = '<div class="alert alert-danger">Email not found in our system</div>';
        }
        $stmt->close();
    }
} elseif(isset($_POST['verify_otp'])) {
    if($_POST['otp'] == $_SESSION['otp'] && (time() - $_SESSION['otp_time']) < 300) { // 5 minutes expiry
        $email = $_SESSION['otp_email'];
        
        // Get user's password
        $stmt = $mysqli->prepare("SELECT password FROM userregistration WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($password);
        $stmt->fetch();
        $stmt->close();
        
        $message = '<div class="alert alert-success">
            Your password is: <strong>'.$password.'</strong><br>
            Please change it after login.
        </div>';
        
        // Clear OTP session
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
    } else {
        $message = '<div class="alert alert-danger">Invalid or expired OTP</div>';
        $showForm = false; // Show OTP verification form again
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('img/login-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-box {
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }
        .auth-header {
            background: #4361ee;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .auth-body {
            padding: 30px;
        }
        .form-control {
            height: 45px;
            border-radius: 5px;
        }
        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .otp-input {
            letter-spacing: 10px;
            font-size: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <div class="auth-header">
                <h3><i class="fas fa-lock"></i> Password Recovery</h3>
            </div>
            <div class="auth-body">
                <?php echo $message; ?>
                
                <?php if($showForm && !isset($_SESSION['otp'])): ?>
                <!-- Email Form -->
                <form method="post">
                    <div class="form-group">
                        <label>Enter Your Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Your registered email" required>
                        </div>
                    </div>
                    <button type="submit" name="send_otp" class="btn btn-primary btn-block mt-3">
                        Send OTP <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <?php elseif(isset($_SESSION['otp'])): ?>
                <!-- OTP Verification Form -->
                <form method="post">
                    <div class="form-group">
                        <label>Enter 6-digit OTP</label>
                        <input type="text" name="otp" class="form-control otp-input" maxlength="6" required>
                        <small class="text-muted">Check your email for the OTP (valid for 5 minutes)</small>
                    </div>
                    <button type="submit" name="verify_otp" class="btn btn-primary btn-block mt-3">
                        Verify OTP <i class="fas fa-check"></i>
                    </button>
                    <button type="button" onclick="window.location.href='forgot-password.php'" class="btn btn-link btn-block">
                        Resend OTP
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-primary"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Auto focus OTP input
        $('.otp-input').focus();
        
        // Auto move to next input in OTP (if you implement multiple inputs)
        $('.otp-input').on('input', function() {
            if(this.value.length === this.maxLength) {
                $(this).next('.otp-input').focus();
            }
        });
    });
    </script>
</body>
</html>