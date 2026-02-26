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
    <!-- Original CSS for Sidebar -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .content-wrapper { padding: 30px; }
        
        .dashboard-header {
            background: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
            border-radius: 16px;
            padding: 30px 40px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
        }
        .dashboard-header::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .dashboard-header h1 { font-weight: 800; font-size: 1.8rem; margin: 0 0 5px; }
        .dashboard-header p { margin: 0; opacity: 0.9; font-weight: 500; }
        
        /* Modern Cards */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            z-index: 1;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.08);
        }
        .stat-icon {
            width: 55px; height: 55px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #fff;
            margin-right: 20px;
        }
        .bg-blue { background: linear-gradient(135deg, #4361ee, #4895ef); }
        .bg-green { background: linear-gradient(135deg, #06d6a0, #2dc653); }
        .bg-orange { background: linear-gradient(135deg, #f77f00, #ffba08); }
        .bg-purple { background: linear-gradient(135deg, #7209b7, #b5179e); }
        
        .stat-info h3 { font-size: 1.4rem; font-weight: 800; color: #2b3452; margin: 0 0 5px; }
        .stat-info p { margin: 0; font-size: 0.85rem; color: #8f9bb3; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Activity Widget */
        .activity-widget {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .widget-header {
            padding: 20px 25px;
            border-bottom: 1px solid #edf1f7;
            display: flex; justify-content: space-between; align-items: center;
        }
        .widget-header h4 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #2b3452; }
        
        .activity-item {
            padding: 20px 25px;
            border-bottom: 1px solid #edf1f7;
            display: flex; align-items: flex-start;
            transition: background 0.3s;
        }
        .activity-item:hover { background: #f8f9fc; }
        .activity-item:last-child { border-bottom: none; }
        
        .activity-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-right: 15px; font-size: 1rem; color: #fff; flex-shrink: 0;
        }
        .activity-details h5 { font-size: 0.95rem; font-weight: 700; color: #2b3452; margin: 0 0 5px; }
        .activity-details p { margin: 0; font-size: 0.85rem; color: #8f9bb3; }
        
        .badge { padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.5px; }
        .badge-new { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
        .badge-process { background: rgba(246, 194, 62, 0.15); color: #d9a300; }
        .badge-closed { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
        
        /* Quick Actions Grid */
        .quick-action-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .quick-action-btn {
            background: #fff;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            color: #2b3452;
            text-decoration: none;
            border: 1px solid #edf1f7;
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            border-color: #4361ee;
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.1);
            color: #4361ee;
            transform: translateY(-3px);
        }
        .quick-action-btn i { font-size: 1.8rem; margin-bottom: 10px; display: block; background: -webkit-linear-gradient(135deg, #4361ee, #7b2ff7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .quick-action-btn span { font-size: 0.85rem; font-weight: 700; }
        
        @media (max-width: 768px) {
            .quick-action-grid { grid-template-columns: 1fr; }
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
                <div class="dashboard-header animate__animated animate__fadeInDown">
                    <h1>Welcome Back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
                    <p>Track your room status, manage complaints, and explore your dashboard.</p>
                </div>
                
                <!-- NEW: Control Number Request Alert -->
                <?php if ($pay_data && empty($pay_data->fee_control_no)): ?>
                <div class="alert alert-warning animate__animated animate__pulse shadow-sm border-0 mb-4" style="border-radius: 12px; background-color: #fff9e6; border-left: 5px solid #ffb703 !important;">
                    <div class="d-flex align-items-center">
                        <div class="me-3 fs-3 text-warning"><i class="fas fa-id-card"></i></div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0" style="color: #856404;">Payment Control Numbers Missing</h6>
                            <p class="mb-0 small text-muted">You need to generate control numbers to proceed with payments for fees and room allocation.</p>
                        </div>
                        <a href="pay-fees.php" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold">Request Control Numbers</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($latest_reply): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="background-color: #e8f4fd; border: 1px solid #b8daff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 25px;">
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="background: linear-gradient(135deg, #4361ee, #4895ef); color: white; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h5 style="margin: 0 0 5px; color: #2b3452; font-weight: 700;">Admin Replied to Complaint #<?php echo htmlspecialchars($latest_reply->ComplainNumber); ?></h5>
                            <p style="margin: 0; color: #4a5568;"><strong style="color: #4361ee;">Status updated to: <?php echo htmlspecialchars($latest_reply->compalintStatus); ?></strong></p>
                            <p style="margin: 5px 0 0; background: white; padding: 10px 15px; border-radius: 8px; border-left: 3px solid #4361ee; color: #2b3452; font-size: 0.95rem;">
                                "<?php echo htmlspecialchars($latest_reply->complaintRemark); ?>"
                            </p>
                            <p style="margin: 5px 0 0; font-size: 0.8rem; color: #858796;"><i class="far fa-clock me-1"></i> <?php echo date('F j, Y, g:i a', strtotime($latest_reply->postingDate)); ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="top: 15px; right: 15px;"></button>
                </div>
                <?php endif; ?>

                <!-- 4 Key Metrics -->
                <div class="row g-4 mb-4">
                    <!-- Room Allocation -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card animate__animated animate__fadeInUp">
                            <div class="stat-icon bg-blue"><i class="fas fa-bed"></i></div>
                            <div class="stat-info">
                                <p>Room Number</p>
                                <h3><?php echo $registration ? htmlspecialchars($registration->roomno) : 'N/A'; ?></h3>
                                <?php if($registration): ?>
                                    <span style="font-size: 0.75rem; color: #858796;"><?php echo htmlspecialchars($registration->seater); ?> Seater</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Status -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card animate__animated animate__fadeInUp animate__delay-1s">
                            <div class="stat-icon bg-green"><i class="fas fa-wallet"></i></div>
                            <div class="stat-info">
                                <p>Fee Status</p>
                                <h3><?php echo $registration ? 'Active' : 'Unpaid'; ?></h3>
                                <?php if($registration): ?>
                                    <span style="font-size: 0.75rem; color: #858796;">TSH <?php echo htmlspecialchars($registration->feespm); ?> /mon</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Complaints -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card animate__animated animate__fadeInUp animate__delay-2s">
                            <div class="stat-icon bg-orange"><i class="fas fa-bug"></i></div>
                            <div class="stat-info">
                                <p>Pending Issues</p>
                                <h3><?php echo $complaint_stats['new'] + $complaint_stats['pending']; ?></h3>
                                <span style="font-size: 0.75rem; color: #858796;">Out of <?php echo $complaint_stats['total']; ?> total</span>
                            </div>
                        </div>
                    </div>
                    <!-- Services -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="stat-card animate__animated animate__fadeInUp animate__delay-3s">
                            <div class="stat-icon bg-purple"><i class="fas fa-utensils"></i></div>
                            <div class="stat-info">
                                <p>Food Service</p>
                                <h3><?php echo ($registration && $registration->foodstatus == 1) ? 'Enabled' : 'Disabled'; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="row g-4">
                    <!-- Activity Feed -->
                    <div class="col-lg-8">
                        <div class="activity-widget animate__animated animate__fadeInUp animate__delay-4s">
                            <div class="widget-header">
                                <h4><i class="fas fa-history me-2 text-primary"></i>Recent Complaints</h4>
                                <a href="my-complaints.php" class="btn btn-sm btn-outline-primary" style="font-weight: 600; border-radius: 8px;">View All</a>
                            </div>
                            <div class="widget-content">
                                <?php if (count($recent_complaints) > 0): ?>
                                    <?php foreach ($recent_complaints as $comp): 
                                        $status = $comp->complaintStatus;
                                        if (empty($status) || $status == 'New') { $bg = 'bg-primary'; $badge = 'badge-new'; $icon='fa-exclamation'; $lbl='New'; }
                                        elseif (strtolower($status) == 'in process' || strtolower($status) == 'in progress') { $bg = 'bg-warning text-dark'; $badge = 'badge-process'; $icon='fa-spinner fa-spin'; $lbl='In Process'; }
                                        else { $bg = 'bg-success'; $badge = 'badge-closed'; $icon='fa-check'; $lbl='Closed'; }
                                    ?>
                                        <div class="activity-item">
                                            <div class="activity-icon <?php echo $bg; ?>"><i class="fas <?php echo $icon; ?>"></i></div>
                                            <div class="activity-details flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h5>Complaint #<?php echo htmlspecialchars($comp->ComplainNumber); ?></h5>
                                                    <span class="badge <?php echo $badge; ?>"><?php echo $lbl; ?></span>
                                                </div>
                                                <p class="mb-1 text-dark"><?php echo htmlspecialchars($comp->complaintType); ?></p>
                                                <p class="mb-0"><small><i class="far fa-calendar-alt me-1"></i> <?php echo date('F j, Y, g:i a', strtotime($comp->registrationDate)); ?></small></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3"><img src="https://cdni.iconscout.com/illustration/premium/thumb/folder-is-empty-4064360-3363921.png" width="120" alt="empty" style="opacity: 0.6;"></div>
                                        <h5 style="color: #2b3452;">No active complaints</h5>
                                        <p style="color: #8f9bb3; font-size: 0.9rem;">You haven't reported any issues yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- NEW: Payment Progress Widget -->
                        <div class="activity-widget mt-4 animate__animated animate__fadeInUp">
                            <div class="widget-header">
                                <h4><i class="fas fa-money-bill-wave me-2 text-success"></i>My Payment Status</h4>
                            </div>
                            <div class="widget-content p-4">
                                <?php if($pay_data): 
                                    $fees_perc = ($pay_data->fees_paid / 1500000) * 100;
                                    $acc_perc = ($pay_data->accommodation_paid / 178500) * 100;
                                    $is_eligible = ($pay_data->fees_paid >= 750000 && $pay_data->accommodation_paid >= 178500);
                                ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small fw-bold">School Fees (TSH 1.5M)</span>
                                                <span class="small"><?php echo number_format($pay_data->fees_paid); ?> /=</span>
                                            </div>
                                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, $fees_perc); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($fees_perc, 1); ?>% Paid</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small fw-bold">Accommodation (TSH 178.5K)</span>
                                                <span class="small"><?php echo number_format($pay_data->accommodation_paid); ?> /=</span>
                                            </div>
                                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $acc_perc); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($acc_perc, 1); ?>% Paid</small>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="alert <?php echo $is_eligible ? 'alert-success' : 'alert-warning'; ?> py-2 px-3 m-0" style="border-radius: 10px; font-size: 0.85rem;">
                                            <i class="fas <?php echo $is_eligible ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-1"></i>
                                            <?php if($is_eligible): ?>
                                                <strong>Hooray!</strong> You have met the minimum payment threshold for room allocation.
                                            <?php else: ?>
                                                <strong>Notice:</strong> You must pay 100% Accommodation and at least 50% Fees to book a room.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12 text-end mt-2">
                                        <a href="pay-fees.php" class="btn btn-sm btn-dark rounded-pill px-3 py-1 fw-bold">
                                            <i class="fas fa-money-check-alt me-1"></i> Pay Fees / View Control Numbers
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Side Tools -->
                    <div class="col-lg-4">
                        <div class="activity-widget mb-4 animate__animated animate__fadeInUp animate__delay-5s">
                            <div class="widget-header">
                                <h4><i class="fas fa-bolt me-2 text-warning"></i>Quick Shortcuts</h4>
                            </div>
                            <div class="widget-content p-4">
                                <div class="quick-action-grid">
                                    <a href="book-hostel.php" class="quick-action-btn">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span>Book Room</span>
                                    </a>
                                    <a href="room-details.php" class="quick-action-btn">
                                        <i class="fas fa-door-open"></i>
                                        <span>My Room</span>
                                    </a>
                                    <a href="register-complaint.php" class="quick-action-btn">
                                        <i class="fas fa-flag"></i>
                                        <span>Report Issue</span>
                                    </a>
                                    <a href="change-password.php" class="quick-action-btn">
                                        <i class="fas fa-lock"></i>
                                        <span>Password</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Summary -->
                        <div class="activity-widget animate__animated animate__fadeInUp animate__delay-5s">
                            <div class="widget-content p-4 text-center">
                                <div class="icon-circle mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #4361ee, #7b2ff7); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 700; box-shadow: 0 8px 15px rgba(67, 97, 238, 0.3);">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                                <h5 class="mb-1" style="font-weight: 800; color: #2b3452;"><?php echo htmlspecialchars($user_name); ?></h5>
                                <p class="mb-3" style="color: #8f9bb3; font-size: 0.9rem;"><?php echo htmlspecialchars($user_email); ?></p>
                                <a href="my-profile.php" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); border: none; font-weight: 600; padding: 12px; border-radius: 10px;">Edit Profile</a>
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