<nav class="ts-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-area">
            <div class="logo-icon-wrapper">
                <i class="fas fa-hotel"></i>
            </div>
            <span class="logo-text">HostelMS</span>
        </div>
        <button class="toggle-btn" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-avatar">
                <?php 
                $initial = isset($_SESSION['name']) ? strtoupper(substr($_SESSION['name'], 0, 1)) : 'S';
                echo $initial;
                ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['name'] ?? 'Student'; ?></span>
                <span class="user-role">Resident Student</span>
            </div>
        <?php else: ?>
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-info">
                <span class="user-name">Guest</span>
                <span class="user-role">Not Logged In</span>
            </div>
        <?php endif; ?>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-label">DASHBOARD</li>
        <li>
            <a href="dashboard.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span class="link-text">Home Dashboard</span>
            </a>
        </li>

        <li class="menu-label">HOSTEL SERVICES</li>
        <li>
            <a href="book-hostel.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'book-hostel.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-plus"></i>
                <span class="link-text">Book Room</span>
            </a>
        </li>
        <li>
            <a href="room-details.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'room-details.php' ? 'active' : ''; ?>">
                <i class="fas fa-door-open"></i>
                <span class="link-text">Room Details</span>
            </a>
        </li>
        <li>
            <a href="pay-fees.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'pay-fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i>
                <span class="link-text">Payments</span>
            </a>
        </li>

        <li class="menu-label">SUPPORT</li>
        <li class="submenu">
            <a href="#" class="menu-link">
                <i class="fas fa-flag"></i>
                <span class="link-text">Complaints</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu-items">
                <li><a href="register-complaint.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'register-complaint.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> New Complaint</a></li>
                <li><a href="my-complaints.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my-complaints.php' ? 'active' : ''; ?>"><i class="fas fa-list-ul"></i> My History</a></li>
            </ul>
        </li>
        <li>
            <a href="feedback.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span class="link-text">Give Feedback</span>
            </a>
        </li>

        <li class="menu-label">SETTINGS</li>
        <li>
            <a href="my-profile.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i>
                <span class="link-text">My Profile</span>
            </a>
        </li>
        <li>
            <a href="access-log.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'access-log.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span class="link-text">Access Logs</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-power-off"></i>
            <span class="link-text">Logout Session</span>
        </a>
    </div>
</nav>

<link rel="stylesheet" href="css/student-modern.css">
<style>
    /* Internal Sidebar Styles */
    .ts-sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: #1e293b; /* Slate-900 */
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        transition: all 0.3s ease;
        z-index: 1060;
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(255,255,255,0.05);
    }
    
    .ts-sidebar.collapsed { width: var(--sidebar-collapsed-width); }

    .sidebar-header {
        height: var(--header-height);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        background: rgba(0,0,0,0.1);
    }

    .logo-area { display: flex; align-items: center; gap: 12px; }
    .logo-icon-wrapper {
        width: 36px; height: 36px;
        background: var(--gradient-primary);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 1.1rem;
    }
    .logo-text { font-weight: 800; font-size: 1.25rem; letter-spacing: -0.5px; }

    .ts-sidebar.collapsed .logo-text, .ts-sidebar.collapsed .toggle-btn i { display: none; }
    .ts-sidebar.collapsed .logo-area { margin: 0 auto; }

    .sidebar-user {
        padding: 24px 20px;
        display: flex; align-items: center; gap: 12px;
        margin: 10px 0;
    }
    .user-avatar {
        width: 44px; height: 44px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.4);
    }
    .user-info { line-height: 1.3; }
    .user-name { display: block; font-weight: 700; font-size: 0.9rem; }
    .user-role { font-size: 0.75rem; opacity: 0.6; }

    .ts-sidebar.collapsed .user-info, .ts-sidebar.collapsed .menu-label, .ts-sidebar.collapsed .link-text, .ts-sidebar.collapsed .dropdown-icon {
        display: none !important;
    }

    .ts-sidebar.collapsed .sidebar-user { justify-content: center; padding: 20px 0; }

    .sidebar-menu { flex: 1; list-style: none; padding: 10px 0; margin: 0; overflow-y: auto; }
    .menu-label {
        font-size: 0.7rem; font-weight: 800; color: rgba(255,255,255,0.3);
        margin: 15px 24px 8px; text-transform: uppercase; letter-spacing: 1px;
    }

    .menu-link {
        display: flex; align-items: center; gap: 14px;
        padding: 12px 24px; color: rgba(255,255,255,0.7);
        text-decoration: none; transition: all 0.2s;
    }
    .menu-link i { font-size: 1.1rem; width: 20px; text-align: center; }
    .menu-link:hover { color: white; background: rgba(255,255,255,0.05); }
    .menu-link.active {
        color: white; background: rgba(67, 97, 238, 0.15);
        border-left: 4px solid var(--primary);
    }
    
    .submenu-items { list-style: none; padding-left: 58px; background: rgba(0,0,0,0.1); display: none; }
    .submenu.active .submenu-items { display: block; }
    .submenu-items a { display: block; padding: 10px 0; color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; }
    .submenu-items a.active { color: var(--primary); font-weight: 700; }

    .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
    .logout-btn {
        display: flex; align-items: center; gap: 12px;
        padding: 12px; border-radius: 12px;
        background: rgba(239, 35, 60, 0.1); color: var(--danger);
        text-decoration: none; font-weight: 700; font-size: 0.9rem;
    }
    .logout-btn:hover { background: var(--danger); color: white; }

    /* Fix Sidebar Layout with Header */
    @media (max-width: 768px) {
        .ts-sidebar { transform: translateX(-100%); }
        .ts-sidebar.mobile-open { transform: translateX(0); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
            const icon = this.querySelector('i');
            icon.className = sidebar.classList.contains('collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
        });
    }

    document.querySelectorAll('.submenu > .menu-link').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentElement.classList.toggle('active');
            const icon = this.querySelector('.dropdown-icon');
            icon.style.transform = this.parentElement.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
        });
    });
});
</script>


