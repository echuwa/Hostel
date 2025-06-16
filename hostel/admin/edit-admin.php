<?php
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Check if super admin is logged in
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: superadmin-login.php");
    exit();
}

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch admin details
$stmt = $mysqli->prepare("SELECT id, username, email, status FROM admins WHERE id = ? AND is_superadmin = 0");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    header("Location: superadmin-dashboard.php?error=Admin not found");
    exit();
}

$error = '';

if (isset($_POST['update'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $status = trim($_POST['status']);
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error = "Username and email are required";
    } else {
        // Check if username or email already exists for other admins
        $stmt = $mysqli->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $admin_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists for another admin";
        } else {
            // Update admin
            $stmt = $mysqli->prepare("UPDATE admins SET username = ?, email = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $status, $admin_id);
            
            if ($stmt->execute()) {
                header("Location: superadmin-dashboard.php?success=Admin account updated successfully");
                exit();
            } else {
                $error = "Error updating admin account: " . $mysqli->error;
            }
        }
    }
}
?>
<!-- HTML form similar to create admin but pre-filled with existing data -->
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="Hostel Management System - Edit Admin Account">
    <meta name="author" content="">
    <title>Edit Admin Account | Hostel Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.png" type="image/png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #ef233c;
            --success-color: #4cc9f0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .edit-container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            background: url('img/login-bg.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            overflow: hidden;
        }
        
        .edit-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        
        .edit-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .edit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .edit-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .edit-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 28px;
        }
        
        .edit-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .superadmin-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .edit-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding-left: 45px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #adb5bd;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .form-control:focus + .input-icon {
            color: var(--primary-color);
        }
        
        .btn-update {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            height: 50px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-update:active {
            transform: translateY(0);
        }
        
        .alert-danger {
            background-color: rgba(239, 35, 60, 0.1);
            border-color: rgba(239, 35, 60, 0.2);
            color: var(--danger-color);
            border-radius: 8px;
            padding: 12px 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .edit-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-card">
            <div class="edit-header">
                <span class="superadmin-badge">SUPER ADMIN</span>
                <h2>Edit Admin Account</h2>
                <p>Update the details for admin ID: <?php echo $admin['id']; ?></p>
            </div>
            
            <div class="edit-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Username" 
                               value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Email Address" 
                               value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Status:</label>
                        <div>
                            <span class="status-badge status-<?php echo $admin['status']; ?>">
                                <?php echo ucfirst($admin['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Update Status:</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo $admin['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $admin['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update" class="btn btn-block btn-update">
                            <i class="fas fa-save"></i> Update Admin Account
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="superadmin-dashboard.php" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add animation to form elements
            $('.form-group').each(function(i) {
                $(this).css({
                    'opacity': '0',
                    'animation': `fadeIn 0.5s ease-out forwards ${i * 0.1 + 0.3}s`
                });
            });
        });
    </script>
</body>
</html>