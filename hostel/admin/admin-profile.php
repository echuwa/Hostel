<?php
session_start();
require_once('includes/config.php');
require_once('includes/checklogin.php');
check_login();
require_once('includes/auth.php');

// Get admin ID from URL parameter or current user
$admin_id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : $_SESSION['id'];

// Prevent non-superadmins from viewing other profiles
if (!isSuperAdmin() && $admin_id != $_SESSION['id']) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Fetch admin details
$query = "SELECT id, username, email, is_superadmin, status, reg_date as reg_date FROM admins WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php?error=Admin not found");
    exit();
}

$admin = $result->fetch_assoc();
$stmt->close();

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $status = trim($_POST['status']);
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error = "Username and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username or email already exists (excluding current admin)
        $query = "SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ssi", $username, $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Update admin details
            $query = "UPDATE admins SET username = ?, email = ?, status = ? WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("sssi", $username, $email, $status, $admin_id);
            
            if ($stmt->execute()) {
                $success = "Admin profile updated successfully";
                // Refresh admin data
                $admin['username'] = $username;
                $admin['email'] = $email;
                $admin['status'] = $status;
            } else {
                $error = "Update failed: " . $mysqli->error;
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
    <title>Admin Profile | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .profile-card {
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .profile-cover {
            height: 120px;
            background: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
            position: relative;
        }
        .profile-avatar-wrapper {
            position: absolute;
            bottom: -40px;
            left: 30px;
            width: 100px;
            height: 100px;
            border-radius: 25px;
            background: #fff;
            padding: 5px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #4361ee;
            font-weight: 800;
        }
        .profile-info-section {
            padding: 60px 30px 30px;
        }
        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-weight: 500;
        }
        .form-control:focus, .form-select:focus {
            background: #fff;
            border-color: #4361ee;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }
        .detail-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 16px;
            border: 1px solid #f1f5f9;
            height: 100%;
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
                            <i class="fas fa-id-card"></i>
                            Admin Profile
                        </h1>
                        <p class="text-muted small">Update your account information and preferences</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="profile-card">
                            <div class="profile-cover">
                                <div class="profile-avatar-wrapper">
                                    <div class="profile-avatar">
                                        <?php echo substr($admin['username'], 0, 1); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="profile-info-section">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($admin['username']); ?></h3>
                                        <span class="badge rounded-pill <?php echo $admin['is_superadmin'] ? 'bg-primary' : 'bg-secondary'; ?>">
                                            <?php echo $admin['is_superadmin'] ? 'Super Admin' : 'Admin'; ?>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">MEMBER SINCE</small>
                                        <span class="fw-bold"><?php echo date('M Y', strtotime($admin['reg_date'])); ?></span>
                                    </div>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger rounded-4 border-0 shadow-sm"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <?php if ($success): ?>
                                    <div class="alert alert-success rounded-4 border-0 shadow-sm"><?php echo $success; ?></div>
                                <?php endif; ?>

                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Account Status</label>
                                            <select name="status" class="form-select" <?php echo !isSuperAdmin() ? 'disabled' : ''; ?>>
                                                <option value="active" <?php echo $admin['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="pending" <?php echo $admin['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="suspended" <?php echo $admin['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                            </select>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <button type="submit" name="update_admin" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-sm">
                                                Update Profile Details
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="profile-card p-4">
                            <h5 class="fw-bold mb-4">Account Integrity</h5>
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="detail-item">
                                        <small class="form-label d-block">Account ID</small>
                                        <span class="fw-bold text-dark">#<?php echo $admin['id']; ?></span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="detail-item">
                                        <small class="form-label d-block">Security Level</small>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fas fa-check-shield me-1"></i> 
                                            <?php echo $admin['is_superadmin'] ? 'High Authority' : 'Standard Authority'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="detail-item">
                                        <small class="form-label d-block">Registration Date</small>
                                        <span class="fw-bold text-dark"><?php echo date('F j, Y', strtotime($admin['reg_date'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <a href="change-password.php" class="btn btn-outline-primary w-100 rounded-pill py-3 fw-bold mt-4">
                                <i class="fas fa-key me-2"></i> Change Password
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>