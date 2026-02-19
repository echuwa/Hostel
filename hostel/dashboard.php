<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
$user_email = $_SESSION['login'];
$user_name = $_SESSION['name'] ?? 'Student'; // Fallback name

// Fetch Registration Details
$reg_query = "SELECT * FROM registration WHERE emailid = ? OR regno = ? ORDER BY id DESC LIMIT 1";
$stmt = $mysqli->prepare($reg_query);
$stmt->bind_param('ss', $user_email, $user_email);
$stmt->execute();
$reg_res = $stmt->get_result();
$registration = $reg_res->fetch_object();
$stmt->close();

// Fetch Complaint Statistics
$complaint_stats = [
    'total' => 0,
    'new' => 0,
    'pending' => 0,
    'resolved' => 0
];

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
    elseif ($status == 'In Progress') $complaint_stats['pending'] += $count;
    elseif ($status == 'Closed' || $status == 'Resolved') $complaint_stats['resolved'] += $count;
}
$stmt->close();

// Fetch Recent Complaints for Activity Feed
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
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Student Hostel Management System Dashboard">
    <meta name="theme-color" content="#3e454c">
    
    <title>Dashboard | Student Hostel</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
            --card-border-radius: 0.75rem;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Nunito', sans-serif;
            margin-left: 0; /* Let style.css handle layout */
        }
        
        .dashboard-header {
            margin-bottom: 1.5rem;
        }

        .welcome-text {
            color: #5a5c69;
            font-weight: 300;
        }

        .stat-card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .card-body {
            padding: 1.25rem;
        }

        .stat-icon {
            font-size: 2rem;
            color: #dddfeb;
        }

        .border-left-primary { border-left: 0.25rem solid var(--primary-color) !important; }
        .border-left-success { border-left: 0.25rem solid var(--success-color) !important; }
        .border-left-info { border-left: 0.25rem solid var(--info-color) !important; }
        .border-left-warning { border-left: 0.25rem solid var(--warning-color) !important; }

        .text-xs {
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            border-top-left-radius: var(--card-border-radius) !important;
            border-top-right-radius: var(--card-border-radius) !important;
        }

        .card-header h6 {
            margin: 0;
            font-weight: 700;
            color: var(--primary-color);
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #e3e6f0;
            transition: background-color 0.2s;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background-color: #f8f9fc;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #fff;
            border-radius: 0.5rem;
            color: #5a5c69;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1rem;
            border-left: 4px solid transparent;
        }

        .quick-action-btn:hover {
            background: #fff;
            color: var(--primary-color);
            transform: translateX(5px);
            border-left: 4px solid var(--primary-color);
        }

        .quick-action-btn i {
            margin-right: 1rem;
            font-size: 1.25rem;
            width: 30px;
            text-align: center;
        }
        
        /* Sidebar collapse handling */
        body.sidebar-collapsed {
            margin-left: 70px;
        }
        
        @media (max-width: 768px) {
            body {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include("includes/header.php");?>

    <div class="ts-main-content">
        <?php include("includes/sidebar.php");?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                
                <!-- Page Heading -->
                <div class="dashboard-header d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    <span class="d-none d-sm-inline-block text-gray-600 small">
                        <?php echo date('l, F j, Y'); ?>
                    </span>
                </div>
                
                <!-- Welcome Banner -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 border-left-primary bg-white animate__animated animate__fadeInDown">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="icon-circle bg-primary text-white rounded-circle p-3">
                                            <i class="fas fa-smile fa-2x"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-gray-800 mb-1">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h4>
                                        <p class="mb-0 text-muted">Here's an overview of your hostel activity.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row g-4 mb-4">
                    <!-- Room Status Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-success shadow h-100 py-2 animate__animated animate__fadeInUp">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Room Allocation</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php if ($registration): ?>
                                                Room <?php echo htmlspecialchars($registration->roomno); ?>
                                                <small class="d-block text-muted text-xs mt-1">Seater: <?php echo htmlspecialchars($registration->seater); ?></small>
                                            <?php else: ?>
                                                Not Allocated
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bed fa-2x text-gray-300" style="color: #e0e0e0;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-info shadow h-100 py-2 animate__animated animate__fadeInUp animate__delay-1s">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Payment Status</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php if ($registration): ?>
                                                Active
                                                <small class="d-block text-muted text-xs mt-1">Fees: <?php echo htmlspecialchars($registration->feespm); ?> / month</small>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-invoice-dollar fa-2x text-gray-300" style="color: #e0e0e0;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Complaints Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-warning shadow h-100 py-2 animate__animated animate__fadeInUp animate__delay-2s">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Complaints</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $complaint_stats['new'] + $complaint_stats['pending']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300" style="color: #e0e0e0;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Food Status or Other Metric -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-primary shadow h-100 py-2 animate__animated animate__fadeInUp animate__delay-3s">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Food Status</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php if ($registration && $registration->foodstatus == 1): ?>
                                                Enabled
                                            <?php else: ?>
                                                Disabled
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-utensils fa-2x text-gray-300" style="color: #e0e0e0;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">

                    <!-- Recent Activity Column -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow mb-4 animate__animated animate__fadeInUp animate__delay-4s">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="activity-feed">
                                    <?php if (count($recent_complaints) > 0): ?>
                                        <?php foreach ($recent_complaints as $comp): ?>
                                            <div class="activity-item d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php 
                                                    $icon_class = 'bg-secondary';
                                                    $status = $comp->complaintStatus;
                                                    if ($status == 'Resolved') $icon_class = 'bg-success';
                                                    elseif ($status == 'In Progress') $icon_class = 'bg-warning';
                                                    elseif ($status == 'New' || empty($status)) $icon_class = 'bg-info';
                                                    ?>
                                                    <div class="icon-circle <?php echo $icon_class; ?> text-white rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-clipboard-list"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="small text-gray-500"><?php echo date('F j, Y', strtotime($comp->registrationDate)); ?></div>
                                                    <span class="font-weight-bold">Complaint #<?php echo htmlspecialchars($comp->ComplainNumber); ?></span>
                                                    <div class="small text-muted"><?php echo htmlspecialchars(substr($comp->complaintDetails, 0, 50)) . '...'; ?></div>
                                                    <span class="badge bg-<?php echo ($status == 'Resolved' ? 'success' : ($status == 'In Progress' ? 'warning' : 'info')); ?>">
                                                        <?php echo !empty($status) ? htmlspecialchars($status) : 'New'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center p-5 text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                                            <p>No recent activity found.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="my-complaints.php" class="text-primary text-decoration-none small">View All Activity <i class="fas fa-arrow-right ml-1"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Column -->
                    <div class="col-lg-4 mb-4">
                        <!-- Quick Actions -->
                        <div class="card shadow mb-4 animate__animated animate__fadeInUp animate__delay-5s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <a href="book-hostel.php" class="quick-action-btn">
                                    <i class="fas fa-calendar-check text-primary"></i>
                                    <div>
                                        <div class="font-weight-bold">Book Hostel</div>
                                        <div class="small text-muted">Reserve your room</div>
                                    </div>
                                </a>
                                <a href="register-complaint.php" class="quick-action-btn">
                                    <i class="fas fa-plus-circle text-warning"></i>
                                    <div>
                                        <div class="font-weight-bold">Register Complaint</div>
                                        <div class="small text-muted">Report an issue</div>
                                    </div>
                                </a>
                                <a href="room-details.php" class="quick-action-btn">
                                    <i class="fas fa-door-open text-success"></i>
                                    <div>
                                        <div class="font-weight-bold">My Room</div>
                                        <div class="small text-muted">View room details</div>
                                    </div>
                                </a>
                                <a href="change-password.php" class="quick-action-btn">
                                    <i class="fas fa-key text-info"></i>
                                    <div>
                                        <div class="font-weight-bold">Change Password</div>
                                        <div class="small text-muted">Update security</div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Profile Summary -->
                        <div class="card shadow mb-4 animate__animated animate__fadeInUp animate__delay-5s">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Profile</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="icon-circle bg-light text-primary rounded-circle mx-auto p-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                    </div>
                                </div>
                                <h5 class="font-weight-bold"><?php echo htmlspecialchars($user_name); ?></h5>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($user_email); ?></p>
                                <hr>
                                <a href="my-profile.php" class="btn btn-primary btn-sm btn-block">View Full Profile</a>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Add active class to dashboard nav item
            $('#dashboard-nav').addClass('active');
        });
    </script>
</body>

</html>