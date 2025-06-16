<?php
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Only super admin can access this page
if (!isSuperAdmin()) {
    header("Location: admin-profile.php");
    exit();
}

// Get admin ID from URL parameter
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch admin details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: superadmin/dashboard.php?error=Admin not found");
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
        $query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ssi", $username, $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Update admin details
            $query = "UPDATE users SET username = ?, email = ?, status = ? WHERE id = ?";
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Hostel Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --accent-color: #4895ef;
            --danger-color: #ef233c;
            --success-color: #4cc9f0;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        
        .profile-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            height: 45px;
            border-radius: 8px;
        }
        
        .status-select {
            width: 200px;
        }
        
        .btn-update {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
        }
        
        .btn-update:hover {
            background: var(--primary-color);
        }
        
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include('includes/superadmin-header.php'); ?>
    
    <div class="container py-5">
        <div class="profile-container">
            <div class="profile-header">
                <h3><i class="fas fa-user-cog"></i> Admin Profile Management</h3>
                <p class="mb-0">ID: <?php echo $admin_id; ?></p>
            </div>
            
            <div class="profile-form">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Registration Date</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y', strtotime($admin['reg_date'])); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Status</label>
                                <select name="status" class="form-control status-select">
                                    <option value="active" <?php echo $admin['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $admin['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="suspended" <?php echo $admin['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_admin" class="btn btn-update">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <a href="superadmin/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
                
                <hr>
                
                <h5><i class="fas fa-info-circle"></i> Additional Information</h5>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <p><strong>Last Login:</strong><br>
                        <?php echo $admin['last_login'] ? date('M j, Y H:i', strtotime($admin['last_login'])) : 'Never'; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Is Superadmin:</strong><br>
                        <?php echo $admin['is_superadmin'] ? 'Yes' : 'No'; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Account ID:</strong><br>
                        <?php echo $admin['id']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>