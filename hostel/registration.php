<?php
session_start();
include('includes/config.php');

function generateControlNumber() {
    return "99" . rand(10, 99) . date('md') . rand(100, 999) . rand(1000, 9999);
}

if(isset($_POST['submit'])) {
    // Generate registration number
    $year = date('y'); // Last two digits of current year
    $quarter = ceil(date('n') / 3); // Get current quarter (1-4)
    $prefix = "T{$year}-0{$quarter}-";
    
    // Get the highest existing registration number for this quarter
    $stmt = $mysqli->prepare("SELECT MAX(regNo) FROM userregistration WHERE regNo LIKE ?");
    $param = $prefix . '%';
    $stmt->bind_param('s', $param);
    $stmt->execute();
    $stmt->bind_result($lastRegNo);
    $stmt->fetch();
    $stmt->close();
    
    if ($lastRegNo) {
        $lastNumber = intval(substr($lastRegNo, strlen($prefix)));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $regno = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    
    // Sanitize inputs
    $fname = htmlspecialchars(trim($_POST['fname']));
    $mname = htmlspecialchars(trim($_POST['mname']));
    $lname = htmlspecialchars(trim($_POST['lname']));
    $gender = $_POST['gender'];
    $contactno = preg_replace('/[^0-9]/', '', $_POST['contact']);
    $emailid = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    
    // Validate inputs
    $errors = [];
    if(empty($fname)) $errors[] = "First name is required";
    if(empty($lname)) $errors[] = "Last name is required";
    if(empty($gender)) $errors[] = "Gender is required";
    if(!preg_match('/^255\d{9}$/', $contactno)) $errors[] = "Contact number must be 12 digits starting with 255 (255XXXXXXXXX)";
    if(!filter_var($emailid, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if(strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if($password !== $cpassword) $errors[] = "Passwords do not match";

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
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate Control Numbers for the new student
            $fee_ctrl = generateControlNumber();
            $acc_ctrl = generateControlNumber();
            $reg_ctrl = generateControlNumber();

            // Insert with Pending status and generated control numbers
            $query = "INSERT INTO userregistration(regNo,firstName,middleName,lastName,gender,contactNo,email,password,status,fee_control_no,acc_control_no,reg_control_no) VALUES(?,?,?,?,?,?,?,?,'Pending',?,?,?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('sssssssssss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $hashed_password, $fee_ctrl, $acc_ctrl, $reg_ctrl);
            
            if($stmt->execute()) {
                $registration_success = true;
            } else {
                $_SESSION['error'] = "Registration failed: " . $stmt->error;
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
    <title>Student Registration | HostelMS</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Auth Modern CSS -->
    <link rel="stylesheet" href="css/auth-modern.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="auth_wrapper">
        <div class="auth_card" data-aos="fade-up" data-aos-duration="1000">
            <!-- Left Panel - Hero Section -->
            <div class="auth_hero">
                <div class="auth_hero_content" data-aos="fade-right" data-aos-delay="200">
                    <h2>Join Our Community</h2>
                    <p>Experience a new way of living. Secure your room and start your academic journey with us today.</p>
                    <img src="assets/img/registration_hero.png" alt="Registration Hero">
                </div>
            </div>

            <!-- Right Panel - Form Section -->
            <div class="auth_content">
                <div class="auth_header" data-aos="fade-up" data-aos-delay="300">
                    <h1 class="auth_title">Student Registration</h1>
                    <p class="auth_subtitle">Create your digital profile to get started.</p>
                </div>

                <!-- Display Errors -->
                <?php if(isset($_SESSION['errors'])): ?>
                    <div class="alert-modern alert-danger-modern" data-aos="fade-in">
                        <ul class="mb-0 p-0" style="list-style: none;">
                            <?php foreach($_SESSION['errors'] as $error): ?>
                                <li><i class="fas fa-circle-exclamation me-2"></i> <?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert-modern alert-danger-modern" data-aos="fade-in">
                        <i class="fas fa-circle-exclamation me-2"></i> <?php echo $_SESSION['error']; ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="post" action="" name="registration" id="registrationForm">
                    <div class="regno-note" data-aos="fade-up" data-aos-delay="400">
                        <i class="fas fa-sparkles"></i>
                        <span>Your registration ID will be automatically generated upon submission.</span>
                    </div>

                    <div class="auth_row">
                        <!-- First Name -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="450">
                            <label class="form-label">First Name</label>
                            <div class="input-group-modern">
                                <i class="fas fa-id-card-clip"></i>
                                <input type="text" name="fname" placeholder="First Name" required
                                       value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Middle Name -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="475">
                            <label class="form-label">Middle Name</label>
                            <div class="input-group-modern">
                                <i class="fas fa-id-card-clip"></i>
                                <input type="text" name="mname" placeholder="Middle Name (Optional)"
                                       value="<?php echo isset($_POST['mname']) ? htmlspecialchars($_POST['mname']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="auth_row">
                        <!-- Last Name -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="500">
                            <label class="form-label">Last Name</label>
                            <div class="input-group-modern">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="lname" placeholder="Last Name" required
                                       value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="550">
                            <label class="form-label">Gender</label>
                            <div class="input-group-modern">
                                <i class="fas fa-venus-mars"></i>
                                <select name="gender" required>
                                    <option value="" disabled selected>Select Gender</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'others') ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="auth_row">
                        <!-- Contact -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="600">
                            <label class="form-label">Contact Number</label>
                            <div class="input-group-modern">
                                <i class="fas fa-phone-volume"></i>
                                <input type="tel" id="contact" name="contact" maxlength="12" placeholder="255XXXXXXXXX" required
                                       value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="650">
                            <label class="form-label">Email Address</label>
                            <div class="input-group-modern">
                                <i class="fas fa-envelope-open-text"></i>
                                <input type="email" id="email" name="email" placeholder="example@domain.com" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="auth_row">
                        <!-- Password -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="700">
                            <label class="form-label">Create Password</label>
                            <div class="input-group-modern">
                                <i class="fas fa-fingerprint"></i>
                                <input type="password" id="password" name="password" placeholder="••••••••" required
                                       onkeyup="checkPasswordStrength()">
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="750">
                            <label class="form-label">Verify Password</label>
                            <div class="input-group-modern">
                                <i class="fas fa-shield-halved"></i>
                                <input type="password" id="cpassword" name="cpassword" placeholder="••••••••" required
                                       onkeyup="checkPasswordMatch()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="password-strength" data-aos="fade-up" data-aos-delay="700">
                        <div id="password-strength-bar" class="strength-bar"></div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="auth_actions mt-4" data-aos="fade-up" data-aos-delay="800">
                        <button type="submit" name="submit" class="btn-primary-modern">
                            <span>Register Account</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <div class="auth_footer" data-aos="fade-up" data-aos-delay="850">
                        Already have an account? <a href="index.php">Log in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
    AOS.init({ duration: 800, once: true });

    <?php if(isset($registration_success) && $registration_success): ?>
    Swal.fire({
        title: 'Registration Protocol Successful!',
        text: 'Your account has been created. Access is currently pending administrative verification.',
        icon: 'success',
        confirmButtonColor: '#4361ee',
        confirmButtonText: 'Enter Terminal'
    }).then((result) => {
        window.location.href = 'index.php';
    });
    <?php endif; ?>

    // Check password strength
    function checkPasswordStrength() {
        var password = $("#password").val();
        var strength = 0;
        
        if (password.length >= 6) strength += 1;
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
        if (password.match(/([0-9])/)) strength += 1;
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
        
        var width = (strength / 4) * 100;
        var bar = $("#password-strength-bar");
        bar.css("width", width + "%");
        
        if (strength < 2) bar.css("background", "#ef4444");
        else if (strength == 2) bar.css("background", "#f59e0b");
        else bar.css("background", "#10b981");
    }
    
    // Check password match
    function checkPasswordMatch() {
        var password = $("#password").val();
        var confirmPassword = $("#cpassword").val();
        var group = $("#cpassword").closest(".input-group-modern");
        
        if (password != confirmPassword && confirmPassword != "") {
            group.find("input").css("border-color", "#ef4444");
            group.find("i").css("color", "#ef4444");
        } else if (password == confirmPassword && confirmPassword != "") {
            group.find("input").css("border-color", "#10b981");
            group.find("i").css("color", "#10b981");
        } else {
            group.find("input").css("border-color", "transparent");
            group.find("i").css("color", "#64748b");
        }
    }
    
    // Contact number validation
    document.getElementById('contact').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (!this.value.startsWith('255') && this.value.length > 0) {
            this.value = '255' + this.value;
        }
        if (this.value.length > 12) {
            this.value = this.value.substring(0, 12);
        }
    });
    </script>
</body>
</html>
