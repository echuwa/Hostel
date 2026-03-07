<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .brand, .ts-sidebar, .sidebar, #sidebar, .sidebar-mobile-toggle,
            .header-page-info, .btn-modern, .print-hidden,
            nav, .ts-profile-nav {
                display: none !important;
            }
            body { background: white !important; margin: 0 !important; }
            .content-wrapper, .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .card, .profile-card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
        .notif-dot {
            position: absolute; top: 0; right: 0; width: 10px; height: 10px;
            background: #ef4444; border-radius: 50%; border: 2px solid #fff;
        }
        .header-action-btn {
            position: relative; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
            color: #fff; width: 40px; height: 40px; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;
        }
        .header-action-btn:hover { background: rgba(255,255,255,0.25); }
        body {
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            background: #f4f6fb;
        }
        .main-content {
            padding-top: 64px !important;
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
            display: none; /* Hide old icon to match student layout */
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
        
        @media (max-width: 991px) {
            .sidebar-mobile-toggle {
                display: flex;
            }
            
            .logo span {
                display: none;
            }

            .ts-account > a .username {
                display: none;
            }

            .header-page-info .page-title-text {
                font-size: 1rem;
                max-width: 150px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
    </style>
</head>
<body>
    <div class="brand">
        <?php 
        $currentPage = basename($_SERVER['PHP_SELF']);
        $pageTitle = "Admin Dashboard";
        switch ($currentPage) {
            case 'manage-students.php': $pageTitle = 'Manage Students'; break;
            case 'create-room.php': $pageTitle = 'Create Room'; break;
            case 'manage-rooms.php': $pageTitle = 'Manage Rooms'; break;
            case 'new-complaints.php': $pageTitle = 'New Complaints'; break;
            case 'inprocess-complaints.php': $pageTitle = 'In Process'; break;
            case 'closed-complaints.php': $pageTitle = 'Closed Complaints'; break;
            case 'all-complaints.php': $pageTitle = 'All Complaints'; break;
            case 'manage-courses.php': $pageTitle = 'Manage Courses'; break;
        }
        $currentDate = date("F j, Y");
        ?>
        <div class="brand-left">
            <button class="sidebar-mobile-toggle" id="adminMobileSidebarToggle" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <?php
            // Global counts for notification bell
            $pending_stud_count = 0;
            $new_compl_count = 0;
            if (isset($mysqli)) {
                $ps_res = $mysqli->query("SELECT COUNT(*) as c FROM userregistration WHERE status = 'Pending'");
                if($ps_res) $pending_stud_count = $ps_res->fetch_object()->c;
                
                $nc_res = $mysqli->query("SELECT COUNT(*) as c FROM complaints WHERE complaintStatus IS NULL OR complaintStatus='' OR complaintStatus='new'");
                if($nc_res) $new_compl_count = $nc_res->fetch_object()->c;
            }
            $total_alerts = $pending_stud_count + $new_compl_count;
            ?>
            <div class="header-page-info" style="margin-left: 10px; display: flex; align-items: center; gap: 20px;">
                <div style="font-size: 1.25rem; font-weight: 700; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-th-large" style="font-size: 1.1rem; opacity: 0.9;"></i> 
                    <span class="page-title-text" style="display:inline-block;"><?php echo $pageTitle; ?></span>
                </div>
                <div class="header-calendar d-none d-md-flex" style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; align-items: center; gap: 8px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.05); display:flex;">
                    <i class="far fa-calendar-alt"></i> 
                    <span><?php echo $currentDate; ?></span>
                </div>
            </div>
        </div>
        
        <ul class="ts-profile-nav" style="display:flex; align-items:center; gap:15px;">
            <!-- Notification Bell -->
            <li class="dropdown d-none d-md-block" style="position:relative;">
                <button class="header-action-btn" data-bs-toggle="dropdown" aria-expanded="false" id="notifBell">
                    <i class="fas fa-bell"></i>
                    <?php if($total_alerts > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-3 mt-2" style="width: 300px;">
                    <h6 class="fw-800 mb-3 px-2">System Alerts</h6>
                    <?php if($pending_stud_count > 0): ?>
                    <a href="manage-students.php" class="dropdown-item p-2 rounded-3 mb-2 d-flex align-items-center gap-3">
                        <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="fas fa-user-clock" style="font-size:0.8rem;"></i>
                        </div>
                        <div>
                            <div class="fw-800 small text-dark"><?php echo $pending_stud_count; ?> Pending Approvals</div>
                            <div class="text-muted" style="font-size: 0.65rem;">Action required</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if($new_compl_count > 0): ?>
                    <a href="new-complaints.php" class="dropdown-item p-2 rounded-3 mb-2 d-flex align-items-center gap-3">
                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="fas fa-exclamation-triangle" style="font-size:0.8rem;"></i>
                        </div>
                        <div>
                            <div class="fw-800 small text-dark"><?php echo $new_compl_count; ?> New Complaints</div>
                            <div class="text-muted" style="font-size: 0.65rem;">New support tickets</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if($total_alerts == 0): ?>
                    <div class="text-center py-3 text-muted small fw-600">No new alerts</div>
                    <?php endif; ?>
                    <hr class="my-2">
                    <div class="text-center"><a href="dashboard.php" class="text-primary small fw-800 text-decoration-none">View All Dashboard</a></div>
                </div>
            </li>

            <li class="ts-account">
                <a href="#" id="adminProfileToggle">
                    <?php
                    $aname = $_SESSION['username'] ?? 'Admin';
                    $adminDisplay = ucfirst($aname);
                    $hdr_initial = strtoupper(substr($aname, 0, 1));
                    $hdr_pic_src = '';
                    
                    if (isset($mysqli) && isset($_SESSION['id'])) {
                        $aid = $_SESSION['id'];
                        $stmt = $mysqli->prepare("SELECT profile_pic FROM admins WHERE id = ? LIMIT 1");
                        if ($stmt) {
                            $stmt->bind_param("i", $aid);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if ($row = $res->fetch_object()) {
                                $hdr_profile_pic = $row->profile_pic ?? '';
                                if (!empty($hdr_profile_pic)) {
                                    if (substr($hdr_profile_pic, 0, 4) === 'http') {
                                        $hdr_pic_src = $hdr_profile_pic;
                                    } else {
                                        $hdr_pic_src = '../' . ltrim($hdr_profile_pic, '/');
                                    }
                                }
                            }
                            $stmt->close();
                        }
                    }
                    ?>
                    <?php if (!empty($hdr_pic_src)): ?>
                    <div class="avatar-circle" style="background: none; padding: 2px; overflow: hidden; border: 2px solid rgba(255,255,255,0.4);">
                        <img src="<?php echo htmlspecialchars($hdr_pic_src); ?>" 
                             alt="Profile" 
                             style="width: 30px; height: 30px; object-fit: cover; border-radius: 50%;"
                             onerror="this.parentElement.innerHTML='<?php echo $hdr_initial; ?>'; this.parentElement.style.background='linear-gradient(135deg, #b5179e, #7209b7)';">
                    </div>
                    <?php else: ?>
                    <div class="avatar-circle"><?php echo $hdr_initial; ?></div>
                    <?php endif; ?>
                    <div class="user-info">
                        <span class="user-label">Admin</span>
                        <span class="username"><?php echo htmlspecialchars($adminDisplay); ?></span>
                    </div>
                    <i class="fa fa-angle-down" style="margin-left:4px; font-size:0.8rem;"></i>
                </a>
                <ul>
                    <li class="dd-profile-head" style="list-style:none;">
                        <div class="dd-name"><?php echo htmlspecialchars($adminDisplay); ?></div>
                        <div class="dd-role">System Administrator</div>
                    </li>
                    <li><a href="admin-profile.php">
                        <span class="menu-icon"><i class="fas fa-user"></i></span> My Profile
                    </a></li>
                    <li><a href="change-password.php">
                        <span class="menu-icon"><i class="fas fa-key"></i></span> Change Password
                    </a></li>
                    <li class="logout-item"><a href="../logout.php">
                        <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span> Logout
                    </a></li>
                </ul>
            </li>
        </ul>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile sidebar toggle for admin
        const mobileToggle = document.getElementById('adminMobileSidebarToggle');
        const sidebar = document.getElementById('sidebar'); // Corrected to match sidebar_modern.php

        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('mobile-open');
                
                // Add backdrop overlay if it doesn't exist
                let backdrop = document.querySelector('.sidebar-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'sidebar-backdrop';
                    backdrop.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9998; display:none; backdrop-filter:blur(2px); transition: 0.3s;';
                    document.body.appendChild(backdrop);
                    
                    backdrop.addEventListener('click', () => {
                        sidebar.classList.remove('mobile-open');
                        backdrop.style.display = 'none';
                        const icon = mobileToggle.querySelector('i');
                        if(icon) icon.className = 'fas fa-bars';
                    });
                }

                const icon = this.querySelector('i');
                if (sidebar.classList.contains('mobile-open')) {
                    icon.className = 'fas fa-times';
                    backdrop.classList.add('active');
                } else {
                    icon.className = 'fas fa-bars';
                    backdrop.classList.remove('active');
                }
            });
        }

        // Dropdown touch support
        const profileToggle = document.getElementById('adminProfileToggle');
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