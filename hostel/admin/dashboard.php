<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ============================================
// Include admin files
// ============================================
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Get counts for dashboard cards
$counts = [];
$queries = [
    'students' => "SELECT count(*) FROM registration",
    'rooms' => "SELECT count(*) FROM rooms",
    'courses' => "SELECT count(*) FROM courses",
    'all_complaints' => "SELECT count(*) FROM complaints",
    'new_complaints' => "SELECT count(*) FROM complaints WHERE complaintStatus IS NULL",
    'inprocess_complaints' => "SELECT count(*) FROM complaints WHERE complaintStatus='In Process'",
    'closed_complaints' => "SELECT count(*) FROM complaints WHERE complaintStatus='Closed'",
    'feedbacks' => "SELECT count(*) FROM feedback",
    'pending_students' => "SELECT count(*) FROM userregistration WHERE status='Pending'"
];

foreach ($queries as $key => $query) {
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $counts[$key] = $count;
    $stmt->close();
}

// Get monthly data for chart
$monthly_data = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$current_month = date('m');

for ($i = 0; $i < 6; $i++) {
    $month_num = $current_month - $i;
    $year = date('Y');
    if ($month_num <= 0) {
        $month_num += 12;
        $year = date('Y') - 1;
    }
    $month_name = $months[$month_num - 1];
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM complaints WHERE MONTH(registrationDate) = ? AND YEAR(registrationDate) = ?");
    $stmt->bind_param("ii", $month_num, $year);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $monthly_data[$month_name] = $count;
    $stmt->close();
}

// Get recent activities
$recent_activities = [];
$stmt = $mysqli->prepare("SELECT 
    'complaint' as type, 
    id, 
    complaintType as title,
    complaintDetails as message,
    registrationDate as date, 
    complaintStatus as status 
FROM complaints 
ORDER BY registrationDate DESC 
LIMIT 5");

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}
$stmt->close();

// Get username safely
$display_name = $_SESSION['username'] ?? $_SESSION['login'] ?? $_SESSION['name'] ?? $_SESSION['user'] ?? 'Admin';
$display_name = htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Hostel Management System Dashboard">
    <meta name="author" content="">
    <meta name="theme-color" content="#f5f6fa">
    
    <title>Dashboard | Hostel Management System</title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- AOS Animation CDN -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS from modern.css -->
    <link rel="stylesheet" href="css/modern.css">
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                
                <!-- Header -->
                <div class="content-header" data-aos="fade-down">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-chart-pie"></i>
                            Dashboard
                        </h1>
                    </div>
                    <div class="header-right">
                        <div class="date-filter">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                        
                        <div class="dropdown">
                            <div class="header-icon dropdown-toggle" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                                <i class="fas fa-bell"></i>
                                <?php if(($counts['new_complaints'] + $counts['pending_students']) > 0): ?>
                                <span class="notification-badge"><?php echo $counts['new_complaints'] + $counts['pending_students']; ?></span>
                                <?php endif; ?>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notifDropdown" style="width: 300px; border: none; border-radius: 12px; padding: 0;">
                                <li class="p-3 border-bottom" style="background: #f8f9ff; border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 fw-bold" style="color: #4361ee;"><i class="fas fa-bell me-2"></i>Notifications</h6>
                                </li>
                                <?php if (count($recent_activities) > 0): ?>
                                    <?php foreach ($recent_activities as $notif): ?>
                                        <li>
                                            <a class="dropdown-item py-3 border-bottom text-wrap" href="complaint-details.php?cid=<?php echo $notif['id']; ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light text-primary rounded-circle p-2 me-3">
                                                        <i class="fas fa-comment-alt"></i>
                                                    </div>
                                                    <div>
                                                        <span class="d-block fw-bold text-dark" style="font-size: 0.85rem; padding-bottom: 2px;"><?php echo htmlspecialchars($notif['title']); ?></span>
                                                        <span class="d-block text-secondary text-truncate" style="font-size: 0.8rem; max-width: 200px;"><?php echo htmlspecialchars($notif['message'] ?? 'No message provided'); ?></span>
                                                        <span class="d-block text-muted mt-1" style="font-size: 0.7rem;"><i class="far fa-clock me-1"></i><?php echo date('M d, Y', strtotime($notif['date'])); ?></span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="p-4 text-center text-muted">No new notifications</li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-center text-primary py-2 fw-bold" href="all-complaints.php" style="border-radius: 0 0 12px 12px;">View All</a></li>
                            </ul>
                        </div>
                        
                        <?php if(isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1): ?>
                        <a href="superadmin-dashboard.php" class="super-admin-btn">
                            <i class="fas fa-shield-alt"></i>
                            <span>Super Admin</span>
                        </a>
                        <?php endif; ?>
                        
                        <div class="dropdown">
                            <div class="profile-dropdown dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer; display: flex; align-items: center; gap: 10px; background: white; padding: 8px 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                                <div class="profile-image" style="width: 35px; height: 35px; background: linear-gradient(135deg, #4361ee, #7b2ff7); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 700;">
                                    <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                                </div>
                                <div class="profile-info d-none d-md-block">
                                    <span class="profile-name d-block fw-bold text-dark" style="font-size: 0.9rem;"><?php echo $display_name; ?></span>
                                    <span class="profile-role d-block text-muted" style="font-size: 0.75rem;">
                                        <?php echo isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] ? 'Super Admin' : 'Admin'; ?>
                                    </span>
                                </div>
                                <i class="fas fa-chevron-down ms-1" style="font-size: 10px; color: #a0aec0;"></i>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="profileDropdown" style="border: none; border-radius: 12px; margin-top: 10px; min-width: 200px;">
                                <li class="px-3 py-2 border-bottom mb-1">
                                    <span class="d-block fw-bold"><?php echo $display_name; ?></span>
                                    <span class="d-block text-muted small"><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@hostel.com'); ?></span>
                                </li>
                                <li><a class="dropdown-item py-2" href="admin-profile.php"><i class="fas fa-user-circle me-2 text-primary"></i>My Profile</a></li>
                                <li><a class="dropdown-item py-2" href="settings.php"><i class="fas fa-key me-2 text-warning"></i>Change Password</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Metric Cards Row -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px; margin-bottom: 30px;">
                    
                    <!-- Pending Approvals -->
                    <a href="manage-students.php" style="text-decoration:none; display:block; height:100%;">
                        <div class="stat-card card-pending" data-aos="fade-up" data-aos-delay="0" style="height:100%;">
                            <div class="stat-info">
                                <h3>Pending Approvals</h3>
                                <div class="stat-number"><?php echo $counts['pending_students']; ?></div>
                                <div class="stat-trend">
                                    <i class="fas fa-exclamation-circle"></i> Needs Action
                                </div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                    </a>

                    <!-- Total Students -->
                    <a href="manage-students.php" style="text-decoration:none; display:block; height:100%;">
                        <div class="stat-card card-students" data-aos="fade-up" data-aos-delay="100" style="height:100%;">
                            <div class="stat-info">
                                <h3>Total Students</h3>
                                <div class="stat-number"><?php echo $counts['students']; ?></div>
                                <div class="stat-trend" style="color: var(--success);">
                                    <i class="fas fa-arrow-up"></i> Registered
                                </div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </a>

                    <!-- Total Rooms -->
                    <a href="manage-rooms.php" style="text-decoration:none; display:block; height:100%;">
                        <div class="stat-card card-rooms" data-aos="fade-up" data-aos-delay="200" style="height:100%;">
                            <div class="stat-info">
                                <h3>Total Rooms</h3>
                                <div class="stat-number"><?php echo $counts['rooms']; ?></div>
                                <div class="stat-trend" style="color: var(--success);">
                                    <i class="fas fa-check-circle"></i> Available
                                </div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-door-open"></i>
                            </div>
                        </div>
                    </a>

                    <!-- Complaints -->
                    <div class="dropdown" style="height:100%;">
                        <div class="stat-card card-complaints dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-aos="fade-up" data-aos-delay="300" style="height:100%; cursor: pointer;">
                            <div class="stat-info">
                                <h3>Complaints <i class="fas fa-chevron-down ms-1" style="font-size: 10px; color: var(--text-muted);"></i></h3>
                                <div class="stat-number"><?php echo $counts['all_complaints']; ?></div>
                                <div class="stat-trend" style="display:flex; gap:12px; margin-top:5px;">
                                    <span style="color:#ef233c; font-size:12px; font-weight:700; display:flex; align-items:center; gap:4px;">
                                        <span style="width:6px; height:6px; background:#ef233c; border-radius:50%;"></span>
                                        <?php echo $counts['new_complaints']; ?> New
                                    </span>
                                    <span style="color:#ffb703; font-size:12px; font-weight:700; display:flex; align-items:center; gap:4px;">
                                        <span style="width:6px; height:6px; background:#ffb703; border-radius:50%;"></span>
                                        <?php echo $counts['inprocess_complaints']; ?> Process
                                    </span>
                                </div>
                            </div>
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-headset"></i>
                            </div>
                        </div>
                        <ul class="dropdown-menu shadow-sm" style="border: none; border-radius: 12px; min-width: 220px;">
                            <li><a class="dropdown-item py-2 fw-bold text-primary" href="all-complaints.php"><i class="fas fa-list me-2"></i> All Complaints</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2" href="new-complaints.php"><i class="fas fa-exclamation-circle me-2 text-danger"></i> New Complaints</a></li>
                            <li><a class="dropdown-item py-2" href="inprocess-complaints.php"><i class="fas fa-spinner me-2 text-warning"></i> In Process</a></li>
                            <li><a class="dropdown-item py-2" href="closed-complaints.php"><i class="fas fa-check-circle me-2 text-success"></i> Closed Complaints</a></li>
                        </ul>
                    </div>

                </div>

                <!-- Charts and Activity Row -->
                <div class="dashboard-content-row">
                    
                    <!-- Left: Trends Chart -->
                    <div class="chart-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="chart-header">
                            <h3 class="chart-title">Complaints Trends</h3>
                            <!-- <select class="form-select form-select-sm" style="width: auto;">
                                <option>Last 6 Months</option>
                            </select> -->
                        </div>
                        <div class="chart-container">
                            <canvas id="trendsChart"></canvas>
                        </div>
                    </div>

                    <!-- Right: Recent Activity -->
                    <div class="activities-card" data-aos="fade-up" data-aos-delay="500">
                        <div class="chart-header">
                            <h3 class="chart-title">Recent Activity</h3>
                            <a href="all-complaints.php" style="font-size: 13px; text-decoration: none; color: var(--primary); font-weight: 600;">
                                View All
                            </a>
                        </div>
                        
                        <div class="activities-list">
                            <?php if(empty($recent_activities)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x" style="color: var(--gray-light); margin-bottom: 15px;"></i>
                                    <p style="color: var(--text-muted);">No recent activities found.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                        <div class="activity-meta">
                                            <span><i class="far fa-clock me-1"></i> <?php echo date('M d, H:i', strtotime($activity['date'])); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge-status <?php echo strtolower($activity['status']); ?>">
                                        <?php echo !empty($activity['status']) ? ucfirst($activity['status']) : 'New'; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });


        // Sidebar Toggle Functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('toggleSidebar');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Change icon direction
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
            } else {
                icon.className = 'fas fa-chevron-left';
            }
        });

        // Mobile menu toggle
        function toggleMobileMenu() {
            sidebar.classList.toggle('mobile-open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isMobile = window.innerWidth <= 992;
            if (isMobile && !sidebar.contains(event.target) && !event.target.closest('.toggle-btn')) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }
        });

        $(document).ready(function() {
            // Complaints Chart
            const complaintsEl = document.getElementById('complaintsChart');
            if (complaintsEl) {
                const complaintsCtx = complaintsEl.getContext('2d');
                new Chart(complaintsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['New', 'In Process', 'Closed'],
                        datasets: [{
                            data: [
                                <?php echo $counts['new_complaints']; ?>,
                                <?php echo $counts['inprocess_complaints']; ?>,
                                <?php echo $counts['closed_complaints']; ?>
                            ],
                            backgroundColor: [
                                '#ef233c',
                                '#ffb703',
                                '#06d6a0'
                            ],
                            borderWidth: 0,
                            borderRadius: 10,
                            spacing: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    color: 'white',
                                    font: {
                                        size: 12,
                                        weight: '500',
                                        family: 'Plus Jakarta Sans'
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Trends Chart
            const trendsEl = document.getElementById('trendsChart');
            if (trendsEl) {
                const trendsCtx = trendsEl.getContext('2d');
                
                // Create gradient
                let gradient = trendsCtx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(67, 97, 238, 0.2)');
            gradient.addColorStop(1, 'rgba(67, 97, 238, 0)');

            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_reverse(array_keys($monthly_data))); ?>,
                    datasets: [{
                        label: 'Complaints',
                        data: <?php echo json_encode(array_reverse(array_values($monthly_data))); ?>,
                        borderColor: '#4361ee',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4361ee',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#2d3436',
                            padding: 12,
                            titleFont: { size: 13 },
                            bodyFont: { size: 13 },
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f0f0f0',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                color: '#636e72',
                                padding: 10,
                                font: { size: 11 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#636e72',
                                padding: 10,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
            }

            // Animate numbers
            function animateNumbers() {
                $('.stat-number').each(function() {
                    const $this = $(this);
                    const target = parseInt($this.text());
                    $({ count: 0 }).animate({ count: target }, {
                        duration: 1500,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.count));
                        },
                        complete: function() {
                            $this.text(this.count);
                        }
                    });
                });
            }
            animateNumbers();
        });
    </script>
</body>
</html>```