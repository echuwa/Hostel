<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ============================================
// Include admin files
// ============================================
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Get counts for dashboard cards
$counts = [];

$block_cond_rooms = "";
$block_cond_reg = "";
$block_join_complaints = "";
$block_cond_complaints = "";

if(isset($_SESSION['assigned_block']) && !empty($_SESSION['assigned_block'])) {
    $block = $_SESSION['assigned_block'];
    $block_cond_rooms = " WHERE room_no LIKE '$block%'";
    $block_cond_reg = " WHERE roomno LIKE '$block%'";
    $block_join_complaints = " JOIN userregistration u ON c.userId = u.id JOIN registration r ON u.regNo = r.regno";
    $block_cond_complaints = " AND r.roomno LIKE '$block%'";
}

$queries = [
    'students' => "SELECT count(*) FROM registration $block_cond_reg",
    'rooms' => "SELECT count(*) FROM rooms $block_cond_rooms",
    'courses' => "SELECT count(*) FROM courses",
    'all_complaints' => "SELECT count(*) FROM complaints c $block_join_complaints WHERE 1=1 $block_cond_complaints",
    'new_complaints' => "SELECT count(*) FROM complaints c $block_join_complaints WHERE (c.complaintStatus IS NULL OR c.complaintStatus='New') $block_cond_complaints",
    'inprocess_complaints' => "SELECT count(*) FROM complaints c $block_join_complaints WHERE (c.complaintStatus='In Process' OR c.complaintStatus='In Progress') $block_cond_complaints",
    'closed_complaints' => "SELECT count(*) FROM complaints c $block_join_complaints WHERE (c.complaintStatus='Closed' OR c.complaintStatus='Resolved') $block_cond_complaints",
    'feedbacks' => "SELECT count(*) FROM feedback",
    'pending_students' => "SELECT count(*) FROM userregistration WHERE status='Pending'"
];

// Identify Most Common Problem
$common_problem = "None";
$problem_query = "SELECT c.complaintType, COUNT(*) as count FROM complaints c $block_join_complaints WHERE 1=1 $block_cond_complaints GROUP BY c.complaintType ORDER BY count DESC LIMIT 1";
if ($stmt = $mysqli->prepare($problem_query)) {
    $stmt->execute();
    $stmt->bind_result($p_type, $p_count);
    if($stmt->fetch()) {
        $common_problem = $p_type;
    }
    $stmt->close();
}

foreach ($queries as $key => $query) {
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $counts[$key] = $count;
        $stmt->close();
    } else {
        $counts[$key] = 0;
    }
}

// Get monthly data for chart (Registrations & Revenue)
$chart_revenue = [];
$chart_occupancy = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$current_month = date('m');

$block_cond_reg_only = "";
$block_join_logs = "";
$block_cond_logs = "";

if(isset($_SESSION['assigned_block']) && !empty($_SESSION['assigned_block'])) {
    $block = $_SESSION['assigned_block'];
    $block_cond_reg_only = " AND roomno LIKE '$block%'";
    $block_join_logs = " JOIN registration r ON p.regNo = r.regno";
    $block_cond_logs = " AND r.roomno LIKE '$block%'";
}

for ($i = 0; $i < 6; $i++) {
    $month_num = $current_month - $i;
    $year = date('Y');
    if ($month_num <= 0) {
        $month_num += 12;
        $year = date('Y') - 1;
    }
    $month_name = $months[$month_num - 1];
    
    // 1. Monthly Occupancy (Registrations)
    $occQ = "SELECT COUNT(*) FROM registration WHERE MONTH(reg_date) = ? AND YEAR(reg_date) = ? $block_cond_reg_only";
    $stmt = $mysqli->prepare($occQ);
    $stmt->bind_param("ii", $month_num, $year);
    $stmt->execute();
    $stmt->bind_result($occCount);
    $stmt->fetch();
    $chart_occupancy[$month_name] = $occCount;
    $stmt->close();

    // 2. Monthly Revenue (Payments)
    $revQ = "SELECT SUM(p.amount) FROM payment_logs p $block_join_logs WHERE MONTH(p.created_at) = ? AND YEAR(p.created_at) = ? $block_cond_logs";
    $stmt = $mysqli->prepare($revQ);
    $stmt->bind_param("ii", $month_num, $year);
    $stmt->execute();
    $stmt->bind_result($revSum);
    $stmt->fetch();
    $chart_revenue[$month_name] = $revSum ?: 0;
    $stmt->close();
}

// Get recent activities
$recent_activities = [];
$recentQ = "SELECT 
    'complaint' as type, 
    c.id, 
    c.complaintType as title,
    c.complaintDetails as message,
    c.registrationDate as date, 
    c.complaintStatus as status 
FROM complaints c $block_join_complaints 
WHERE 1=1 $block_cond_complaints
ORDER BY c.registrationDate DESC 
LIMIT 5";

$stmt = $mysqli->prepare($recentQ);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}
$stmt->close();

// Get username safely
$display_name = $_SESSION['username'] ?? $_SESSION['login'] ?? $_SESSION['name'] ?? $_SESSION['user'] ?? 'Admin';
$display_name = htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Dashboard | Hostel Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Unified Admin CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .header-action-btn {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: #fff; border: 1px solid var(--gray-light);
            color: var(--gray); transition: all 0.3s; position: relative;
        }
        .header-action-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }
        .notif-dot { position: absolute; top: 10px; right: 10px; width: 10px; height: 10px; background: var(--danger); border: 2px solid #fff; border-radius: 50%; }
        
        .activity-item {
            padding: 16px; border-radius: 16px; background: #f8fafc;
            border-left: 4px solid var(--primary); margin-bottom: 12px;
            transition: all 0.2s;
        }
        .activity-item:hover { transform: translateX(5px); background: #f1f5f9; }
        
        .chart-card { min-height: 400px; padding: 30px; }
        .greeting-card {
            background: var(--gradient-primary); color: #fff;
            padding: 40px; border-radius: 24px; margin-bottom: 30px;
            position: relative; overflow: hidden;
        }
        .greeting-card::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                
                <!-- TOP HEADER -->
                <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" style="position: relative; z-index: 1050;">
                    <div>
                        <h4 class="fw-800 mb-1">Administrative Overview</h4>
                        <p class="text-muted small fw-600 mb-0"><i class="fas fa-calendar-alt me-2"></i>Status updated as of <?php echo date('F d, Y - H:i'); ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="dropdown">
                            <button class="header-action-btn" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if(($counts['new_complaints'] + $counts['pending_students']) > 0): ?>
                                    <span class="notif-dot"></span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-3" style="width: 320px; z-index: 9999 !important; position: absolute !important;">
                                <h6 class="fw-800 mb-3 px-2">Recent Notifications</h6>
                                <?php if ($counts['pending_students'] > 0): ?>
                                    <a href="manage-students.php" class="dropdown-item p-3 bg-light rounded-3 mb-2 d-flex align-items-center gap-3">
                                        <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-user-clock"></i>
                                        </div>
                                        <div>
                                            <div class="fw-800 small text-dark"><?php echo $counts['pending_students']; ?> Pending Approvals</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Action required for registration</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <?php if ($counts['new_complaints'] > 0): ?>
                                    <a href="new-complaints.php" class="dropdown-item p-3 bg-light rounded-3 mb-2 d-flex align-items-center gap-3">
                                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div>
                                            <div class="fw-800 small text-dark"><?php echo $counts['new_complaints']; ?> New Complaints</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">New support tickets reported</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <a href="all-complaints.php" class="dropdown-item text-center fw-700 text-primary small py-2">See Master Console</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GREETING -->
                <div class="greeting-card shadow-lg" data-aos="fade-up">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h2 class="fw-800 mb-2">Welcome back, <?php echo $display_name; ?></h2>
                            <p class="opacity-75 fw-600 mb-0">
                                Current Strategy: Focus on <strong><?php echo htmlspecialchars($common_problem); ?></strong> issues. 
                                Resolve <strong><?php echo $counts['new_complaints'] + $counts['inprocess_complaints']; ?></strong> active cases to maintain health.
                            </p>
                        </div>
                        <div class="col-lg-4 text-end d-none d-lg-block" style="position: relative; z-index: 100;">
                            <a href="manage-students.php" class="btn btn-light rounded-pill px-4 fw-800 text-primary shadow-sm" style="position: relative; z-index: 101;">Verification Hub</a>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-xl-3 col-md-6">
                        <div class="card-modern stat-card clickable-card">
                            <a href="manage-students.php" class="card-link" title="View Students"></a>
                            <div>
                                <div class="stat-label">Occupancy Health</div>
                                <div class="stat-value counter"><?php echo $counts['students']; ?></div>
                                <div class="text-success small fw-700"><i class="fas fa-arrow-up me-1"></i>Verified Residents</div>
                            </div>
                            <div class="stat-icon bg-primary text-white">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card-modern stat-card clickable-card">
                            <a href="manage-rooms.php" class="card-link" title="View Rooms"></a>
                            <div>
                                <div class="stat-label">Total Inventory</div>
                                <div class="stat-value counter"><?php echo $counts['rooms']; ?></div>
                                <div class="text-primary small fw-700"><i class="fas fa-bed me-1"></i>Dormitory units</div>
                            </div>
                            <div class="stat-icon bg-info text-white">
                                <i class="fas fa-door-open"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card-modern stat-card clickable-card">
                            <a href="all-complaints.php" class="card-link" title="Support Portal"></a>
                            <div>
                                <div class="stat-label">Support Efficiency</div>
                                <div class="stat-value counter"><?php echo $counts['all_complaints'] > 0 ? round(($counts['closed_complaints'] / $counts['all_complaints']) * 100) : 0; ?>%</div>
                                <div class="text-danger small fw-700">
                                    <i class="fas fa-check-circle me-1"></i> <?php echo $counts['closed_complaints']; ?> Solved of <?php echo $counts['all_complaints']; ?>
                                </div>
                            </div>
                            <div class="stat-icon bg-danger text-white">
                                <i class="fas fa-life-ring"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card-modern stat-card clickable-card">
                            <a href="manage-students.php?status=Pending" class="card-link" title="Vetting Hub"></a>
                            <div>
                                <div class="stat-label">Vetting Pipeline</div>
                                <div class="stat-value counter"><?php echo $counts['pending_students']; ?></div>
                                <div class="text-warning small fw-700"><i class="fas fa-clock me-1"></i>Registration Vetting</div>
                            </div>
                            <div class="stat-icon bg-warning text-white">
                                <i class="fas fa-shield-virus"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- TRENDS CHART -->
                    <div class="col-lg-8" data-aos="fade-right">
                        <div class="card-modern chart-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="fw-800 mb-0">Hostel Growth & Collection Performance</h5>
                                    <p class="text-muted small fw-600 mb-0">Monthly Admissions vs. Revenue Inflow</p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-light rounded-pill dropdown-toggle fw-700 small" data-bs-toggle="dropdown">Last 6 Months</button>
                                </div>
                            </div>
                            <div style="height: 300px;">
                                <canvas id="trendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT FEEDBACKS/ACTIVITIES -->
                    <div class="col-lg-4" data-aos="fade-left">
                        <div class="card-modern p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-800 mb-0">Recent Activities</h5>
                                <a href="access-log.php" class="text-primary small fw-800">Logs</a>
                            </div>
                            
                            <div class="activities-list">
                                <?php if(empty($recent_activities)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-clipboard-check fa-3x text-light mb-3"></i>
                                        <p class="text-muted fw-600">No recent transactions</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="badge rounded-pill bg-primary-light text-primary small fw-800"><?php echo htmlspecialchars($activity['title']); ?></span>
                                            <small class="text-muted fw-600"><?php echo date('H:i', strtotime($activity['date'])); ?></small>
                                        </div>
                                        <div class="text-dark small fw-700 text-truncate" style="max-width: 250px;">
                                            <?php echo htmlspecialchars($activity['message'] ?: 'System event updated'); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <div class="bg-success-light text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fas fa-check-circle small"></i>
                                    </div>
                                    <span class="text-muted small fw-600">All systems are running smoothly</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        AOS.init({ duration: 800, once: true });

        // Chart Data
        const ctx = document.getElementById('trendsChart').getContext('2d');
        const months = <?php echo json_encode(array_reverse(array_keys($chart_occupancy))); ?>;
        const revenueData = <?php echo json_encode(array_reverse(array_values($chart_revenue))); ?>;
        const occupancyData = <?php echo json_encode(array_reverse(array_values($chart_occupancy))); ?>;
        
        const revGradient = ctx.createLinearGradient(0, 0, 0, 400);
        revGradient.addColorStop(0, 'rgba(67, 97, 238, 0.2)');
        revGradient.addColorStop(1, 'rgba(67, 97, 238, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                {
                    label: 'Revenue (TSH)',
                    data: revenueData,
                    borderColor: '#4361ee',
                    backgroundColor: revGradient,
                    borderWidth: 4,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'yRevenue',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'New Occupancy',
                    data: occupancyData,
                    borderColor: '#06d6a0',
                    borderWidth: 3,
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'yOccupancy',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: { font: { family: 'Plus Jakarta Sans', weight: '700', size: 11 } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Plus Jakarta Sans', weight: '800' },
                        bodyFont: { family: 'Plus Jakarta Sans', weight: '600' }
                    }
                },
                scales: {
                    yRevenue: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: { borderDash: [5, 5] },
                        ticks: { 
                            font: { weight: '600' },
                            callback: function(value) { return (value/1000) + 'k'; }
                        },
                        title: { display: true, text: 'Revenue (TSH)', font: { weight: '700' } }
                    },
                    yOccupancy: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { font: { weight: '600' } },
                        title: { display: true, text: 'Students', font: { weight: '700' } }
                    },
                    x: { grid: { display: false }, ticks: { font: { weight: '600' } } }
                }
            }
        });
        
        // Counter animation
        $('.counter').each(function () {
            $(this).prop('Counter', 0).animate({
                Counter: $(this).text()
            }, {
                duration: 2000,
                easing: 'swing',
                step: function (now) {
                    $(this).text(Math.ceil(now));
                }
            });
        });
    </script>
</body>
</html>