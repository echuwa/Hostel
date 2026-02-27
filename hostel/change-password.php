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
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/student-modern.css">
    
    <style>
        .password-card-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .password-input-wrapper {
            position: relative;
        }
        .password-input-wrapper i.toggle-pwd {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            padding: 10px;
            transition: color 0.2s;
        }
        .password-input-wrapper i.toggle-pwd:hover {
            color: var(--primary);
        }
        .strength-meter {
            height: 6px;
            background: #f1f5f9;
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                
                <div class="password-card-container">
                    <div class="d-flex align-items-center mb-5 animate__animated animate__fadeInLeft">
                        <div class="stat-icon-box bg-primary text-white rounded-circle me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Security Settings</h2>
                            <p class="section-subtitle">Keep your account safe by updating your password regularly.</p>
                        </div>
                    </div>

                    <div class="card-modern p-4 p-md-5 animate__animated animate__fadeInUp" style="border-radius: 30px;">
                        <?php if($message) echo $message; ?>
                        
                        <div class="bg-light p-3 rounded-4 mb-5 d-flex align-items-center">
                            <i class="fas fa-history text-muted me-3 fs-4"></i>
                            <div>
                                <span class="text-muted small fw-800 text-uppercase d-block">Last Password Update</span>
                                <span class="fw-700 text-dark"><?php echo $last_update ? date('d F, Y \a\t h:i A', strtotime($last_update)) : 'No previous updates recorded'; ?></span>
                            </div>
                        </div>

                        <form method="post" id="change-pwd" onsubmit="return validatePassword()">
                            <div class="row g-4">
                                <!-- Current Password -->
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">CURRENT PASSWORD</label>
                                        <div class="password-input-wrapper">
                                            <input type="password" name="oldpassword" id="oldpassword" class="form-control border-0 bg-transparent fs-5 fw-600 p-0 pe-5" placeholder="Confirm your identity..." required>
                                            <i class="fas fa-eye toggle-pwd" onclick="togglePassword('oldpassword')"></i>
                                        </div>
                                        <div class="form-underline"></div>
                                        <div id="password-match" class="text-danger small mt-2 fw-600"></div>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="col-12">
                                    <div class="form-group-modern mb-2">
                                        <label class="form-label-modern">NEW PASSWORD</label>
                                        <div class="password-input-wrapper">
                                            <input type="password" name="newpassword" id="newpassword" class="form-control border-0 bg-transparent fs-5 fw-600 p-0 pe-5" placeholder="Choose a strong password..." required onkeyup="checkPasswordStrength()">
                                            <i class="fas fa-eye toggle-pwd" onclick="togglePassword('newpassword')"></i>
                                        </div>
                                        <div class="form-underline"></div>
                                    </div>
                                    <div class="strength-meter">
                                        <div id="strength-bar" class="strength-meter-fill"></div>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0 fw-600"><i class="fas fa-info-circle me-1 text-primary"></i> Mix uppercase, lowercase, numbers, and symbols for maximum security.</p>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">CONFIRM NEW PASSWORD</label>
                                        <div class="password-input-wrapper">
                                            <input type="password" name="cpassword" id="cpassword" class="form-control border-0 bg-transparent fs-5 fw-600 p-0 pe-5" placeholder="Repeat your new password..." required>
                                            <i class="fas fa-eye toggle-pwd" onclick="togglePassword('cpassword')"></i>
                                        </div>
                                        <div class="form-underline"></div>
                                        <div id="password-confirm" class="text-danger small mt-2 fw-600"></div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="col-12 mt-5 text-center">
                                    <button type="submit" name="changepwd" class="btn-modern btn-modern-primary d-inline-flex px-5 py-4 shadow-lg w-100 justify-content-center fs-5">
                                        <i class="fas fa-key me-3 mt-1"></i> UPDATE PASSWORD
                                    </button>
                                    <a href="dashboard.php" class="btn btn-link text-muted mt-3 fw-700 text-decoration-none d-block">NEVERMIND, GO BACK</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        const strengthBar = document.getElementById('strength-bar');
        if (!strengthBar) return;

        let strength = 0;
        if (password.length >= 8) strength += 1;
        if (password.match(/[A-Z]/)) strength += 1;
        if (password.match(/[a-z]/)) strength += 1;
        if (password.match(/[0-9]/)) strength += 1;
        if (password.match(/[^A-Za-z0-9]/)) strength += 1;
        
        const width = strength * 20;
        strengthBar.style.width = width + '%';
        
        if (strength <= 2) strengthBar.style.backgroundColor = '#ef4444';
        else if (strength <= 4) strengthBar.style.backgroundColor = '#f59e0b';
        else strengthBar.style.backgroundColor = '#10b981';
    }
    
    // Validate password match
    function validatePassword() {
        const newPassword = document.getElementById('newpassword').value;
        const confirmPassword = document.getElementById('cpassword').value;
        const confirmMsg = document.getElementById('password-confirm');
        
        if (newPassword !== confirmPassword) {
            confirmMsg.textContent = "Passwords do not match!";
            return false;
        }
        
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
