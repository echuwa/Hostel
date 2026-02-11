<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Get counts for dashboard cards
$counts = [];
$queries = [
    'students' => "SELECT count(*) FROM registration",
    'rooms' => "SELECT count(*) FROM rooms",
    'courses' => "SELECT count(*) FROM courses",
    'all_complaints' => "SELECT count(*) FROM complaints",
    'new_complaints' => "SELECT count(*) FROM complaints WHERE complaintStatus IS NULL",
    'inprocess_complaints' => "SELECT count(*) FROM complaints WHERE complaintStatus='In Process'",
    'closed_complaints' => "SELECT count(*) FROM complaints WHERE complaintStatus='Closed'",
    'feedbacks' => "SELECT count(*) FROM feedback"
];

foreach ($queries as $key => $query) {
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $counts[$key] = $count;
    $stmt->close();
}
?>

<!doctype html>
<html lang="en" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Hostel Management System Dashboard">
    <meta name="author" content="">
    <meta name="theme-color" content="#4361ee">
    
    <title>Dashboard | Hostel Management System</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #ef233c;
            --warning-color: #f77f00;
            --success-color: #4cc9f0;
            --info-color: #4895ef;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #495057;
        }
        
        .ts-main-content {
            background-color: #f5f7fa;
        }
        
        .content-wrapper {
            padding: 20px;
        }
        
        .page-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .dashboard-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card .panel-heading {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 15px 20px;
            font-weight: 600;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .dashboard-card .panel-body {
            padding: 25px 20px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .stat-panel {
            text-align: center;
        }
        
        .stat-panel-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .stat-panel-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .block-anchor {
            display: block;
            padding: 12px 20px;
            background-color: rgba(0, 0, 0, 0.03);
            color: #495057;
            text-decoration: none;
            transition: var(--transition);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            font-weight: 500;
        }
        
        .block-anchor:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--primary-color);
        }
        
        .block-anchor i {
            margin-left: 5px;
            transition: var(--transition);
        }
        
        .block-anchor:hover i {
            transform: translateX(3px);
        }
        
        /* Card Colors */
        .card-primary {
            border-left-color: var(--primary-color);
        }
        
        .card-primary .panel-heading {
            background-color: var(--primary-color);
            color: white;
        }
        
        .card-success {
            border-left-color: var(--success-color);
        }
        
        .card-success .panel-heading {
            background-color: var(--success-color);
            color: white;
        }
        
        .card-info {
            border-left-color: var(--info-color);
        }
        
        .card-info .panel-heading {
            background-color: var(--info-color);
            color: white;
        }
        
        .card-danger {
            border-left-color: var(--danger-color);
        }
        
        .card-danger .panel-heading {
            background-color: var(--danger-color);
            color: white;
        }
        
        .card-warning {
            border-left-color: var(--warning-color);
        }
        
        .card-warning .panel-heading {
            background-color: var(--warning-color);
            color: white;
        }
        
        /* Notification */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .notification-container {
            position: relative;
            display: inline-block;
        }
        
        .alert-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 350px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            border-left: 4px solid var(--success-color);
        }
        
        /* Back to Dashboard Button */
        .back-to-dashboard {
            margin-bottom: 20px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stat-panel-number {
                font-size: 28px;
            }
            
            .dashboard-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include("includes/header.php");?>

    <div class="ts-main-content">
        <?php include("includes/sidebar.php");?>
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Notification Alert -->
                <?php if($counts['new_complaints'] > 0): ?>
                <div class="alert alert-success alert-notification" id="newComplaintAlert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong><i class="fas fa-bell"></i> New Complaint Alert!</strong> 
                    You have <?php echo $counts['new_complaints']; ?> new complaint(s) to review.
                    <a href="new-complaints.php" class="alert-link">View now</a>
                </div>
                <?php endif; ?>

                <h2 class="page-title">Dashboard Overview</h2>
                
                <!-- First Row - Basic Stats -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-primary">
                            <div class="panel-heading">Students</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['students']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Total Students</div>
                                </div>
                            </div>
                            <a href="manage-students.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-success">
                            <div class="panel-heading">Rooms</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['rooms']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Total Rooms</div>
                                </div>
                            </div>
                            <a href="manage-rooms.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-info">
                            <div class="panel-heading">Courses</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['courses']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Total Courses</div>
                                </div>
                            </div>
                            <a href="manage-courses.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row - Complaints -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card">
                            <div class="panel-heading">Complaints</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['all_complaints']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Total Complaints</div>
                                </div>
                            </div>
                            <a href="all-complaints.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-danger">
                            <div class="panel-heading">New Complaints</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="notification-container">
                                        <div class="stat-panel-number h1"><?php echo $counts['new_complaints']; ?></div>
                                        <?php if($counts['new_complaints'] > 0): ?>
                                        <span class="notification-badge">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stat-panel-title text-uppercase">Require Attention</div>
                                </div>
                            </div>
                            <a href="new-complaints.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-warning">
                            <div class="panel-heading">In Process</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['inprocess_complaints']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Complaints In Process</div>
                                </div>
                            </div>
                            <a href="inprocess-complaints.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row - Additional Stats -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-success">
                            <div class="panel-heading">Resolved</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['closed_complaints']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Closed Complaints</div>
                                </div>
                            </div>
                            <a href="closed-complaints.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card card-info">
                            <div class="panel-heading">Feedback</div>
                            <div class="panel-body">
                                <div class="stat-panel">
                                    <div class="stat-panel-number h1"><?php echo $counts['feedbacks']; ?></div>
                                    <div class="stat-panel-title text-uppercase">Total Feedback Received</div>
                                </div>
                            </div>
                            <a href="feedbacks.php" class="block-anchor">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Empty column for balance or add another card -->
                    <div class="col-md-4">
                        <div class="panel panel-default dashboard-card">
                            <div class="panel-heading">Quick Actions</div>
                            <div class="panel-body">
                                <div class="text-center">
                                    <a href="new-complaints.php" class="btn btn-primary btn-sm m-1">
                                        <i class="fas fa-plus"></i> Add Complaint
                                    </a>
                                    <a href="registration.php" class="btn btn-success btn-sm m-1">
                                        <i class="fas fa-user-plus"></i> Add Student
                                    </a>
                                    <a href="settings.php" class="btn btn-info btn-sm m-1">
                                        <i class="fas fa-cog"></i> Settings
                                    </a>
                                    <!-- Super Admin Back Button -->
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <div class="d-flex justify-content-between mt-4 back-to-dashboard">
                        <a href="superadmin-dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Super Admin Dashboard
                        </a>
                    </div>
                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/dataTables.bootstrap.min.js"></script>
    <script src="js/Chart.min.js"></script>
    <script src="js/main.js"></script>
    
    <script>
    $(document).ready(function() {
        <?php if($counts['new_complaints'] > 0): ?>
        // Show the alert
        $('#newComplaintAlert').fadeIn();
        
        // Auto-hide after 10 seconds
        setTimeout(function() {
            $('#newComplaintAlert').fadeOut();
        }, 10000);
        <?php endif; ?>
        
        // Close button functionality
        $('.alert-notification .close').click(function() {
            $(this).parent().fadeOut();
        });
        
        // Add hover effect to cards
        $('.dashboard-card').hover(
            function() {
                $(this).css('transform', 'translateY(-5px)');
                $(this).css('box-shadow', '0 10px 25px rgba(0, 0, 0, 0.1)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
                $(this).css('box-shadow', '0 5px 15px rgba(0, 0, 0, 0.1)');
            }
        );
    });
    </script>
    
</body>
</html>