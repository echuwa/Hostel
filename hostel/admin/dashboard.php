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

// ==================== ANALYTICS DATA ====================
// Occupancy Stats
$total_rooms = $mysqli->query("SELECT COUNT(*) FROM rooms $block_cond_rooms")->fetch_row()[0];
$total_capacity_q = $mysqli->query("SELECT SUM(seater) FROM rooms $block_cond_rooms");
$total_capacity = $total_capacity_q->fetch_row()[0] ?? 0;
$total_occupied_q = $mysqli->query("SELECT COUNT(*) FROM registration $block_cond_reg");
$total_occupied = $total_occupied_q->fetch_row()[0] ?? 0;
$total_vacant = max(0, $total_capacity - $total_occupied);
$occupancy_pct = $total_capacity > 0 ? round(($total_occupied / $total_capacity) * 100) : 0;

// Revenue totals
$rev_total_q = $mysqli->query("SELECT SUM(u.fees_paid + u.accommodation_paid + u.registration_paid) FROM userregistration u");
$total_revenue = $rev_total_q->fetch_row()[0] ?? 0;

// Full rooms vs partial
$full_q = "SELECT COUNT(*) FROM rooms r WHERE (SELECT COUNT(*) FROM registration rg WHERE rg.roomno = r.room_no) >= r.seater" . (empty($block_cond_rooms) ? '' : str_replace(' WHERE ',' AND r.',$block_cond_rooms));
$full_rooms = $mysqli->query("SELECT COUNT(*) FROM rooms r JOIN registration rg ON rg.roomno = r.room_no WHERE r.seater <= (SELECT COUNT(*) FROM registration rg2 WHERE rg2.roomno = r.room_no)" . (empty($block_cond_rooms) ? '' : " AND r.room_no LIKE '{$_SESSION['assigned_block']}%'"))->fetch_row()[0] ?? 0;
$partial_rooms = $total_rooms - $full_rooms - ($total_rooms - (int)$mysqli->query("SELECT COUNT(DISTINCT roomno) FROM registration $block_cond_reg")->fetch_row()[0]);
$empty_rooms_cnt = $total_rooms - (int)$mysqli->query("SELECT COUNT(DISTINCT roomno) FROM registration $block_cond_reg")->fetch_row()[0];

// Complaints by type
$comp_types = [];
$comp_q = $mysqli->query("SELECT c.complaintType, COUNT(*) as cnt FROM complaints c $block_join_complaints WHERE 1=1 $block_cond_complaints GROUP BY c.complaintType ORDER BY cnt DESC LIMIT 5");
while($cr = $comp_q->fetch_assoc()) { $comp_types[] = $cr; }
$max_comp = !empty($comp_types) ? $comp_types[0]['cnt'] : 1;

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
        
        /* ======== PREMIUM ANALYTICS ======== */
        .analytics-section { margin-top: 32px; }

        .analytics-card {
            background: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 4px 24px rgba(67,97,238,0.07);
            border: 1px solid rgba(67,97,238,0.07);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .analytics-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4361ee, #7209b7, #f72585);
        }
        .analytics-title {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .analytics-heading {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0;
        }

        /* Donut center text */
        .donut-center {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }
        .donut-center .pct { font-size: 2rem; font-weight: 900; color: #1e293b; line-height: 1; }
        .donut-center .lbl { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }

        /* Donut legend */
        .donut-legend { display: flex; flex-direction: column; gap: 10px; justify-content: center; }
        .donut-legend-item { display: flex; align-items: center; gap: 10px; }
        .donut-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .donut-legend-label { font-size: 0.78rem; font-weight: 700; color: #334155; }
        .donut-legend-val { font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-left: auto; }

        /* Complaint bars */
        .comp-bar-wrap { margin-bottom: 18px; }
        .comp-bar-label { font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: flex; justify-content: space-between; }
        .comp-bar-track { height: 10px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
        .comp-bar-fill { height: 100%; border-radius: 99px; transition: width 1.4s cubic-bezier(.23,1,.32,1); width: 0; }

        /* Revenue KPI strip */
        .kpi-strip {
            display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .kpi-pill {
            flex: 1; min-width: 100px;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 16px;
            text-align: center;
        }
        .kpi-pill .kpi-num { font-size: 1.05rem; font-weight: 900; color: #4361ee; }
        .kpi-pill .kpi-lbl { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }

        .chart-card { padding: 28px; }
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

                <!-- ============================================================
                     PREMIUM ANALYTICS SECTION
                     3-panel: Occupancy Donut | Monthly Bar | Complaint Breakdown
                ============================================================ -->
                <div class="analytics-section" data-aos="fade-up" data-aos-delay="100">

                    <!-- Section Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <div class="analytics-title">Live Intelligence</div>
                            <h5 class="analytics-heading">Hostel Performance Analytics</h5>
                        </div>
                        <span class="badge rounded-pill px-3 py-2 fw-700" style="background:linear-gradient(135deg,#4361ee,#7209b7);color:#fff;font-size:0.7rem;">
                            <i class="fas fa-circle me-1" style="font-size:0.5rem;"></i>
                            <?php echo date('F Y'); ?>
                        </span>
                    </div>

                    <div class="row g-4">

                        <!-- ── PANEL 1: Occupancy Donut ── -->
                        <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="150">
                            <div class="analytics-card h-100">
                                <div class="analytics-title">Panel 01</div>
                                <div class="analytics-heading mb-3">Room Occupancy</div>

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge rounded-pill fw-800" style="background:rgba(67,97,238,0.1);color:#4361ee;font-size:0.75rem;">
                                        <?php echo $occupancy_pct; ?>% Occupied
                                    </span>
                                    <span class="badge rounded-pill fw-700 bg-light text-muted" style="font-size:0.7rem;">
                                        <?php echo $total_rooms; ?> Total Rooms
                                    </span>
                                </div>

                                <!-- Donut Chart -->
                                <div class="position-relative" style="height:200px;">
                                    <div id="donutOccupancy"></div>
                                    <div class="donut-center">
                                        <div class="pct"><?php echo $occupancy_pct; ?>%</div>
                                        <div class="lbl">Filled</div>
                                    </div>
                                </div>

                                <!-- Legend -->
                                <div class="donut-legend mt-3 pt-3 border-top">
                                    <div class="donut-legend-item">
                                        <div class="donut-dot" style="background:#4361ee;"></div>
                                        <span class="donut-legend-label">Occupied Beds</span>
                                        <span class="donut-legend-val"><?php echo $total_occupied; ?></span>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-dot" style="background:#06d6a0;"></div>
                                        <span class="donut-legend-label">Available Beds</span>
                                        <span class="donut-legend-val"><?php echo $total_vacant; ?></span>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-dot" style="background:#f8fafc;border:2px solid #e2e8f0;"></div>
                                        <span class="donut-legend-label">Total Capacity</span>
                                        <span class="donut-legend-val"><?php echo $total_capacity; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── PANEL 2: Monthly Bar Chart ── -->
                        <div class="col-lg-5" data-aos="zoom-in" data-aos-delay="200">
                            <div class="analytics-card h-100">
                                <div class="analytics-title">Panel 02</div>
                                <div class="analytics-heading mb-1">Monthly Trend</div>
                                <p class="text-muted" style="font-size:0.75rem;font-weight:600;margin-bottom:16px;">Admissions &amp; Revenue (Last 6 Months)</p>

                                <!-- KPI Pills -->
                                <div class="kpi-strip">
                                    <div class="kpi-pill">
                                        <div class="kpi-num"><?php echo $counts['students']; ?></div>
                                        <div class="kpi-lbl">Residents</div>
                                    </div>
                                    <div class="kpi-pill" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:#bbf7d0;">
                                        <div class="kpi-num" style="color:#059669;">TSH <?php echo number_format($total_revenue/1000000, 1); ?>M</div>
                                        <div class="kpi-lbl">Collected</div>
                                    </div>
                                    <div class="kpi-pill" style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border-color:#fed7aa;">
                                        <div class="kpi-num" style="color:#d97706;"><?php echo $counts['pending_students']; ?></div>
                                        <div class="kpi-lbl">Pending</div>
                                    </div>
                                </div>

                                <div id="barMonthly" style="height:220px;"></div>
                            </div>
                        </div>

                        <!-- ── PANEL 3: Complaint Breakdown ── -->
                        <div class="col-lg-3" data-aos="zoom-in" data-aos-delay="250">
                            <div class="analytics-card h-100">
                                <div class="analytics-title">Panel 03</div>
                                <div class="analytics-heading mb-1">Issue Radar</div>
                                <p class="text-muted" style="font-size:0.75rem;font-weight:600;margin-bottom:20px;">Complaints by Category</p>

                                <?php
                                $bar_colors = ['#4361ee','#f72585','#fb8500','#06d6a0','#7209b7'];
                                if(empty($comp_types)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-check-circle fa-3x mb-3" style="color:#06d6a0;"></i>
                                        <p class="fw-700 text-muted small">No complaints recorded</p>
                                    </div>
                                <?php else: foreach($comp_types as $ci => $ct): ?>
                                    <div class="comp-bar-wrap">
                                        <div class="comp-bar-label">
                                            <span><?php echo htmlspecialchars($ct['complaintType']); ?></span>
                                            <span style="color:<?php echo $bar_colors[$ci % 5]; ?>;"><?php echo $ct['cnt']; ?></span>
                                        </div>
                                        <div class="comp-bar-track">
                                            <div class="comp-bar-fill" style="background:<?php echo $bar_colors[$ci % 5]; ?>;" data-width="<?php echo round(($ct['cnt']/$max_comp)*100); ?>"></div>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>

                                <!-- Status Summary -->
                                <div class="mt-4 pt-3 border-top">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-700 text-success" style="font-size:0.78rem;"><i class="fas fa-check-circle me-1"></i>Resolved</span>
                                        <span class="fw-900" style="font-size:0.85rem;color:#059669;"><?php echo $counts['closed_complaints']; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-700 text-warning" style="font-size:0.78rem;"><i class="fas fa-spinner me-1"></i>In Process</span>
                                        <span class="fw-900" style="font-size:0.85rem;color:#d97706;"><?php echo $counts['inprocess_complaints']; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-700 text-danger" style="font-size:0.78rem;"><i class="fas fa-exclamation-circle me-1"></i>New / Open</span>
                                        <span class="fw-900" style="font-size:0.85rem;color:#ef233c;"><?php echo $counts['new_complaints']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /row -->
                </div><!-- /analytics-section -->

            </div><!-- /content-wrapper -->
        </div><!-- /main-content -->
    </div><!-- /app-container -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- ApexCharts for premium charts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        AOS.init({ duration: 800, once: true });

        // ==================== DONUT: Occupancy ====================
        const donutOpts = {
            series: [<?php echo $total_occupied; ?>, <?php echo max(1, $total_vacant); ?>],
            chart: { type: 'donut', height: 200, sparkline: { enabled: true } },
            labels: ['Occupied', 'Vacant'],
            colors: ['#4361ee', '#e2e8f0'],
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: { show: false }
                    }
                }
            },
            stroke: { width: 0 },
            legend: { show: false },
            tooltip: {
                y: { formatter: val => val + ' beds' },
                style: { fontFamily: 'Plus Jakarta Sans' }
            }
        };
        new ApexCharts(document.getElementById('donutOccupancy'), donutOpts).render();

        // ==================== BAR: Monthly Admissions + Revenue ====================
        const months      = <?php echo json_encode(array_reverse(array_keys($chart_occupancy))); ?>;
        const admissions  = <?php echo json_encode(array_reverse(array_values($chart_occupancy))); ?>;
        const revenueData = <?php echo json_encode(array_map(fn($v) => round($v/1000, 1), array_reverse(array_values($chart_revenue)))); ?>;

        const barOpts = {
            series: [
                {
                    name: 'New Students',
                    type: 'bar',
                    data: admissions
                },
                {
                    name: 'Revenue (K TSH)',
                    type: 'line',
                    data: revenueData
                }
            ],
            chart: {
                height: 220,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            stroke: { width: [0, 3], curve: 'smooth' },
            plotOptions: {
                bar: {
                    columnWidth: '50%',
                    borderRadius: 8,
                    distributed: false
                }
            },
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#7209b7'],
                    stops: [0, 100]
                }
            },
            colors: ['#4361ee', '#f72585'],
            labels: months,
            yaxis: [
                { title: { text: 'Students', style: { fontWeight: 700 } }, min: 0 },
                { opposite: true, title: { text: 'Revenue (K TSH)', style: { fontWeight: 700 } }, min: 0 }
            ],
            xaxis: { labels: { style: { fontWeight: 700, fontSize: '11px' } } },
            legend: { position: 'top', fontWeight: 700, fontSize: '11px' },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    { formatter: val => val + ' students' },
                    { formatter: val => 'TSH ' + (val * 1000).toLocaleString() }
                ]
            }
        };
        new ApexCharts(document.getElementById('barMonthly'), barOpts).render();

        // ==================== Animate complaint progress bars ====================
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.comp-bar-fill').forEach(bar => {
                    bar.style.width = bar.dataset.width + '%';
                });
            }, 600);
        });

        // ==================== Counter animation ====================
        $('.counter').each(function () {
            $(this).prop('Counter', 0).animate({
                Counter: $(this).text().replace('%','').replace(/,/g,'')
            }, {
                duration: 2000,
                easing: 'swing',
                step: function (now) {
                    $(this).text(Math.ceil(now) + ($(this).text().indexOf('%') > -1 ? '%' : ''));
                }
            });
        });
    </script>
</body>
</html>
