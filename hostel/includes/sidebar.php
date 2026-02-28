<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$dn = $_SESSION['name'] ?? ($_SESSION['username'] ?? 'Student');
?>
<!-- Include Global Assets -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-area" style="display: flex; align-items: center; gap: 12px; overflow: hidden;">
            <div class="logo-icon" style="flex-shrink: 0; width: 38px; height: 38px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                <i class="fas fa-hotel"></i>
            </div>
            <span class="logo-text" style="font-weight: 800; font-size: 1.25rem; letter-spacing: -0.5px; color: #fff; white-space: nowrap;">Hostel<span style="color: var(--primary);">MS</span></span>
        </div>
        <div class="toggle-btn" id="toggleSidebar" style="color: rgba(255,255,255,0.5); cursor: pointer; transition: 0.3s; padding: 5px;">
            <i class="fas fa-chevron-left" id="toggleIcon"></i>
        </div>
    </div>

    <!-- Student Profile Mini -->
    <div class="sidebar-profile" style="padding: 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div style="display: flex; align-items: center; gap: 12px; transition: 0.3s; overflow: hidden;">
            <div style="flex-shrink: 0; width: 44px; height: 44px; background: linear-gradient(135deg, #4361ee, #7b2ff7); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <?php echo strtoupper(substr($dn, 0, 1)); ?>
            </div>
            <div class="profile-text" style="overflow: hidden;">
                <div style="font-weight: 700; font-size: 0.95rem; color: #fff; line-height: 1.2; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?php echo htmlspecialchars($dn); ?></div>
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); font-weight: 600; text-transform: uppercase; margin-top: 4px; white-space: nowrap;">Resident Student</div>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-menu" style="flex: 1; overflow-y: auto; padding: 15px 12px;">
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 10px 16px;">Core</li>
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Portal Home</span>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Accommodation</li>
        <li>
            <a href="book-hostel.php" class="<?php echo $current_page == 'book-hostel.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Book a Room</span>
            </a>
        </li>
        <li>
            <a href="room-details.php" class="<?php echo $current_page == 'room-details.php' ? 'active' : ''; ?>">
                <i class="fas fa-door-closed"></i>
                <span>Room View</span>
            </a>
        </li>
        <li>
            <a href="pay-fees.php" class="<?php echo $current_page == 'pay-fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i>
                <span>Settlements</span>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Support Center</li>
        <li class="has-submenu <?php echo in_array($current_page, ['register-complaint.php', 'my-complaints.php', 'complaint-details.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-flag"></i>
                <span>Complaints</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="display: <?php echo in_array($current_page, ['register-complaint.php', 'my-complaints.php', 'complaint-details.php']) ? 'block' : 'none'; ?>;">
                <li><a href="register-complaint.php" class="<?php echo $current_page == 'register-complaint.php' ? 'active-link' : ''; ?>">New Request</a></li>
                <li><a href="my-complaints.php" class="<?php echo $current_page == 'my-complaints.php' ? 'active-link' : ''; ?>">My History</a></li>
            </ul>
        </li>
        <li>
            <a href="feedback.php" class="<?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-dots"></i>
                <span>Give Feedback</span>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Preferences</li>
        <li>
            <a href="my-profile.php" class="<?php echo $current_page == 'my-profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-gear"></i>
                <span>Profile Settings</span>
            </a>
        </li>
        <li>
            <a href="access-log.php" class="<?php echo $current_page == 'access-log.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Access Logs</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer" style="padding: 24px; border-top: 1px solid rgba(255,255,255,0.05);">
        <a href="logout.php" style="display: flex; align-items: center; gap: 14px; color: #ff6b6b; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: 0.2s;">
            <i class="fas fa-power-off"></i>
            <span>Secure Logout</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.content-wrapper') || document.querySelector('.ts-main-content');
    const toggleBtn = document.getElementById('toggleSidebar');
    const toggleIcon = document.getElementById('toggleIcon');

    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if(mainContent) mainContent.classList.toggle('expanded');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.className = 'fas fa-chevron-right';
            } else {
                toggleIcon.className = 'fas fa-chevron-left';
            }
        });
    }

    document.querySelectorAll('.has-submenu > a').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const submenu = parent.querySelector('.submenu');
            const chevron = this.querySelector('.fa-chevron-down');
            
            if(submenu && (submenu.style.display === 'block' || parent.classList.contains('active'))) {
                submenu.style.display = 'none';
                parent.classList.remove('active');
                if(chevron) chevron.style.transform = 'rotate(0deg)';
            } else if(submenu) {
                document.querySelectorAll('.has-submenu').forEach(other => {
                    const otherSub = other.querySelector('.submenu');
                    const otherChev = other.querySelector('.fa-chevron-down');
                    if(otherSub) otherSub.style.display = 'none';
                    other.classList.remove('active');
                    if(otherChev) otherChev.style.transform = 'rotate(0deg)';
                });
                
                submenu.style.display = 'block';
                parent.classList.add('active');
                if(chevron) chevron.style.transform = 'rotate(180deg)';
            }
        });
    });
});
</script>
