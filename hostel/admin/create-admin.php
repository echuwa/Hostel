<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Make sure admin_logs.php exists before including it
$admin_logs_path = 'includes/admin_logs.php';
if (!file_exists($admin_logs_path)) {
    die("Critical system file missing: admin_logs.php");
}
require_once($admin_logs_path);

// Only superadmin can access this page
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$formData = [];
$permissions = [
    'manage_students' => true,
    'manage_rooms' => true,
    'manage_complaints' => true,
    'view_reports' => true
];

// Check for success message from redirect
if (isset($_SESSION['admin_created'])) {
    $success_data = $_SESSION['admin_created'];
    $success = "Admin account for <strong>" . htmlspecialchars($success_data['username']) . "</strong> created successfully!";
    unset($_SESSION['admin_created']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create-admin'])) {
    // Sanitize and validate input
    $formData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => trim($_POST['password'] ?? ''),
        'confirm_password' => trim($_POST['confirm_password'] ?? '')
    ];
    
    // Update permissions from form
    $permissions = [
        'manage_students' => isset($_POST['manage_students']),
        'manage_rooms' => isset($_POST['manage_rooms']),
        'manage_complaints' => isset($_POST['manage_complaints']),
        'view_reports' => isset($_POST['view_reports'])
    ];

    // Basic validation
    if (empty($formData['username']) || empty($formData['email']) || 
        empty($formData['password']) || empty($formData['confirm_password'])) {
        $error = "All fields are required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $error = "Passwords do not match";
    } elseif (strlen($formData['password']) < 8) {
        $error = "Password must be at least 8 characters";
    } else {
        // Check if username/email exists
        $stmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        if (!$stmt) {
            $error = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("ss", $formData['username'], $formData['email']);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Username or email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($formData['password'], PASSWORD_BCRYPT);
                
                // Insert admin
                $stmt = $mysqli->prepare("INSERT INTO admins (username, email, password, is_superadmin, permissions, status, reg_date) VALUES (?, ?, ?, 0, ?, 'active', NOW())");
                if (!$stmt) {
                    $error = "Database error: " . $mysqli->error;
                } else {
                    $permissionsJson = json_encode($permissions);
                    $stmt->bind_param("ssss", $formData['username'], $formData['email'], $hashed_password, $permissionsJson);
                    
                    if ($stmt->execute()) {
                        $new_admin_id = $stmt->insert_id;
                        
                        // Log this action
                        if (function_exists('logAdminAction')) {
                            logAdminAction(
                                $mysqli, 
                                $_SESSION['id'], 
                                'create-admin', 
                                "Created new admin account: {$formData['username']}",
                                $new_admin_id,
                                'admins'
                            );
                        }
                        
                        // Store success message in session and redirect
                        $_SESSION['admin_created'] = [
                            'username' => $formData['username'],
                            'email' => $formData['email'],
                            'time' => date('M j, Y g:i A')
                        ];
                        
                        header("Location: create-admin.php");
                        exit();
                    } else {
                        $error = "Error creating admin account: " . $stmt->error;
                    }
                }
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#4361ee">
    <title>Create Admin | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .form-card {
            background: #fff;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .permission-item {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            cursor: pointer;
            height: 100%;
        }
        .permission-item:hover {
            border-color: #4361ee;
            background: #eff6ff;
        }
        .permission-item.active {
            border-color: #4361ee;
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.1);
        }
        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .form-control:focus {
            background: #fff;
            border-color: #4361ee;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="content-wrapper">
                
                <!-- Header -->
                <div class="content-header mb-4">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-user-plus"></i>
                            Create Admin
                        </h1>
                        <p class="text-muted small">Register a new administrator and assign permissions</p>
                    </div>
                </div>

                <div class="row g-4 justify-content-center">
                    <div class="col-lg-10">
                        <div class="form-card">
                            <?php if ($error): ?>
                                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><?php echo $success; ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <h5 class="fw-bold mb-4">Account Credentials</h5>
                                <div class="row g-3 mb-5">
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required placeholder="e.g. admin_john">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required placeholder="john@example.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required placeholder="Min 8 characters">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
                                    </div>
                                </div>

                                <h5 class="fw-bold mb-4">Access Permissions</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="permission-item <?php echo ($permissions['manage_students'] ?? true) ? 'active' : ''; ?>" onclick="togglePerm(this, 'manage_students')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manage_students" name="manage_students" <?php echo ($permissions['manage_students'] ?? true) ? 'checked' : ''; ?> style="display:none">
                                                <label class="form-check-label fw-bold d-block" for="manage_students">
                                                    <i class="fas fa-user-graduate me-2 text-primary"></i> Manage Students
                                                </label>
                                                <small class="text-muted">Register, edit and manage student information</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="permission-item <?php echo ($permissions['manage_rooms'] ?? true) ? 'active' : ''; ?>" onclick="togglePerm(this, 'manage_rooms')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manage_rooms" name="manage_rooms" <?php echo ($permissions['manage_rooms'] ?? true) ? 'checked' : ''; ?> style="display:none">
                                                <label class="form-check-label fw-bold d-block" for="manage_rooms">
                                                    <i class="fas fa-bed me-2 text-primary"></i> Manage Rooms
                                                </label>
                                                <small class="text-muted">Allocate rooms and handle room settings</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="permission-item <?php echo ($permissions['manage_complaints'] ?? true) ? 'active' : ''; ?>" onclick="togglePerm(this, 'manage_complaints')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manage_complaints" name="manage_complaints" <?php echo ($permissions['manage_complaints'] ?? true) ? 'checked' : ''; ?> style="display:none">
                                                <label class="form-check-label fw-bold d-block" for="manage_complaints">
                                                    <i class="fas fa-headset me-2 text-primary"></i> Manage Complaints
                                                </label>
                                                <small class="text-muted">Respond to student issues and feedback</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="permission-item <?php echo ($permissions['view_reports'] ?? true) ? 'active' : ''; ?>" onclick="togglePerm(this, 'view_reports')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="view_reports" name="view_reports" <?php echo ($permissions['view_reports'] ?? true) ? 'checked' : ''; ?> style="display:none">
                                                <label class="form-check-label fw-bold d-block" for="view_reports">
                                                    <i class="fas fa-chart-line me-2 text-primary"></i> View Reports
                                                </label>
                                                <small class="text-muted">Access analytics and system reports</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-3 border-top d-flex gap-3">
                                    <button type="submit" name="create-admin" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-sm">
                                        Create Administrator
                                    </button>
                                    <a href="manage-admins.php" class="btn btn-outline-secondary rounded-pill px-4 py-3 fw-bold">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePerm(el, id) {
            const cb = document.getElementById(id);
            cb.checked = !cb.checked;
            if(cb.checked) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        }
    </script>
</body>
</html>