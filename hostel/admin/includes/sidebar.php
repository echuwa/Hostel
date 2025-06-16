<nav class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-header">Menu</li>
        
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
                <li><a href="add-courses.php">Add Courses</a></li>
                <li><a href="manage-courses.php">Manage Courses</a></li>
            </ul>
        </li>
        
        <li class="has-submenu">
            <a href="#" class="sidebar-link">
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
            <a href="registration.php" class="sidebar-link">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
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
                <li><a href="new-complaints.php">New</a></li>
                <li><a href="inprocess-complaints.php">In Process</a></li>
                <li><a href="closed-complaints.php">Closed</a></li>
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
    </ul>
</nav>

<style>
/* Base Styles */
.sidebar {
    width: 200px;
    height: 100vh;
    background: #2c3e50;
    position: fixed;
    top: 0;
    left: 0;
    overflow-y: auto;
    z-index: 100;
}

.sidebar-header {
    padding: 15px;
    font-size: 13px;
    color: #bdc3c7;
    text-transform: uppercase;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

/* Menu Items */
.sidebar-link {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #ecf0f1;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.2s;
}

.sidebar-link:hover {
    background: rgba(255,255,255,0.1);
}

.sidebar-link i {
    width: 20px;
    text-align: center;
    margin-right: 10px;
    font-size: 14px;
}

.dropdown-icon {
    margin-left: auto;
    font-size: 12px;
    transition: transform 0.2s;
}

/* Submenu Styles */
.submenu {
    list-style: none;
    padding: 0;
    background: rgba(0,0,0,0.2);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s;
}

.submenu li a {
    display: block;
    padding: 8px 15px 8px 45px;
    color: #bdc3c7;
    text-decoration: none;
    font-size: 13px;
}

.submenu li a:hover {
    color: #fff;
    background: rgba(255,255,255,0.05);
}

/* Active States */
.has-submenu.active .dropdown-icon {
    transform: rotate(180deg);
}

.has-submenu.active .submenu {
    max-height: 300px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 50px;
    }
    
    .sidebar:hover {
        width: 200px;
    }
    
    .sidebar-header,
    .sidebar-link span,
    .dropdown-icon {
        display: none;
    }
    
    .sidebar:hover .sidebar-header,
    .sidebar:hover .sidebar-link span,
    .sidebar:hover .dropdown-icon {
        display: block;
    }
    
    .sidebar-link {
        justify-content: center;
    }
    
    .sidebar:hover .sidebar-link {
        justify-content: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.has-submenu');
    
    menuItems.forEach(item => {
        const link = item.querySelector('.sidebar-link');
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            item.classList.toggle('active');
            
            // Close other open submenus
            menuItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
        });
    });
});
</script>