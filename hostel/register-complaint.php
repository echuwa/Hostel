<?php
session_start();
include('includes/config.php');
date_default_timezone_set('Africa/Nairobi');
include('includes/checklogin.php');
check_login();
$aid = $_SESSION['user_id'] ?? $_SESSION['id'];
$uid_login = $_SESSION['login'] ?? '';

// ============ CHECK: Student must have an assigned room ============
$has_room = false;
$stmt_room = $mysqli->prepare("SELECT id FROM registration WHERE emailid=? OR regno=? LIMIT 1");
$stmt_room->bind_param('ss', $uid_login, $uid_login);
$stmt_room->execute();
$stmt_room->store_result();
$has_room = $stmt_room->num_rows > 0;
$stmt_room->close();

if(isset($_POST['submit'])) {
    if (!$has_room) {
        $_SESSION['error'] = "Huwezi kutuma malalamiko. Unahitaji kwanza kupewa chumba na admin.";
        header("Location: register-complaint.php");
        exit();
    }
    // Sanitize inputs
    $complainttype = htmlspecialchars(trim($_POST['ctype']));
    $complaintdetails = htmlspecialchars(trim($_POST['cdetails']));
    $imgfile = $_FILES["image"]["name"];
    $cnumber = mt_rand(100000000, 999999999);
    $imgnewfile = null;

    // File upload handling
    if($imgfile != '') {
        $extension = strtolower(pathinfo($imgfile, PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "pdf");
        
        if(!in_array($extension, $allowed_extensions)) {
            $_SESSION['error'] = "Invalid format. Only jpg/jpeg/png/gif/pdf formats allowed.";
        } else {
            $imgnewfile = md5($imgfile.time()).'.'.$extension;
            $target_path = "comnplaintdoc/".$imgnewfile;
            
            if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
                $query = "INSERT INTO complaints(ComplainNumber, userId, complaintType, complaintDetails, complaintDoc) VALUES(?,?,?,?,?)";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('iisss', $cnumber, $aid, $complainttype, $complaintdetails, $imgnewfile);
                
                if($stmt->execute()) {
                    $_SESSION['success'] = "Complaint registered successfully. Complaint number: $cnumber";
                    header("Location: my-complaints.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Error registering complaint. Please try again.";
                }
            } else {
                $_SESSION['error'] = "Error uploading file. Please try again.";
            }
        }
    } else {
        $query = "INSERT INTO complaints(ComplainNumber, userId, complaintType, complaintDetails) VALUES(?,?,?,?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iiss', $cnumber, $aid, $complainttype, $complaintdetails);
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Complaint registered successfully. Complaint number: $cnumber";
            header("Location: my-complaints.php");
            exit();
        } else {
            $_SESSION['error'] = "Error registering complaint. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Registration | Hostel Management</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .complaint-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-header {
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
        .form-icon input, 
        .form-icon select, 
        .form-icon textarea {
            padding-left: 40px;
        }
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .file-upload-btn {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-btn:hover {
            border-color: #3a7bd5;
            background-color: #f8f9fa;
        }
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-name {
            margin-top: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        .btn-submit {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #2c65b4, #00b7eb);
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="complaint-form-container">
                            <div class="form-header">
                                <h2><i class="fas fa-exclamation-circle me-2"></i> Register a Complaint</h2>
                                <p class="text-muted">Please provide details about your issue</p>
                            </div>
                            
                            <!-- Display Success/Error Messages -->
                            <?php if(isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!$has_room): ?>
                            <!-- NOT ELIGIBLE: No room assigned -->
                            <div class="text-center py-5">
                                <div style="width:90px;height:90px;background:linear-gradient(135deg,#fed7d7,#fc8181);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.5rem;color:#c53030;">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Huna Chumba Kilichopewa</h4>
                                <p class="text-muted mb-4">Unahitaji kwanza kupewa chumba na admin ili uweze kutuma malalamiko.<br>Omba chumba hapa chini, na baada ya admin kukuidhinisha utaweza kutuma malalamiko.</p>
                                <a href="book-hostel.php" class="btn btn-primary me-2">
                                    <i class="fas fa-bed me-2"></i> Omba Chumba
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i> Rudi Dashboard
                                </a>
                            </div>

                            <?php else: ?>
                            <!-- ELIGIBLE: Has room -->
                            <form method="post" action="" name="complaint" class="needs-validation" novalidate enctype="multipart/form-data">
                                <div class="row g-3">
                                    <!-- Complaint Type -->
                                    <div class="col-md-12">
                                        <label for="ctype" class="form-label">Complaint Type</label>
                                        <div class="form-icon">
                                            <i class="fas fa-tag"></i>
                                            <select class="form-select" id="ctype" name="ctype" required>
                                                <option value="" selected disabled>Select Complaint Type</option>
                                                <option value="Food Related">Food Related</option>
                                                <option value="Room Related">Room Related</option>
                                                <option value="Fee Related">Fee Related</option>
                                                <option value="Electrical">Electrical</option>
                                                <option value="Plumbing">Plumbing</option>
                                                <option value="Discipline">Discipline</option>
                                                <option value="Other">Other</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select a complaint type.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Complaint Details -->
                                    <div class="col-md-12">
                                        <label for="cdetails" class="form-label">Complaint Details</label>
                                        <div class="form-icon">
                                            <i class="fas fa-align-left"></i>
                                            <textarea class="form-control" id="cdetails" name="cdetails" rows="5" required></textarea>
                                            <div class="invalid-feedback">
                                                Please provide details about your complaint.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- File Upload -->
                                    <div class="col-md-12">
                                        <label class="form-label">Attachment (Optional)</label>
                                        <div class="file-upload">
                                            <div class="file-upload-btn">
                                                <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color: #3a7bd5;"></i>
                                                <p>Drag & drop files here or click to browse</p>
                                                <p class="small text-muted">Supported formats: JPG, PNG, GIF, PDF</p>
                                                <input type="file" class="file-upload-input" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.pdf">
                                            </div>
                                            <div id="file-name" class="file-name"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="col-12 mt-4 text-center">
                                        <button type="submit" name="submit" class="btn btn-submit text-white">
                                            <i class="fas fa-paper-plane me-1"></i> Submit Complaint
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
    <?php if ($has_room): ?>
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
    
    // File upload display
    var imgInput = document.getElementById('image');
    if (imgInput) {
        imgInput.addEventListener('change', function(e) {
            var fileName = '';
            if(this.files && this.files.length > 1) {
                fileName = (this.files.length + ' files selected');
            } else {
                fileName = this.files[0] ? this.files[0].name : '';
            }
            var fnEl = document.getElementById('file-name');
            if (fnEl) fnEl.textContent = fileName;
        });
    }
    
    // Drag and drop functionality
    var fileUploadBtn = document.querySelector('.file-upload-btn');
    if (fileUploadBtn) {
        fileUploadBtn.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadBtn.style.borderColor = '#3a7bd5';
            fileUploadBtn.style.backgroundColor = '#f8f9fa';
        });
        
        fileUploadBtn.addEventListener('dragleave', () => {
            fileUploadBtn.style.borderColor = '#ddd';
            fileUploadBtn.style.backgroundColor = 'transparent';
        });
        
        fileUploadBtn.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadBtn.style.borderColor = '#ddd';
            fileUploadBtn.style.backgroundColor = 'transparent';
            
            const fileInput = document.querySelector('.file-upload-input');
            fileInput.files = e.dataTransfer.files;
            
            // Trigger change event
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        });
    }
    <?php endif; ?>
    </script>
</body>
</html>
