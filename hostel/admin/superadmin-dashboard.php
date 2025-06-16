<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");

// Check if super admin is logged in
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: superadmin-login.php");
    exit();
}

require_once('includes/config.php');
require_once('includes/auth.php');

// Set timeout for inactivity (30 minutes)
$inactive = 1800;
if (isset($_SESSION['last_login']) && (time() - $_SESSION['last_login'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: superadmin-login.php?timeout=1");
    exit();
}
$_SESSION['last_login'] = time();

// Get admin list
$admins = [];
$stmt = $mysqli->prepare("SELECT id, username, email, reg_date, status FROM admins WHERE is_superadmin = 0 ORDER BY reg_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    $stmt->close();
}

// Handle messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3A0CA3;
            --secondary-color: #4361EE;
            --accent-color: #4CC9F0;
            --danger-color: #EF233C;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            background-color: #F8F9FA;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
        }
        .badge-active {
            background-color: var(--accent-color);
        }
        .badge-inactive {
            background-color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4 p-3">
                        <h4><i class="bi bi-shield-lock"></i> Super Admin</h4>
                        <hr>
                        <p class="mb-0 small">Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <ul class="nav flex-column px-3">
                        <li class="nav-item">
                            <a class="nav-link active" href="superadmin-dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create-admin.php">
                                <i class="bi bi-person-plus"></i> Create Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage-admins.php">
                                <i class="bi bi-people"></i> Manage Admins
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit_logs.php">
                                <i class="bi bi-journal-text"></i> Audit Log
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Overview</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                        <span class="text-muted small">Last login: <?php echo date('Y-m-d H:i:s', $_SESSION['last_login']); ?></span>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Total Admins</h5>
                                        <h2 class="mb-0"><?php echo count($admins); ?></h2>
                                    </div>
                                    <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Active Admins</h5>
                                        <h2 class="mb-0"><?php echo count(array_filter($admins, fn($a) => $a['status'] === 'active')); ?></h2>
                                    </div>
                                    <i class="bi bi-check-circle-fill" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Inactive Admins</h5>
                                        <h2 class="mb-0"><?php echo count(array_filter($admins, fn($a) => $a['status'] !== 'active')); ?></h2>
                                    </div>
                                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-table"></i> Admin Accounts</h5>
                        <a href="create-admin.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Add New Admin
                        </a>
                        <!-- <a href="superadmin-register.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Add New Super Admin
                        </a> -->
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo $admin['id']; ?></td>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($admin['reg_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $admin['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($admin['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit-admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="delete-admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this admin?')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>


<?php
function changeAdminPassword($adminId, $newPassword) {
    global $mysqli;
    
    // Get admin details first
    $admin = $mysqli->query("SELECT username FROM admins WHERE id = $adminId")->fetch_assoc();
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password
    $stmt = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $adminId);
    $success = $stmt->execute();
    
    if ($success) {
        // Log the activity (without logging the actual password)
        log_activity(
            'admin_password_change',
            "Changed password for admin {$admin['username']} (#$adminId)",
            null, // Don't include sensitive data
            'admins',
            $adminId
        );
    }
    
    return $success;
}
?>
</html>