<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once('includes/config.php');
require_once('includes/checklogin.php');

// Redirect if already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
        header("Location: admin/superadmin-dashboard.php");
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'student') {
        header("Location: dashboard.php");
    } elseif (isset($_SESSION['id'])) {
        header("Location: admin/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Username/Email and password are required";
    } else {
        // FIRST: Check in admins table (for superadmin and admin)
        $adminStmt = $mysqli->prepare("SELECT id, username, email, password, is_superadmin, status, assigned_block FROM admins WHERE (username = ? OR email = ?)");
        $adminStmt->bind_param("ss", $username, $username);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        
        if ($adminRow = $adminResult->fetch_assoc()) {
            // Found in admins table
            if (password_verify($password, $adminRow['password'])) {
                if ($adminRow['status'] !== 'active') {
                    $error = "Invalid credentials"; // Same message for all
                } else {
                    // Set session for admin/superadmin
                    $_SESSION['id'] = $adminRow['id'];
                    $_SESSION['username'] = $adminRow['username'];
                    $_SESSION['email'] = $adminRow['email'];
                    $_SESSION['is_superadmin'] = $adminRow['is_superadmin'];
                    $_SESSION['assigned_block'] = $adminRow['assigned_block']; // STORE ASSIGNED BLOCK
                    $_SESSION['last_login'] = time();
                    
                    // Update last login
                    $update = $mysqli->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $update->bind_param("i", $adminRow['id']);
                    $update->execute();
                    $update->close();
                    
                    // Log login
                    $log = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_type, description, ip_address) VALUES (?, 'login', 'User logged in', ?)");
                    $log->bind_param("is", $adminRow['id'], $_SERVER['REMOTE_ADDR']);
                    $log->execute();
                    $log->close();
                    
                    // Redirect based on role (USER DOESN'T SEE THIS)
                    if ($adminRow['is_superadmin'] == 1) {
                        header("Location: admin/superadmin-dashboard.php");
                    } else {
                        header("Location: admin/dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid credentials"; // Same message for wrong password
            }
        } else {
            // SECOND: Check in userregistration table (for students)
            $studentStmt = $mysqli->prepare("SELECT id, email, password, firstName, lastName, status FROM userregistration WHERE email = ?");
            $studentStmt->bind_param("s", $username);
            $studentStmt->execute();
            $studentResult = $studentStmt->get_result();
            
            if ($studentRow = $studentResult->fetch_assoc()) {
                // Found in students table
                if (password_verify($password, $studentRow['password'])) {
                    // Check student status
                    if (strtolower($studentRow['status'] ?? '') === 'pending') {
                        $error = "Your account is pending admin approval. Please wait for verification.";
                    } else if (strtolower($studentRow['status'] ?? '') === 'blocked') {
                        $error = "Your account has been blocked. Please contact administration.";
                    } else {
                        // Set session for student
                        $_SESSION['user_id'] = $studentRow['id'];
                        $_SESSION['login'] = $studentRow['email'];
                        $_SESSION['name'] = $studentRow['firstName'] . ' ' . $studentRow['lastName'];
                        $_SESSION['user_role'] = 'student';
                        
                        // Log the access
                        log_student_access($studentRow['id'], $studentRow['email']);
                        
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Invalid credentials"; // Same message for wrong password
                }
            } else {
                $error = "Invalid credentials"; // Same message for user not found
            }
        }
        $adminStmt->close();
        if (isset($studentStmt)) $studentStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | HostelMS</title>
    
    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Favicon (Data URI to prevent 404) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏨</text></svg>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Auth Modern CSS -->
    <link rel="stylesheet" href="css/auth-modern.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <style>
        /* Optimize font loading */
        * { font-display: swap; }
        
        .google_login_container {
            width: 100%;
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="auth_wrapper" style="max-width: 850px;">
        <div class="auth_card" data-aos="zoom-in" data-aos-duration="1000">
            <!-- Left Panel - Hero Section -->
            <div class="auth_hero">
                <div class="auth_hero_content" data-aos="fade-right" data-aos-delay="200">
                    <h2>Secure Access</h2>
                    <p>Enter your credentials to access your student portal and manage your residence.</p>
                    <img src="assets/img/login_hero.png" alt="Login Hero" style="max-width: 350px;">
                </div>
            </div>

            <!-- Right Panel - Form Section -->
            <div class="auth_content">
                <div class="auth_header" data-aos="fade-up" data-aos-delay="300">
                    <h1 class="auth_title">Welcome Back</h1>
                    <p class="auth_subtitle">Sign in to continue your journey.</p>
                </div>

                <!-- Display Errors -->
                <?php if (!empty($error)): ?>
                    <div class="alert-modern alert-danger-modern" data-aos="shake" data-aos-duration="500">
                        <i class="fas fa-shield-slash me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input_container" data-aos="fade-up" data-aos-delay="400">
                        <label class="form-label">Username or Email</label>
                        <div class="input-group-modern">
                            <i class="fas fa-user-shield"></i>
                            <input type="text" name="username" placeholder="Username / Email" required
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="input_container" data-aos="fade-up" data-aos-delay="500">
                        <label class="form-label">Password</label>
                        <div class="input-group-modern">
                            <i class="fas fa-key"></i>
                            <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                            <span style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #64748b;" onclick="toggleLoginPassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up" data-aos-delay="550" style="font-size: 0.85rem; font-weight: 600;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="remember" name="remember" style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="remember" style="cursor: pointer; color: #64748b;">Keep me signed in</label>
                        </div>
                        <a href="forgot-password.php" style="color: #4361ee; text-decoration: none;">Reset Password?</a>
                    </div>

                    <!-- Action Buttons -->
                    <div class="auth_actions" data-aos="fade-up" data-aos-delay="600">
                        <button type="submit" name="login" class="btn-primary-modern">
                            <span>Sign Into Portal</span>
                            <i class="fas fa-right-to-bracket"></i>
                        </button>
                    </div>

                    <div class="auth_divider" data-aos="fade-up" data-aos-delay="650">OR</div>

                    <div class="google_login_container" data-aos="fade-up" data-aos-delay="700">
                        <div id="google_btn"></div>
                    </div>

                    <div class="auth_footer" data-aos="fade-up" data-aos-delay="700">
                        New student user? <a href="javascript:void(0)" onclick="openRegistrationModal()" class="fw-800 text-primary">Create Account</a>
                    </div>
                </form>
            </div>
        </div>


    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
    AOS.init({ duration: 800, once: true });

    window.onload = function () {
        google.accounts.id.initialize({
            client_id: "1017558814874-u6ve0qj0qjhoskriggokakum2l3g9hap.apps.googleusercontent.com",
            callback: handleCredentialResponse
        });
        const gBtnWidth = window.innerWidth < 400 ? window.innerWidth - 60 : 350;
        google.accounts.id.renderButton(
            document.getElementById("google_btn"),
            { 
                theme: "outline", 
                size: "large", 
                width: gBtnWidth, 
                shape: "pill",
                text: "signin_with",
                logo_alignment: "left"
            }
        );
    }

    function toggleLoginPassword() {
        const pass = document.getElementById("loginPassword");
        const icon = document.getElementById("toggleIcon");
        if (pass.type === "password") {
            pass.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            pass.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    function handleCredentialResponse(response) {
        // Send the ID token to your server
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'google-auth.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.status === 'success') {
                    if (res.redirect) {
                        window.location.href = res.redirect;
                    } else {
                        window.location.reload();
                    }
                } else if (res.status === 'pending') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        text: res.message || 'Your account is pending admin approval.',
                        confirmButtonColor: '#4361ee'
                    });
                } else if (res.status === 'register') {
                    // Redirect to registration with prefilled data
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'registration.php';
                    
                    const fields = ['google_id', 'email', 'fname', 'lname', 'pic'];
                    fields.forEach(field => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'google_' + field;
                        input.value = res.data[field];
                        form.appendChild(input);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: res.message || 'Could not verify Google account.'
                    });
                }
            } catch (e) {
                console.error("Response error:", e);
            }
        };
        xhr.send('idtoken=' + response.credential);
    }

    function openRegistrationModal() {
        // Detect mobile for responsive width
        const modalWidth = window.innerWidth > 1000 ? '1000px' : '95%';
        // Use a more robust height calculation
        const modalHeight = Math.min(window.innerHeight * 0.9, 850) + 'px';

        Swal.fire({
            html: `<iframe src="registration.php" style="width:100%; height:${modalHeight}; border:none; border-radius:15px;" scrolling="yes"></iframe>`,
            width: modalWidth,
            padding: '0',
            showConfirmButton: false,
            showCloseButton: true, // Allow closing easily on mobile
            background: '#ffffff',
            backdrop: `rgba(15, 23, 42, 0.75)`,
            customClass: {
                popup: 'rounded-4 shadow-2xl border-0'
            },
            showClass: {
                popup: 'animate__animated animate__fadeInUp animate__fast'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutDown animate__fast'
            }
        });
    }
    </script>
</body>
</html>
