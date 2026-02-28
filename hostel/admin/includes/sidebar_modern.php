<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Global Metric Calculations
$pending_sidebar_count = 0;
if(isset($mysqli)) {
    // 1. Pending Students for Super Admin
    $stmt = $mysqli->prepare("SELECT count(*) FROM userregistration WHERE status='Pending'");
    if($stmt) {
        $stmt->execute();
        $stmt->bind_result($pending_sidebar_count);
        $stmt->fetch();
        $stmt->close();
    }
}

// Global Notification Logic
$notif_title = "";
$notif_text = "";
$notif_icon = "info";
$show_notif = false;

if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
    // Super Admin Notifications: Pending Reports
    $new_reports_query = $mysqli->query("SELECT COUNT(*) FROM debtor_reports WHERE status='pending' AND deleted_by_admin = 0");
    $new_reports = $new_reports_query ? $new_reports_query->fetch_row()[0] : 0;
    
    if ($new_reports > 0 && (!isset($_SESSION['last_notif_count_reports']) || $_SESSION['last_notif_count_reports'] != $new_reports)) {
        $notif_title = "Command Update";
        $notif_text = "You have $new_reports pending debtor reports requiring your directive.";
        $notif_icon = "warning";
        $show_notif = true;
        $_SESSION['last_notif_count_reports'] = $new_reports;
    }
} else {
    // Debtor Notifications: New Replies
    $unread_debtor_count = 0;
    if(isset($mysqli) && isset($_SESSION['id'])) {
        $stmt = $mysqli->prepare("SELECT count(*) FROM debtor_reports WHERE debtor_id=? AND debtor_read=0 AND admin_reply IS NOT NULL AND deleted_by_debtor=0");
        if($stmt) {
            $stmt->bind_param("i", $_SESSION['id']);
            $stmt->execute();
            $stmt->bind_result($unread_debtor_count);
            $stmt->fetch();
            $stmt->close();
        }
    }

    if ($unread_debtor_count > 0 && (!isset($_SESSION['last_notif_count_replies']) || $_SESSION['last_notif_count_replies'] != $unread_debtor_count)) {
        $notif_title = "Directive Received";
        $notif_text = "Super Admin has responded to your block reports. Check the feedback center.";
        $notif_icon = "success";
        $show_notif = true;
        $_SESSION['last_notif_count_replies'] = $unread_debtor_count;
    }
}

$dn = $_SESSION['username'] ?? 'Admin';
$role_label = 'Administrator';
if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
    $role_label = 'Super Admin';
} else if(isset($_SESSION['assigned_block']) && !empty($_SESSION['assigned_block'])) {
    $role_label = 'Debtor - ' . htmlspecialchars($_SESSION['assigned_block']);
}
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

    <!-- Search Tool (Universal Command) -->
    <div style="padding: 0 16px 15px;">
        <div id="openSearchBtn" style="background: rgba(255,255,255,0.05); border-radius: 12px; padding: 10px 15px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: 0.2s; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
            <i class="fas fa-search" style="font-size: 0.8rem; color: rgba(255,255,255,0.4); flex-shrink: 0;"></i>
            <span style="font-size: 0.8rem; color: rgba(255,255,255,0.4); font-weight: 600; white-space: nowrap;">Search...</span>
            <kbd style="margin-left: auto; font-size: 0.65rem; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; color: rgba(255,255,255,0.3); border: none;">⌘K</kbd>
        </div>
    </div>

    <!-- Admin Profile Mini -->
    <div class="sidebar-profile" style="padding: 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div style="display: flex; align-items: center; gap: 12px; transition: 0.3s; overflow: hidden;">
            <div style="flex-shrink: 0; width: 44px; height: 44px; background: linear-gradient(135deg, #4361ee, #7b2ff7); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <?php echo strtoupper(substr($dn, 0, 1)); ?>
            </div>
            <div class="profile-text" style="overflow: hidden;">
                <div style="font-weight: 700; font-size: 0.95rem; color: #fff; line-height: 1.2; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?php echo htmlspecialchars($dn); ?></div>
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); font-weight: 600; text-transform: uppercase; margin-top: 4px; white-space: nowrap;"><?php echo $role_label; ?></div>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-menu" style="flex: 1; overflow-y: auto; padding: 15px 12px;">
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 10px 16px;">Overview</li>
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Statistics</span>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Management</li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['add-courses.php', 'manage-courses.php', 'edit-course.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-graduation-cap"></i>
                <span>Academic Courses</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="display: <?php echo in_array($current_page, ['add-courses.php', 'manage-courses.php', 'edit-course.php']) ? 'block' : 'none'; ?>;">
                <li><a href="add-courses.php" class="<?php echo $current_page == 'add-courses.php' ? 'active-link' : ''; ?>">Add New</a></li>
                <li><a href="manage-courses.php" class="<?php echo $current_page == 'manage-courses.php' ? 'active-link' : ''; ?>">Directory</a></li>
            </ul>
        </li>
        
        <li class="has-submenu <?php echo in_array($current_page, ['create-room.php', 'manage-rooms.php', 'edit-room.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-bed"></i>
                <span>Hostel Portfolio</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="display: <?php echo in_array($current_page, ['create-room.php', 'manage-rooms.php', 'edit-room.php']) ? 'block' : 'none'; ?>;">
                <li><a href="create-room.php" class="<?php echo $current_page == 'create-room.php' ? 'active-link' : ''; ?>">Generate Block</a></li>
                <li><a href="manage-rooms.php" class="<?php echo $current_page == 'manage-rooms.php' ? 'active-link' : ''; ?>">Room List</a></li>
            </ul>
        </li>
        
        <li>
            <a href="manage-students.php" class="<?php echo $current_page == 'manage-students.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Student Registry</span>
                <?php if($pending_sidebar_count > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.65rem; padding: 4px 8px;"><?php echo $pending_sidebar_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Reports</li>

         <?php if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1): ?>
         <li>
            <a href="manage-reports.php" class="<?php echo $current_page == 'manage-reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-paste"></i>
                <span>Debtor Reports</span>
                <?php 
                $pending_reports_query = $mysqli->query("SELECT COUNT(*) FROM debtor_reports WHERE status='pending' AND deleted_by_admin = 0");
                $pending_reports = $pending_reports_query ? $pending_reports_query->fetch_row()[0] : 0;
                if($pending_reports > 0): ?>
                    <span class="badge rounded-pill bg-warning text-dark ms-auto" style="font-size: 0.65rem; padding: 4px 8px;"><?php echo $pending_reports; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php else: ?>
        <li>
            <a href="prepare-report.php" class="<?php echo $current_page == 'prepare-report.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-export"></i>
                <span>Submit Report</span>
                <?php if(isset($unread_debtor_count) && $unread_debtor_count > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto shadow-sm" style="font-size: 0.6rem; padding: 4px 6px; animation: pulse 2s infinite;"><i class="fas fa-circle me-1" style="font-size: 0.4rem;"></i>NEW</span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Support & Logs</li>

        <li class="has-submenu <?php echo in_array($current_page, ['new-complaints.php', 'inprocess-complaints.php', 'closed-complaints.php', 'all-complaints.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0)">
                <i class="fas fa-headset"></i>
                <span>Complaints</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; transition: 0.3s;"></i>
            </a>
            <ul class="submenu" style="display: <?php echo in_array($current_page, ['new-complaints.php', 'inprocess-complaints.php', 'closed-complaints.php', 'all-complaints.php']) ? 'block' : 'none'; ?>;">
                <li><a href="new-complaints.php" class="<?php echo $current_page == 'new-complaints.php' ? 'active-link' : ''; ?>">New Cases</a></li>
                <li><a href="all-complaints.php" class="<?php echo $current_page == 'all-complaints.php' ? 'active-link' : ''; ?>">All Complaints</a></li>
            </ul>
        </li>
        
        <li>
            <a href="feedbacks.php" class="<?php echo $current_page == 'feedbacks.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-dots"></i>
                <span>Feedbacks</span>
            </a>
        </li>
        
        <li>
            <a href="audit_logs.php" class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i>
                <span>Audit Trail</span>
            </a>
        </li>

         <?php if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1): ?>
        <li class="menu-header" style="font-size: 0.65rem; font-weight: 700; color: #ffb703; text-transform: uppercase; letter-spacing: 1.5px; padding: 20px 16px 10px;">Authority</li>
        <li>
            <a href="superadmin-dashboard.php" class="<?php echo $current_page == 'superadmin-dashboard.php' ? 'active' : ''; ?>" style="color: #ffb703 !important; background: rgba(255,183,3,0.05);">
                <i class="fas fa-crown"></i>
                <span>High Console</span>
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
            <i class="fas fa-question-circle"></i>
            <span class="small fw-700">Get Help</span>
        </button>
        <a href="../logout.php" style="display: flex; align-items: center; gap: 14px; color: #ff6b6b; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: 0.2s;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log Out</span>
        </a>
    </div>
</div>

<!-- Universal Search Modal -->
<div class="modal fade" id="universalSearchModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #fff;">
            <div class="modal-header p-4 border-0 pb-0">
                <div class="w-100 d-flex align-items-center gap-3 bg-light rounded-4 px-3 py-1">
                    <i class="fas fa-search text-muted"></i>
                    <input type="text" id="universalSearchInput" class="form-control border-0 bg-transparent shadow-none py-3" placeholder="Search students, rooms, or complaints..." autocomplete="off">
                </div>
            </div>
            <div class="modal-body p-4 pt-2">
                <div id="searchResults" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-search fa-2x mb-3 opacity-25"></i>
                        <p class="small fw-700 mb-0">Type to find records...</p>
                    </div>
                </div>
            </div>
        </div>
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

    // Submenu Toggle - Strengthened
    document.querySelectorAll('.has-submenu').forEach(parent => {
        const link = parent.querySelector(':scope > a');
        if (link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const submenu = parent.querySelector('.submenu');
                const chevron = this.querySelector('.fa-chevron-down');
                const isOpen = parent.classList.contains('active');
                
                // Close all other submenus first
                document.querySelectorAll('.has-submenu').forEach(other => {
                    if (other !== parent) {
                        other.classList.remove('active');
                        const otherSub = other.querySelector('.submenu');
                        const otherChev = other.querySelector('.fa-chevron-down');
                        if (otherSub) otherSub.style.display = 'none';
                        if (otherChev) otherChev.style.transform = 'rotate(0deg)';
                    }
                });

                if (isOpen) {
                    parent.classList.remove('active');
                    if (submenu) submenu.style.display = 'none';
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                } else {
                    parent.classList.add('active');
                    if (submenu) submenu.style.display = 'block';
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                }
            });
        }
    });

    // Universal Search Logic
    const searchModal = new bootstrap.Modal(document.getElementById('universalSearchModal'));
    const searchInput = document.getElementById('universalSearchInput');
    const searchResults = document.getElementById('searchResults');
    const openSearchBtn = document.getElementById('openSearchBtn');

    if(openSearchBtn) {
        openSearchBtn.addEventListener('click', () => searchModal.show());
    }

    // Hotkey Ctrl+K / Cmd+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchModal.show();
        }
    });

    const modalEl = document.getElementById('universalSearchModal');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', () => searchInput.focus());
    }

    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResults.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-search fa-2x mb-3 opacity-25"></i><p class="small fw-700">Type at least 2 characters...</p></div>';
                return;
            }

            searchResults.innerHTML = '<div class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

            searchTimeout = setTimeout(() => {
                fetch(`includes/search_api.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            searchResults.innerHTML = '<div class="text-center py-5 text-muted"><p class="small fw-700">No matches found.</p></div>';
                            return;
                        }

                        let html = '<div class="list-group list-group-flush">';
                        data.forEach(item => {
                            html += `
                                <a href="${item.url}" class="list-group-item list-group-item-action border-0 rounded-4 mb-2 p-3 d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                        <i class="fas ${item.icon}"></i>
                                    </div>
                                    <div style="flex: 1; overflow: hidden;">
                                        <div class="fw-800 text-dark small text-truncate">${item.title}</div>
                                        <div class="text-muted text-truncate" style="font-size: 0.7rem;">${item.type} • ${item.subtitle}</div>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-25 small"></i>
                                </a>
                            `;
                        });
                        html += '</div>';
                        searchResults.innerHTML = html;
                    })
                    .catch(err => {
                        searchResults.innerHTML = '<div class="text-center py-5 text-danger"><p class="small fw-700">Search error. Try again.</p></div>';
                    });
            }, 300);
        });
    }
});

// Professional Global Notifications Trigger
<?php if (isset($show_notif) && $show_notif): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: '<?php echo addslashes($notif_title); ?>',
        text: '<?php echo addslashes($notif_text); ?>',
        icon: '<?php echo $notif_icon; ?>',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 8000,
        timerProgressBar: true,
        showClass: { popup: 'animate__animated animate__fadeInRight' },
        hideClass: { popup: 'animate__animated animate__fadeOutRight' },
        background: '#fff',
        color: '#1e293b',
        iconColor: '<?php echo $notif_icon == "success" ? "#10b981" : ($notif_icon == "warning" ? "#f59e0b" : "#4361ee"); ?>',
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
            toast.addEventListener('click', () => {
                window.location.href = '<?php echo isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1 ? "manage-reports.php" : "prepare-report.php"; ?>';
            });
        }
    });
});
<?php endif; ?>
</script>
