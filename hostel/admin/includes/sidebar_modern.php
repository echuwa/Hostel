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

$dn = $_SESSION['username'] ?? 'Admin';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-area" style="display: flex; align-items: center; gap: 12px;">
            <div class="logo-icon" style="width: 38px; height: 38px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                <i class="fas fa-hotel"></i>
            </div>
            <span class="logo-text" style="font-weight: 800; font-size: 1.25rem; letter-spacing: -0.5px; color: #fff;">Hostel<span style="color: var(--primary);">MS</span></span>
        </div>
        <div class="toggle-btn" id="toggleSidebar" style="color: rgba(255,255,255,0.5); cursor: pointer; transition: 0.3s;">
            <i class="fas fa-chevron-left"></i>
        </div>
    </div>

    <!-- Admin Profile Mini -->
    <div class="sidebar-profile" style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #4361ee, #7b2ff7); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <?php echo strtoupper(substr($dn, 0, 1)); ?>
            </div>
            <div class="profile-text">
                <div style="font-weight: 700; font-size: 0.95rem; color: #fff; line-height: 1.2;"><?php echo htmlspecialchars($dn); ?></div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); font-weight: 600; text-transform: uppercase; margin-top: 4px;">
                    <?php echo isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] ? 'Super Admin' : 'Administrator'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-header" style="font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1px; padding: 0 16px 12px;">Dashboard</li>
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-grid-2"></i>
                <span>Stat Overview</span>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1px; padding: 24px 16px 12px;">Management</li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['add-courses.php', 'manage-courses.php', 'edit-course.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-book-open"></i>
                <span>Academic Courses</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="list-style: none; padding: 5px 0 5px 44px; display: <?php echo in_array($current_page, ['add-courses.php', 'manage-courses.php', 'edit-course.php']) ? 'block' : 'none'; ?>;">
                <li><a href="add-courses.php" style="padding: 8px 0; font-size: 0.85rem; background: transparent; box-shadow: none;" class="<?php echo $current_page == 'add-courses.php' ? 'text-white fw-bold' : ''; ?>">Add New</a></li>
                <li><a href="manage-courses.php" style="padding: 8px 0; font-size: 0.85rem; background: transparent; box-shadow: none;" class="<?php echo $current_page == 'manage-courses.php' ? 'text-white fw-bold' : ''; ?>">Directory</a></li>
            </ul>
        </li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['create-room.php', 'manage-rooms.php', 'edit-room.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-door-open"></i>
                <span>Hostel Portfolio</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="list-style: none; padding: 5px 0 5px 44px; display: <?php echo in_array($current_page, ['create-room.php', 'manage-rooms.php', 'edit-room.php']) ? 'block' : 'none'; ?>;">
                <li><a href="create-room.php" style="padding: 8px 0; font-size: 0.85rem; background: transparent; box-shadow: none;" class="<?php echo $current_page == 'create-room.php' ? 'text-white fw-bold' : ''; ?>">Generate Block</a></li>
                <li><a href="manage-rooms.php" style="padding: 8px 0; font-size: 0.85rem; background: transparent; box-shadow: none;" class="<?php echo $current_page == 'manage-rooms.php' ? 'text-white fw-bold' : ''; ?>">Room List</a></li>
            </ul>
        </li>
        
        <li>
            <a href="manage-students.php" class="<?php echo $current_page == 'manage-students.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Student Center</span>
                <?php if($pending_sidebar_count > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.65rem; padding: 4px 8px;"><?php echo $pending_sidebar_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1px; padding: 24px 16px 12px;">Support & Logs</li>

        <li class="has-submenu <?php echo in_array($current_page, ['new-complaints.php', 'inprocess-complaints.php', 'closed-complaints.php', 'all-complaints.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-headset"></i>
                <span>Complaints</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="list-style: none; padding: 5px 0 5px 44px; display: <?php echo in_array($current_page, ['new-complaints.php', 'inprocess-complaints.php', 'closed-complaints.php', 'all-complaints.php']) ? 'block' : 'none'; ?>;">
                <li><a href="new-complaints.php" style="padding: 8px 0; font-size: 0.85rem; background: transparent; box-shadow: none;" class="<?php echo $current_page == 'new-complaints.php' ? 'text-white fw-bold' : ''; ?>">New Cases</a></li>
                <li><a href="all-complaints.php" style="padding: 8px 0; font-size: 0.85rem; background: transparent; box-shadow: none;" class="<?php echo $current_page == 'all-complaints.php' ? 'text-white fw-bold' : ''; ?>">Master View</a></li>
            </ul>
        </li>
        
        <li>
            <a href="feedbacks.php" class="<?php echo $current_page == 'feedbacks.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-alt-smile"></i>
                <span>Feedbacks</span>
            </a>
        </li>
        
        <li>
            <a href="access-log.php" class="<?php echo $current_page == 'access-log.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Audit Logs</span>
            </a>
        </li>

        <?php if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1): ?>
        <li class="menu-header" style="font-size: 0.7rem; font-weight: 700; color: #ffb703; text-transform: uppercase; letter-spacing: 1px; padding: 24px 16px 12px;">Admin Authority</li>
        <li>
            <a href="superadmin-dashboard.php" class="<?php echo $current_page == 'superadmin-dashboard.php' ? 'active' : ''; ?>" style="color: #ffb703 !important; background: rgba(255,183,3,0.05);">
                <i class="fas fa-shield-crown"></i>
                <span>Super Console</span>
            </a>
        </li>
        <li>
            <a href="superadmin-dashboard.php#debtor-section" class="" style="color: #4361ee !important; background: rgba(67, 97, 238, 0.05);">
                <i class="fas fa-user-shield"></i>
                <span>Block Debtors</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer" style="padding: 24px; border-top: 1px solid rgba(255,255,255,0.05);">
        <button type="button" class="btn btn-link p-0 text-decoration-none mb-3 d-flex align-items-center gap-3 w-100" style="color: rgba(255,255,255,0.6);" data-bs-toggle="modal" data-bs-target="#supportModal">
            <i class="fas fa-headset"></i>
            <span class="small fw-700">Contact Support</span>
        </button>
        <a href="../logout.php" style="display: flex; align-items: center; gap: 14px; color: #ff6b6b; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: 0.2s;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Secure Logout</span>
        </a>
    </div>
</div>

<!-- Support Modal (Global for Admin) -->
<div class="modal fade" id="supportModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #fff;">
            <div class="modal-header border-0 bg-primary text-white p-4">
                <h5 class="modal-title fw-800"><i class="fas fa-headset me-2"></i>Technical Command</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-4">
                     <div class="bg-primary bg-opacity-10 text-primary mx-auto rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-cog fa-2x"></i>
                     </div>
                     <h4 class="fw-800 mb-1 text-dark">Emmanuel Chuwa</h4>
                     <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-700">Engineer</span>
                </div>
                
                <div class="bg-light p-3 rounded-4 mb-3">
                    <div class="small fw-800 text-muted opacity-75 mb-2">HOTLINE / WHATSAPP</div>
                    <div class="d-flex flex-column gap-3">
                        <a href="tel:+255788020014" class="text-decoration-none text-dark fw-800 h5 mb-0">
                            <i class="fas fa-phone-alt me-2 text-primary"></i>+255 788 020 014
                        </a>
                        <a href="tel:+255748230014" class="text-decoration-none text-dark fw-800 h5 mb-0">
                            <i class="fas fa-phone-alt me-2 text-primary"></i>+255 748 230 014
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-info border-0 rounded-4 small mb-0 d-flex align-items-center gap-3">
                    <i class="fas fa-info-circle"></i>
                    <div class="text-start">For complex logic adjustments or database maintenance, use the encrypted lines above.</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-800 shadow-sm" data-bs-dismiss="modal">Close Terminal</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleBtn = document.getElementById('toggleSidebar');

    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if(mainContent) mainContent.classList.toggle('expanded');
            
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
                this.style.transform = 'rotate(180deg)';
            } else {
                icon.className = 'fas fa-chevron-left';
                this.style.transform = 'rotate(0deg)';
            }
        });
    }

    // Submenu Toggle
    const hasSubmenu = document.querySelectorAll('.has-submenu > a');
    hasSubmenu.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const submenu = parent.querySelector('.submenu');
            const chevron = this.querySelector('.fa-chevron-down');
            
            if(submenu.style.display === 'block') {
                submenu.style.display = 'none';
                parent.classList.remove('active');
                if(chevron) chevron.style.transform = 'rotate(0deg)';
            } else {
                // Close others
                document.querySelectorAll('.has-submenu').forEach(other => {
                    if(other !== parent) {
                        const otherSub = other.querySelector('.submenu');
                        const otherChev = other.querySelector('.fa-chevron-down');
                        if(otherSub) otherSub.style.display = 'none';
                        other.classList.remove('active');
                        if(otherChev) otherChev.style.transform = 'rotate(0deg)';
                    }
                });
                
                submenu.style.display = 'block';
                parent.classList.add('active');
                if(chevron) chevron.style.transform = 'rotate(180deg)';
            }
        });
    });
});
</script>

