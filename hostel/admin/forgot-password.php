<?php
session_start();
include('includes/config.php');

if(isset($_POST['reset'])) {
    $email = trim($_POST['email']);
    
    if(empty($email)) {
        $error = "Email is required";
    } else {
        // Check if email exists
        $stmt = $mysqli->prepare("SELECT id FROM admin WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        
        if($stmt->num_rows > 0) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $update = $mysqli->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $update->bind_param('sss', $token, $expires, $email);
            $update->execute();
            
            // Send reset email (implementation depends on your mail server)
            $reset_link = "http://yourdomain.com/reset-password.php?token=$token";
            $subject = "Password Reset Request";
            $message = "Click the link to reset your password: $reset_link";
            
            // In a real application, use a proper mailer like PHPMailer
            // mail($email, $subject, $message);
            
            $success = "Password reset link has been sent to your email";
        } else {
            $error = "Email not found";
        }
    }
}
?>

<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-page bk-img" style="background-image: url(img/login-bg.jpg);">
        <div class="form-content">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-md-offset-3" style="margin-top:4%">
                        <h1 class="text-center text-bold text-light mt-4x">Forgot Password</h1>
                        <div class="well row pt-2x pb-3x bk-light">
                            <div class="col-md-8 col-md-offset-2">
                                <?php if(isset($error)): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php elseif(isset($success)): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                
                                <form method="post">
                                    <div class="form-group">
                                        <label>Enter your email address</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="reset" class="btn btn-primary btn-block">Reset Password</button>
                                    </div>
                                    <div class="text-center">
                                        Remember your password? <a href="superadmin-login.php">Login here</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>