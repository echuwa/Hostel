<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
$user_email = $_SESSION['login'];
$user_name = $_SESSION['name'] ?? 'Student';

// Fetch Registration Details by joining with userregistration to be more robust
$reg_query = "SELECT r.* FROM registration r JOIN userregistration u ON r.regno = u.regNo WHERE u.email = ? ORDER BY r.id DESC LIMIT 1";
$stmt = $mysqli->prepare($reg_query);
$stmt->bind_param('s', $user_email);
$stmt->execute();
$reg_res = $stmt->get_result();
$registration = $reg_res->fetch_object();
$stmt->close();

// Fetch Complaint Statistics
$complaint_stats = ['total' => 0, 'new' => 0, 'pending' => 0, 'resolved' => 0];
$comp_query = "SELECT complaintStatus, COUNT(*) as count FROM complaints WHERE userId = ? GROUP BY complaintStatus";
$stmt = $mysqli->prepare($comp_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$comp_res = $stmt->get_result();

while ($row = $comp_res->fetch_object()) {
    $status = $row->complaintStatus;
    $count = $row->count;
    $complaint_stats['total'] += $count;
    
    if (empty($status) || $status == 'New') $complaint_stats['new'] += $count;
    elseif (strtolower($status) == 'in progress' || strtolower($status) == 'in process') $complaint_stats['pending'] += $count;
    elseif (strtolower($status) == 'closed' || strtolower($status) == 'resolved') $complaint_stats['resolved'] += $count;
}
$stmt->close();

// Fetch Recent Complaints
$recent_complaints = [];
$act_query = "SELECT * FROM complaints WHERE userId = ? ORDER BY registrationDate DESC LIMIT 5";
$stmt = $mysqli->prepare($act_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$act_res = $stmt->get_result();
while ($row = $act_res->fetch_object()) {
    $recent_complaints[] = $row;
}
$stmt->close();

// Fetch Latest Admin Reply (Notification)
$alert_query = "SELECT c.ComplainNumber, h.compalintStatus, h.complaintRemark, h.postingDate 
                FROM complainthistory h 
                JOIN complaints c ON h.complaintid = c.id 
                WHERE c.userId = ? 
                ORDER BY h.postingDate DESC LIMIT 1";
$stmt = $mysqli->prepare($alert_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$alert_res = $stmt->get_result();
$latest_reply = $alert_res->fetch_object();
$stmt->close();

// Fetch Payment Data for Student
$pay_data = null;
$pay_query = "SELECT fees_paid, accommodation_paid, registration_paid, payment_status, fee_control_no FROM userregistration WHERE id = ?";
$stmt = $mysqli->prepare($pay_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$pay_data = $stmt->get_result()->fetch_object();
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Hostel Management</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/student-modern.css">
    <style>
        .dashboard-header-modern {
            background: var(--gradient-primary);
            border-radius: 24px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        .dashboard-header-modern::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        
        .stat-card-modern {
            background: #fff;
            border-radius: 24px;
            padding: 24px;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .stat-icon-box {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include("includes/header.php");?>
    <div class="ts-main-content">
        <?php include("includes/sidebar.php");?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                
                <!-- Dashboard Welcome Header -->
                <div class="dashboard-header-modern animate__animated animate__fadeInDown">
                    <h1 class="fw-800">Welcome Back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
                    <p class="opacity-75">Track your room status, manage complaints, and explore your personal dashboard.</p>
                </div>
                
                <!-- Alerts / Notifications -->
                <div class="row">
                    <div class="col-12">
                        <?php if ($pay_data && empty($pay_data->fee_control_no)): ?>
                        <div class="alert alert-warning border-0 shadow-sm mb-4 animate__animated animate__pulse animate__infinite animate__slower" style="border-radius: 20px; background-color: #fffbeb; border-left: 6px solid var(--warning) !important;">
                            <div class="d-flex align-items-center p-2">
                                <div class="me-4 bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                                    <i class="fas fa-id-card fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-800 mb-1 text-dark">Payment Control Numbers Missing</h6>
                                    <p class="mb-0 small text-muted">Action required: Please request control numbers to proceed with fee payments.</p>
                                </div>
                                <a href="pay-fees.php" class="btn-modern btn-modern-primary px-4 py-2 border-0 shadow-sm" style="font-size: 0.85rem;">Request Now</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($latest_reply): ?>
                        <div class="alert alert-info border-0 shadow-sm mb-5 animate__animated animate__fadeIn" style="border-radius: 20px; background-color: #f0f7ff; border-left: 6px solid var(--primary) !important;">
                            <div class="d-flex align-items-start p-2">
                                <div class="me-4 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                                    <i class="fas fa-bell fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-800 mb-1 text-dark">Admin Replied to Complaint #<?php echo htmlspecialchars($latest_reply->ComplainNumber); ?></h6>
                                    <div class="badge-modern badge-modern-primary mb-2">STATUS: <?php echo strtoupper($latest_reply->compalintStatus); ?></div>
                                    <div class="p-3 bg-white border border-info border-opacity-25 rounded-4 mb-2 text-dark" style="border-left: 3px solid var(--primary);">
                                        "<?php echo htmlspecialchars($latest_reply->complaintRemark); ?>"
                                    </div>
                                    <div class="small text-muted"><i class="far fa-clock me-1"></i> <?php echo date('F j, Y, g:i a', strtotime($latest_reply->postingDate)); ?></div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4 Key Metrics -->
                <div class="row g-4 mb-5">
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card-modern animate__animated animate__fadeInUp">
                            <div class="stat-icon-box bg-primary-light text-primary">
                                <i class="fas fa-bed"></i>
                            </div>
                            <label class="text-muted fw-800 small text-uppercase mb-1 d-block">Room Status</label>
                            <h3 class="fw-800 text-dark mb-1"><?php echo $registration ? htmlspecialchars($registration->roomno) : 'N/A'; ?></h3>
                            <?php if($registration): ?>
                                <span class="badge-modern badge-modern-primary py-1"><?php echo htmlspecialchars($registration->seater); ?> Seater</span>
                            <?php else: ?>
                                <span class="text-muted small">No allocation yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card-modern animate__animated animate__fadeInUp animate__delay-1s">
                            <div class="stat-icon-box bg-success-light text-success">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <label class="text-muted fw-800 small text-uppercase mb-1 d-block">Account Level</label>
                            <h3 class="fw-800 text-dark mb-1"><?php echo $registration ? 'Verified' : 'Unpaid'; ?></h3>
                            <?php if($registration): ?>
                                <span class="text-success small fw-700">TSH <?php echo number_format($registration->feespm); ?> /mon</span>
                            <?php else: ?>
                                <span class="text-danger small fw-700">Action Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card-modern animate__animated animate__fadeInUp animate__delay-2s">
                            <div class="stat-icon-box bg-warning-light text-warning">
                                <i class="fas fa-bug"></i>
                            </div>
                            <label class="text-muted fw-800 small text-uppercase mb-1 d-block">Support Tickets</label>
                            <h3 class="fw-800 text-dark mb-1"><?php echo $complaint_stats['new'] + $complaint_stats['pending']; ?> Pending</h3>
                            <span class="text-muted small fw-600"><?php echo $complaint_stats['total']; ?> tickets combined</span>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card-modern animate__animated animate__fadeInUp animate__delay-3s">
                            <div class="stat-icon-box bg-info-light text-info">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <label class="text-muted fw-800 small text-uppercase mb-1 d-block">Dining Access</label>
                            <h3 class="fw-800 text-dark mb-1"><?php echo ($registration && $registration->foodstatus == 1) ? 'ENABLED' : 'DISABLED'; ?></h3>
                            <span class="text-muted small fw-600">Premium Mess Plan</span>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Activity Feed & Payment -->
                    <div class="col-lg-8">
                        <!-- Recent Complaints -->
                        <div class="card-modern mb-4 animate__animated animate__fadeInUp">
                            <div class="card-header-modern d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-800 text-dark"><i class="fas fa-history me-2 text-primary"></i>My Recent Activity</h5>
                                <a href="my-complaints.php" class="small fw-700 text-primary text-decoration-none">View History <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                            <div class="p-2">
                                <?php if (count($recent_complaints) > 0): ?>
                                    <?php foreach ($recent_complaints as $comp): 
                                        $status = $comp->complaintStatus ?: 'New';
                                        $theme = 'primary';
                                        if (strtolower($status) == 'in process' || strtolower($status) == 'in progress') $theme = 'warning';
                                        elseif (strtolower($status) == 'closed') $theme = 'success';
                                    ?>
                                        <div class="p-3 mb-2 border-bottom d-flex align-items-center justify-content-between hover-bg-light transition-all rounded-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-<?php echo $theme; ?>-light text-<?php echo $theme; ?> rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-ticket-alt"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-800 text-dark mb-0">Ticket #<?php echo $comp->ComplainNumber; ?></div>
                                                    <div class="small text-muted fw-600"><?php echo htmlspecialchars($comp->complaintType); ?></div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge-modern badge-modern-<?php echo $theme; ?> mb-1"><?php echo strtoupper($status); ?></div>
                                                <div class="small text-muted" style="font-size: 0.7rem;"><?php echo date('M d, Y', strtotime($comp->registrationDate)); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="bg-gray-light d-inline-flex p-3 rounded-circle mb-3">
                                            <i class="fas fa-inbox text-gray fs-3"></i>
                                        </div>
                                        <h6 class="fw-800">No Active Complaints</h6>
                                        <p class="text-muted small">Everything looks good! No issues reported.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="card-modern animate__animated animate__fadeInUp">
                            <div class="card-header-modern">
                                <h5 class="mb-0 fw-800 text-dark"><i class="fas fa-tachometer-alt me-2 text-success"></i>Fulfillment Progress</h5>
                            </div>
                            <div class="p-4">
                                <?php if($pay_data): 
                                    $fees_perc = ($pay_data->fees_paid / 1500000) * 100;
                                    $acc_perc = ($pay_data->accommodation_paid / 178500) * 100;
                                    $is_eligible = ($pay_data->fees_paid >= 750000 && $pay_data->accommodation_paid >= 178500);
                                ?>
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-2 d-flex justify-content-between align-items-end">
                                                <span class="small fw-800 text-dark">HOSTEL TUITION FEES</span>
                                                <span class="small fw-700 text-primary"><?php echo number_format($fees_perc, 1); ?>%</span>
                                            </div>
                                            <div class="progress rounded-pill shadow-sm" style="height: 12px; background-color: #f1f5f9;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: <?php echo min(100, $fees_perc); ?>%"></div>
                                            </div>
                                            <div class="mt-2 small text-muted">TSH <?php echo number_format($pay_data->fees_paid); ?> / 1,500,000</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2 d-flex justify-content-between align-items-end">
                                                <span class="small fw-800 text-dark">ACCOMMODATION</span>
                                                <span class="small fw-700 text-success"><?php echo number_format($acc_perc, 1); ?>%</span>
                                            </div>
                                            <div class="progress rounded-pill shadow-sm" style="height: 12px; background-color: #f1f5f9;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: <?php echo min(100, $acc_perc); ?>%"></div>
                                            </div>
                                            <div class="mt-2 small text-muted">TSH <?php echo number_format($pay_data->accommodation_paid); ?> / 178,500</div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3 <?php echo $is_eligible ? 'bg-success-light' : 'bg-warning-light'; ?> rounded-4 mb-0">
                                        <div class="d-flex align-items-center">
                                            <i class="fas <?php echo $is_eligible ? 'fa-check-double text-success' : 'fa-info-circle text-warning'; ?> fs-4 me-3"></i>
                                            <div>
                                                <h6 class="mb-1 fw-800 <?php echo $is_eligible ? 'text-success' : 'text-warning-emphasis'; ?>">
                                                    <?php echo $is_eligible ? 'Allocation Eligible' : 'Eligibility Pending'; ?>
                                                </h6>
                                                <p class="mb-0 small opacity-75 fw-600">
                                                    <?php echo $is_eligible ? 'You have cleared the minimum requirements for room allocation.' : 'Requirement: Pay 100% Accommodation and 50% Tuition to qualify.'; ?>
                                                </p>
                                            </div>
                                            <a href="pay-fees.php" class="ms-auto btn-modern <?php echo $is_eligible ? 'btn-modern-success' : 'btn-modern-primary'; ?> px-4 py-2 border-0 shadow-sm" style="font-size: 0.8rem;">PAYMENT PORTAL</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Tools -->
                    <div class="col-lg-4">
                        <div class="card-modern mb-4 animate__animated animate__fadeInUp">
                            <div class="p-4 d-flex align-items-center">
                                <div class="bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-md" style="width: 70px; height: 70px; font-size: 1.8rem; font-weight: 800;">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fw-800 mb-0 text-dark"><?php echo htmlspecialchars($user_name); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($user_email); ?></p>
                                    <a href="my-profile.php" class="small fw-800 text-primary text-decoration-none">Update Profile <i class="fas fa-pencil-alt ms-1"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="card-modern animate__animated animate__fadeInUp">
                            <div class="card-header-modern">
                                <h5 class="mb-0 fw-800 text-dark"><i class="fas fa-th-large me-2 text-primary"></i>Speed Dial</h5>
                            </div>
                            <div class="p-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <a href="book-hostel.php" class="d-flex flex-column align-items-center p-3 rounded-4 bg-light text-decoration-none transition-all hover-transform hover-shadow">
                                            <i class="fas fa-calendar-alt text-primary fs-3 mb-2"></i>
                                            <span class="small fw-800 text-dark uppercase">Book Room</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="room-details.php" class="d-flex flex-column align-items-center p-3 rounded-4 bg-light text-decoration-none transition-all hover-transform hover-shadow">
                                            <i class="fas fa-door-open text-primary fs-3 mb-2"></i>
                                            <span class="small fw-800 text-dark uppercase">My Room</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="register-complaint.php" class="d-flex flex-column align-items-center p-3 rounded-4 bg-light text-decoration-none transition-all hover-transform hover-shadow">
                                            <i class="fas fa-flag text-primary fs-3 mb-2"></i>
                                            <span class="small fw-800 text-dark uppercase">Report</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="change-password.php" class="d-flex flex-column align-items-center p-3 rounded-4 bg-light text-decoration-none transition-all hover-transform hover-shadow">
                                            <i class="fas fa-shield-alt text-primary fs-3 mb-2"></i>
                                            <span class="small fw-800 text-dark uppercase">Security</span>
                                        </a>
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
    <script src="js/jquery.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>