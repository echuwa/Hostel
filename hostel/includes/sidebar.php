<nav class="ts-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-area">
            <div class="logo-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span class="logo-text">HostelMS</span>
        </div>
        <button class="toggle-btn" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <?php if(isset($_SESSION['id']) || isset($_SESSION['user_id'])): ?>
            <div class="user-avatar">
                <?php 
                $initial = isset($_SESSION['login']) ? strtoupper(substr($_SESSION['login'], 0, 1)) : 'S';
                echo $initial;
                ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['login'] ?? 'Student'; ?></span>
                <span class="user-role">
                    <?php 
                    if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
                        echo 'Super Admin';
                    } elseif(isset($_SESSION['id'])) {
                        echo 'Admin';
                    } else {
                        echo 'Student';
                    }
                    ?>
                </span>
            </div>
        <?php else: ?>
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <span class="user-name">Guest</span>
                <span class="user-role">Visitor</span>
            </div>
        <?php endif; ?>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-label">MAIN</li>
        
        <?php if(isset($_SESSION['id']) || isset($_SESSION['user_id'])): ?>
            <!-- Dashboard -->
            <li>
                <a href="dashboard.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            
            <!-- Book Hostel -->
            <li>
                <a href="book-hostel.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'book-hostel.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    <span class="link-text">Book Hostel</span>
                </a>
            </li>
            
            <!-- Room Details -->
            <li>
                <a href="room-details.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'room-details.php' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i>
                    <span class="link-text">Room Details</span>
                </a>
            </li>
            
            <!-- Complaints Section -->
            <li class="submenu">
                <a href="#" class="menu-link">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="link-text">Complaints</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu-items">
                    <li>
                        <a href="register-complaint.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'register-complaint.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i>
                            <span>Register Complaint</span>
                        </a>
                    </li>
                    <li>
                        <a href="my-complaints.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my-complaints.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span>My Complaints</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Feedback -->
            <li>
                <a href="feedback.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comment-alt"></i>
                    <span class="link-text">Feedback</span>
                </a>
            </li>
            
            <!-- My Account Section -->
            <li class="submenu">
                <a href="#" class="menu-link">
                    <i class="fas fa-user-circle"></i>
                    <span class="link-text">My Account</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu-items">
                    <li>
                        <a href="my-profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="change-password.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'change-password.php' ? 'active' : ''; ?>">
                            <i class="fas fa-key"></i>
                            <span>Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="access-log.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'access-log.php' ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i>
                            <span>Access Log</span>
                        </a>
                    </li>
                </ul>
            </li>
            
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
        <?php if(isset($_SESSION['id']) || isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="link-text">Logout</span>
            </a>
        <?php else: ?>
            <a href="index.php" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span class="link-text">Login</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<style>
    /* ============================================
         COLLAPSIBLE SIDEBAR - MODERN DESIGN
         ============================================ */
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 70px;
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --primary: #667eea;
        --secondary: #764ba2;
        --dark: #1e293b;
        --light: #f8fafc;
        --gray: #64748b;
        --sidebar-bg: linear-gradient(135deg, #1a1e2c 0%, #2d3a4a 100%);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Main Layout */
    body {
        margin-left: var(--sidebar-width);
        transition: var(--transition);
        background-color: #f1f5f9;
    }
    
    body.sidebar-collapsed {
        margin-left: var(--sidebar-collapsed-width);
    }
    
    /* Sidebar */
    .ts-sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--sidebar-bg);
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        transition: var(--transition);
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    .ts-sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }
    
    /* Sidebar Header */
    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logo-area {
        display: flex;
        align-items: center;
        gap: 12px;
        overflow: hidden;
    }
    
    .logo-icon-wrapper {
        width: 40px;
        height: 40px;
        background: var(--primary-gradient);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .logo-text {
        font-size: 18px;
        font-weight: 700;
        color: white;
        white-space: nowrap;
        transition: var(--transition);
        opacity: 1;
    }
    
    .ts-sidebar.collapsed .logo-text {
        opacity: 0;
        width: 0;
        margin-left: -20px;
    }
    
    /* Toggle Button */
    .toggle-btn {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        transition: var(--transition);
        flex-shrink: 0;
    }
    
    .toggle-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }
    
    .toggle-btn i {
        font-size: 16px;
        transition: transform 0.3s ease;
    }
    
    .ts-sidebar.collapsed .toggle-btn i {
        transform: rotate(180deg);
    }
    
    /* User Section */
    .sidebar-user {
        padding: 20px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        overflow: hidden;
    }
    
    .user-avatar {
        width: 45px;
        height: 45px;
        background: var(--primary-gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 18px;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
        transition: var(--transition);
        white-space: nowrap;
    }
    
    .ts-sidebar.collapsed .user-info {
        opacity: 0;
        width: 0;
    }
    
    .user-name {
        font-weight: 600;
        font-size: 14px;
        color: white;
    }
    
    .user-role {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
    }
    
    /* Sidebar Menu */
    .sidebar-menu {
        flex: 1;
        list-style: none;
        padding: 20px 12px;
        margin: 0;
        overflow-y: auto;
    }
    
    .menu-label {
        padding: 10px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 600;
        transition: var(--transition);
    }
    
    .ts-sidebar.collapsed .menu-label {
        font-size: 0;
        padding: 5px 0;
    }
    
    .ts-sidebar.collapsed .menu-label::before {
        content: '';
        display: block;
        width: 100%;
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
    }
    
    /* Menu Links */
    .menu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 10px;
        transition: var(--transition);
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
    }
    
    .menu-link i {
        font-size: 18px;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .menu-link span {
        transition: var(--transition);
        font-weight: 500;
    }
    
    .ts-sidebar.collapsed .menu-link span,
    .ts-sidebar.collapsed .dropdown-icon {
        opacity: 0;
        width: 0;
    }
    
    .menu-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }
    
    .menu-link.active {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));
        color: white;
        border-left: 3px solid #667eea;
    }
    
    /* Submenu */
    .submenu {
        position: relative;
    }
    
    .dropdown-icon {
        margin-left: auto;
        font-size: 12px;
        transition: transform 0.3s;
    }
    
    .submenu.active .dropdown-icon {
        transform: rotate(180deg);
    }
    
    .submenu-items {
        list-style: none;
        padding-left: 36px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .submenu.active .submenu-items {
        max-height: 300px;
    }
    
    .ts-sidebar.collapsed .submenu-items {
        display: none;
    }
    
    .submenu-items li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        border-radius: 8px;
        transition: var(--transition);
        font-size: 14px;
    }
    
    .submenu-items li a i {
        font-size: 14px;
        width: 20px;
        text-align: center;
    }
    
    .submenu-items li a:hover {
        background: rgba(255, 255, 255, 0.05);
        color: white;
        transform: translateX(5px);
    }
    
    .submenu-items li a.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    /* Sidebar Footer */
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logout-btn, .login-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 10px;
        transition: var(--transition);
        background: rgba(255, 255, 255, 0.05);
        white-space: nowrap;
        overflow: hidden;
    }
    
    .logout-btn i, .login-btn i {
        font-size: 18px;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
    }
    
    .logout-btn:hover {
        background: rgba(220, 53, 69, 0.2);
        color: #ff6b6b;
    }
    
    .login-btn:hover {
        background: rgba(40, 167, 69, 0.2);
        color: #06d6a0;
    }
    
    .ts-sidebar.collapsed .logout-btn span,
    .ts-sidebar.collapsed .login-btn span {
        opacity: 0;
        width: 0;
    }
    
    /* Scrollbar */
    .ts-sidebar::-webkit-scrollbar {
        width: 4px;
    }
    
    .ts-sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .ts-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 4px;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        body {
            margin-left: 0;
        }
        
        .ts-sidebar {
            transform: translateX(-100%);
        }
        
        .ts-sidebar.mobile-open {
            transform: translateX(0);
        }
        
        body.sidebar-collapsed {
            margin-left: 0;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const body = document.body;
        
        // Toggle sidebar collapse/expand
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                
                // Update body margin
                if (sidebar.classList.contains('collapsed')) {
                    body.classList.add('sidebar-collapsed');
                } else {
                    body.classList.remove('sidebar-collapsed');
                }
                
                // Change icon direction
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('collapsed')) {
                    icon.className = 'fas fa-chevron-right';
                } else {
                    icon.className = 'fas fa-chevron-left';
                }
            });
        }
        
        // Mobile menu toggle
        if (window.innerWidth <= 768) {
            // Add mobile toggle button (you can add this to header)
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isMobile = window.innerWidth <= 768;
            if (isMobile && !sidebar.contains(event.target) && !event.target.closest('.sidebar-toggle')) {
                sidebar.classList.remove('mobile-open');
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed');
                body.classList.remove('sidebar-collapsed');
            }
        });
        
        // Toggle submenus
        document.querySelectorAll('.submenu > .menu-link').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                if (!sidebar.classList.contains('collapsed')) {
                    this.parentElement.classList.toggle('active');
                }
            });
        });
        
        // Auto-open submenu if child is active
        document.querySelectorAll('.submenu-items a.active').forEach(activeLink => {
            const submenu = activeLink.closest('.submenu');
            if (submenu) {
                submenu.classList.add('active');
            }
        });
    });
</script>