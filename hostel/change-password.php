<?php
session_start();
include('includes/config.php');
date_default_timezone_set('Asia/Kolkata');
include('includes/checklogin.php');
check_login();

$user_id = $_SESSION['id'];
$message = '';

// Change password logic
if(isset($_POST['changepwd'])) {
    $old_password = $_POST['oldpassword'];
    $new_password = $_POST['newpassword'];
    $update_date = date('Y-m-d H:i:s');
    
    // Verify old password
    $stmt = $mysqli->prepare("SELECT password FROM userregistration WHERE id = ? AND password = ?");
    $stmt->bind_param('is', $user_id, $old_password);
    $stmt->execute();
    $stmt->store_result();
    
    if($stmt->num_rows > 0) {
        // Update password
        $update = $mysqli->prepare("UPDATE userregistration SET password = ?, passUdateDate = ? WHERE id = ?");
        $update->bind_param('ssi', $new_password, $update_date, $user_id);
        
        if($update->execute()) {
            $message = '<div class="alert alert-success">Password changed successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating password. Please try again.</div>';
        }
        $update->close();
    } else {
        $message = '<div class="alert alert-danger">Old password is incorrect</div>';
    }
    $stmt->close();
}

// Get last update date
$last_update = '';
$stmt = $mysqli->prepare("SELECT passUdateDate FROM userregistration WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($last_update);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Change Password</title>
    
    <!-- Combined CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --danger: #ef233c;
            --success: #4cc9f0;
        }
        
        .password-card {
            max-width: 800px;
            margin: 0 auto;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .password-header {
            background: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        
        .password-body {
            padding: 30px;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .last-updated {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .password-input-group {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <h2 class="page-title mb-4">
                            <i class="fas fa-key"></i> Change Password
                        </h2>
                        
                        <div class="card password-card">
                            <div class="card-header password-header">
                                <h4 class="mb-0">Update Your Password</h4>
                            </div>
                            
                            <div class="card-body password-body">
                                <?php echo $message; ?>
                                
                                <div class="last-updated mb-4">
                                    <i class="fas fa-clock"></i> Last updated: 
                                    <?php echo $last_update ? date('d M Y h:i A', strtotime($last_update)) : 'Never'; ?>
                                </div>
                                
                                <form method="post" id="change-pwd" onsubmit="return validatePassword()">
                                    <div class="form-group mb-4">
                                        <label class="form-label">Current Password</label>
                                        <div class="password-input-group">
                                            <input type="password" name="oldpassword" id="oldpassword" 
                                                   class="form-control" required 
                                                   placeholder="Enter your current password">
                                            <i class="fas fa-eye password-toggle" 
                                               onclick="togglePassword('oldpassword')"></i>
                                        </div>
                                        <div id="password-match" class="text-danger small mt-1"></div>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label class="form-label">New Password</label>
                                        <div class="password-input-group">
                                            <input type="password" name="newpassword" id="newpassword" 
                                                   class="form-control" required 
                                                   placeholder="Enter new password" 
                                                   onkeyup="checkPasswordStrength()">
                                            <i class="fas fa-eye password-toggle" 
                                               onclick="togglePassword('newpassword')"></i>
                                        </div>
                                        <div class="password-strength">
                                            <div id="password-strength-bar" class="password-strength-bar"></div>
                                        </div>
                                        <small class="text-muted">
                                            Password must be at least 8 characters with uppercase, lowercase, number and special character
                                        </small>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label class="form-label">Confirm New Password</label>
                                        <div class="password-input-group">
                                            <input type="password" name="cpassword" id="cpassword" 
                                                   class="form-control" required 
                                                   placeholder="Confirm new password">
                                            <i class="fas fa-eye password-toggle" 
                                               onclick="togglePassword('cpassword')"></i>
                                        </div>
                                        <div id="password-confirm" class="text-danger small mt-1"></div>
                                    </div>
                                    
                                    <div class="form-group text-end">
                                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" name="changepwd" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Combined JS -->
    <script src="js/jquery.min.js+bootstrap.min.js+main.js"></script>
    
    <script>
    // Toggle password visibility
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = input.nextElementSibling;
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    
    // Check password strength
    function checkPasswordStrength() {
        const password = document.getElementById('newpassword').value;
        const strengthBar = document.getElementById('password-strength-bar');
        let strength = 0;
        
        // Check length
        if (password.length >= 8) strength += 1;
        
        // Check for uppercase
        if (password.match(/[A-Z]/)) strength += 1;
        
        // Check for lowercase
        if (password.match(/[a-z]/)) strength += 1;
        
        // Check for numbers
        if (password.match(/[0-9]/)) strength += 1;
        
        // Check for special chars
        if (password.match(/[^A-Za-z0-9]/)) strength += 1;
        
        // Update strength bar
        const width = strength * 20;
        strengthBar.style.width = width + '%';
        
        // Change color based on strength
        if (strength <= 2) {
            strengthBar.style.backgroundColor = 'var(--danger)';
        } else if (strength <= 4) {
            strengthBar.style.backgroundColor = 'orange';
        } else {
            strengthBar.style.backgroundColor = 'var(--success)';
        }
    }
    
    // Validate password match
    function validatePassword() {
        const newPassword = document.getElementById('newpassword').value;
        const confirmPassword = document.getElementById('cpassword').value;
        const confirmMsg = document.getElementById('password-confirm');
        
        // Check if passwords match
        if (newPassword !== confirmPassword) {
            confirmMsg.textContent = "Passwords do not match!";
            return false;
        }
        
        // Check password strength
        if (newPassword.length < 8) {
            confirmMsg.textContent = "Password must be at least 8 characters!";
            return false;
        }
        
        return true;
    }
    
    // Check current password via AJAX
    function checkCurrentPassword() {
        const password = document.getElementById('oldpassword').value;
        const matchMsg = document.getElementById('password-match');
        
        if (password.length === 0) {
            matchMsg.textContent = "";
            return;
        }
        
        $.ajax({
            url: 'check_availability.php',
            type: 'POST',
            data: { oldpassword: password, user_id: <?php echo $user_id; ?> },
            success: function(response) {
                matchMsg.textContent = response;
            }
        });
    }
    
    // Add event listeners
    document.getElementById('oldpassword').addEventListener('blur', checkCurrentPassword);
    document.getElementById('cpassword').addEventListener('keyup', function() {
        const newPassword = document.getElementById('newpassword').value;
        const confirmPassword = this.value;
        const confirmMsg = document.getElementById('password-confirm');
        
        if (confirmPassword.length > 0 && newPassword !== confirmPassword) {
            confirmMsg.textContent = "Passwords do not match!";
        } else {
            confirmMsg.textContent = "";
        }
    });
    </script>
</body>
</html>