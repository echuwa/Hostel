<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Fetch student ID from URL
$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: manage-students.php");
    exit();
}

// Fetch student data
$query = "SELECT u.*, r.*, u.id as studentId 
          FROM userregistration u 
          LEFT JOIN registration r ON u.regNo = r.regno 
          WHERE u.id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_object();
$stmt->close();

if (!$data) {
    header("Location: manage-students.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>Student Details | Hostel Management</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern Styling -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
        }
        .profile-header {
            background: var(--primary-gradient);
            border-radius: 24px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
        }
        .profile-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .info-section-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            height: 100%;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .section-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 15px;
        }
        .bg-p-light { background: #eff6ff; color: #3b82f6; }
        .bg-s-light { background: #ecfdf5; color: #10b981; }
        .bg-w-light { background: #fffbeb; color: #f59e0b; }
        .bg-v-light { background: #f5f3ff; color: #8b5cf6; }

        .detail-label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .detail-value { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 15px; word-break: break-all; }

        .avatar-lg {
            width: 100px; height: 100px; border-radius: 24px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: 800; border: 2px solid rgba(255,255,255,0.3);
        }

        .payment-pill {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }

        @media print {
            .app-container .sidebar, .app-container .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .profile-header { background: #f8fafc !important; color: #000 !important; box-shadow: none !important; border: 1px solid #ddd !important; }
            .profile-header::after { display: none; }
            .avatar-lg { border: 2px solid #ddd !important; color: #000 !important; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR (No-Print) -->
        <div class="no-print">
            <?php include('includes/sidebar_modern.php'); ?>
        </div>

        <div class="main-content">
            <div class="content-wrapper">
                
                <!-- Simple Breadcrumb/Back -->
                <div class="no-print mb-4 d-flex justify-content-between align-items-center">
                    <a href="manage-students.php" class="btn border-0 fw-bold text-muted p-0"><i class="fas fa-arrow-left me-2"></i> Back to Directory</a>
                    <button onclick="window.print()" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm"><i class="fas fa-print me-2"></i> Print Report</button>
                </div>

                <div id="printArea">
                    <!-- Profile Header -->
                    <div class="profile-header animate__animated animate__fadeInDown">
                        <div class="row align-items-center g-4">
                            <div class="col-auto">
                                <div class="avatar-lg">
                                    <?php echo strtoupper(substr($data->firstName, 0, 1) . substr($data->lastName, 0, 1)); ?>
                                </div>
                            </div>
                            <div class="col">
                                <span class="badge bg-white text-primary mb-2 px-3 rounded-pill fw-bold">Student Identity Card</span>
                                <h1 class="fw-800 mb-1"><?php echo htmlspecialchars($data->firstName . ' ' . ($data->middleName ? $data->middleName . ' ' : '') . $data->lastName); ?></h1>
                                <div class="d-flex flex-wrap gap-3 opacity-90 fw-600">
                                    <span><i class="fas fa-id-card me-1"></i> <?php echo $data->regNo; ?></span>
                                    <span><i class="fas fa-calendar-alt me-1"></i> Registered: <?php echo date('M d, Y', strtotime($data->regDate)); ?></span>
                                    <span><i class="fas fa-user-check me-1"></i> Status: <?php echo ucfirst($data->status); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-lg-4 col-md-6">
                            <div class="info-section-card animate__animated animate__fadeInUp">
                                <div class="section-icon bg-p-light"><i class="fas fa-user"></i></div>
                                <h5 class="fw-800 text-dark mb-4">Personal Details</h5>
                                
                                <div class="detail-label">Email Address</div>
                                <div class="detail-value"><?php echo $data->email; ?></div>

                                <div class="detail-label">Contact Number</div>
                                <div class="detail-value"><?php echo $data->contactNo; ?></div>

                                <div class="detail-label">Gender / Course</div>
                                <div class="detail-value"><?php echo ucfirst($data->gender); ?> • <?php echo $data->course ?: 'N/A'; ?></div>

                                <div class="detail-label">Guardian Name</div>
                                <div class="detail-value"><?php echo $data->guardianName ?: 'N/A'; ?> (<?php echo $data->guardianRelation ?: 'Unknown'; ?>)</div>

                                <div class="detail-label">Guardian Contact</div>
                                <div class="detail-value"><?php echo $data->guardianContactno ?: 'N/A'; ?></div>
                            </div>
                        </div>

                        <!-- Room Info -->
                        <div class="col-lg-4 col-md-6">
                            <div class="info-section-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                                <div class="section-icon bg-s-light"><i class="fas fa-bed"></i></div>
                                <h5 class="fw-800 text-dark mb-4">Hostel Allocation</h5>

                                <?php if($data->roomno): ?>
                                    <div class="detail-label">Room Details</div>
                                    <div class="detail-value">No. <?php echo $data->roomno; ?> (<?php echo $data->seater; ?> Seater)</div>

                                    <div class="detail-label">Fees Per Month</div>
                                    <div class="detail-value text-success">TSH <?php echo number_format($data->feespm); ?></div>

                                    <div class="detail-label">Stay From / Duration</div>
                                    <div class="detail-value"><?php echo date('M d, Y', strtotime($data->stayfrom)); ?> • <?php echo $data->duration; ?> Months</div>

                                    <div class="detail-label">Food Status</div>
                                    <div class="detail-value"><?php echo $data->foodstatus == 1 ? 'With Food Management' : 'Without Food'; ?></div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-hotel fa-3x text-light mb-3"></i>
                                        <p class="text-muted fw-bold">No Room Assigned Yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <!-- Address & Payments -->
                    <div class="col-lg-4 col-md-12">
                        <div class="row g-4">
                            <!-- Address -->
                            <div class="col-12">
                                <div class="info-section-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                                    <div class="section-icon bg-w-light"><i class="fas fa-map-marker-alt"></i></div>
                                    <h5 class="fw-800 text-dark mb-4">Address Info</h5>
                                    
                                    <div class="detail-label">Correspondence Address</div>
                                    <div class="detail-value small lh-sm"><?php echo $data->corresAddress; ?>, <?php echo $data->corresState; ?>, <?php echo $data->corresCountry; ?></div>

                                    <div class="detail-label">Permanent Address</div>
                                    <div class="detail-value small lh-sm"><?php echo $data->pmntAddress; ?>, <?php echo $data->pmntState; ?>, <?php echo $data->pmntCountry; ?></div>
                                </div>
                            </div>
                            
                            <!-- Payments Summary -->
                            <div class="col-12">
                                <div class="info-section-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                                    <div class="section-icon bg-v-light"><i class="fas fa-wallet"></i></div>
                                    <h5 class="fw-800 text-dark mb-4">Payment Control Numbers</h5>
                                    
                                    <div class="payment-pill">
                                        <div class="detail-label">School Fees (TSH <?php echo number_format($data->fees_paid); ?> Paid)</div>
                                        <div class="detail-value mb-0 text-primary font-monospace"><?php echo $data->fee_control_no ?: 'GEN_PENDING'; ?></div>
                                    </div>

                                    <div class="payment-pill">
                                        <div class="detail-label">Accommodation (TSH <?php echo number_format($data->accommodation_paid); ?> Paid)</div>
                                        <div class="detail-value mb-0 text-primary font-monospace"><?php echo $data->acc_control_no ?: 'GEN_PENDING'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
