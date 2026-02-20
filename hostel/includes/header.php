<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .brand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: #3a7bd5;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            height: 60px;
        }
        
        .logo {
            font-size: 1rem;
            font-weight: 500;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .menu-btn {
            display: none;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 5px;
        }
        
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
            color: #fff;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .ts-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
        }
        
        .ts-account ul {
            position: absolute;
            right: 0;
            top: 100%;
            background: #fff;
            min-width: 160px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            list-style: none;
            padding: 5px 0;
            margin-top: 5px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 1000;
        }
        
        .ts-account:hover ul {
            opacity: 1;
            visibility: visible;
        }
        
        .ts-account ul li a {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .ts-account ul li a:hover {
            background-color: #f5f5f5;
            color: #3a7bd5;
        }
        
        .ts-account ul li a i {
            width: 18px;
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .menu-btn {
                display: block;
            }
            
            .logo span {
                display: none;
            }
            
            .logo i {
                margin-right: 0;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<div class="brand">
    <a href="dashboard.php" class="logo" style="font-size:16px; color:#fff !important">
        <i class="fas fa-home"></i>
        <span>Hostel Management System</span>
    </a>
    <span class="menu-btn"><i class="fa fa-bars"></i></span>
    
    <?php if(isset($_SESSION['id'])) { ?>
    <ul class="ts-profile-nav">
        <li class="ts-account">
            <a href="#">
                <span class="username"><?php echo isset($_SESSION['login']) ? htmlspecialchars($_SESSION['login']) : 'Account'; ?></span>
                <i class="fa fa-angle-down" style="margin-left:8px;"></i>
            </a>
            <ul>
                <li><a href="my-profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="change-password.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </li>
    </ul>
    <?php } ?>
</div>