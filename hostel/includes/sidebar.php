<nav class="ts-sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <i class="fas fa-home logo-icon"></i>
            <span class="logo-text">HostelMS</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <ul class="ts-sidebar-menu">
        <li class="ts-label">Navigation</li>
        
        <?php if(isset($_SESSION['id'])): ?>
            <!-- Dashboard -->
            <li>
                <a href="dashboard.php" class="menu-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            
            <!-- Hostel Booking -->
            <li>
                <a href="book-hostel.php" class="menu-link">
                    <i class="fas fa-bed"></i>
                    <span class="link-text">Book Hostel</span>
                </a>
            </li>
            
            <!-- Room Details -->
            <li>
                <a href="room-details.php" class="menu-link">
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
                        <a href="register-complaint.php">
                            <i class="fas fa-plus-circle"></i>
                            <span>Register Complaint</span>
                        </a>
                    </li>
                    <li>
                        <a href="my-complaints.php">
                            <i class="fas fa-list"></i>
                            <span>My Complaints</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Feedback -->
            <li>
                <a href="feedback.php" class="menu-link">
                    <i class="fas fa-comment-alt"></i>
                    <span class="link-text">Feedback</span>
                </a>
            </li>
            
            <!-- Profile Section -->
            <li class="submenu">
                <a href="#" class="menu-link">
                    <i class="fas fa-user-circle"></i>
                    <span class="link-text">My Account</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu-items">
                    <li>
                        <a href="my-profile.php">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="change-password.php">
                            <i class="fas fa-key"></i>
                            <span>Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="access-log.php">
                            <i class="fas fa-history"></i>
                            <span>Access Log</span>
                        </a>
                    </li>
                </ul>
            </li>
            
        <?php else: ?>
            <!-- Guest Links -->
            <li>
                <a href="index.php" class="menu-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span class="link-text">User Login</span>
                </a>
            </li>
            <li>
                <a href="admin/superadmin-login.php" class="menu-link">
                    <i class="fas fa-user-shield"></i>
                    <span class="link-text">Admin Login</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <?php if(isset($_SESSION['id'])): ?>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo $_SESSION['login']; ?></span>
                    <span class="user-role">Student</span>
                </div>
            <?php else: ?>
                <div class="guest-message">
                    <span>Welcome Guest</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
    /* Sidebar Styles */
    .ts-sidebar {
        width: 250px;
        height: 100vh;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: #fff;
        position: fixed;
        left: 0;
        top: 0;
        transition: all 0.3s;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-header {
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .logo-container {
        display: flex;
        align-items: center;
    }
    
    .logo-icon {
        font-size: 24px;
        margin-right: 10px;
        color: #3498db;
    }
    
    .logo-text {
        font-size: 18px;
        font-weight: 600;
    }
    
    .sidebar-toggle {
        background: none;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .sidebar-toggle:hover {
        color: #3498db;
    }
    
    .ts-sidebar-menu {
        flex: 1;
        list-style: none;
        padding: 20px 0;
        margin: 0;
        overflow-y: auto;
    }
    
    .ts-label {
        padding: 10px 20px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
    }
    
    .menu-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s;
        position: relative;
    }
    
    .menu-link:hover, .menu-link.active {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    
    .menu-link i {
        width: 24px;
        text-align: center;
        margin-right: 15px;
        font-size: 16px;
    }
    
    .link-text {
        flex: 1;
    }
    
    .dropdown-icon {
        transition: transform 0.3s;
    }
    
    .submenu.active .dropdown-icon {
        transform: rotate(180deg);
    }
    
    .submenu-items {
        list-style: none;
        padding: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .submenu.active .submenu-items {
        max-height: 500px;
    }
    
    .submenu-items li a {
        display: flex;
        align-items: center;
        padding: 10px 20px 10px 50px;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .submenu-items li a:hover {
        background: rgba(255,255,255,0.05);
        color: #fff;
    }
    
    .submenu-items li a i {
        margin-right: 10px;
        font-size: 14px;
    }
    
    .sidebar-footer {
        padding: 15px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .user-info {
        display: flex;
        align-items: center;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    
    .user-details {
        display: flex;
        flex-direction: column;
    }
    
    .username {
        font-weight: 600;
        font-size: 14px;
    }
    
    .user-role {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
    }
    
    .guest-message {
        width: 100%;
        text-align: center;
        font-size: 14px;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        .ts-sidebar {
            transform: translateX(-100%);
        }
        
        .ts-sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<script>
    // Toggle submenus
    document.querySelectorAll('.submenu > a').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentElement.classList.toggle('active');
        });
    });
    
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.ts-sidebar').classList.toggle('active');
    });
</script><nav class="ts-sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <i class="fas fa-home logo-icon"></i>
            <span class="logo-text">HostelMS</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <ul class="ts-sidebar-menu">
        <li class="ts-label">Navigation</li>
        
        <?php if(isset($_SESSION['id'])): ?>
            <!-- Dashboard -->
            <li>
                <a href="dashboard.php" class="menu-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            
            <!-- Hostel Booking -->
            <li>
                <a href="book-hostel.php" class="menu-link">
                    <i class="fas fa-bed"></i>
                    <span class="link-text">Book Hostel</span>
                </a>
            </li>
            
            <!-- Room Details -->
            <li>
                <a href="room-details.php" class="menu-link">
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
                        <a href="register-complaint.php">
                            <i class="fas fa-plus-circle"></i>
                            <span>Register Complaint</span>
                        </a>
                    </li>
                    <li>
                        <a href="my-complaints.php">
                            <i class="fas fa-list"></i>
                            <span>My Complaints</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Feedback -->
            <li>
                <a href="feedback.php" class="menu-link">
                    <i class="fas fa-comment-alt"></i>
                    <span class="link-text">Feedback</span>
                </a>
            </li>
            
            <!-- Profile Section -->
            <li class="submenu">
                <a href="#" class="menu-link">
                    <i class="fas fa-user-circle"></i>
                    <span class="link-text">My Account</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu-items">
                    <li>
                        <a href="my-profile.php">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="change-password.php">
                            <i class="fas fa-key"></i>
                            <span>Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="access-log.php">
                            <i class="fas fa-history"></i>
                            <span>Access Log</span>
                        </a>
                    </li>
                </ul>
            </li>
            
        <?php else: ?>
            <!-- Guest Links -->
            <li>
                <a href="index.php" class="menu-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span class="link-text">User Login</span>
                </a>
            </li>
            <li>
                <a href="admin/superadmin-login.php" class="menu-link">
                    <i class="fas fa-user-shield"></i>
                    <span class="link-text">Admin Login</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <?php if(isset($_SESSION['id'])): ?>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo $_SESSION['login']; ?></span>
                    <span class="user-role">Student</span>
                </div>
            <?php else: ?>
                <div class="guest-message">
                    <span>Welcome Guest</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
    /* Sidebar Styles */
    .ts-sidebar {
        width: 250px;
        height: 100vh;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: #fff;
        position: fixed;
        left: 0;
        top: 0;
        transition: all 0.3s;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-header {
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .logo-container {
        display: flex;
        align-items: center;
    }
    
    .logo-icon {
        font-size: 24px;
        margin-right: 10px;
        color: #3498db;
    }
    
    .logo-text {
        font-size: 18px;
        font-weight: 600;
    }
    
    .sidebar-toggle {
        background: none;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .sidebar-toggle:hover {
        color: #3498db;
    }
    
    .ts-sidebar-menu {
        flex: 1;
        list-style: none;
        padding: 20px 0;
        margin: 0;
        overflow-y: auto;
    }
    
    .ts-label {
        padding: 10px 20px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
    }
    
    .menu-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s;
        position: relative;
    }
    
    .menu-link:hover, .menu-link.active {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    
    .menu-link i {
        width: 24px;
        text-align: center;
        margin-right: 15px;
        font-size: 16px;
    }
    
    .link-text {
        flex: 1;
    }
    
    .dropdown-icon {
        transition: transform 0.3s;
    }
    
    .submenu.active .dropdown-icon {
        transform: rotate(180deg);
    }
    
    .submenu-items {
        list-style: none;
        padding: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .submenu.active .submenu-items {
        max-height: 500px;
    }
    
    .submenu-items li a {
        display: flex;
        align-items: center;
        padding: 10px 20px 10px 50px;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .submenu-items li a:hover {
        background: rgba(255,255,255,0.05);
        color: #fff;
    }
    
    .submenu-items li a i {
        margin-right: 10px;
        font-size: 14px;
    }
    
    .sidebar-footer {
        padding: 15px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .user-info {
        display: flex;
        align-items: center;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    
    .user-details {
        display: flex;
        flex-direction: column;
    }
    
    .username {
        font-weight: 600;
        font-size: 14px;
    }
    
    .user-role {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
    }
    
    .guest-message {
        width: 100%;
        text-align: center;
        font-size: 14px;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        .ts-sidebar {
            transform: translateX(-100%);
        }
        
        .ts-sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<script>
    // Toggle submenus
    document.querySelectorAll('.submenu > a').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentElement.classList.toggle('active');
        });
    });
    
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.ts-sidebar').classList.toggle('active');
    });
</script>