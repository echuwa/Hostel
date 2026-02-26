<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-theme.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            background: #f4f6fb;
        }
        .brand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
            background: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
            color: #fff;
            box-shadow: 0 3px 16px rgba(67,97,238,0.3);
            height: 64px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
        }
        
        .brand-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logo i {
            font-size: 1.3rem;
        }
        
        .logo:hover {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
        }
        
        /* Mobile sidebar toggle */
        .sidebar-mobile-toggle {
            display: none;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 8px;
            color: #fff;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background 0.2s;
        }
        
        .sidebar-mobile-toggle:hover {
            background: rgba(255,255,255,0.25);
        }
        
        /* Profile nav */
        .ts-profile-nav {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .ts-account {
            position: relative;
        }
        
        .ts-account > a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.28);
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        
        .ts-account > a:hover {
            background: rgba(255,255,255,0.32);
            color: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        }
        
        .ts-account > a .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #b5179e, #7209b7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.95rem;
            color: #fff;
            box-shadow: 0 2px 8px rgba(114,9,183,0.4);
            flex-shrink: 0;
        }

        .ts-account > a .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .ts-account > a .user-info .user-label {
            font-size: 0.7rem;
            font-weight: 500;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .ts-account > a .user-info .username {
            font-size: 0.88rem;
            font-weight: 700;
        }
        
        .ts-account ul {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: #fff;
            min-width: 210px;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.18);
            list-style: none;
            padding: 10px 0;
            margin: 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.25s ease;
            z-index: 1000;
            border: 1px solid rgba(0,0,0,0.06);
        }
        
        .ts-account:hover ul,
        .ts-account.dropdown-open ul {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .ts-account ul::before {
            content: '';
            position: absolute;
            top: -7px;
            right: 20px;
            border-left: 7px solid transparent;
            border-right: 7px solid transparent;
            border-bottom: 7px solid #fff;
            filter: drop-shadow(0 -2px 2px rgba(0,0,0,0.06));
        }
        
        /* Dropdown profile header */
        .ts-account ul .dd-profile-head {
            padding: 12px 18px 10px;
            border-bottom: 1px solid #f0f2f5;
            margin-bottom: 4px;
        }
        .ts-account ul .dd-profile-head .dd-name {
            font-weight: 700;
            font-size: 0.92rem;
            color: #1a202c;
        }
        .ts-account ul .dd-profile-head .dd-role {
            font-size: 0.75rem;
            color: #718096;
            font-weight: 500;
        }
        
        .ts-account ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            color: #2d3748;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.15s;
            border-radius: 0;
        }
        .ts-account ul li a .menu-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: #4361ee;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .ts-account ul li a:hover {
            background: #f7f8ff;
            color: #4361ee;
        }
        .ts-account ul li a:hover .menu-icon {
            background: #4361ee;
            color: #fff;
        }
        .ts-account ul li.logout-item a {
            color: #e74a3b;
        }
        .ts-account ul li.logout-item a .menu-icon {
            background: #fff5f5;
            color: #e74a3b;
        }
        .ts-account ul li.logout-item a:hover {
            background: #fff5f5;
            color: #c53030;
        }
        .ts-account ul li.logout-item a:hover .menu-icon {
            background: #e74a3b;
            color: #fff;
        }
        .ts-account ul li:last-child a {
            border-top: 1px solid #f0f2f5;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .sidebar-mobile-toggle {
                display: flex;
            }
            .logo span {
                display: none;
            }
            .ts-account > a .user-info {
                display: none;
            }
        }
    </style>
</head>
<div class="brand">
    <?php 
    $currentPage = basename($_SERVER['PHP_SELF']);
    $pageTitle = "Dashboard";
    switch ($currentPage) {
        case 'book-hostel.php': $pageTitle = 'Book Hostel'; break;
        case 'room-details.php': $pageTitle = 'Room Details'; break;
        case 'my-profile.php': $pageTitle = 'My Profile'; break;
        case 'register-complaint.php': $pageTitle = 'Register Complaint'; break;
        case 'my-complaints.php': $pageTitle = 'My Complaints'; break;
        case 'change-password.php': $pageTitle = 'Change Password'; break;
        case 'access-log.php': $pageTitle = 'Access Log'; break;
        case 'dashboard.php': 
        default: $pageTitle = 'Student Dashboard'; break;
    }
    $currentDate = date("F j, Y");
    ?>
    <div class="brand-left">
        <button class="sidebar-mobile-toggle" id="mobileSidebarToggle" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-page-info" style="margin-left: 10px; display: flex; align-items: center; gap: 20px;">
            <div style="font-size: 1.25rem; font-weight: 700; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-th-large" style="font-size: 1.1rem; opacity: 0.9;"></i> 
                <span class="page-title-text"><?php echo $pageTitle; ?></span>
            </div>
            <div class="header-calendar d-none d-md-flex" style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; align-items: center; gap: 8px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);">
                <i class="far fa-calendar-alt"></i> 
                <span><?php echo $currentDate; ?></span>
            </div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
        // Fetch student name for header display
        $hdr_name = $_SESSION['login'] ?? 'Student';
        $hdr_display = $hdr_name;
        if (isset($mysqli)) {
            $hdr_stmt = $mysqli->prepare("SELECT firstName, lastName FROM userregistration WHERE email=? OR regNo=? LIMIT 1");
            if ($hdr_stmt) {
                $hdr_stmt->bind_param('ss', $hdr_name, $hdr_name);
                $hdr_stmt->execute();
                $hdr_res = $hdr_stmt->get_result();
                if ($hdr_row = $hdr_res->fetch_object()) {
                    $hdr_display = trim($hdr_row->firstName . ' ' . $hdr_row->lastName);
                }
                $hdr_stmt->close();
            }
        }
        $hdr_initial = strtoupper(substr($hdr_display, 0, 1));
    ?>
    <ul class="ts-profile-nav">
        <li class="ts-account">
            <a href="#" id="profileDropdownToggle">
                <div class="avatar-circle"><?php echo $hdr_initial; ?></div>
                <div class="user-info">
                    <span class="user-label">Student</span>
                    <span class="username"><?php echo htmlspecialchars($hdr_display); ?></span>
                </div>
                <i class="fa fa-angle-down" style="margin-left:4px; font-size:0.8rem;"></i>
            </a>
            <ul>
                <li class="dd-profile-head" style="list-style:none;">
                    <div class="dd-name"><?php echo htmlspecialchars($hdr_display); ?></div>
                    <div class="dd-role">Hostel Student</div>
                </li>
                <li><a href="my-profile.php">
                    <span class="menu-icon"><i class="fas fa-user"></i></span> My Profile
                </a></li>
                <li><a href="change-password.php">
                    <span class="menu-icon"><i class="fas fa-key"></i></span> Change Password
                </a></li>
                <li class="logout-item"><a href="logout.php">
                    <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span> Logout
                </a></li>
            </ul>
        </li>
    </ul>
    <?php } ?>
</div>

<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }

    // Dropdown touch support
    const profileToggle = document.getElementById('profileDropdownToggle');
    if (profileToggle) {
        profileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const account = this.closest('.ts-account');
            account.classList.toggle('dropdown-open');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ts-account')) {
                document.querySelectorAll('.ts-account').forEach(a => a.classList.remove('dropdown-open'));
            }
        });
    }
});
</script>