<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Redirect regular admins if they try to access this page
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

// Delete admin functionality
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    // Prevent self-deletion
    if ($id == $_SESSION['id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        $adn = "DELETE FROM admins WHERE id=?";
        $stmt = $mysqli->prepare($adn);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();   
        $_SESSION['success'] = "Administrator deleted successfully";
    }
    header("Location: manage-admins.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#4361ee">
    <title>Manage Administrators | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .admin-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .admin-avatar {
            width: 45px; height: 45px; border-radius: 12px;
            background: #eff6ff; color: #4361ee;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.1rem;
        }
        .role-badge {
            padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;
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
                            <i class="fas fa-users-cog"></i>
                            Administrators
                        </h1>
                        <p class="text-muted small">Manage system users and their access levels</p>
                    </div>
                    <div class="header-right">
                        <a href="create-admin.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="fas fa-plus me-2"></i> Add New Admin
                        </a>
                    </div>
                </div>

                <div class="admin-card p-4">
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table id="admins-table" class="table table-modern" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Admin User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ret = "SELECT * FROM admins ORDER BY id DESC";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                $cnt = 1;
                                
                                while($row = $res->fetch_object()):
                                ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="admin-avatar">
                                                <?php echo substr($row->username, 0, 1); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row->username); ?></div>
                                                <small class="text-muted">Reg: <?php echo date('d M Y', strtotime($row->reg_date)); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row->email); ?></td>
                                    <td>
                                        <?php if($row->is_superadmin): ?>
                                            <span class="role-badge bg-primary-subtle text-primary">SUPER ADMIN</span>
                                        <?php else: ?>
                                            <span class="role-badge bg-light text-muted">ADMIN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $row->status == 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($row->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="fw-bold text-dark">
                                            <?php echo $row->last_login ? date('d M, h:i A', strtotime($row->last_login)) : '--'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="admin-profile.php?id=<?php echo $row->id; ?>" class="btn btn-sm btn-light rounded-3" title="Edit">
                                                <i class="fas fa-edit text-primary"></i>
                                            </a>
                                            <?php if($row->id != $_SESSION['id']): ?>
                                                <a href="manage-admins.php?del=<?php echo $row->id; ?>" class="btn btn-sm btn-light rounded-3" title="Delete" onclick="return confirm('Remove this administrator?');">
                                                    <i class="fas fa-trash-alt text-danger"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#admins-table').DataTable({
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search admins..."
                },
                "dom": '<"d-flex justify-content-between align-items-center mb-4"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>'
            });
        });
    </script>
</body>
</html>