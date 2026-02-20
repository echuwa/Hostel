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
        .brand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            background: linear-gradient(135deg, #2c3136, #3a4149);
            color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            height: 60px;
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
            color: #37a6c4;
        }
        
        .logo:hover {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
        }

        /* Mobile sidebar toggle */
        .sidebar-mobile-toggle {
            display: none;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
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
            background: rgba(255,255,255,0.2);
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
            gap: 8px;
            color: #fff;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            background: rgba(255,255,255,0.1);
            transition: background 0.2s;
        }
        
        .ts-account > a:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .ts-account > a .avatar-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #37a6c4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
        }
        
        .ts-account ul {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: #fff;
            min-width: 190px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            list-style: none;
            padding: 8px 0;
            margin: 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.25s ease;
            z-index: 1000;
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
            top: -6px;
            right: 16px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 6px solid #fff;
        }
        
        .ts-account ul li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            color: #444;
            text-decoration: none;
            font-size: 0.88rem;
            transition: all 0.15s;
        }
        
        .ts-account ul li a:hover {
            background: #f0f5ff;
            color: #3a7bd5;
            padding-left: 22px;
        }
        
        .ts-account ul li a i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
            color: #37a6c4;
        }

        .ts-account ul li:last-child a {
            color: #e74a3b;
            border-top: 1px solid #f0f0f0;
            margin-top: 4px;
        }

        .ts-account ul li:last-child a i {
            color: #e74a3b;
        }
        
        @media (max-width: 768px) {
            .sidebar-mobile-toggle {
                display: flex;
            }
            
            .logo span {
                display: none;
            }

            .ts-account > a .username {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="brand">
        <div class="brand-left">
            <button class="sidebar-mobile-toggle" id="adminMobileSidebarToggle" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <a href="dashboard.php" class="logo">
                <i class="fas fa-building"></i>
                <span>HostelMS Admin</span>
            </a>
        </div>
        
        <ul class="ts-profile-nav">
            <li class="ts-account">
                <a href="#" id="adminProfileToggle">
                    <div class="avatar-circle">
                        <?php
                        $aname = $_SESSION['username'] ?? $_SESSION['login'] ?? 'A';
                        echo strtoupper(substr($aname, 0, 1));
                        ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($aname); ?></span>
                    <i class="fa fa-angle-down"></i>
                </a>
                <ul>
                    <li><a href="admin-profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="change-password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile sidebar toggle for admin
        const mobileToggle = document.getElementById('adminMobileSidebarToggle');
        const sidebar = document.querySelector('.ts-sidebar');

        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('mobile-open')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
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