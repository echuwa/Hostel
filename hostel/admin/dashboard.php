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

// ==================== ACCESS LEVEL ====================
// Full access: Super Admin OR admin with no assigned_block (e.g. Dr.Alex)
$is_full_access = !empty($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1
                  || empty($_SESSION['assigned_block']);

// All formal blocks for dropdown (rooms with dash format like 1A-G01)
$all_blocks = [];
$blk_q = $mysqli->query("
    SELECT SUBSTRING_INDEX(room_no,'-',1) as block_id, COUNT(*) as rooms, SUM(seater) as capacity
    FROM rooms WHERE room_no LIKE '%-%'
    GROUP BY block_id ORDER BY block_id
");
while($blk_r = $blk_q->fetch_assoc()) { $all_blocks[] = $blk_r; }

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
    
    <!-- Favicon (Data URI to prevent 404) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏨</text></svg>">
    
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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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

        /* Range Pill Buttons */
        .range-pill {
            padding: 4px 12px; border-radius: 99px; border: 1.5px solid #e2e8f0;
            background: #f8fafc; color: #64748b; font-size: 0.72rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; font-family: inherit;
        }
        .range-pill:hover { border-color: #f72585; color: #f72585; background: rgba(247,37,133,0.05); }
        .range-pill.active { background: linear-gradient(135deg,#f72585,#7209b7); color: #fff; border-color: transparent; }

        /* Dropdown active item */
        .active-item { background: rgba(67,97,238,0.08) !important; color: #4361ee !important; }

        .chart-card { padding: 28px; }
        .greeting-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%); 
            color: #fff;
            padding: 25px 35px; border-radius: 20px; margin-bottom: 30px;
            position: relative; overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        }
        .greeting-card::after {
            content: ''; position: absolute; top: -30px; right: -30px;
            width: 120px; height: 120px; background: rgba(255,255,255,0.05); border-radius: 50%;
        }
            /* ======== DRAWER & COMPLAINT STACKS ======== */
        .comp-bar-wrap { margin-bottom: 14px; }
        .comp-stack { display: flex; height: 8px; border-radius: 99px; overflow: hidden; gap: 1px; background: #f1f5f9; margin-top: 5px; }
        .comp-stack-seg { height: 100%; transition: width 1.2s cubic-bezier(.23,1,.32,1); }
        .kpi-pill { cursor: pointer; transition: all 0.2s; }
        .kpi-pill:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(67,97,238,0.15); }
        .kpi-pill.selected { box-shadow: 0 0 0 2px #4361ee; }
        .detail-drawer {
            display: none; background: #fff; border-radius: 20px; border: 1.5px solid rgba(67,97,238,0.12);
            box-shadow: 0 8px 32px rgba(67,97,238,0.1); overflow: hidden; margin-top: 16px; animation: slideDown 0.3s ease;
        }
        .detail-drawer.open { display: block; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
        .drawer-tabs { display: flex; border-bottom: 1.5px solid #f1f5f9; background: #fafbff; align-items: center; }
        .drawer-tab {
            flex: 1; padding: 12px 4px; text-align: center; font-size: 0.75rem; font-weight: 800;
            cursor: pointer; border: none; background: none; color: #64748b;
            border-bottom: 2px solid transparent; transition: all 0.2s; outline: none !important;
        }
        .drawer-tab.active { color: #4361ee; border-bottom-color: #4361ee; background: #fff; }
        .close-drawer-btn {
            width: 30px; height: 30px; border-radius: 50%; border: none; background: transparent;
            color: #94a3b8; display: flex; align-items: center; justify-content: center;
            margin: 0 10px; cursor: pointer; transition: all 0.2s;
        }
        .close-drawer-btn:hover { background: #fee2e2; color: #ef233c; transform: rotate(90deg); }
        .drawer-body { max-height: 280px; overflow-y: auto; padding: 15px; }
        .stu-row { display: flex; align-items: center; gap: 12px; padding: 10px; border-bottom: 1px solid #f8fafc; }
        .stu-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 900; }
        .stu-name { font-size: 0.8rem; font-weight: 800; color: #1e293b; }
        .stu-badge { font-size: 0.65rem; font-weight: 800; padding: 3px 10px; border-radius: 99px; margin-left: auto; }

    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <?php include('includes/header.php'); ?>
            <div class="content-wrapper">

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
                     PREMIUM ANALYTICS SECTION — INTERACTIVE FILTERS
                     Panel 01: Occupancy Donut  (block filter for full-access)
                     Panel 02: Trend Bar+Line   (block + range filter)
                     Panel 03: Issue Radar (complaint breakdown)
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
                                <!-- Header row -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="analytics-title">Panel 01</div>
                                        <div class="analytics-heading">Room Occupancy</div>
                                    </div>
                                    <?php if($is_full_access): ?>
                                    <!-- Block Dropdown (Super Admin / Dr.Alex only) -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm rounded-pill fw-700 dropdown-toggle"
                                                id="donut-block-btn"
                                                data-bs-toggle="dropdown"
                                                style="background:rgba(67,97,238,0.08);color:#4361ee;border:1px solid rgba(67,97,238,0.2);font-size:0.72rem;padding:5px 12px;">
                                            <i class="fas fa-building me-1"></i>All Blocks
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2" style="min-width:160px;">
                                            <li><a class="dropdown-item rounded-3 fw-700 small donut-block-item active-item" data-block="all" href="#">🏢 All Blocks</a></li>
                                            <?php foreach($all_blocks as $bl): ?>
                                            <li><a class="dropdown-item rounded-3 fw-700 small donut-block-item" data-block="<?php echo htmlspecialchars($bl['block_id']); ?>" href="#">
                                                Block <?php echo htmlspecialchars($bl['block_id']); ?>
                                                <span class="badge bg-light text-muted ms-1" style="font-size:0.6rem;"><?php echo $bl['rooms']; ?> rooms</span>
                                            </a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Stats badges -->
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span id="donut-pct-badge" class="badge rounded-pill fw-800"
                                          style="background:rgba(67,97,238,0.1);color:#4361ee;font-size:0.75rem;">
                                        <?php echo $occupancy_pct; ?>% Occupied
                                    </span>
                                    <span id="donut-rooms-badge" class="badge rounded-pill fw-700 bg-light text-muted" style="font-size:0.7rem;">
                                        <?php echo $total_rooms; ?> Rooms
                                    </span>
                                </div>

                                <!-- Donut -->
                                <div class="position-relative" style="height:200px;">
                                    <div id="donutOccupancy"></div>
                                    <div class="donut-center" id="donut-center-text">
                                        <div class="pct" id="donut-pct-num"><?php echo $occupancy_pct; ?>%</div>
                                        <div class="lbl" id="donut-lbl-text">Filled</div>
                                    </div>
                                </div>

                                <!-- Legend (dynamic) -->
                                <div class="donut-legend mt-3 pt-3 border-top" id="donut-legend">
                                    <div class="donut-legend-item">
                                        <div class="donut-dot" style="background:#4361ee;"></div>
                                        <span class="donut-legend-label">Occupied Beds</span>
                                        <span class="donut-legend-val" id="leg-occupied"><?php echo $total_occupied; ?></span>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-dot" style="background:#06d6a0;"></div>
                                        <span class="donut-legend-label">Available Beds</span>
                                        <span class="donut-legend-val" id="leg-vacant"><?php echo $total_vacant; ?></span>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-dot" style="background:#f8fafc;border:2px solid #e2e8f0;"></div>
                                        <span class="donut-legend-label">Total Capacity</span>
                                        <span class="donut-legend-val" id="leg-capacity"><?php echo $total_capacity; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── PANEL 2: Trend Analysis + Detail Drawer ── -->
                        <div class="col-lg-5" data-aos="zoom-in" data-aos-delay="200">
                            <div class="analytics-card">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <div class="analytics-title">Panel 02</div>
                                        <div class="analytics-heading">Trend Analysis</div>
                                    </div>
                                    <?php if($is_full_access): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm rounded-pill fw-700 dropdown-toggle" id="trend-block-btn" data-bs-toggle="dropdown"
                                                style="background:rgba(247,37,133,0.08);color:#f72585;border:1px solid rgba(247,37,133,0.2);font-size:0.72rem;padding:5px 12px;">
                                            <i class="fas fa-building me-1"></i>All Blocks
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2" style="min-width:160px;">
                                            <li><a class="dropdown-item rounded-3 fw-700 small trend-block-item active-item" data-block="all" href="#">🏢 All Blocks</a></li>
                                            <?php foreach($all_blocks as $bl): ?>
                                            <li><a class="dropdown-item rounded-3 fw-700 small trend-block-item" data-block="<?php echo htmlspecialchars($bl['block_id']); ?>" href="#">
                                                Block <?php echo htmlspecialchars($bl['block_id']); ?>
                                            </a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Range pills -->
                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                    <button class="range-pill active" data-range="7d">7 Days</button>
                                    <button class="range-pill" data-range="30d">30 Days</button>
                                    <button class="range-pill" data-range="3m">3 Months</button>
                                    <button class="range-pill" data-range="6m">6 Months</button>
                                </div>

                                <!-- KPI Pills — clickable to open drawer -->
                                <div class="kpi-strip" id="kpi-strip">
                                    <div class="kpi-pill" id="kpi-fp" onclick="openDrawer('fp')" title="Click to see fully paid students">
                                        <div class="kpi-num" id="kpi-fp-num" style="color:#059669;">—</div>
                                        <div class="kpi-lbl">✅ Fully Paid</div>
                                    </div>
                                    <div class="kpi-pill" id="kpi-debt" onclick="openDrawer('debt')" title="Click to see debtors"
                                         style="background:linear-gradient(135deg,#fff1f2,#ffe4e6);border-color:#fecdd3;">
                                        <div class="kpi-num" id="kpi-debt-num" style="color:#ef233c;">—</div>
                                        <div class="kpi-lbl">⚠️ Debtors</div>
                                    </div>
                                    <div class="kpi-pill" id="kpi-pend" onclick="openDrawer('pend')" title="Click to see pending verification"
                                         style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border-color:#fed7aa;">
                                        <div class="kpi-num" id="kpi-pend-num" style="color:#d97706;"><?php echo $counts['pending_students']; ?></div>
                                        <div class="kpi-lbl">⏳ Pending Verify</div>
                                    </div>
                                </div>

                                <div id="barMonthly" style="height:180px;"></div>

                                <!-- Detail Drawer — shows inline student lists -->
                                <div class="detail-drawer" id="detail-drawer">
                                    <!-- Tabs -->
                                    <div class="drawer-tabs">
                                        <button class="drawer-tab active" data-tab="fp" onclick="switchTab('fp')">
                                            <i class="fas fa-check-circle me-1 text-success"></i>Fully Paid
                                        </button>
                                        <button class="drawer-tab" data-tab="debt" onclick="switchTab('debt')">
                                            <i class="fas fa-exclamation-triangle me-1 text-danger"></i>Debtors
                                        </button>
                                        <button class="drawer-tab" data-tab="pend" onclick="switchTab('pend')">
                                            <i class="fas fa-clock me-1 text-warning"></i>Awaiting
                                        </button>
                                        <button class="close-drawer-btn" onclick="closeDrawer()" title="Close details"><i class="fas fa-times"></i></button>
                                    </div>
                                    <!-- Body -->
                                    <div class="drawer-body" id="drawer-body">
                                        <div class="drawer-spinner"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── PANEL 3: Issue Radar with Block Filter ── -->
                        <div class="col-lg-3" data-aos="zoom-in" data-aos-delay="250">
                            <div class="analytics-card h-100">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="analytics-title">Panel 03</div>
                                        <div class="analytics-heading">Issue Radar</div>
                                    </div>
                                    <?php if($is_full_access): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm rounded-pill fw-700 dropdown-toggle" id="comp-block-btn" data-bs-toggle="dropdown"
                                                style="background:rgba(251,133,0,0.08);color:#fb8500;border:1px solid rgba(251,133,0,0.25);font-size:0.7rem;padding:4px 10px;">
                                            <i class="fas fa-building me-1"></i>All
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2" style="min-width:155px;">
                                            <li><a class="dropdown-item rounded-3 fw-700 small comp-block-item active-item" data-block="all" href="#">🏢 All Blocks</a></li>
                                            <?php foreach($all_blocks as $bl): ?>
                                            <li><a class="dropdown-item rounded-3 fw-700 small comp-block-item" data-block="<?php echo htmlspecialchars($bl['block_id']); ?>" href="#">
                                                Block <?php echo htmlspecialchars($bl['block_id']); ?>
                                            </a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Status badges (dynamic) -->
                                <div class="d-flex gap-1 mb-3 flex-wrap" id="comp-status-badges">
                                    <span class="badge rounded-pill fw-800" style="background:rgba(6,214,160,0.12);color:#059669;font-size:0.67rem;">
                                        <i class="fas fa-check me-1"></i>Resolved: <span id="cs-resolved"><?php echo $counts['closed_complaints']; ?></span>
                                    </span>
                                    <span class="badge rounded-pill fw-800" style="background:rgba(251,133,0,0.12);color:#d97706;font-size:0.67rem;">
                                        <i class="fas fa-sync-alt me-1"></i>Process: <span id="cs-process"><?php echo $counts['inprocess_complaints']; ?></span>
                                    </span>
                                    <span class="badge rounded-pill fw-800" style="background:rgba(239,35,60,0.1);color:#ef233c;font-size:0.67rem;">
                                        <i class="fas fa-bell me-1"></i>New: <span id="cs-new"><?php echo $counts['new_complaints']; ?></span>
                                    </span>
                                </div>

                                <!-- Complaint type list (dynamic stacked bars) -->
                                <div id="comp-type-list">
                                    <?php
                                    $bar_colors = ['#4361ee','#f72585','#fb8500','#06d6a0','#7209b7'];
                                    foreach($comp_types as $ci => $ct):
                                        $total_c = max(1, $counts['all_complaints']);
                                        $res_w   = round(($ct['cnt']/$total_c)*100);
                                    ?>
                                    <div class="comp-bar-wrap">
                                        <div class="comp-bar-label">
                                            <span><?php echo htmlspecialchars($ct['complaintType']); ?></span>
                                            <span class="badge rounded-pill fw-800" style="background:<?php echo $bar_colors[$ci%5]; ?>20;color:<?php echo $bar_colors[$ci%5]; ?>;font-size:0.62rem;"><?php echo $ct['cnt']; ?> total</span>
                                        </div>
                                        <div class="comp-stack">
                                            <div class="comp-stack-seg" style="background:#06d6a0;" data-width="0"></div>
                                            <div class="comp-stack-seg" style="background:#fb8500;" data-width="0"></div>
                                            <div class="comp-stack-seg" style="background:#ef233c;" data-width="<?php echo $res_w; ?>"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Block comparison strip (shows which block has most complaints) -->
                                <div class="mt-3 pt-3 border-top" id="block-comp-strip">
                                    <div class="analytics-title mb-2">Block Comparison</div>
                                    <div id="block-comp-list"></div>
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
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
    AOS.init({ duration: 800, once: true });

    const IS_FULL_ACCESS = <?php echo $is_full_access ? 'true' : 'false'; ?>;

    // ===================================================================
    //  DONUT CHART — Panel 01
    // ===================================================================
    const donutDefaults = {
        chart: { type: 'donut', height: 200, sparkline: { enabled: true },
                 animations: { enabled: true, speed: 700 } },
        labels: ['Occupied', 'Vacant'],
        colors: ['#4361ee', '#e2e8f0'],
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: false } } } },
        stroke: { width: 0 },
        legend: { show: false },
        tooltip: { y: { formatter: v => v + ' beds' }, style: { fontFamily: 'Plus Jakarta Sans' } }
    };

    let donutChart = new ApexCharts(
        document.getElementById('donutOccupancy'),
        { ...donutDefaults, series: [<?php echo $total_occupied; ?>, <?php echo max(1, $total_vacant); ?>] }
    );
    donutChart.render();

    function updateDonut(data) {
        const occ = data.occupied || 0;
        const vac = Math.max(1, data.vacant || 0);
        donutChart.updateSeries([occ, vac]);
        document.getElementById('donut-pct-num').textContent   = data.pct + '%';
        document.getElementById('donut-pct-badge').textContent = data.pct + '% Occupied';
        document.getElementById('leg-occupied').textContent    = occ;
        document.getElementById('leg-vacant').textContent      = data.vacant || 0;
        document.getElementById('leg-capacity').textContent    = data.total_capacity || 0;
    }

    // ===================================================================
    //  BAR+LINE CHART — Panel 02
    // ===================================================================
    function buildBarOpts(labels, admissions, revenue) {
        return {
            series: [
                { name: 'New Students', type: 'bar',  data: admissions },
                { name: 'Revenue (K TSH)', type: 'line', data: revenue }
            ],
            chart: { height: 190, type: 'line', toolbar: { show: false },
                     fontFamily: 'Plus Jakarta Sans, sans-serif',
                     animations: { enabled: true, speed: 600 } },
            stroke: { width: [0, 3], curve: 'smooth' },
            plotOptions: { bar: { columnWidth: '52%', borderRadius: 7 } },
            fill: {
                type: ['gradient', 'solid'],
                gradient: { type: 'vertical', shadeIntensity: 0.5,
                            gradientToColors: ['#7209b7'], stops: [0, 100] }
            },
            colors: ['#4361ee', '#f72585'],
            labels: labels,
            yaxis: [
                { title: { text: 'Students', style: { fontWeight: 700 } }, min: 0,
                  labels: { style: { fontWeight: 700, fontSize: '10px' } } },
                { opposite: true, title: { text: 'K TSH', style: { fontWeight: 700 } }, min: 0,
                  labels: { style: { fontWeight: 700, fontSize: '10px' },
                            formatter: v => v + 'K' } }
            ],
            xaxis: { labels: { style: { fontWeight: 700, fontSize: '10px' } } },
            legend: { position: 'top', fontWeight: 700, fontSize: '11px' },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            tooltip: {
                shared: true, intersect: false,
                y: [
                    { formatter: v => v + ' students' },
                    { formatter: v => 'TSH ' + (v * 1000).toLocaleString() }
                ]
            }
        };
    }

    let barChart = new ApexCharts(
        document.getElementById('barMonthly'),
        buildBarOpts(
            <?php echo json_encode(array_reverse(array_keys($chart_occupancy))); ?>,
            <?php echo json_encode(array_reverse(array_values($chart_occupancy))); ?>,
            <?php echo json_encode(array_map(fn($v) => round($v/1000, 1), array_reverse(array_values($chart_revenue)))); ?>
        )
    );
    barChart.render();

    // State
    let activeDonutBlock = 'all';
    let activeTrendBlock = 'all';
    let activeRange      = '7d';

    // ===================================================================
    //  RANGE PILLS — toggle
    // ===================================================================
    document.querySelectorAll('.range-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.range-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeRange = this.dataset.range;
            if (IS_FULL_ACCESS) fetchTrend(activeTrendBlock, activeRange);
        });
    });

    // ===================================================================
    //  DONUT BLOCK DROPDOWN
    // ===================================================================
    document.querySelectorAll('.donut-block-item').forEach(a => {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.donut-block-item').forEach(x => x.classList.remove('active-item'));
            this.classList.add('active-item');
            activeDonutBlock = this.dataset.block;
            const label = activeDonutBlock === 'all' ? '🏢 All Blocks' : 'Block ' + activeDonutBlock;
            document.getElementById('donut-block-btn').innerHTML =
                '<i class="fas fa-building me-1"></i>' + label;
            fetchOccupancy(activeDonutBlock);
        });
    });

    // ===================================================================
    //  TREND BLOCK DROPDOWN
    // ===================================================================
    document.querySelectorAll('.trend-block-item').forEach(a => {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.trend-block-item').forEach(x => x.classList.remove('active-item'));
            this.classList.add('active-item');
            activeTrendBlock = this.dataset.block;
            const label = activeTrendBlock === 'all' ? '🏢 All Blocks' : 'Block ' + activeTrendBlock;
            document.getElementById('trend-block-btn').innerHTML =
                '<i class="fas fa-building me-1"></i>' + label;
            fetchTrend(activeTrendBlock, activeRange);
        });
    });

    // ===================================================================
    //  FETCH FUNCTIONS
    // ===================================================================
    function fetchOccupancy(block) {
        document.getElementById('donut-pct-num').textContent = '...';
        fetch('ajax/analytics-api.php?action=occupancy&block=' + encodeURIComponent(block))
            .then(r => r.json())
            .then(data => updateDonut(data))
            .catch(() => console.warn('Occupancy fetch failed'));
    }

    function fetchTrend(block, range) {
        fetch('ajax/analytics-api.php?action=trend&block=' + encodeURIComponent(block) + '&range=' + range)
            .then(r => r.json())
            .then(data => barChart.updateOptions(buildBarOpts(data.labels, data.admissions, data.revenue)))
            .catch(() => console.warn('Trend fetch failed'));
    }

    // ===================================================================
    //  TREND DETAIL — Drawer (student lists)
    // ===================================================================
    let activeDrawerTab  = 'fp';
    let detailData       = null;

    function fetchDetail(block) {
        const fpNum = document.getElementById('kpi-fp-num');
        const dbNum = document.getElementById('kpi-debt-num');
        if(fpNum) fpNum.textContent = '...';
        if(dbNum) dbNum.textContent = '...';

        fetch('ajax/analytics-api.php?action=trend_detail&block=' + encodeURIComponent(block))
            .then(r => r.json())
            .then(data => {
                detailData = data;
                if(fpNum) fpNum.textContent = data.fully_paid_count;
                if(dbNum) dbNum.textContent = data.partial_count;
                // If drawer is open refresh its content
                if (document.getElementById('detail-drawer').classList.contains('open')) {
                    renderDrawer(activeDrawerTab);
                }
            });
    }

    function openDrawer(tab) {
        activeDrawerTab = tab;
        document.getElementById('detail-drawer').classList.add('open');
        switchTab(tab);
        if (!detailData) {
            document.getElementById('drawer-body').innerHTML =
                '<div class="drawer-spinner"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</div>';
            fetchDetail(activeTrendBlock);
        } else {
            renderDrawer(tab);
        }
    }

    function closeDrawer() {
        document.getElementById('detail-drawer').classList.remove('open');
        document.querySelectorAll('.kpi-pill').forEach(p => p.classList.remove('selected'));
    }

    function switchTab(tab) {
        activeDrawerTab = tab;
        document.querySelectorAll('.drawer-tab[data-tab]').forEach(t => t.classList.remove('active'));
        const activeBtn = document.querySelector(`.drawer-tab[data-tab="${tab}"]`);
        if (activeBtn) activeBtn.classList.add('active');
        document.querySelectorAll('.kpi-pill').forEach(p => p.classList.remove('selected'));
        const selPill = document.getElementById('kpi-' + tab);
        if (selPill) selPill.classList.add('selected');
        if (detailData) renderDrawer(tab);
    }

    function renderDrawer(tab) {
        const body = document.getElementById('drawer-body');
        if (!detailData) return;

        let html = '';
        const avatarColors = ['#4361ee','#f72585','#fb8500','#06d6a0','#7209b7','#3a0ca3'];

        if (tab === 'fp') {
            const list = detailData.fp_students || [];
            if (!list.length) {
                html = '<div class="drawer-spinner" style="color:#06d6a0;"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>No fully paid students found</div>';
            } else {
                html += `<div style="padding:8px 10px 4px;font-size:0.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;">
                    ${detailData.fully_paid_count} Fully Paid — Total Collected: TSH ${detailData.fully_paid_amount}
                </div>`;
                list.forEach((s, i) => {
                    const col = avatarColors[i % avatarColors.length];
                    html += `<div class="stu-row">
                        <div class="stu-avatar" style="background:${col}20;color:${col};">${s.name.charAt(0)}</div>
                        <div>
                            <div class="stu-name">${s.name}</div>
                            <div class="stu-sub">${s.regNo} &bull; Room ${s.room || 'N/A'}</div>
                        </div>
                        <span class="stu-badge" style="background:#dcfce7;color:#059669;">TSH ${s.paid}</span>
                        <a href="student-details.php?id=${s.id}" class="btn btn-sm ms-1 rounded-pill fw-700" style="font-size:0.65rem;padding:3px 10px;background:#f0f4ff;color:#4361ee;">View</a>
                    </div>`;
                });
            }
        } else if (tab === 'debt') {
            const list = detailData.debt_students || [];
            if (!list.length) {
                html = '<div class="drawer-spinner" style="color:#059669;"><i class="fas fa-smile fa-2x mb-2 d-block"></i>No debtors found! All paid.</div>';
            } else {
                html += `<div style="padding:8px 10px 4px;font-size:0.7rem;font-weight:800;color:#ef233c;text-transform:uppercase;letter-spacing:1px;">
                    ${detailData.partial_count} Debtors — Outstanding: TSH ${detailData.total_balance}
                </div>`;
                list.forEach((s, i) => {
                    const col = avatarColors[i % avatarColors.length];
                    html += `<div class="stu-row">
                        <div class="stu-avatar" style="background:#fff1f220;color:#ef233c;">${s.name.charAt(0)}</div>
                        <div>
                            <div class="stu-name">${s.name}</div>
                            <div class="stu-sub">${s.regNo} &bull; Room ${s.room || 'N/A'}</div>
                        </div>
                        <span class="stu-badge" style="background:#fee2e2;color:#ef233c;">Owes TSH ${s.balance}</span>
                        <a href="student-details.php?id=${s.id}" class="btn btn-sm ms-1 rounded-pill fw-700" style="font-size:0.65rem;padding:3px 10px;background:#f0f4ff;color:#4361ee;">View</a>
                    </div>`;
                });
            }
        } else {
            const list = detailData.pend_students || [];
            if (!list.length) {
                html = '<div class="drawer-spinner"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No pending verifications.</div>';
            } else {
                html += `<div style="padding:8px 10px 4px;font-size:0.7rem;font-weight:800;color:#d97706;text-transform:uppercase;letter-spacing:1px;">
                    ${detailData.pending_verify_count} Awaiting Admin Verification
                </div>`;
                list.forEach((s, i) => {
                    const col = avatarColors[i % avatarColors.length];
                    html += `<div class="stu-row">
                        <div class="stu-avatar" style="background:#fff7ed;color:#d97706;">${s.name.charAt(0)}</div>
                        <div>
                            <div class="stu-name">${s.name}</div>
                            <div class="stu-sub">${s.regNo} &bull; Since ${s.since}</div>
                        </div>
                        <span class="stu-badge" style="background:#fef3c7;color:#d97706;">Pending</span>
                        <a href="student-details.php?id=${s.id}" class="btn btn-sm ms-1 rounded-pill fw-700" style="font-size:0.65rem;padding:3px 10px;background:#f0f4ff;color:#4361ee;">View</a>
                    </div>`;
                });
            }
        }
        body.innerHTML = html;
    }

    // ===================================================================
    //  COMPLAINTS STATS — Panel 03
    // ===================================================================
    let activeCompBlock = 'all';

    function fetchComplaints(block) {
        activeCompBlock = block;
        fetch('ajax/analytics-api.php?action=complaints_stats&block=' + encodeURIComponent(block))
            .then(r => r.json())
            .then(data => renderComplaints(data));
    }

    function renderComplaints(data) {
        // Update status badges
        document.getElementById('cs-resolved').textContent = data.resolved || 0;
        document.getElementById('cs-process').textContent  = data.in_process || 0;
        document.getElementById('cs-new').textContent      = data.new_open || 0;

        // Complaint types with stacked bars
        const colors = ['#4361ee','#f72585','#fb8500','#06d6a0','#7209b7'];
        const list = data.types || [];
        const maxTotal = list.reduce((m, t) => Math.max(m, t.total), 1);
        let html = '';
        list.forEach((t, i) => {
            const col    = colors[i % colors.length];
            const resW   = Math.round((t.resolved   / maxTotal) * 100);
            const procW  = Math.round((t.in_process / maxTotal) * 100);
            const newW   = Math.round((t.new_open   / maxTotal) * 100);
            html += `<div class="comp-bar-wrap">
                <div class="comp-bar-label">
                    <span>${t.type}</span>
                    <span class="badge rounded-pill fw-800" style="background:${col}20;color:${col};font-size:0.62rem;">${t.total} total</span>
                </div>
                <div class="comp-stack">
                    <div class="comp-stack-seg" style="background:#06d6a0;width:${resW}%" title="Resolved: ${t.resolved}"></div>
                    <div class="comp-stack-seg" style="background:#fb8500;width:${procW}%" title="In Process: ${t.in_process}"></div>
                    <div class="comp-stack-seg" style="background:#ef233c;width:${newW}%" title="New/Open: ${t.new_open}"></div>
                </div>
            </div>`;
        });
        document.getElementById('comp-type-list').innerHTML = html || '<p class="text-muted small fw-700 text-center py-3">No complaints for this block</p>';

        // Block comparison
        const compList = data.block_comp || [];
        const maxBlk = compList.reduce((m, b) => Math.max(m, b.total), 1);
        let bhtml = '';
        compList.forEach(b => {
            const w = Math.round((b.total / maxBlk) * 100);
            bhtml += `<div style="margin-bottom:8px;">
                <div style="display:flex;justify-content:space-between;font-size:0.7rem;font-weight:800;margin-bottom:3px;">
                    <span style="color:#334155;">Block ${b.block}</span>
                    <span style="color:#4361ee;">${b.total} cases</span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:${w}%;background:linear-gradient(90deg,#4361ee,#f72585);border-radius:99px;transition:width 1s;"></div>
                </div>
            </div>`;
        });
        document.getElementById('block-comp-list').innerHTML = bhtml || '<p class="text-muted small fw-700">No block data</p>';
    }

    // Complaint block dropdown
    document.querySelectorAll('.comp-block-item').forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.comp-block-item').forEach(x => x.classList.remove('active-item'));
            this.classList.add('active-item');
            const blk = this.dataset.block;
            const lbl = blk === 'all' ? '<i class="fas fa-building me-1"></i>All' : '<i class="fas fa-building me-1"></i>'+blk;
            document.getElementById('comp-block-btn').innerHTML = lbl;
            if (IS_FULL_ACCESS) fetchComplaints(blk);
        });
    });

    // ===================================================================
    //  Trend block dropdown — also refreshes detail
    // ===================================================================
    document.querySelectorAll('.trend-block-item').forEach(a => {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.trend-block-item').forEach(x => x.classList.remove('active-item'));
            this.classList.add('active-item');
            activeTrendBlock = this.dataset.block;
            const label = activeTrendBlock === 'all' ? '🏢 All Blocks' : 'Block ' + activeTrendBlock;
            document.getElementById('trend-block-btn').innerHTML = '<i class="fas fa-building me-1"></i>' + label;
            detailData = null; // Reset detail cache
            if (IS_FULL_ACCESS) {
                fetchTrend(activeTrendBlock, activeRange);
                fetchDetail(activeTrendBlock);
            }
        });
    });

    // ===================================================================
    //  ANIMATE complaint stacked bars on load
    // ===================================================================
    setTimeout(() => {
        document.querySelectorAll('.comp-stack-seg').forEach(s => {
            s.style.width = (s.dataset.width || s.style.width);
        });
    }, 700);

    // ===================================================================
    //  COUNTER ANIMATION
    // ===================================================================
    $('.counter').each(function () {
        const raw = $(this).text().replace('%','').replace(/,/g,'');
        const hasPct = $(this).text().indexOf('%') > -1;
        $(this).prop('Counter', 0).animate({ Counter: parseFloat(raw) || 0 }, {
            duration: 2000, easing: 'swing',
            step: function (now) {
                $(this).text(Math.ceil(now) + (hasPct ? '%' : ''));
            }
        });
    });

    // ===================================================================
    //  ON PAGE LOAD — fetch all dynamic data
    // ===================================================================
    if (IS_FULL_ACCESS) {
        fetchTrend('all', '7d');
        fetchDetail('all');
        fetchComplaints('all');
    }
    </script>
</body>
</html>
