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

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/student-modern.css">
    
    <style>
        .complaint-card-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .file-upload-modern {
            position: relative;
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .file-upload-modern:hover, .file-upload-modern.active {
            border-color: var(--primary);
            background: #f0f7ff;
        }
        .file-upload-modern i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
            opacity: 0.7;
        }
        .file-upload-input {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                
                <div class="complaint-card-container">
                    <div class="d-flex align-items-center mb-5 animate__animated animate__fadeInLeft">
                        <div class="stat-icon-box bg-primary text-white rounded-circle me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Report an Issue</h2>
                            <p class="section-subtitle">Something not right? Tell us about it.</p>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4 animate__animated animate__shakeX" style="border-radius: 16px;">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$has_room): ?>
                        <div class="card-modern p-5 text-center shadow-lg animate__animated animate__zoomIn" style="border-radius: 30px;">
                            <div class="mb-4">
                                <div class="bg-warning-light d-inline-flex p-4 rounded-circle">
                                    <i class="fas fa-lock text-warning fa-3x"></i>
                                </div>
                            </div>
                            <h3 class="fw-800 text-dark mb-3">Room Assignment Required</h3>
                            <p class="text-muted mb-4 fs-5">You are not eligible to file a complaint yet. Please ensure you have requested a room and the administration has approved your allocation.</p>
                            <div class="d-flex gap-3 justify-content-center">
                                <a href="book-hostel.php" class="btn-modern btn-modern-primary px-5 py-3">
                                    <i class="fas fa-bed me-2"></i> Book a Room
                                </a>
                                <a href="dashboard.php" class="btn-modern btn-modern-light px-5 py-3">
                                    <i class="fas fa-home me-2"></i> Dashboard
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-modern p-4 p-md-5 animate__animated animate__fadeInUp" style="border-radius: 30px;">
                            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="row g-4">
                                    <!-- Category -->
                                    <div class="col-12">
                                        <div class="form-group-modern">
                                            <label class="form-label-modern">ISSUE CATEGORY</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-0 ps-0"><i class="fas fa-list-ul text-primary"></i></span>
                                                <select name="ctype" class="form-select border-0 bg-transparent fs-5 fw-600" required>
                                                    <option value="" selected disabled>What type of problem are you facing?</option>
                                                    <option value="Food Related">Food & Dining Services</option>
                                                    <option value="Room Related">Room Maintenance & Comfort</option>
                                                    <option value="Fee Related">Financial & Payment Issues</option>
                                                    <option value="Electrical">Electrical Repairs</option>
                                                    <option value="Plumbing">Water & Plumbing Problems</option>
                                                    <option value="Security">Security & Safety Concerns</option>
                                                    <option value="Discipline">Conduct & Discipline Issues</option>
                                                    <option value="Other">Something Else</option>
                                                </select>
                                            </div>
                                            <div class="form-underline"></div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="col-12">
                                        <div class="form-group-modern">
                                            <label class="form-label-modern">DESCRIBE WHAT HAPPENED</label>
                                            <textarea name="cdetails" rows="6" class="form-control border-0 bg-transparent fs-5 fw-600 p-0" placeholder="Please provide specific details so we can help you faster..." required></textarea>
                                            <div class="form-underline"></div>
                                        </div>
                                    </div>

                                    <!-- File Upload -->
                                    <div class="col-12">
                                        <label class="form-label-modern mb-3">SUPPORTING EVIDENCE (OPTIONAL)</label>
                                        <div class="file-upload-modern" id="dropZone">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <h5 class="fw-800 text-dark mb-1">Click to Upload or Drag & Drop</h5>
                                            <p class="text-muted small mb-0">PDF, PNG, JPG, or GIF (Max 5MB)</p>
                                            <input type="file" name="image" class="file-upload-input" id="fileInput" accept=".jpg,.jpeg,.png,.gif,.pdf">
                                            <div id="fileInfo" class="mt-3 fw-700 text-primary" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- Submit -->
                                    <div class="col-12 mt-5 text-center">
                                        <button type="submit" name="submit" class="btn-modern btn-modern-primary d-inline-flex px-5 py-4 shadow-lg w-100 justify-content-center fs-5">
                                            <i class="fas fa-paper-plane me-3 mt-1"></i> SEND REPORT
                                        </button>
                                        <p class="text-muted mt-4 small fw-600">Your ticket will be assigned a priority based on the category.</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
    <?php if ($has_room): ?>
    // File upload display
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const dropZone = document.getElementById('dropZone');

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if(this.files && this.files.length > 0) {
                fileInfo.style.display = 'block';
                fileInfo.innerHTML = `<i class="fas fa-file-alt me-2"></i> Selected: ${this.files[0].name}`;
                dropZone.classList.add('active');
            } else {
                fileInfo.style.display = 'none';
                dropZone.classList.remove('active');
            }
        });
    }
    
    // Drag and drop
    if (dropZone) {
        ['dragover', 'dragenter'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.add('active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.remove('active');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }, false);
    }
    <?php endif; ?>
    </script>
</body>
</html>
