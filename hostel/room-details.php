<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details | Student Hostel</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .room-details-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            padding: 20px;
        }
        .detail-section {
            border-bottom: 1px solid #eee;
            padding: 20px;
        }
        .detail-section h4 {
            color: #3a7bd5;
            margin-bottom: 20px;
            border-bottom: 2px solid #f5f5f5;
            padding-bottom: 10px;
        }
        .detail-row {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .fee-highlight {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s;
        }
        .print-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
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
                        <h2 class="page-title">
                            <i class="fas fa-bed me-2"></i> My Room Details
                        </h2>
                        
                        <?php
                        $aid = $_SESSION['login'];
                        $ret = "SELECT * FROM registration WHERE (emailid=? || regno=?)";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->bind_param('ss', $aid, $aid);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        
                        if($res->num_rows > 0):
                            while($row = $res->fetch_object()):
                                // Calculate fees
                                $fpm = $row->feespm;
                                $dr = $row->duration;
                                $hf = $dr * $fpm;
                                $ff = ($row->foodstatus == 1) ? (2000 * $dr) : 0;
                                $totalFee = $hf + $ff;
                        ?>
                        
                        <div class="card room-details-card" id="print">
                            <!-- Room Information Section -->
                            <div class="card-header">
                                <h3 class="mb-0">
                                    <i class="fas fa-door-open me-2"></i>Room Information
                                </h3>
                            </div>
                            
                            <div class="card-body">
                                <div class="detail-section">
                                    <div class="row detail-row">
                                        <div class="col-md-4">
                                            <span class="detail-label">Registration Number:</span>
                                            <span class="detail-value"><?php echo $row->regno; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Apply Date:</span>
                                            <span class="detail-value"><?php echo $row->postingDate; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row detail-row">
                                        <div class="col-md-4">
                                            <span class="detail-label">Room Number:</span>
                                            <span class="detail-value"><?php echo $row->roomno; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Seater:</span>
                                            <span class="detail-value"><?php echo $row->seater; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Fees Per Month:</span>
                                            <span class="detail-value">₹<?php echo $fpm; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row detail-row">
                                        <div class="col-md-4">
                                            <span class="detail-label">Food Status:</span>
                                            <span class="detail-value">
                                                <?php echo ($row->foodstatus == 0) ? 'Without Food' : 'With Food (+₹2000/month)'; ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Stay From:</span>
                                            <span class="detail-value"><?php echo $row->stayfrom; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Duration:</span>
                                            <span class="detail-value"><?php echo $dr; ?> Months</span>
                                        </div>
                                    </div>
                                    
                                    <div class="fee-highlight">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <span class="detail-label">Hostel Fee:</span>
                                                <span class="detail-value">₹<?php echo $hf; ?></span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="detail-label">Food Fee:</span>
                                                <span class="detail-value">
                                                    ₹<?php echo $ff; ?>
                                                    <?php if($row->foodstatus == 0): ?>
                                                        <span class="text-muted small">(Without Food)</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="detail-label">Total Fee:</span>
                                                <span class="detail-value fw-bold">₹<?php echo $totalFee; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Personal Information Section -->
                                <div class="detail-section">
                                    <h4><i class="fas fa-user-circle me-2"></i>Personal Information</h4>
                                    
                                    <div class="row detail-row">
                                        <div class="col-md-4">
                                            <span class="detail-label">Registration No:</span>
                                            <span class="detail-value"><?php echo $row->regno; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Full Name:</span>
                                            <span class="detail-value"><?php echo $row->firstName.' '.$row->middleName.' '.$row->lastName; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Email:</span>
                                            <span class="detail-value"><?php echo $row->emailid; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row detail-row">
                                        <div class="col-md-4">
                                            <span class="detail-label">Contact No:</span>
                                            <span class="detail-value"><?php echo $row->contactno; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Gender:</span>
                                            <span class="detail-value"><?php echo ucfirst($row->gender); ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Course:</span>
                                            <span class="detail-value"><?php echo $row->course; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row detail-row">
                                        <div class="col-md-4">
                                            <span class="detail-label">Emergency Contact:</span>
                                            <span class="detail-value"><?php echo $row->egycontactno; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Guardian Name:</span>
                                            <span class="detail-value"><?php echo $row->guardianName; ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="detail-label">Guardian Relation:</span>
                                            <span class="detail-value"><?php echo $row->guardianRelation; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row detail-row">
                                        <div class="col-md-12">
                                            <span class="detail-label">Guardian Contact No:</span>
                                            <span class="detail-value"><?php echo $row->guardianContactno; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Address Information Section -->
                                <div class="detail-section">
                                    <h4><i class="fas fa-map-marker-alt me-2"></i>Address Information</h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="address-box p-3 border rounded">
                                                <h5 class="detail-label mb-3">Correspondence Address</h5>
                                                <p class="detail-value">
                                                    <?php echo $row->corresAddress; ?><br>
                                                    <?php echo $row->corresCIty; ?>, <?php echo $row->corresPincode; ?><br>
                                                    <?php echo $row->corresState; ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="address-box p-3 border rounded">
                                                <h5 class="detail-label mb-3">Permanent Address</h5>
                                                <p class="detail-value">
                                                    <?php echo $row->pmntAddress; ?><br>
                                                    <?php echo $row->pmntCity; ?>, <?php echo $row->pmntPincode; ?><br>
                                                    <?php echo $row->pmnatetState; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                            endwhile;
                        else:
                        ?>
                        
                        <div class="alert alert-info">
                            <h4 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i> No Room Assigned
                            </h4>
                            <p>You haven't been assigned a room yet. Please complete the registration process.</p>
                            <hr>
                            <a href="registration.php" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Complete Registration
                            </a>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Print Button -->
    <div class="print-btn" onclick="CallPrint()" title="Print Details">
        <i class="fas fa-print fa-2x"></i>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    
    <!-- Print Function -->
    <script>
    function CallPrint() {
        var prtContent = document.getElementById("print");
        var WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
        
        // Add print-specific styles
        WinPrint.document.write('<html><head><title>Room Details</title>');
        WinPrint.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        WinPrint.document.write('<style>body{font-family:Arial,sans-serif} .detail-label{font-weight:bold} .fee-highlight{background:#f5f5f5;padding:10px;border-radius:5px;margin:10px 0} @page{size:auto;margin:5mm}</style>');
        WinPrint.document.write('</head><body>');
        WinPrint.document.write(prtContent.innerHTML);
        WinPrint.document.write('</body></html>');
        
        WinPrint.document.close();
        WinPrint.focus();
        WinPrint.print();
    }
    </script>
</body>
</html>
Total Fee: ₹2800
Personal Information