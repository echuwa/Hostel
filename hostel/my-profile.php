<?php
session_start();
include('includes/config.php');
date_default_timezone_set('Asia/Kolkata');
include('includes/checklogin.php');
check_login();
$aid = $_SESSION['id'];

if(isset($_POST['update'])) {
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $gender = $_POST['gender'];
    $contactno = trim($_POST['contact']);
    $udate = date('d-m-Y h:i:s', time());
    
    $query = "UPDATE userregistration SET firstName=?, middleName=?, lastName=?, gender=?, contactNo=?, updationDate=? WHERE id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssisi', $fname, $mname, $lname, $gender, $contactno, $udate, $aid);
    
    if($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: my-profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }
}
?>

<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Update your profile information">
    <meta name="author" content="">
    <meta name="theme-color" content="#3e454c">
    
    <title>Update Profile | Student Hostel</title>
    
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
</head>

<body>
    <?php include('includes/header.php');?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php');?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <?php
                $aid = $_SESSION['id'];
                $ret = "SELECT * FROM userregistration WHERE id=?";
                $stmt = $mysqli->prepare($ret);
                $stmt->bind_param('i', $aid);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if($row = $res->fetch_object()) {
                ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="page-title mb-0">
                                <i class="fas fa-user-edit me-2"></i> Update Profile
                            </h2>
                            <div class="text-muted small">
                                Last Updated: <?php echo $row->updationDate; ?>
                            </div>
                        </div>
                        
                        <!-- Success/Error Messages -->
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <form method="post" action="" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <!-- Registration Number -->
                                        <div class="col-md-6">
                                            <label for="regno" class="form-label">
                                                <i class="fas fa-id-card me-1"></i> Registration Number
                                            </label>
                                            <input type="text" class="form-control" id="regno" name="regno" 
                                                   value="<?php echo $row->regNo; ?>" readonly>
                                        </div>
                                        
                                        <!-- First Name -->
                                        <div class="col-md-6">
                                            <label for="fname" class="form-label">
                                                <i class="fas fa-user me-1"></i> First Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="fname" name="fname" 
                                                   value="<?php echo $row->firstName; ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide your first name.
                                            </div>
                                        </div>
                                        
                                        <!-- Middle Name -->
                                        <div class="col-md-6">
                                            <label for="mname" class="form-label">
                                                <i class="fas fa-user me-1"></i> Middle Name
                                            </label>
                                            <input type="text" class="form-control" id="mname" name="mname" 
                                                   value="<?php echo $row->middleName; ?>">
                                        </div>
                                        
                                        <!-- Last Name -->
                                        <div class="col-md-6">
                                            <label for="lname" class="form-label">
                                                <i class="fas fa-user me-1"></i> Last Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="lname" name="lname" 
                                                   value="<?php echo $row->lastName; ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide your last name.
                                            </div>
                                        </div>
                                        
                                        <!-- Gender -->
                                        <div class="col-md-6">
                                            <label for="gender" class="form-label">
                                                <i class="fas fa-venus-mars me-1"></i> Gender <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="<?php echo $row->gender; ?>" selected><?php echo ucfirst($row->gender); ?></option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="others">Others</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select your gender.
                                            </div>
                                        </div>
                                        
                                        <!-- Contact Number -->
                                        <div class="col-md-6">
                                            <label for="contact" class="form-label">
                                                <i class="fas fa-phone me-1"></i> Contact Number <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" class="form-control" id="contact" name="contact" 
                                                   maxlength="10" value="<?php echo $row->contactNo; ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide a valid contact number.
                                            </div>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="col-md-12">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-1"></i> Email Address
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo $row->email; ?>" readonly>
                                            <span id="user-availability-status" class="small text-muted"></span>
                                        </div>
                                        
                                        <!-- Submit Button -->
                                        <div class="col-12 mt-4">
                                            <button type="submit" name="update" class="btn btn-primary px-4">
                                                <i class="fas fa-save me-1"></i> Update Profile
                                            </button>
                                            <a href="my-profile.php" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/main.js"></script>
    
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
    
    // Contact number validation (digits only)
    document.getElementById('contact').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // Email availability check
    function checkAvailability() {
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "check_availability.php",
            data: 'emailid=' + $("#email").val(),
            type: "POST",
            success: function(data) {
                $("#user-availability-status").html(data);
                $("#loaderIcon").hide();
            },
            error: function() {}
        });
    }
    </script>
</body>
</html>