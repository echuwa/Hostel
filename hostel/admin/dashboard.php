<?php
session_start();

// ============================================
// USE ABSOLUTE PATHS FROM ROOT
// ============================================
define('ROOT_PATH', dirname(__DIR__) . '/');

// Include files with absolute paths
include(ROOT_PATH . 'includes/config.php');
include(ROOT_PATH . 'includes/checklogin.php');
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

// Get monthly data for chart
$monthly_data = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$current_month = date('m');

for ($i = 0; $i < 6; $i++) {
    $month_num = $current_month - $i;
    $year = date('Y');
    if ($month_num <= 0) {
        $month_num += 12;
        $year = date('Y') - 1;
    }
    $month_name = $months[$month_num - 1];
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM complaints WHERE MONTH(complaintDate) = ? AND YEAR(complaintDate) = ?");
    $stmt->bind_param("ii", $month_num, $year);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $monthly_data[$month_name] = $count;
    $stmt->close();
}

// Get recent activities
$recent_activities = [];
$stmt = $mysqli->prepare("SELECT 'complaint' as type, id, complaintTitle as title, complaintDate as date, 'pending' as status FROM complaints ORDER BY complaintDate DESC LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
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
    
    <!-- ============================================
         ALL CDNS - HAZINA 404 ERRORS
         ============================================ -->
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- AOS Animation CDN -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- ============================================
         LOCAL FILES - USE FULL URL OR REMOVE IF NOT EXISTS
         ============================================ -->
    <?php if(file_exists(ROOT_PATH . 'css/bootstrap.min.css')): ?>
    <link rel="stylesheet" href="/hostel/css/bootstrap.min.css">
    <?php endif; ?>
    
    <?php if(file_exists(ROOT_PATH . 'css/dataTables.bootstrap.min.css')): ?>
    <link rel="stylesheet" href="/hostel/css/dataTables.bootstrap.min.css">
    <?php endif; ?>
    
    <?php if(file_exists(ROOT_PATH . 'css/style.css')): ?>
    <link rel="stylesheet" href="/hostel/css/style.css">
    <?php endif; ?>
    
    <style>
        /* ============================================
             ALL STYLES INCLUDED DIRECTLY - NO 404 ERRORS!
             ============================================ */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --secondary: #3f37c9;
            --success: #06d6a0;
            --danger: #ef233c;
            --warning: #ffb703;
            --info: #4cc9f0;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --border-radius: 20px;
            --card-radius: 16px;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }

        .ts-main-content {
            background: linear-gradient(135deg, #f1f5f9 0%, #e6edf5 100%);
            min-height: 100vh;
        }

        .content-wrapper {
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            position: relative;
            padding-bottom: 12px;
            margin: 0;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--info));
            border-radius: 2px;
        }

        /* Date Filter */
        .date-filter {
            display: flex;
            gap: 10px;
            align-items: center;
            background: white;
            padding: 8px 16px;
            border-radius: 40px;
            box-shadow: var(--shadow-sm);
        }

        .date-filter i {
            color: var(--primary);
        }

        .date-filter span {
            font-weight: 500;
            color: var(--dark);
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--card-radius);
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .welcome-text {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 20px;
        }

        .welcome-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-info h4 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .stat-info p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }

        .welcome-illustration {
            position: relative;
            z-index: 1;
        }

        .welcome-illustration i {
            font-size: 120px;
            opacity: 0.2;
            color: white;
        }

        /* Super Admin Button */
        .super-admin-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
            text-decoration: none;
            margin-top: 15px;
        }

        .super-admin-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--info));
        }

        .stat-info h3 {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-trend {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--success);
        }

        .stat-icon-wrapper {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--primary);
        }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .chart-more {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Complaints Status */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .status-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }

        .status-icon.new { background: #fee2e2; color: var(--danger); }
        .status-icon.process { background: #fff3cd; color: var(--warning); }
        .status-icon.closed { background: #d1e7dd; color: var(--success); }
        .status-icon.feedback { background: #e2e3e5; color: var(--gray); }

        .status-card h4 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .status-card p {
            font-size: 14px;
            color: var(--gray);
            margin: 0;
            font-weight: 500;
        }

        /* Recent Activities */
        .activities-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .activities-list {
            margin-top: 20px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .activity-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: var(--gray);
        }

        .activity-meta i {
            margin-right: 5px;
        }

        .activity-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.process { background: #cce5ff; color: #004085; }
        .status-badge.closed { background: #d4edda; color: #155724; }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 30px;
        }

        .quick-action-btn {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 40px;
            padding: 14px 24px;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: var(--transition);
            font-weight: 500;
        }

        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .quick-action-btn i {
            font-size: 18px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-card {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .stat-item {
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fadeInUp {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Loading Spinner */
        .chart-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--primary);
        }
    </style>
</head>

<body>
    <?php include(ROOT_PATH . 'includes/header.php');?>

    <div class="ts-main-content">
        <?php include(ROOT_PATH . 'includes/sidebar.php');?>
        <div class="content-wrapper">
            
            <!-- Page Header -->
            <div class="page-header fadeInUp">
                <h1 class="page-title">Dashboard Overview</h1>
                <div class="date-filter">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F d, Y'); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <!-- Welcome Card with Super Admin Button -->
            <div class="welcome-card fadeInUp" data-aos="fade-up">
                <div class="welcome-content">
                    <h2 class="welcome-title">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['login']); ?>! 👋
                    </h2>
                    <p class="welcome-text">
                        Here's what's happening with your hostel management today.
                    </p>
                    
                    <?php if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1): ?>
                    <a href="/hostel/admin/superadmin-dashboard.php" class="super-admin-btn">
                        <i class="fas fa-shield-alt"></i>
                        <span>Switch to Super Admin Dashboard</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <?php endif; ?>
                    
                    <div class="welcome-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $counts['students']; ?></h4>
                                <p>Total Students</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $counts['rooms']; ?></h4>
                                <p>Total Rooms</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo $counts['all_complaints']; ?></h4>
                                <p>Total Complaints</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="welcome-illustration">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>

            <!-- Stats Cards Grid -->
            <div class="stats-grid">
                <!-- Students Card -->
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-info">
                        <h3>Students</h3>
                        <div class="stat-number"><?php echo $counts['students']; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> +12% this month
                        </div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #eef2ff; color: #4361ee;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <!-- Rooms Card -->
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-info">
                        <h3>Rooms</h3>
                        <div class="stat-number"><?php echo $counts['rooms']; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-minus"></i> 85% occupancy
                        </div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #e0f2fe; color: #0ea5e9;">
                        <i class="fas fa-door-open"></i>
                    </div>
                </div>

                <!-- Courses Card -->
                <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-info">
                        <h3>Courses</h3>
                        <div class="stat-number"><?php echo $counts['courses']; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> +2 this year
                        </div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #fae8ff; color: #c026d3;">
                        <i class="fas fa-book"></i>
                    </div>
                </div>

                <!-- Feedbacks Card -->
                <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-info">
                        <h3>Feedbacks</h3>
                        <div class="stat-number"><?php echo $counts['feedbacks']; ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-star"></i> 4.8 avg rating
                        </div>
                    </div>
                    <div class="stat-icon-wrapper" style="background: #ffedd5; color: #ea580c;">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>

            <!-- Complaints Status Cards -->
            <div class="status-grid">
                <div class="status-card" data-aos="zoom-in" data-aos-delay="100">
                    <div class="status-icon new">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4><?php echo $counts['new_complaints']; ?></h4>
                    <p>New Complaints</p>
                </div>
                
                <div class="status-card" data-aos="zoom-in" data-aos-delay="200">
                    <div class="status-icon process">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4><?php echo $counts['inprocess_complaints']; ?></h4>
                    <p>In Process</p>
                </div>
                
                <div class="status-card" data-aos="zoom-in" data-aos-delay="300">
                    <div class="status-icon closed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4><?php echo $counts['closed_complaints']; ?></h4>
                    <p>Closed</p>
                </div>
                
                <div class="status-card" data-aos="zoom-in" data-aos-delay="400">
                    <div class="status-icon feedback">
                        <i class="fas fa-comment"></i>
                    </div>
                    <h4><?php echo $counts['all_complaints']; ?></h4>
                    <p>Total Complaints</p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <!-- Complaints Overview Chart -->
                <div class="chart-card" data-aos="fade-right">
                    <div class="chart-header">
                        <h3 class="chart-title">Complaints Overview</h3>
                        <a href="all-complaints.php" class="chart-more">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="chart-container">
                        <canvas id="complaintsChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trends Chart -->
                <div class="chart-card" data-aos="fade-left">
                    <div class="chart-header">
                        <h3 class="chart-title">Monthly Trends</h3>
                        <a href="#" class="chart-more">
                            Last 6 Months <i class="fas fa-chevron-down"></i>
                        </a>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="activities-card" data-aos="fade-up">
                <div class="chart-header">
                    <h3 class="chart-title">Recent Activities</h3>
                    <a href="all-complaints.php" class="chart-more">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="activities-list">
                    <?php if(empty($recent_activities)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent activities</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-meta">
                                    <span><i class="fas fa-clock"></i> <?php echo date('M d, H:i', strtotime($activity['date'])); ?></span>
                                    <span><i class="fas fa-tag"></i> <?php echo ucfirst($activity['type']); ?></span>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $activity['status']; ?>">
                                <?php echo ucfirst($activity['status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="registration.php" class="quick-action-btn" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-user-plus"></i>
                    Add Student
                </a>
                <a href="new-complaints.php" class="quick-action-btn" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-plus-circle"></i>
                    New Complaint
                </a>
                <a href="manage-rooms.php" class="quick-action-btn" data-aos="fade-up" data-aos-delay="300">
                    <i class="fas fa-door-closed"></i>
                    Manage Rooms
                </a>
                <a href="feedbacks.php" class="quick-action-btn" data-aos="fade-up" data-aos-delay="400">
                    <i class="fas fa-star"></i>
                    View Feedbacks
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================
         ALL JS CDNS - HAZINA 404 ERRORS
         ============================================ -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });

        $(document).ready(function() {
            // Complaints Chart
            const complaintsCtx = document.getElementById('complaintsChart').getContext('2d');
            new Chart(complaintsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['New', 'In Process', 'Closed'],
                    datasets: [{
                        data: [
                            <?php echo $counts['new_complaints']; ?>,
                            <?php echo $counts['inprocess_complaints']; ?>,
                            <?php echo $counts['closed_complaints']; ?>
                        ],
                        backgroundColor: [
                            '#ef233c',
                            '#ffb703',
                            '#06d6a0'
                        ],
                        borderWidth: 0,
                        borderRadius: 10,
                        spacing: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: 'Inter',
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        }
                    }
                }
            });

            // Trends Chart
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_reverse(array_keys($monthly_data))); ?>,
                    datasets: [{
                        label: 'Complaints',
                        data: <?php echo json_encode(array_reverse(array_values($monthly_data))); ?>,
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Auto-hide notification after 5 seconds
            <?php if($counts['new_complaints'] > 0): ?>
            setTimeout(function() {
                $('.alert-notification').fadeOut();
            }, 5000);
            <?php endif; ?>

            // Animate numbers
            function animateNumbers() {
                $('.stat-number').each(function() {
                    const $this = $(this);
                    const target = parseInt($this.text());
                    $({ count: 0 }).animate({ count: target }, {
                        duration: 1500,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.count));
                        },
                        complete: function() {
                            $this.text(this.count);
                        }
                    });
                });
            }
            animateNumbers();
        });
    </script>
</body>
</html>