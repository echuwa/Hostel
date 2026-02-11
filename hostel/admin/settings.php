<?php
// Start session securely before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/checklogin.php';

// Check login status
check_login();

// Initialize variables
$error = '';
$success = '';
$isAdmin = false;
$users = [];
$admins = [];
$students = [];

try {
    // Check if user is admin
    $adminCheck = $mysqli->prepare("SELECT is_admin FROM userregistration WHERE id = ?");
    if (!$adminCheck) {
        throw new Exception("Error preparing admin check: " . $mysqli->error);
    }
    $adminCheck->bind_param('i', $_SESSION['id']);
    $adminCheck->execute();
    $adminCheck->bind_result($isAdmin);
    $adminCheck->fetch();
    $adminCheck->close();
    
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Delete student functionality
    if (isset($_POST['delete_student']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $studentId = intval($_POST['student_id']);
        
        // Begin transaction
        $mysqli->begin_transaction();
        
        try {
            // First delete from user logs
            $deleteLogs = $mysqli->prepare("DELETE FROM userlog WHERE userId = ?");
            if (!$deleteLogs) {
                throw new Exception("Error preparing log deletion: " . $mysqli->error);
            }
            $deleteLogs->bind_param('i', $studentId);
            $deleteLogs->execute();
            $deleteLogs->close();
            
            // Then delete from registration
            $deleteStudent = $mysqli->prepare("DELETE FROM registration WHERE id = ?");
            if (!$deleteStudent) {
                throw new Exception("Error preparing student deletion: " . $mysqli->error);
            }
            $deleteStudent->bind_param('i', $studentId);
            $deleteStudent->execute();
            
            if ($deleteStudent->affected_rows > 0) {
                $success = "Student deleted successfully";
                // Log the deletion action
                log_action($mysqli, $_SESSION['id'], "Deleted student ID: $studentId");
            } else {
                throw new Exception("No student found with that ID");
            }
            
            $deleteStudent->close();
            $mysqli->commit();
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = "Error deleting student: " . $e->getMessage();
            error_log("Student deletion failed: " . $e->getMessage());
        }
    }

    // Delete user functionality
    if (isset($_POST['delete_user']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $userId = intval($_POST['user_id']);
        
        // Prevent self-deletion
        if ($userId == $_SESSION['id']) {
            $error = "You cannot delete your own account from this page.";
        } else {
            // Begin transaction
            $mysqli->begin_transaction();
            
            try {
                // First delete from user logs
                $deleteLogs = $mysqli->prepare("DELETE FROM userlog WHERE userId = ?");
                if (!$deleteLogs) {
                    throw new Exception("Error preparing log deletion: " . $mysqli->error);
                }
                $deleteLogs->bind_param('i', $userId);
                $deleteLogs->execute();
                $deleteLogs->close();
                
                // Then delete from user registration
                $deleteUser = $mysqli->prepare("DELETE FROM userregistration WHERE id = ?");
                if (!$deleteUser) {
                    throw new Exception("Error preparing user deletion: " . $mysqli->error);
                }
                $deleteUser->bind_param('i', $userId);
                $deleteUser->execute();
                
                if ($deleteUser->affected_rows > 0) {
                    $success = "User deleted successfully";
                    // Log the deletion action
                    log_action($mysqli, $_SESSION['id'], "Deleted user ID: $userId");
                } else {
                    throw new Exception("No user found with that ID");
                }
                
                $deleteUser->close();
                $mysqli->commit();
            } catch (Exception $e) {
                $mysqli->rollback();
                $error = "Error deleting user: " . $e->getMessage();
                error_log("User deletion failed: " . $e->getMessage());
            }
        }
    }

    // Delete admin functionality (only for admins)
    if ($isAdmin && isset($_POST['delete_admin']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $adminId = intval($_POST['admin_id']);
        
        // Prevent self-deletion
        if ($adminId == $_SESSION['id']) {
            $error = "You cannot delete your own admin account from this page.";
        } else {
            // First check if we're not deleting the last admin
            $checkAdmins = $mysqli->query("SELECT COUNT(*) as count FROM admin WHERE id != " . $_SESSION['id']);
            if (!$checkAdmins) {
                throw new Exception("Error checking admin count: " . $mysqli->error);
            }
            
            $adminCount = $checkAdmins->fetch_assoc()['count'];
            $checkAdmins->close();
            
            if ($adminCount < 1) {
                $error = "Cannot delete the last admin account";
            } else {
                $deleteAdmin = $mysqli->prepare("DELETE FROM admin WHERE id = ?");
                if (!$deleteAdmin) {
                    throw new Exception("Error preparing admin deletion: " . $mysqli->error);
                }
                
                $deleteAdmin->bind_param('i', $adminId);
                $deleteAdmin->execute();
                
                if ($deleteAdmin->affected_rows > 0) {
                    $success = "Admin deleted successfully";
                    // Log the deletion action
                    log_action($mysqli, $_SESSION['id'], "Deleted admin ID: $adminId");
                } else {
                    $error = "No admin found with that ID or deletion failed";
                }
                
                $deleteAdmin->close();
            }
        }
    }

    // Get all students
    $studentsResult = $mysqli->query("SELECT id, regno, firstName, lastName, emailid, contactno, stayfrom FROM registration ORDER BY stayfrom DESC");
    if (!$studentsResult) {
        throw new Exception("Error fetching students: " . $mysqli->error);
    }
    $students = $studentsResult->fetch_all(MYSQLI_ASSOC);
    $studentsResult->close();

    // Get all users
    $usersResult = $mysqli->query("SELECT id, regNo, firstName, lastName, email, contactNo, regDate FROM userregistration ORDER BY regDate DESC");
    if (!$usersResult) {
        throw new Exception("Error fetching users: " . $mysqli->error);
    }
    $users = $usersResult->fetch_all(MYSQLI_ASSOC);
    $usersResult->close();

    // Get all admins (only if current user is admin)
    if ($isAdmin) {
        $adminsResult = $mysqli->query("SELECT id, username, email, reg_date, updation_date FROM admin ORDER BY reg_date DESC");
        if (!$adminsResult) {
            throw new Exception("Error fetching admins: " . $mysqli->error);
        }
        $admins = $adminsResult->fetch_all(MYSQLI_ASSOC);
        $adminsResult->close();
    }

} catch (Exception $e) {
    error_log("Error in settings.php: " . $e->getMessage());
    $error = "A system error occurred. Please try again later.";
}

// Function to log actions
function log_action($mysqli, $userId, $action) {
    $stmt = $mysqli->prepare("INSERT INTO action_logs (user_id, action, action_date) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $userId, $action);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="description" content="System Settings">
    <meta name="author" content="">
    <meta name="theme-color" content="#3e454c">
    
    <title>System Settings | Hostel Management</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .settings-container {
            padding: 20px;
        }
        
        .settings-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border: none;
        }
        
        .settings-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: #212529;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px 8px 0 0;
        }
        
        .settings-body {
            padding: 20px;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            border-left: 4px solid #4cc9f0;
            color: #212529;
        }
        
        .alert-danger {
            background-color: rgba(239, 35, 60, 0.1);
            border-left: 4px solid #ef233c;
            color: #212529;
        }
        
        .alert-info {
            background-color: rgba(78, 115, 223, 0.1);
            border-left: 4px solid #4e73df;
            color: #212529;
        }
        
        .badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 3px solid #4361ee;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php');?>
        <div class="content-wrapper">
            <div class="container-fluid settings-container">
                <h2 class="page-title">System Settings</h2>
                
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="students-tab" data-toggle="tab" href="#students" role="tab">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">Users</a>
                    </li>
                    <?php if($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" id="admins-tab" data-toggle="tab" href="#admins" role="tab">Admins</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="tab-content" id="settingsTabsContent">
                    <!-- Students Tab -->
                    <div class="tab-pane fade show active" id="students" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <i class="fas fa-user-graduate"></i> Student Management
                            </div>
                            <div class="settings-body">
                                <?php if(!empty($students)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="studentsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Reg No</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Contact</th>
                                                <th>Stay From</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['regno']); ?></td>
                                                <td><?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?></td>
                                                <td><?php echo htmlspecialchars($student['emailid']); ?></td>
                                                <td><?php echo htmlspecialchars($student['contactno']); ?></td>
                                                <td><?php echo htmlspecialchars($student['stayfrom']); ?></td>
                                                <td>
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to permanently delete this student and all their data?');">
                                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <button type="submit" name="delete_student" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No students found</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <i class="fas fa-users"></i> User Management
                            </div>
                            <div class="settings-body">
                                <?php if(!empty($users)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Reg No</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Contact</th>
                                                <th>Registration Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['regNo']); ?></td>
                                                <td><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['contactNo']); ?></td>
                                                <td><?php echo htmlspecialchars($user['regDate']); ?></td>
                                                <td>
                                                    <?php if($user['id'] != $_SESSION['id']): ?>
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to permanently delete this user and all their data?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                    <?php else: ?>
                                                    <span class="badge badge-info">Current User</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No users found</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admins Tab (only visible to admins) -->
                    <?php if($isAdmin): ?>
                    <div class="tab-pane fade" id="admins" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <i class="fas fa-user-shield"></i> Admin Management
                            </div>
                            <div class="settings-body">
                                <?php if(!empty($admins)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="adminsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Registration Date</th>
                                                <th>Last Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($admins as $admin): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                <td><?php echo htmlspecialchars($admin['reg_date']); ?></td>
                                                <td><?php echo htmlspecialchars($admin['updation_date'] ?? 'Never'); ?></td>
                                                <td>
                                                    <?php if($admin['id'] != $_SESSION['id']): ?>
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this admin? This action cannot be undone.');">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <button type="submit" name="delete_admin" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                    <?php else: ?>
                                                    <span class="badge badge-primary">Current User</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No admins found</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/dataTables.bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#studentsTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "responsive": true
            });
            
            $('#usersTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "responsive": true
            });
            
            <?php if($isAdmin): ?>
            $('#adminsTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "responsive": true
            });
            <?php endif; ?>
            
            // Tab functionality
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
            });
        });
    </script>
</body>
</html>