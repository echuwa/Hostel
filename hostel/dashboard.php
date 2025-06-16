<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Student Hostel Management System Dashboard">
    <meta name="author" content="">
    <meta name="theme-color" content="#3e454c">
    
    <title>Dashboard | Student Hostel</title>
    
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

<body class="dashboard-body">
    <?php include("includes/header.php");?>

    <div class="ts-main-content">
        <?php include("includes/sidebar.php");?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <!-- Welcome Banner -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="welcome-banner p-4 rounded-3 shadow-sm bg-primary text-white">
                            <h2 class="mb-1">Welcome Back, <?php echo $_SESSION['login']; ?>!</h2>
                            <p class="mb-0">Here's what's happening with your hostel today</p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Cards -->
                <div class="row g-4">
                    <!-- My Profile Card -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100 animate__animated animate__fadeInUp">
                            <div class="card-body text-center p-4">
                                <div class="icon-circle bg-primary-light text-primary mb-3 mx-auto">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                                <h3 class="h5 mb-2">My Profile</h3>
                                <p class="text-muted mb-3">View and update your personal information</p>
                                <a href="my-profile.php" class="btn btn-primary btn-sm stretched-link">
                                    View Profile <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- My Room Card -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100 animate__animated animate__fadeInUp animate__delay-1s">
                            <div class="card-body text-center p-4">
                                <div class="icon-circle bg-success-light text-success mb-3 mx-auto">
                                    <i class="fas fa-bed fa-2x"></i>
                                </div>
                                <h3 class="h5 mb-2">My Room</h3>
                                <p class="text-muted mb-3">Check your room details and facilities</p>
                                <a href="room-details.php" class="btn btn-success btn-sm stretched-link">
                                    View Room <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Card (Placeholder for future features) -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100 animate__animated animate__fadeInUp animate__delay-2s">
                            <div class="card-body text-center p-4">
                                <div class="icon-circle bg-info-light text-info mb-3 mx-auto">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                                <h3 class="h5 mb-2">Upcoming Events</h3>
                                <p class="text-muted mb-3">View hostel events and activities</p>
                                <a href="#" class="btn btn-info btn-sm stretched-link">
                                    View Events <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-0 pb-0">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle-sm bg-primary-light text-primary me-3">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Profile Updated</h6>
                                                <small class="text-muted">2 hours ago</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle-sm bg-success-light text-success me-3">
                                                <i class="fas fa-bed"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Room Inspection Completed</h6>
                                                <small class="text-muted">1 day ago</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle-sm bg-warning-light text-warning me-3">
                                                <i class="fas fa-exclamation"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Maintenance Request Submitted</h6>
                                                <small class="text-muted">3 days ago</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/main.js"></script>
    
    <script>
    // Any dashboard-specific JavaScript can go here
    $(document).ready(function() {
        // Add active class to dashboard nav item
        $('#dashboard-nav').addClass('active');
    });
    </script>
</body>
</html>