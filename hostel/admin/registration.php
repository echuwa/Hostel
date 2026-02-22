<?php
session_start();
include('includes/config.php');

if(isset($_POST['submit'])) {
    // Generate registration number
    $year = date('y'); // Last two digits of current year
    $quarter = ceil(date('n') / 3); // Get current quarter (1-4)
    $prefix = "T{$year}-0{$quarter}-";
    
    // Get the highest existing registration number for this quarter
    $result = $mysqli->query("SELECT MAX(regNo) FROM userregistration WHERE regNo LIKE '$prefix%'");
    $row = $result->fetch_array();
    $lastRegNo = $row[0] ?? null;
    
    if ($lastRegNo) {
        $lastNumber = intval(substr($lastRegNo, strlen($prefix)));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $regno = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    
    // Sanitize inputs with proper null checks
    $fname = isset($_POST['fname']) ? htmlspecialchars(trim($_POST['fname'])) : '';
    $mname = isset($_POST['mname']) ? htmlspecialchars(trim($_POST['mname'])) : '';
    $lname = isset($_POST['lname']) ? htmlspecialchars(trim($_POST['lname'])) : '';
    $gender = $_POST['gender'] ?? '';
    $contactno = isset($_POST['contact']) ? preg_replace('/[^0-9]/', '', $_POST['contact']) : '';
    $emailid = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    
    // Validate inputs
    $errors = [];
    if(empty($fname)) $errors[] = "First name is required";
    if(empty($lname)) $errors[] = "Last name is required";
    if(empty($gender)) $errors[] = "Gender is required";
    if(!preg_match('/^255\d{9}$/', $contactno)) $errors[] = "Contact number must be 12 digits starting with 255 (255XXXXXXXXX)";
    if(!filter_var($emailid, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if(strlen($_POST['password'] ?? '') < 6) $errors[] = "Password must be at least 6 characters";
    if(($_POST['password'] ?? '') !== ($_POST['cpassword'] ?? '')) $errors[] = "Passwords do not match";

    if(empty($errors)) {
        // Check if email already exists
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM userregistration WHERE email=?");
        $stmt->bind_param('s', $emailid);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if($count > 0) {
            $_SESSION['error'] = "Email already registered. Please use a different email.";
        } else {
            $query = "INSERT INTO userregistration(regNo,firstName,middleName,lastName,gender,contactNo,email,password) VALUES(?,?,?,?,?,?,?,?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('sssssiss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $password);
            
            if($stmt->execute()) {
                $room = isset($_POST['room']) ? htmlspecialchars(trim($_POST['room'])) : '';
                $seater = isset($_POST['seater']) ? intval($_POST['seater']) : 0;
                $fpm = isset($_POST['fpm']) ? intval($_POST['fpm']) : 0;
                $foodstatus = isset($_POST['foodstatus']) ? intval($_POST['foodstatus']) : 0;
                $stayf = isset($_POST['stayf']) ? htmlspecialchars(trim($_POST['stayf'])) : '';
                $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
                
                if (!empty($room)) {
                    $regQuery = "INSERT INTO registration(roomno, seater, feespm, foodstatus, stayfrom, duration, regno, firstName, middleName, lastName, gender, contactno, emailid) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    $regStmt = $mysqli->prepare($regQuery);
                    if($regStmt) {
                        $regStmt->bind_param('siiisisssssss', $room, $seater, $fpm, $foodstatus, $stayf, $duration, $regno, $fname, $mname, $lname, $gender, $contactno, $emailid);
                        $regStmt->execute();
                        $regStmt->close();
                    }
                }
                
                $_SESSION['email_for_login']    = $emailid;
                $_SESSION['registration_number'] = $regno;
                $_SESSION['reg_success'] = [
                    'name'  => "$fname $lname",
                    'regno' => $regno,
                    'email' => $emailid,
                ];
                header("Location: registration.php?registered=1");
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | Hostel Management</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .registration-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .registration-header {
            text-align: center;
            margin-bottom: 30px;
            color: #3a7bd5;
        }
        .form-icon {
            position: relative;
        }
        .form-icon i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        .form-icon input, .form-icon select {
            padding-left: 40px;
        }
        .password-strength {
            height: 5px;
            background: #ddd;
            margin-top: 5px;
            border-radius: 5px;
            overflow: hidden;
        }
        .password-strength span {
            display: block;
            height: 100%;
            width: 0;
            background: #28a745;
            transition: width 0.3s;
        }
        .btn-register {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #2c65b4, #00b7eb);
        }
        .login-link {
            color: #3a7bd5;
            text-decoration: none;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .regno-display {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .contact-format {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Success overlay */
        .success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 9999;
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .success-overlay.show { display: flex; }
        .success-modal {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: popIn 0.4s cubic-bezier(.175,.885,.32,1.275);
        }
        @keyframes popIn {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .success-check {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #06d6a0, #0ab575);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem; color: #fff;
            box-shadow: 0 10px 30px rgba(6,214,160,.35);
        }
        .success-modal h3 { font-size: 1.4rem; font-weight: 800; color: #1a202c; margin-bottom: 8px; }
        .success-modal p  { color: #718096; font-size: 0.9rem; margin-bottom: 20px; }
        .success-details {
            background: #f7fafc;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            text-align: left;
        }
        .success-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.85rem;
        }
        .success-detail-row:not(:last-child) { border-bottom: 1px solid #edf2f7; }
        .success-detail-row .key { color: #718096; }
        .success-detail-row .val { font-weight: 700; color: #2d3748; }
        .btn-modal-primary {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: #fff; border: none;
            padding: 11px 24px; border-radius: 10px;
            font-weight: 700; font-size: 0.88rem;
            margin: 4px; cursor: pointer; transition: all 0.2s;
        }
        .btn-modal-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(58,123,213,.3); }
        .btn-modal-secondary {
            background: #f0f2f5; color: #4a5568; border: none;
            padding: 11px 24px; border-radius: 10px;
            font-weight: 600; font-size: 0.88rem;
            margin: 4px; cursor: pointer; transition: all 0.2s;
        }
        .btn-modal-secondary:hover { background: #e2e8f0; }
    </style>

</head>
<body>
    <!-- Success Popup Modal -->
    <div class="success-overlay" id="regSuccessOverlay">
        <div class="success-modal">
            <div class="success-check"><i class="fas fa-check"></i></div>
            <h3>Registration Successful!</h3>
            <p>The student account has been created and is ready to use.</p>
            <?php if(isset($_SESSION['reg_success']) && isset($_GET['registered'])):
                $rs = $_SESSION['reg_success'];
                unset($_SESSION['reg_success']);
            ?>
            <div class="success-details">
                <div class="success-detail-row">
                    <span class="key">Student Name</span>
                    <span class="val"><?php echo htmlspecialchars($rs['name']); ?></span>
                </div>
                <div class="success-detail-row">
                    <span class="key">Registration #</span>
                    <span class="val" style="color:#4361ee;"><?php echo htmlspecialchars($rs['regno']); ?></span>
                </div>
                <div class="success-detail-row">
                    <span class="key">Email (login)</span>
                    <span class="val"><?php echo htmlspecialchars($rs['email']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <button class="btn-modal-primary" onclick="registerAnother()">
                    <i class="fas fa-user-plus me-1"></i> Register Another
                </button>
                <button class="btn-modal-secondary" onclick="goToDashboard()">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </button>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- Back to Dashboard bar -->
        <div style="max-width:800px; margin:0 auto 16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; font-size:0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <a href="manage-students.php" class="btn btn-outline-primary btn-sm" style="border-radius:8px; font-size:0.85rem;">
                <i class="fas fa-users me-1"></i> Manage Students
            </a>
        </div>
        <div class="registration-container">

            <div class="registration-header">
                <h2><i class="fas fa-user-graduate me-2"></i> Student Registration</h2>
                <p class="text-muted">Fill in your details to create an account</p>
            </div>
            
            <!-- Display Errors -->
            <?php if(isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach($_SESSION['errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
            
            <!-- Display Success/Error Messages -->
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="post" action="" name="registration" class="needs-validation" novalidate>

			 <!-- Room Info -->
			 <div class="form-section">
                    <h4 class="form-title">Room Information</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number</label>
                            <select name="room" id="room" class="form-control" onChange="getSeater(this.value);" required>
                                <option value="">Select Room</option>
                                <?php 
                                $query = "SELECT * FROM rooms";
                                $stmt = $mysqli->prepare($query);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while($row = $res->fetch_object()) {
                                    echo "<option value='{$row->room_no}'>{$row->room_no}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seater</label>
                            <input type="text" name="seater" id="seater" class="form-control" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fees Per Month</label>
                            <input type="text" name="fpm" id="fpm" class="form-control" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Food Status</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="foodstatus" value="0" checked>
                                <label class="form-check-label">Without Food</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="foodstatus" value="1">
                                <label class="form-check-label">With Food (TSH2000)</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stay From</label>
                            <input type="date" name="stayf" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Months)</label>
                            <select name="duration" class="form-control" required>
                                <option value="">Select Duration</option>
                                <?php for($i=1; $i<=12; $i++) { echo "<option value='$i'>$i</option>"; } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Registration Info -->
                <div class="regno-display">
                    <i class="fas fa-id-card me-2"></i>
                    Your registration number will be automatically generated
                </div>
                
                <div class="row g-3">
                    <!-- First Name -->
                    <div class="col-md-6">
                        <label for="fname" class="form-label">First Name</label>
                        <div class="form-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="fname" name="fname" required
                                   value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Middle Name -->
                    <div class="col-md-6">
                        <label for="mname" class="form-label">Middle Name</label>
                        <div class="form-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="mname" name="mname"
                                   value="<?php echo isset($_POST['mname']) ? htmlspecialchars($_POST['mname']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label for="lname" class="form-label">Last Name</label>
                        <div class="form-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="lname" name="lname" required
                                   value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Gender -->
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender</label>
                        <div class="form-icon">
                            <i class="fas fa-venus-mars"></i>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'others') ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Contact Number -->
                    <div class="col-md-6">
                        <label for="contact" class="form-label">Contact Number</label>
                        <div class="form-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" class="form-control" id="contact" name="contact" required
                                   maxlength="12" placeholder="255XXXXXXXXX"
                                   value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                        </div>
                        <div class="contact-format">Format: 255 followed by 9 digits (12 digits total)</div>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="form-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div id="user-availability-status" class="small text-muted mt-1"></div>
                    </div>
                    
                    <!-- Password -->
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="form-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" required
                                   onkeyup="checkPasswordStrength()">
                        </div>
                        <div class="password-strength">
                            <span id="password-strength-bar"></span>
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label for="cpassword" class="form-label">Confirm Password</label>
                        <div class="form-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="cpassword" name="cpassword" required
                                   onkeyup="checkPasswordMatch()">
                        </div>
                        <div id="password-match" class="small mt-1"></div>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        Already have an account? <a href="../index.php" class="login-link">Login here</a>
                    </div>
                    <div>
                        <button type="reset" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-redo me-1"></i> Reset
                        </button>
                        <button type="submit" name="submit" class="btn btn-register text-white">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    // Check email availability
    function checkAvailability() {
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "check_availability.php",
            data: 'emailid='+$("#email").val(),
            type: "POST",
            success:function(data){
                $("#user-availability-status").html(data);
                $("#loaderIcon").hide();
            },
            error:function () {
                alert('Error checking email availability');
            }
        });
    }
    
    // Check password strength
    function checkPasswordStrength() {
        var password = $("#password").val();
        var strength = 0;
        
        if (password.length >= 6) strength += 1;
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
        if (password.match(/([0-9])/)) strength += 1;
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
        
        var width = (strength / 4) * 100;
        $("#password-strength-bar").css("width", width + "%");
        
        if (strength < 2) {
            $("#password-strength-bar").css("background-color", "#dc3545");
        } else if (strength == 2) {
            $("#password-strength-bar").css("background-color", "#ffc107");
        } else {
            $("#password-strength-bar").css("background-color", "#28a745");
        }
    }
    
    // Check password match
    function checkPasswordMatch() {
        var password = $("#password").val();
        var confirmPassword = $("#cpassword").val();
        
        if (password != confirmPassword) {
            $("#password-match").html("<span style='color:#dc3545'>Passwords do not match!</span>");
            return false;
        } else {
            $("#password-match").html("<span style='color:#28a745'>Passwords match.</span>");
            return true;
        }
    }
    
    // Contact number validation and formatting
    document.getElementById('contact').addEventListener('input', function(e) {
        // Remove any non-digit characters
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Ensure it starts with 255
        if (!this.value.startsWith('255') && this.value.length > 0) {
            this.value = '255' + this.value.replace(/^255/, '');
        }
        
        // Limit to 12 characters (255 + 9 digits)
        if (this.value.length > 12) {
            this.value = this.value.substring(0, 12);
        }
    });
    
    // Validate form submission
    function validateForm() {
        if (!checkPasswordMatch()) {
            alert("Passwords do not match!");
            return false;
        }
        
        // Validate contact number format
        var contact = document.getElementById('contact').value;
        if (!/^255\d{9}$/.test(contact)) {
            alert("Contact number must be 12 digits starting with 255 (255XXXXXXXXX)");
            return false;
        }
        
        return true;
    }

	function getSeater(val) {
        $.ajax({
            type: "POST",
            url: "get_seater.php",
            data: 'roomid=' + val,
            success: function(data) {
                $('#seater').val(data);
            }
        });

        $.ajax({
            type: "POST",
            url: "get_seater.php",
            data: 'rid=' + val,
            success: function(data) {
                $('#fpm').val(data);
            }
        });
    }

    </script>

    <script>
    <?php if(isset($_GET['registered'])): ?>
    // Show success popup
    window.addEventListener('DOMContentLoaded', function() {
        var overlay = document.getElementById('regSuccessOverlay');
        if (overlay) overlay.classList.add('show');
    });
    <?php endif; ?>

    function registerAnother() {
        document.getElementById('regSuccessOverlay').classList.remove('show');
        window.location.href = 'registration.php';
    }

    function goToDashboard() {
        window.location.href = 'dashboard.php';
    }
    </script>
</body>
</html>
