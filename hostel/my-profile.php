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
    
    <title>My Profile | Student Hostel</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        .profile-header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .section-title {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: #444;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 50px;
            height: 3px;
            background: #667eea;
            border-radius: 2px;
        }
    </style>
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
                
                <div class="row animate__animated animate__fadeIn">
                    <div class="col-md-12">
                        
                        <!-- Profile Header -->
                        <div class="profile-header-card d-flex align-items-center">
                            <div class="profile-avatar me-4">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?></h2>
                                <p class="mb-2 opacity-75"><i class="fas fa-id-badge me-2"></i><?php echo htmlspecialchars($row->regNo); ?></p>
                                <span class="badge bg-light text-primary rounded-pill px-3 py-2">
                                    <i class="fas fa-check-circle me-1"></i> Student
                                </span>
                            </div>
                            <div class="ms-auto text-end d-none d-md-block">
                                <p class="mb-1"><small>Account Status</small></p>
                                <h4 class="mb-0">Active</h4>
                            </div>
                        </div>

                        <!-- Success/Error Messages -->
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <!-- Main Profile Form -->
                            <div class="col-lg-8">
                                <div class="card shadow-sm border-0 mb-4 h-100">
                                    <div class="card-header bg-white py-3 border-bottom-0">
                                        <h5 class="mb-0 text-primary"><i class="fas fa-user-edit me-2"></i>Personal Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="" class="needs-validation" novalidate>
                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">First Name</label>
                                                    <input type="text" class="form-control form-control-lg" name="fname" value="<?php echo htmlspecialchars($row->firstName); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">Last Name</label>
                                                    <input type="text" class="form-control form-control-lg" name="lname" value="<?php echo htmlspecialchars($row->lastName); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">Middle Name</label>
                                                    <input type="text" class="form-control" name="mname" value="<?php echo htmlspecialchars($row->middleName); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">Gender</label>
                                                    <select class="form-select" name="gender" required>
                                                        <option value="<?php echo $row->gender; ?>"><?php echo ucfirst($row->gender); ?></option>
                                                        <option value="male">Male</option>
                                                        <option value="female">Female</option>
                                                        <option value="others">Others</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">Contact Number</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                        <input type="tel" class="form-control" name="contact" value="<?php echo htmlspecialchars($row->contactNo); ?>" maxlength="10" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">Last Updated</label>
                                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($row->updationDate); ?>" readonly>
                                                </div>
                                                
                                                <div class="col-12 text-end mt-4">
                                                    <button type="submit" name="update" class="btn btn-primary btn-lg px-5">
                                                        <i class="fas fa-save me-2"></i> Save Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sidebar Info -->
                            <div class="col-lg-4">
                                <div class="card shadow-sm border-0 mb-4 text-center">
                                    <div class="card-body p-4">
                                        <div class="mb-3">
                                            <span class="fa-stack fa-2x text-primary">
                                                <i class="fas fa-circle fa-stack-2x opacity-25"></i>
                                                <i class="fas fa-envelope fa-stack-1x"></i>
                                            </span>
                                        </div>
                                        <h5 class="card-title">Email Address</h5>
                                        <p class="card-text text-muted mb-0"><?php echo htmlspecialchars($row->email); ?></p>
                                        <small class="text-success fw-bold"><i class="fas fa-check-circle small me-1"></i>Verified</small>
                                    </div>
                                </div>
                                
                                <div class="card shadow-sm border-0">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-uppercase text-muted mb-3 small">Registration Info</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li class="d-flex justify-content-between mb-3">
                                                <span><i class="fas fa-calendar-alt me-2 text-primary"></i>Reg. Date</span>
                                                <span class="fw-bold"><?php echo date("d M Y", strtotime($row->regDate)); ?></span>
                                            </li>
                                            <li class="d-flex justify-content-between">
                                                <span><i class="fas fa-bed me-2 text-primary"></i>Room Status</span>
                                                <span class="badge bg-warning text-dark">Check Room Details</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
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
    </script>
</body>
</html>