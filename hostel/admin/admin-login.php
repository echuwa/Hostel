<?php
session_start();
require_once('includes/config.php');

// Redirect if already logged in
if (isset($_SESSION['id'])) {
    header("Location: " . (isset($_SESSION['is_superadmin']) ? 'superadmin-dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Username/Email and password are required";
    } else {
        $stmt = $mysqli->prepare("SELECT id, username, email, password, is_superadmin, permissions, status FROM admins WHERE (username = ? OR email = ?) LIMIT 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    // Regenerate session ID
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_superadmin'] = $user['is_superadmin'];
                    $_SESSION['permissions'] = $user['permissions'];
                    $_SESSION['last_login'] = time();
                    
                    // Update last login in database
                    $update_stmt = $mysqli->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Redirect to appropriate dashboard
                    header("Location: " . ($user['is_superadmin'] ? 'superadmin-dashboard.php' : 'dashboard.php'));
                    exit();
                } else {
                    $error = "Your account is inactive. Please contact the superadmin.";
                }
            } else {
                $error = "Invalid credentials";
            }
        } else {
            $error = "Invalid credentials";
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
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .form-control {
            height: 50px;
            border-radius: 8px;
            padding-left: 45px;
        }
        .input-icon {
            position: absolute;
            left: 15px;
            top: 13px;
            color: #6c757d;
            font-size: 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            border: none;
            height: 50px;
            font-weight: 600;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-card">
                    <div class="login-header">
                        <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
                        <p class="mb-0">Hostel Management System</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3 position-relative">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required autofocus>
                            </div>
                            <div class="mb-3 position-relative">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="login" class="btn btn-login btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="forgot-password.php">Forgot Password?</a>
                            <p class="mt-2">Superadmin? <a href="superadmin-login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>