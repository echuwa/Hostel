<?php
// ============================================
// DEBUG MODE - ONDOA BAADA YA KUTATUA
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ============================================
// SECURITY HEADERS - ZOTE KWA HTTP HEADER, SI META
// ============================================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://use.fontawesome.com; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://use.fontawesome.com 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://use.fontawesome.com data:; connect-src 'self' https://cdn.jsdelivr.net https://*.jsdelivr.net;");
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: superadmin-login.php");
    exit();
}

require_once('includes/config.php');
require_once('includes/auth.php');

// Set timeout for inactivity (30 minutes)
$inactive = 1800;
if (isset($_SESSION['last_login']) && (time() - $_SESSION['last_login'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: superadmin-login.php?timeout=1");
    exit();
}
$_SESSION['last_login'] = time();

// Get admin list
$admins = [];
$stmt = $mysqli->prepare("SELECT id, username, email, reg_date, status FROM admins WHERE is_superadmin = 0 ORDER BY reg_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    $stmt->close();
}

// Get pending admins count
$pending_count = 0;
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM admins WHERE status = 'pending' AND is_superadmin = 0");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pending_count = $row['count'];
    $stmt->close();
}

// ============================================
// GET AUDIT LOGS - FIXED KWA MUUNDO WAKO WA DATABASE
// ============================================
$audit_logs = [];

// Check if audit_logs table exists
$tableCheck = $mysqli->query("SHOW TABLES LIKE 'audit_logs'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    
    // Query inayolingana na muundo wako wa database
    $logStmt = $mysqli->prepare("SELECT al.id, al.user_id, al.action_type as action, al.description as details, 
                                        al.ip_address, al.created_at, a.username 
                                 FROM audit_logs al 
                                 LEFT JOIN admins a ON al.user_id = a.id 
                                 ORDER BY al.created_at DESC LIMIT 20");
    
    if ($logStmt) {
        $logStmt->execute();
        $logResult = $logStmt->get_result();
        while ($row = $logResult->fetch_assoc()) {
            // Handle case when username is null (deleted admin or system)
            if (empty($row['username'])) {
                $row['username'] = 'System';
            }
            // Ensure action field exists
            if (empty($row['action'])) {
                $row['action'] = 'unknown';
            }
            $audit_logs[] = $row;
        }
        $logStmt->close();
    }
}

// Handle messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard | Hostel Management System</title>
    
    <!-- CSS Links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #3A0CA3;
            --secondary-color: #4361EE;
            --accent-color: #4CC9F0;
            --danger-color: #EF233C;
            --success-color: #06D6A0;
            --warning-color: #FFD166;
            --dark: #1e293b;
            --gray: #64748b;
            --light: #f8fafc;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F8F9FA;
        }
        
        /* ============================================
           FIX: REDUCE SIDEBAR AND MAIN CONTENT GAP
           ============================================ */
        .container-fluid {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        
        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding-right: 0 !important;
            margin-right: 0 !important;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
        }
        
        .main-content {
            background-color: #F8F9FA;
            min-height: 100vh;
            padding: 20px;
            padding-left: 10px !important;
            margin-left: 0 !important;
        }
        
        /* Kolamu za sidebar na main */
        .col-md-3, .col-lg-2 {
            padding-right: 0 !important;
        }
        
        .col-md-9, .col-lg-10 {
            padding-left: 10px !important;
            padding-right: 15px !important;
        }
        
        /* Responsive kwa screen ndogo */
        @media (max-width: 768px) {
            .main-content {
                padding-left: 20px !important;
            }
            .col-md-9, .col-lg-10 {
                padding-left: 20px !important;
            }
            .sidebar {
                min-height: auto;
                height: auto;
            }
        }
        
        /* Remove gutter kutoka kwa row */
        .no-gutters {
            margin-right: 0 !important;
            margin-left: 0 !important;
        }
        
        .no-gutters > .col,
        .no-gutters > [class*="col-"] {
            padding-right: 0 !important;
            padding-left: 0 !important;
        }
        
        /* Sidebar Navigation */
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 10px;
            margin-bottom: 8px;
            padding: 12px 15px;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        .sidebar .nav-link.text-danger {
            color: #ffb3b3 !important;
        }
        
        .sidebar .nav-link.text-danger:hover {
            background: rgba(220, 38, 38, 0.2);
            color: white !important;
        }
        
        /* Sidebar Sections */
        .sidebar-section {
            padding: 8px 12px;
            margin-bottom: 5px;
        }
        
        .sidebar-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        /* Notification Badge */
        .notification-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            margin-left: auto;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stats-card {
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .stats-card:hover::after {
            transform: scale(1.2);
        }
        
        /* Profile Icon */
        .profile-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 0 auto;
            transition: var(--transition);
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .profile-icon:hover {
            transform: scale(1.1);
            background: rgba(255,255,255,0.3);
            border-color: white;
        }
        
        /* Modals */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-lg);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 20px;
            border-bottom: none;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: var(--transition);
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        
        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 15px 20px;
        }
        
        /* Table */
        .table thead th {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--secondary-color);
            padding: 15px 12px;
        }
        
        .table td {
            padding: 15px 12px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: var(--transition);
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.02);
        }
        
        /* Buttons */
        .btn-action {
            border-radius: 8px;
            padding: 8px 12px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-group-sm .btn {
            border-radius: 6px;
            margin: 0 2px;
        }
        
        /* Audit Log Entry */
        .audit-log-entry {
            padding: 15px;
            border-left: 4px solid var(--secondary-color);
            background: white;
            margin-bottom: 10px;
            border-radius: 0 12px 12px 0;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .audit-log-entry:hover {
            box-shadow: var(--shadow-md);
            border-left-width: 6px;
        }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 30px;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, #06D6A0, #05b585) !important;
        }
        
        .badge.bg-danger {
            background: linear-gradient(135deg, #EF233C, #d91c32) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #FFD166, #ffc233) !important;
            color: #000;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .welcome-card::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 12px 15px;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
            padding-left: 0;
        }
        
        /* Loading Spinner */
        .spinner-border {
            vertical-align: middle;
        }

        /* ============================================
           SIDEBAR ENHANCEMENTS - ICONS & DROPDOWNS
           ============================================ */
        .sidebar-section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.6);
            font-weight: 700;
            margin-bottom: 12px;
            padding-left: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sidebar-section-title i {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 12px;
            margin-bottom: 6px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            color: rgba(255,255,255,0.9);
        }

        .sidebar .nav-link span {
            flex: 1;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.05));
            color: white;
            border-left: 4px solid white;
        }

        .sidebar .nav-link.text-danger {
            color: #ffb3b3 !important;
        }

        .sidebar .nav-link.text-danger i {
            color: #ffb3b3 !important;
        }

        .sidebar .nav-link.text-danger:hover {
            background: rgba(220, 38, 38, 0.2);
            color: white !important;
        }

        .sidebar .nav-link.text-danger:hover i {
            color: white !important;
        }

        .notification-badge {
            background: linear-gradient(135deg, #EF233C, #d9042b);
            color: white;
            border-radius: 30px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            margin-left: auto;
            box-shadow: 0 2px 5px rgba(239, 35, 60, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        #systemSettingsMenu {
            padding-left: 12px;
            margin-top: 5px;
        }

        #systemSettingsMenu .nav-link {
            padding: 10px 16px;
            margin-bottom: 4px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }

        #systemSettingsMenu .nav-link i {
            font-size: 0.95rem;
        }

        #systemSettingsMenu .nav-link:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(3px);
        }

        .sidebar hr {
            margin: 15px 0;
            opacity: 0.2;
            border-color: white;
        }

        .profile-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #FFD166, #FFB347);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 0 auto;
            transition: all 0.3s ease;
            border: 3px solid rgba(255,255,255,0.3);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-icon i {
            font-size: 2.2rem !important;
            color: white;
        }

        .profile-icon:hover {
            transform: scale(1.1);
            border-color: white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .fa-arrow-right {
            font-size: 12px;
            opacity: 0.8;
            transition: transform 0.3s ease;
        }

        .nav-link:hover .fa-arrow-right {
            transform: translateX(3px);
            opacity: 1;
        }

        .fa-chevron-down {
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        [aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .text-white-50 {
            color: rgba(255,255,255,0.7) !important;
        }

        .bg-dark.bg-opacity-25 {
            background-color: rgba(0,0,0,0.25) !important;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }
    </style>
    
    <!-- Disable source map fetching -->
    <script>
        window.disableSourceMaps = true;
        document.currentScript?.setAttribute('data-sourcemap', 'disabled');
    </script>
</head>
<body>
    <div class="container-fluid">
        <!-- ROW WITH NO-GUTTERS -->
        <div class="row no-gutters">
            <!-- SIDEBAR - KAMILI NA ICONS ZOTE -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="position-sticky pt-3">
                    <!-- Profile Section -->
                    <div class="text-center mb-4 p-3">
                        <div class="profile-icon mx-auto mb-3">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h5 class="mb-1 fw-bold">Super Admin</h5>
                        <hr class="bg-white opacity-25 my-2">
                        <p class="mb-0 small">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </p>
                    </div>
                    
                    <!-- MAIN NAVIGATION - DASHBOARD -->
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">
                            <i class="fas fa-compass"></i> MAIN
                        </div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="superadmin-dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- ADMIN MANAGEMENT - ICONS ZOTE ZIPO -->
                    <div class="sidebar-section mt-3">
                        <div class="sidebar-section-title">
                            <i class="fas fa-users-cog"></i> ADMIN MANAGEMENT
                        </div>
                        <ul class="nav flex-column">
                            <!-- Admin Registration - Icon + Badge -->
                            <li class="nav-item">
                                <a href="admin/admin-register.php" class="nav-link w-100 text-start">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Admin Registration</span>
                                    <?php if($pending_count > 0): ?>
                                    <span class="notification-badge"><?php echo $pending_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            
                            <!-- Manage Admins - Icon + Modal -->
                            <li class="nav-item">
                                <button class="nav-link w-100 text-start" id="manageAdminsBtn">
                                    <i class="fas fa-users"></i>
                                    <span>Manage Admins</span>
                                </button>
                            </li>
                            
                            <!-- Admin Dashboard - Icon + Arrow -->
                            <li class="nav-item">
                                <a href="../admin/dashboard.php" class="nav-link w-100 text-start">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Admin Dashboard</span>
                                    <i class="fas fa-arrow-right ms-auto"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- SYSTEM SETTINGS - ICONS ZOTE ZIPO -->
                    <div class="sidebar-section mt-3">
                        <div class="sidebar-section-title">
                            <i class="fas fa-cog"></i> SYSTEM
                        </div>
                        <ul class="nav flex-column">
                            <!-- Audit Log - Icon + Modal -->
                            <li class="nav-item">
                                <button class="nav-link w-100 text-start" id="auditLogBtn">
                                    <i class="fas fa-history"></i>
                                    <span>Audit Log</span>
                                </button>
                            </li>
                            
                            <!-- System Settings Dropdown - Icon + Chevron -->
                            <li class="nav-item">
                                <button class="nav-link w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#systemSettingsMenu" aria-expanded="false">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>System Settings</span>
                                    <i class="fas fa-chevron-down ms-auto"></i>
                                </button>
                                <div class="collapse ms-3 mt-1" id="systemSettingsMenu">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a href="general-settings.php" class="nav-link w-100 text-start">
                                                <i class="fas fa-globe"></i>
                                                <span>General</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="security-settings.php" class="nav-link w-100 text-start">
                                                <i class="fas fa-shield-alt"></i>
                                                <span>Security</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="email-settings.php" class="nav-link w-100 text-start">
                                                <i class="fas fa-envelope"></i>
                                                <span>Email</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="backup-settings.php" class="nav-link w-100 text-start">
                                                <i class="fas fa-database"></i>
                                                <span>Backup</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            
                            <!-- Divider -->
                            <li class="nav-item my-2">
                                <hr class="bg-white opacity-25">
                            </li>
                            
                            <!-- Logout - Icon + Danger -->
                            <li class="nav-item">
                                <button class="nav-link text-danger w-100 text-start" id="logoutBtn">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Last Login Info -->
                    <div class="px-3 mt-4">
                        <div class="small text-white-50 bg-dark bg-opacity-25 p-3 rounded">
                            <i class="fas fa-clock me-1"></i> Last login:<br>
                            <?php echo date('M j, Y H:i', $_SESSION['last_login']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <main class="col-md-9 col-lg-10 px-md-2 main-content" id="mainContent">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0" style="color: var(--dark);">
                        <i class="fas fa-tachometer-alt me-2" style="color: var(--primary-color);"></i>
                        Dashboard Overview
                    </h2>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-3">
                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('F j, Y'); ?>
                        </span>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="welcome-card mb-4">
                    <div class="welcome-content">
                        <h3 class="fw-bold mb-2">
                            Welcome back, Super Admin! 👋
                        </h3>
                        <p class="mb-0 opacity-75">
                            You have <strong><?php echo $pending_count; ?></strong> pending admin registration(s) and 
                            <strong><?php echo count($admins); ?></strong> total admin accounts.
                        </p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4 g-4">
                    <div class="col-md-4">
                        <div class="card h-100 stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted text-uppercase small fw-bold">Total Admins</span>
                                        <h2 class="mb-0 mt-2 fw-bold"><?php echo count($admins); ?></h2>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                        <i class="fas fa-users fa-2x" style="color: var(--primary-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted text-uppercase small fw-bold">Active Admins</span>
                                        <h2 class="mb-0 mt-2 fw-bold" style="color: var(--success-color);">
                                            <?php echo count(array_filter($admins, fn($a) => $a['status'] === 'active')); ?>
                                        </h2>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                        <i class="fas fa-check-circle fa-2x" style="color: var(--success-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted text-uppercase small fw-bold">Pending/Inactive</span>
                                        <h2 class="mb-0 mt-2 fw-bold" style="color: var(--warning-color);">
                                            <?php echo count(array_filter($admins, fn($a) => $a['status'] !== 'active')); ?>
                                        </h2>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                        <i class="fas fa-clock fa-2x" style="color: var(--warning-color);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Table -->
                <div class="card">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-users-cog me-2" style="color: var(--primary-color);"></i>
                            Admin Accounts
                        </h5>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm" id="createAdminBtn">
                                <i class="fas fa-user-plus me-1"></i> Add New Admin
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($admins) > 0): ?>
                                        <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td class="fw-bold">#<?php echo $admin['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded-circle p-2 me-2">
                                                        <i class="fas fa-user" style="color: var(--gray);"></i>
                                                    </div>
                                                    <?php echo htmlspecialchars($admin['username']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($admin['reg_date'])); ?></td>
                                            <td>
                                                <?php if($admin['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif($admin['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><?php echo ucfirst($admin['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- ACTION BUTTONS - ICONS ZOTE ZIPO -->
                                                <div class="btn-group btn-group-sm">
                                                    <!-- View Profile - Eye Icon -->
                                                    <button type="button" class="btn btn-outline-info btn-view-profile" 
                                                            data-admin-id="<?php echo $admin['id']; ?>"
                                                            title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Edit Admin - Pencil Icon -->
                                                    <button type="button" class="btn btn-outline-primary btn-edit-admin" 
                                                            data-admin-id="<?php echo $admin['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>"
                                                            data-email="<?php echo htmlspecialchars($admin['email'], ENT_QUOTES); ?>"
                                                            data-status="<?php echo $admin['status']; ?>"
                                                            title="Edit Admin">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Admin - Trash Icon -->
                                                    <button type="button" class="btn btn-outline-danger btn-delete-admin" 
                                                            data-admin-id="<?php echo $admin['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>"
                                                            title="Delete Admin">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <i class="fas fa-users-slash fa-3x mb-3" style="color: var(--gray);"></i>
                                                <p class="mb-0 text-muted">No admin accounts found</p>
                                                <button class="btn btn-primary btn-sm mt-3" id="createAdminBtn">
                                                    <i class="fas fa-user-plus me-1"></i> Create First Admin
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ============================================
         MODALS - ZOTE ZIPO KAMILI
         ============================================ -->

    <!-- Create Admin Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i> Create New Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAdminForm">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="username" 
                                       name="username" placeholder="Enter username" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control border-start-0" id="email" 
                                       name="email" placeholder="Enter email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control border-start-0" id="password" 
                                       name="password" placeholder="Enter password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control border-start-0" id="confirm_password" 
                                       name="confirm_password" placeholder="Confirm password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Account Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveAdminBtn">
                        <i class="fas fa-check-circle me-1"></i> Create Admin
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Admins Modal -->
    <div class="modal fade" id="manageAdminsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-users-cog me-2"></i> Manage Admins
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="manageAdminsTableBody">
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($admin['reg_date'])); ?></td>
                                    <td>
                                        <?php if($admin['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif($admin['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo ucfirst($admin['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info btn-view-profile" 
                                                    data-admin-id="<?php echo $admin['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-edit-admin" 
                                                    data-admin-id="<?php echo $admin['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($admin['email'], ENT_QUOTES); ?>"
                                                    data-status="<?php echo $admin['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-admin" 
                                                    data-admin-id="<?php echo $admin['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <a href="admin/admin-register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Admin Registration Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Log Modal -->
    <div class="modal fade" id="auditLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2"></i> Audit Log
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="auditSearch" 
                                           placeholder="Search logs...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="auditFilter">
                                    <option value="">All Actions</option>
                                    <option value="admin_created">Admin Created</option>
                                    <option value="admin_updated">Admin Updated</option>
                                    <option value="admin_deleted">Admin Deleted</option>
                                    <option value="admin_login">Login</option>
                                    <option value="admin_logout">Logout</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="auditLogsContainer">
                        <?php if(count($audit_logs) > 0): ?>
                            <?php foreach ($audit_logs as $log): ?>
                            <div class="audit-log-entry">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-light rounded-circle p-2 me-2">
                                                <i class="fas fa-user-circle" style="color: var(--primary-color);"></i>
                                            </div>
                                            <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                                            <span class="badge bg-info ms-2">
                                                <?php echo str_replace('_', ' ', ucfirst($log['action'])); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1 text-dark"><?php echo htmlspecialchars($log['details']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i> <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                                            <i class="fas fa-laptop ms-3 me-1"></i> <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-secondary">ID: <?php echo $log['id']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x mb-3" style="color: var(--gray);"></i>
                                <p class="mb-0 text-muted">No audit logs found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" id="refreshAuditBtn">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Details Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-id-card me-2"></i> Admin Profile Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="profileContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading profile details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i> Edit Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAdminForm">
                        <input type="hidden" id="editAdminId" name="id">
                        
                        <div class="mb-3">
                            <label for="editUsername" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="editUsername" 
                                       name="username" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEmail" class="form-label fw-semibold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control border-start-0" id="editEmail" 
                                       name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStatus" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="changePassword" name="change_password">
                                <label class="form-check-label fw-semibold" for="changePassword">
                                    Change Password
                                </label>
                            </div>
                        </div>
                        
                        <div id="passwordFields" style="display: none;">
                            <div class="mb-3">
                                <label for="newPassword" class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="newPassword" 
                                           name="new_password" placeholder="Enter new password">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label fw-semibold">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="confirmPassword" 
                                           name="confirm_password" placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="updateAdminBtn">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" data-sourcemap="disabled"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js" data-sourcemap="disabled"></script>
    
    <script>
        // DISABLE SOURCE MAPS
        (function() {
            const originalFetch = window.fetch;
            window.fetch = function(url, options) {
                if (typeof url === 'string' && (url.includes('.map') || url.includes('sourcemap'))) {
                    return Promise.reject(new Error('Source maps disabled'));
                }
                return originalFetch.call(this, url, options);
            };
            
            const originalOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method, url) {
                if (typeof url === 'string' && (url.includes('.map') || url.includes('sourcemap'))) {
                    return;
                }
                return originalOpen.apply(this, arguments);
            };
        })();

        // MODAL FUNCTIONS
        function openCreateAdminModal() {
            const modal = document.getElementById('createAdminModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                const createModal = new bootstrap.Modal(modal);
                createModal.show();
            }
        }

        function openManageAdminsModal() {
            const modal = document.getElementById('manageAdminsModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                const manageModal = new bootstrap.Modal(modal);
                manageModal.show();
            }
        }

        function openAuditLogModal() {
            const modal = document.getElementById('auditLogModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                const auditModal = new bootstrap.Modal(modal);
                auditModal.show();
            }
        }

        function openEditAdminModal() {
            const modal = document.getElementById('editAdminModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                const editModal = new bootstrap.Modal(modal);
                editModal.show();
            }
        }

        function openProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                const profileModal = new bootstrap.Modal(modal);
                profileModal.show();
            }
        }

        // FIX MODAL CLOSE BUTTONS
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-close').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.setAttribute('aria-hidden', 'true');
                    }
                });
            });
        });

        // INITIALIZE ALL COMPONENTS
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            loadDashboardContent();
        });

        // EVENT LISTENERS
        function initializeEventListeners() {
            // Create Admin Button - Modal
            const createAdminBtn = document.querySelectorAll('#createAdminBtn');
            createAdminBtn.forEach(btn => {
                btn.addEventListener('click', function() {
                    openCreateAdminModal();
                });
            });

            // Manage Admins Button - Modal
            const manageAdminsBtn = document.getElementById('manageAdminsBtn');
            if (manageAdminsBtn) {
                manageAdminsBtn.addEventListener('click', function() {
                    openManageAdminsModal();
                });
            }

            // Audit Log Button - Modal
            const auditLogBtn = document.getElementById('auditLogBtn');
            if (auditLogBtn) {
                auditLogBtn.addEventListener('click', function() {
                    openAuditLogModal();
                });
            }

            // Logout Button - SweetAlert
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    confirmLogout();
                });
            }

            // Save Admin Button - Create Admin
            const saveAdminBtn = document.getElementById('saveAdminBtn');
            if (saveAdminBtn) {
                saveAdminBtn.addEventListener('click', function() {
                    saveNewAdmin();
                });
            }

            // Update Admin Button
            const updateAdminBtn = document.getElementById('updateAdminBtn');
            if (updateAdminBtn) {
                updateAdminBtn.addEventListener('click', function() {
                    updateAdmin();
                });
            }

            // Toggle Password Visibility
            const togglePassword = document.getElementById('togglePassword');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    togglePasswordVisibility('password');
                });
            }

            // Change Password Checkbox
            const changePassword = document.getElementById('changePassword');
            if (changePassword) {
                changePassword.addEventListener('change', function() {
                    const passwordFields = document.getElementById('passwordFields');
                    if (passwordFields) {
                        passwordFields.style.display = this.checked ? 'block' : 'none';
                    }
                });
            }

            // Create Admin from Manage Modal
            const createAdminFromManageBtn = document.getElementById('createAdminFromManageBtn');
            if (createAdminFromManageBtn) {
                createAdminFromManageBtn.addEventListener('click', function() {
                    const manageModal = bootstrap.Modal.getInstance(document.getElementById('manageAdminsModal'));
                    if (manageModal) manageModal.hide();
                    openCreateAdminModal();
                });
            }

            // Refresh Audit Log
            const refreshAuditBtn = document.getElementById('refreshAuditBtn');
            if (refreshAuditBtn) {
                refreshAuditBtn.addEventListener('click', function() {
                    refreshAuditLog();
                });
            }

            // GLOBAL CLICK HANDLER for dynamic buttons
            document.addEventListener('click', function(e) {
                // View Profile
                const viewBtn = e.target.closest('.btn-view-profile');
                if (viewBtn) {
                    const adminId = viewBtn.getAttribute('data-admin-id');
                    if (adminId) window.viewProfile(adminId);
                }
                
                // Edit Admin
                const editBtn = e.target.closest('.btn-edit-admin');
                if (editBtn) {
                    const adminId = editBtn.getAttribute('data-admin-id');
                    const username = editBtn.getAttribute('data-username');
                    const email = editBtn.getAttribute('data-email');
                    const status = editBtn.getAttribute('data-status');
                    if (adminId && username && email && status) {
                        window.editAdmin(adminId, username, email, status);
                    }
                }
                
                // Delete Admin
                const deleteBtn = e.target.closest('.btn-delete-admin');
                if (deleteBtn) {
                    const adminId = deleteBtn.getAttribute('data-admin-id');
                    const username = deleteBtn.getAttribute('data-username');
                    if (adminId && username) {
                        window.confirmDelete(adminId, username);
                    }
                }
            });
        }

        // LOAD DASHBOARD CONTENT (AJAX)
        function loadDashboardContent() {
            fetch('ajax/get-dashboard-stats-ajax.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateDashboardStats(data);
                    }
                })
                .catch(error => console.error('Error loading dashboard:', error));
        }

        // UPDATE DASHBOARD STATS
        function updateDashboardStats(data) {
            console.log('Dashboard stats updated:', data);
        }

        // SAVE NEW ADMIN
        function saveNewAdmin() {
            const form = document.getElementById('createAdminForm');
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match!'
                });
                return;
            }
            
            if (password.length < 8) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Password',
                    text: 'Password must be at least 8 characters long!'
                });
                return;
            }
            
            const formData = new FormData(form);
            
            fetch('ajax/create-admin-ajax.php', { 
                method: 'POST', 
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Admin created successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        const createModal = bootstrap.Modal.getInstance(document.getElementById('createAdminModal'));
                        if (createModal) createModal.hide();
                        form.reset();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to create admin'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while creating admin.'
                });
                console.error('Error:', error);
            });
        }

        // EDIT ADMIN
        window.editAdmin = function(id, username, email, status) {
            document.getElementById('editAdminId').value = id;
            document.getElementById('editUsername').value = decodeHtml(username);
            document.getElementById('editEmail').value = decodeHtml(email);
            document.getElementById('editStatus').value = status;
            openEditAdminModal();
        };

        // UPDATE ADMIN
        function updateAdmin() {
            const form = document.getElementById('editAdminForm');
            const formData = new FormData(form);
            
            const changePassword = document.getElementById('changePassword').checked;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (changePassword) {
                if (newPassword !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Password Mismatch',
                        text: 'New password and confirm password do not match!'
                    });
                    return;
                }
                
                if (newPassword.length < 8) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Password',
                        text: 'Password must be at least 8 characters long!'
                    });
                    return;
                }
            }
            
            fetch('ajax/update-admin-ajax.php', { 
                method: 'POST', 
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editAdminModal'));
                        if (editModal) editModal.hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while updating admin.'
                });
                console.error('Error:', error);
            });
        }

        // VIEW PROFILE
        window.viewProfile = function(adminId) {
            const profileContent = document.getElementById('profileContent');
            if (!profileContent) return;
            
            profileContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading profile details...</p>
                </div>
            `;
            
            fetch(`ajax/get-admin-details-ajax.php?id=${adminId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.admin) {
                        const admin = data.admin;
                        const regDate = admin.reg_date ? new Date(admin.reg_date) : new Date();
                        const lastLogin = admin.last_login ? new Date(admin.last_login) : null;
                        
                        const profileHtml = `
                            <div class="text-center mb-4">
                                <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                                    <i class="fas fa-user-circle fa-4x" style="color: var(--primary-color);"></i>
                                </div>
                                <h4 class="fw-bold mb-1">${escapeHtml(admin.username || '')}</h4>
                                <span class="badge ${admin.status === 'active' ? 'bg-success' : (admin.status === 'pending' ? 'bg-warning' : 'bg-danger')}">
                                    ${capitalizeFirst(admin.status || '')}
                                </span>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted d-block">Admin ID</small>
                                        <strong class="fs-5">#${admin.id || ''}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted d-block">Email</small>
                                        <strong>${escapeHtml(admin.email || '')}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted d-block">Registered</small>
                                        <strong>${regDate.toLocaleDateString('en-US', { 
                                            year: 'numeric', 
                                            month: 'long', 
                                            day: 'numeric'
                                        })}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted d-block">Account Type</small>
                                        <strong>${admin.is_superadmin == 1 ? 'Super Admin' : 'Admin'}</strong>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bg-light p-3 rounded">
                                        <small class="text-muted d-block">Last Login</small>
                                        <strong>${lastLogin ? lastLogin.toLocaleString() : 'Never'}</strong>
                                    </div>
                                </div>
                            </div>
                        `;
                        profileContent.innerHTML = profileHtml;
                    } else {
                        profileContent.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${data.message || 'Admin not found'}
                            </div>
                        `;
                    }
                    openProfileModal();
                })
                .catch(function(error) {
                    profileContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading profile details.
                        </div>
                    `;
                    console.error('Error:', error);
                });
        };

        // CONFIRM DELETE
        window.confirmDelete = function(adminId, username) {
            Swal.fire({
                title: 'Are you sure?',
                html: `<strong>${escapeHtml(username)}</strong> will be permanently deleted!<br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF233C',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return fetch(`ajax/delete-admin-ajax.php?id=${adminId}`, { 
                        method: 'POST', 
                        body: 'confirm=true' 
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to delete admin');
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: `Admin ${escapeHtml(username)} has been deleted.`,
                        timer: 2000,
                        showConfirmButton: false,
                        willClose: function() {
                            window.location.reload();
                        }
                    });
                }
            });
        };

        // CONFIRM LOGOUT
        function confirmLogout() {
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4361EE',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Stay logged in',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return fetch('ajax/logout-ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'confirm=true'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Logout failed');
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out!',
                        text: 'You have been successfully logged out.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = 'superadmin-login.php';
                    });
                }
            });
        }

        // REFRESH AUDIT LOG
        function refreshAuditLog() {
            const container = document.getElementById('auditLogsContainer');
            if (!container) return;
            
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Refreshing logs...</p>
                </div>
            `;
            
            fetch('ajax/get-audit-logs-ajax.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAuditLogs(data.logs || []);
                    } else {
                        container.innerHTML = '<div class="alert alert-danger">Failed to load audit logs.</div>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<div class="alert alert-danger">Error loading audit logs.</div>';
                    console.error('Error:', error);
                });
        }

        // DISPLAY AUDIT LOGS
        function displayAuditLogs(logs) {
            const container = document.getElementById('auditLogsContainer');
            if (!container) return;
            
            let html = '';
            
            if (logs && logs.length > 0) {
                logs.forEach(function(log) {
                    html += `
                        <div class="audit-log-entry">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-light rounded-circle p-2 me-2">
                                            <i class="fas fa-user-circle" style="color: var(--primary-color);"></i>
                                        </div>
                                        <strong>${escapeHtml(log.username || 'System')}</strong>
                                        <span class="badge bg-info ms-2">${formatAction(log.action || '')}</span>
                                    </div>
                                    <p class="mb-1 text-dark">${escapeHtml(log.details || '')}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i> ${formatDate(log.created_at || '')}
                                        <i class="fas fa-laptop ms-3 me-1"></i> ${escapeHtml(log.ip_address || '')}
                                    </small>
                                </div>
                                <span class="badge bg-secondary">ID: ${log.id || ''}</span>
                            </div>
                        </div>
                    `;
                });
            }
            
            container.innerHTML = html || '<div class="alert alert-info">No audit logs found.</div>';
        }

        // FILTER AUDIT LOGS
        function filterAuditLogs() {
            const searchText = document.getElementById('auditSearch')?.value.toLowerCase() || '';
            const filterAction = document.getElementById('auditFilter')?.value.toLowerCase() || '';
            const entries = document.querySelectorAll('.audit-log-entry');
            
            entries.forEach(function(entry) {
                const text = entry.textContent.toLowerCase();
                const actionEl = entry.querySelector('.badge.bg-info');
                const action = actionEl ? actionEl.textContent.toLowerCase() : '';
                
                const matchesSearch = searchText === '' || text.includes(searchText);
                const matchesFilter = filterAction === '' || action.includes(filterAction.replace('_', ' '));
                
                entry.style.display = matchesSearch && matchesFilter ? 'block' : 'none';
            });
        }

        // TOGGLE PASSWORD VISIBILITY
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            
            const button = document.getElementById('togglePassword');
            if (button) {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                }
            }
        }

        // HELPER FUNCTIONS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        function decodeHtml(html) {
            if (!html) return '';
            const div = document.createElement('div');
            div.innerHTML = html;
            return div.textContent || div.innerText || '';
        }

        function capitalizeFirst(string) {
            if (!string) return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function formatAction(action) {
            if (!action) return '';
            return action.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                return date.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }

        // AUTO-DISMISS ALERTS
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch(e) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);

        // OVERWRITE MODAL FUNCTIONS
        window.openCreateAdminModal = openCreateAdminModal;
        window.openManageAdminsModal = openManageAdminsModal;
        window.openAuditLogModal = openAuditLogModal;
        window.openEditAdminModal = openEditAdminModal;
        window.openProfileModal = openProfileModal;
    </script>
</body>
</html>