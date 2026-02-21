<?php
session_start();
require_once('includes/config.php');
require_once('includes/checklogin.php');
check_login();

$error = '';
$success = '';

if (isset($_POST['change_pwd'])) {
    $current = trim($_POST['current_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);
    
    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All fields are required";
    } elseif ($new !== $confirm) {
        $error = "New password and confirm password do not match";
    } else {
        $admin_id = $_SESSION['id'];
        
        $stmt = $mysqli->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (password_verify($current, $row['password'])) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $update = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $update->bind_param("si", $hash, $admin_id);
                if ($update->execute()) {
                    $success = "Password changed successfully";
                } else {
                    $error = "Error updating password";
                }
                $update->close();
            } else {
                $error = "Incorrect current password";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Hostel Management System</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS from modern.css -->
    <link rel="stylesheet" href="css/modern.css">
    <style>
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            border-color: #4361ee;
        }
        .change-btn {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .change-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(67,97,238,0.3);
            color: white;
        }
        .card-header {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                
                <div class="content-header">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-key"></i> Change Password
                        </h1>
                    </div>
                </div>

                <div class="row justify-content-center mt-4">
                    <div class="col-md-8 col-lg-6">
                        <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                            <div class="card-header p-4 text-center">
                                <h4 class="mb-0"><i class="fas fa-lock me-2"></i> Update Password</h4>
                            </div>
                            <div class="card-body p-5">
                                <?php if($error): ?>
                                    <div class="alert alert-danger" style="border-radius: 8px;">
                                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($success): ?>
                                    <div class="alert alert-success" style="border-radius: 8px;">
                                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary">New Password</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button type="submit" name="change_pwd" class="change-btn w-100">
                                            <i class="fas fa-save me-2"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
