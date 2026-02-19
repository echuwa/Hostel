<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Count pending students for badge
if(isset($mysqli)) {
    $stmt = $mysqli->prepare("SELECT count(*) FROM userregistration WHERE status='Pending'");
    if($stmt) {
        $stmt->execute();
        $stmt->bind_result($pending_sidebar_count);
        $stmt->fetch();
        $stmt->close();
    }
}
$pending_sidebar_count = $pending_sidebar_count ?? 0;
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-area">
            <div class="logo-icon">
                <i class="fas fa-hotel"></i>
            </div>
            <span class="logo-text">HostelMS</span>
        </div>
        <div class="toggle-btn" id="toggleSidebar">
            <i class="fas fa-chevron-left"></i>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['add-courses.php', 'manage-courses.php']) ? 'active' : ''; ?>">
            <a href="#">
                <i class="fas fa-book-open"></i>
                <span>Courses</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu">
                <li><a href="add-courses.php">Add Courses</a></li>
                <li><a href="manage-courses.php">Manage Courses</a></li>
            </ul>
        </li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['create-room.php', 'manage-rooms.php']) ? 'active' : ''; ?>">
            <a href="#">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu">
                <li><a href="create-room.php">Add Room</a></li>
                <li><a href="manage-rooms.php">Manage Rooms</a></li>
            </ul>
        </li>
        
        <li>
            <a href="registration.php" class="<?php echo $current_page == 'registration.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i>
                <span>Student Registration</span>
            </a>
        </li>
        
        <li>
            <a href="manage-students.php" class="<?php echo $current_page == 'manage-students.php' ? 'active' : ''; ?>" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <i class="fas fa-users"></i>
                    <span>Manage Students</span>
                </div>
                <?php if($pending_sidebar_count > 0): ?>
                    <span class="notification-badge" style="position:static;"><?php echo $pending_sidebar_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['new-complaints.php', 'inprocess-complaints.php', 'closed-complaints.php']) ? 'active' : ''; ?>">
            <a href="#">
                <i class="fas fa-exclamation-circle"></i>
                <span>Complaints</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu">
                <li><a href="new-complaints.php">New</a></li>
                <li><a href="inprocess-complaints.php">In Process</a></li>
                <li><a href="closed-complaints.php">Closed</a></li>
            </ul>
        </li>
        
        <li>
            <a href="feedbacks.php" class="<?php echo $current_page == 'feedbacks.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-alt"></i>
                <span>Feedback</span>
            </a>
        </li>
        
        <li>
            <a href="access-log.php" class="<?php echo $current_page == 'access-log.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Access Logs</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('toggleSidebar');

    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if(mainContent) mainContent.classList.toggle('expanded');
            
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
            } else {
                icon.className = 'fas fa-chevron-left';
            }
        });
    }

    // Submenu Toggle
    const menuItems = document.querySelectorAll('.has-submenu > a');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('active');
        });
    });
});
</script>
