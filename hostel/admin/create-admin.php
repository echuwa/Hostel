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
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: superadmin-login.php");
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
    $success = $_SESSION['admin_created'];
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account | Hostel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .permission-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .permission-card:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        
        .permission-card.active {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.2em;
        }
        
        .success-message {
            border-left: 4px solid #28a745;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="admin-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield fa-2x me-3"></i>
                            <div>
                                <h2 class="h4 mb-0">Create New Admin Account</h2>
                                <p class="mb-0 opacity-75">Add new administrators with specific permissions</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle me-2 fa-lg"></i>
                                    <div>
                                        <h5 class="alert-heading mb-1">Admin Account Created Successfully!</h5>
                                        <p class="mb-0">New administrator <strong><?php echo htmlspecialchars($success['username']); ?></strong> has been added to the system.</p>
                                        <p class="mb-0"><small>Created on: <?php echo htmlspecialchars($success['time']); ?></small></p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please provide a username</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please provide a valid email</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                        <small class="text-muted">Minimum 8 characters</small>
                                        <div class="invalid-feedback">Password must be at least 8 characters</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                        <div class="invalid-feedback">Passwords must match</div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3">
                                <i class="fas fa-key me-2"></i> Permissions
                            </h5>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="permission-card" onclick="toggleCheckbox('manage_students')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="manage_students" name="manage_students" <?php echo ($permissions['manage_students'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="manage_students">
                                                <i class="fas fa-users me-2"></i> Manage Students
                                            </label>
                                            <p class="text-muted mb-0 mt-1 small">Create, edit, and delete student records</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="permission-card" onclick="toggleCheckbox('manage_rooms')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="manage_rooms" name="manage_rooms" <?php echo ($permissions['manage_rooms'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="manage_rooms">
                                                <i class="fas fa-bed me-2"></i> Manage Rooms
                                            </label>
                                            <p class="text-muted mb-0 mt-1 small">Manage room allocations and details</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="permission-card" onclick="toggleCheckbox('manage_complaints')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="manage_complaints" name="manage_complaints" <?php echo ($permissions['manage_complaints'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="manage_complaints">
                                                <i class="fas fa-comment-dots me-2"></i> Manage Complaints
                                            </label>
                                            <p class="text-muted mb-0 mt-1 small">Handle student complaints and issues</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="permission-card" onclick="toggleCheckbox('view_reports')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="view_reports" name="view_reports" <?php echo ($permissions['view_reports'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="view_reports">
                                                <i class="fas fa-chart-bar me-2"></i> View Reports
                                            </label>
                                            <p class="text-muted mb-0 mt-1 small">Access system reports and analytics</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="superadmin-dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                                </a>
                                
                                <button type="submit" name="create-admin" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Create Admin Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Toggle permission cards
        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            checkbox.checked = !checkbox.checked;
            
            const card = checkbox.closest('.permission-card');
            if (checkbox.checked) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        }
        
        // Initialize permission cards
        document.querySelectorAll('.permission-card').forEach(card => {
            const checkbox = card.querySelector('.form-check-input');
            if (checkbox.checked) {
                card.classList.add('active');
            }
        });
        
        // Password confirmation check
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password')?.value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity("Passwords don't match");
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Auto-dismiss success alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>
</html>