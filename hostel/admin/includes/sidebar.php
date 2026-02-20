<nav class="sidebar ts-sidebar" id="adminSidebar">
    <div style="padding: 15px 0 5px; text-align:center; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div style="font-size:13px; color:#bdc3c7; text-transform:uppercase; letter-spacing:2px; padding: 5px 15px;">Admin Panel</div>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="sidebar-link">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="has-submenu">
            <a href="#" class="sidebar-link">
                <i class="fas fa-book-open"></i>
                <span>Courses</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu">
                <li><a href="add-courses.php"><i class="fas fa-plus-circle"></i> Add Courses</a></li>
                <li><a href="manage-courses.php"><i class="fas fa-list"></i> Manage Courses</a></li>
            </ul>
        </li>
        
        <li class="has-submenu">
            <a href="#" class="sidebar-link">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu">
                <li><a href="create-room.php"><i class="fas fa-plus-circle"></i> Add Room</a></li>
                <li><a href="manage-rooms.php"><i class="fas fa-list"></i> Manage Rooms</a></li>
            </ul>
        </li>
        
        <li>
            <a href="registration.php" class="sidebar-link">
                <i class="fas fa-user-graduate"></i>
                <span>Register Student</span>
            </a>
        </li>
        
        <li>
            <a href="manage-students.php" class="sidebar-link">
                <i class="fas fa-users"></i>
                <span>Manage Students</span>
            </a>
        </li>
        
        <li class="has-submenu">
            <a href="#" class="sidebar-link">
                <i class="fas fa-exclamation-circle"></i>
                <span>Complaints</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <ul class="submenu">
                <li><a href="new-complaints.php"><i class="fas fa-exclamation-triangle" style="color:#e74a3b;"></i> New</a></li>
                <li><a href="inprocess-complaints.php"><i class="fas fa-spinner" style="color:#f6c23e;"></i> In Process</a></li>
                <li><a href="closed-complaints.php"><i class="fas fa-check-circle" style="color:#1cc88a;"></i> Closed</a></li>
                <li><a href="all-complaints.php"><i class="fas fa-list"></i> All Complaints</a></li>
            </ul>
        </li>
        
        <li>
            <a href="feedbacks.php" class="sidebar-link">
                <i class="fas fa-comment-alt"></i>
                <span>Feedback</span>
            </a>
        </li>
        
        <li>
            <a href="access-log.php" class="sidebar-link">
                <i class="fas fa-clipboard-list"></i>
                <span>Access Logs</span>
            </a>
        </li>

        <li style="border-top: 1px solid rgba(255,255,255,0.1); margin-top:10px;">
            <a href="logout.php" class="sidebar-link" style="color:#ff6b6b;">
                <i class="fas fa-sign-out-alt" style="color:#ff6b6b;"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Mobile overlay backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<style>
/* Admin Sidebar */
.ts-sidebar, .sidebar {
    width: 220px;
    height: calc(100vh - 60px);
    background: linear-gradient(180deg, #2c3136 0%, #1e2328 100%);
    position: fixed;
    top: 60px;
    left: 0;
    overflow-y: auto;
    z-index: 1040;
    box-shadow: 2px 0 10px rgba(0,0,0,0.2);
    transition: transform 0.3s ease;
    padding-bottom: 20px;
}

.sidebar-menu {
    list-style: none;
    padding: 10px 0;
    margin: 0;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 11px 18px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 13.5px;
    transition: all 0.2s;
    gap: 10px;
}

.sidebar-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    text-decoration: none;
    padding-left: 22px;
}

.sidebar-link i:first-child {
    width: 20px;
    text-align: center;
    font-size: 15px;
    color: #37a6c4;
    flex-shrink: 0;
}

.sidebar-link span {
    flex: 1;
}

.dropdown-icon {
    margin-left: auto;
    font-size: 11px;
    transition: transform 0.25s;
    color: rgba(255,255,255,0.5) !important;
}

.submenu {
    list-style: none;
    padding: 0;
    background: rgba(0,0,0,0.25);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.submenu li a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px 9px 48px;
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s;
}

.submenu li a:hover {
    color: #fff;
    background: rgba(255,255,255,0.06);
    text-decoration: none;
    padding-left: 52px;
}

.submenu li a i {
    font-size: 12px;
    width: 16px;
}

.has-submenu.active .dropdown-icon {
    transform: rotate(180deg);
}

.has-submenu.active .submenu {
    max-height: 400px;
}

.sidebar-link.active {
    background: rgba(55, 166, 196, 0.2);
    color: #37a6c4;
    border-left: 3px solid #37a6c4;
}

.ts-sidebar::-webkit-scrollbar, .sidebar::-webkit-scrollbar { width: 4px; }
.ts-sidebar::-webkit-scrollbar-thumb, .sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
}

/* Content layout */
.ts-main-content {
    display: flex;
    padding-top: 60px;
}

.ts-main-content .content-wrapper {
    margin-left: 220px;
    flex: 1;
    padding: 20px;
    min-width: 0;
    transition: margin-left 0.3s ease;
}

/* Mobile */
@media (max-width: 768px) {
    .ts-sidebar, .sidebar {
        transform: translateX(-100%);
        width: 260px;
        z-index: 1045;
    }

    .ts-sidebar.mobile-open, .sidebar.mobile-open {
        transform: translateX(0);
    }

    .ts-main-content .content-wrapper {
        margin-left: 0 !important;
    }

    .sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1039;
        backdrop-filter: blur(2px);
    }

    .sidebar-backdrop.active {
        display: block;
    }

    .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 576px) {
    .ts-main-content .content-wrapper {
        padding: 12px 10px;
    }

    .page-title {
        font-size: 1.2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.has-submenu');
    
    menuItems.forEach(item => {
        const link = item.querySelector('.sidebar-link');
        if (link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                item.classList.toggle('active');
                menuItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('active')) {
                        otherItem.classList.remove('active');
                    }
                });
            });
        }
    });

    const mobileToggle = document.getElementById('adminMobileSidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (backdrop) backdrop.classList.remove('active');
        if (mobileToggle) {
            const icon = mobileToggle.querySelector('i');
            if (icon) icon.className = 'fas fa-bars';
        }
    }

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            if (backdrop) backdrop.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon) icon.className = sidebar.classList.contains('mobile-open') ? 'fas fa-times' : 'fas fa-bars';
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // Wrap tables in responsive divs
    document.querySelectorAll('table').forEach(function(table) {
        if (!table.closest('.table-responsive') && !table.closest('.table-responsive-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive-wrapper';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
});
</script>