<?php
// ============================================
// SUPER ADMIN MISSION CONTROL
// ============================================
session_start();
require_once('includes/config.php');
require_once('includes/auth.php');

// Security Check
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: superadmin-login.php");
    exit();
}

// Inactivity timeout
$inactive = 1800;
if (isset($_SESSION['last_login']) && (time() - $_SESSION['last_login'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: superadmin-login.php?timeout=1");
    exit();
}
$_SESSION['last_login'] = time();

// Fetch Admin Metrics
$admins = [];
$stmt = $mysqli->prepare("SELECT id, username, email, reg_date, status, assigned_block FROM admins WHERE is_superadmin = 0 ORDER BY reg_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    $stmt->close();
}

// Pending Admins
$pending_count = 0;
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM admins WHERE status = 'pending' AND is_superadmin = 0");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pending_count = $row['count'];
    $stmt->close();
}

// Audit Logs
$audit_logs = [];
$tableCheck = $mysqli->query("SHOW TABLES LIKE 'audit_logs'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $logStmt = $mysqli->prepare("SELECT al.id, al.user_id, al.action_type as action, al.description as details, 
                                        al.ip_address, al.created_at, a.username 
                                 FROM audit_logs al 
                                 LEFT JOIN admins a ON al.user_id = a.id 
                                 ORDER BY al.created_at DESC LIMIT 20");
    if ($logStmt) {
        $logStmt->execute();
        $logResult = $logStmt->get_result();
        while ($row = $logResult->fetch_assoc()) {
            $row['username'] = $row['username'] ?: 'System';
            $row['action'] = $row['action'] ?: 'unknown';
            $audit_logs[] = $row;
        }
        $logStmt->close();
    }
}

// Global Metrics for Architect View
$total_students = $mysqli->query("SELECT COUNT(*) as c FROM userregistration")->fetch_object()->c;
$total_rooms = $mysqli->query("SELECT COUNT(*) as c FROM rooms")->fetch_object()->c;
$total_complaints = $mysqli->query("SELECT COUNT(*) as c FROM complaints")->fetch_object()->c;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Super Admin Mission Control | HostelMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .super-card {
            background: #fff; border-radius: 24px; border: 1px solid #f1f5f9;
            padding: 30px; height: 100%; transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }
        .super-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: var(--primary); opacity: 0; transition: 0.3s;
        }
        .super-card:hover { transform: translateY(-5px); border-color: rgba(67, 97, 238, 0.2); box-shadow: 0 10px 30px rgba(67, 97, 238, 0.08); }
        .super-card:hover::before { opacity: 0.7; }
        
        .role-badge {
            padding: 4px 12px; border-radius: 12px; font-size: 0.7rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .admin-avatar {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .mission-header {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%); padding: 50px 40px; border-radius: 30px;
            color: #fff; margin-bottom: 40px; position: relative; overflow: hidden;
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.2);
        }
        .mission-header::after {
            content: ''; position: absolute; bottom: -50px; right: -50px;
            width: 250px; height: 250px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        
        .table-mission { width: 100%; }
        .table-mission th { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; padding: 15px; border-bottom: 1px solid #f1f5f9; }
        .table-mission td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
        
        .audit-entry {
            padding: 15px; border-radius: 16px; background: #f8fafc;
            border-left: 3px solid rgba(67, 97, 238, 0.3); margin-bottom: 15px;
            transition: all 0.3s;
        }
        .audit-entry:hover { border-left-color: var(--primary); background: #fff; }
        
        /* Stats hover effect */
        .metric-card { cursor: pointer; }
        .metric-card:hover i { transform: scale(1.2); transition: 0.3s; }
        
        /* Professional Executive Dashboard UI */
        .main-content { padding-top: 70px; background: #f8fafc; }
        .content-wrapper { padding: 25px 35px; }

        .executive-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px; padding: 25px 35px; color: #fff;
            position: relative; overflow: hidden; margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
        }
        .executive-header::before {
            content: ''; position: absolute; top: -50%; right: -10%; 
            width: 400px; height: 400px; background: rgba(67, 97, 238, 0.1); 
            border-radius: 50%; blur: 80px;
        }
        .eco-node-mini {
            background: #fff; border-radius: 16px; padding: 15px;
            border: 1px solid #eef2f6; text-align: center; transition: 0.3s;
            height: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .eco-node-mini:hover { border-color: #4361ee; transform: translateY(-3px); }
        .clock-box {
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(10px); color: #fff; padding: 10px 20px;
            border-radius: 14px; font-family: 'Courier New', monospace;
            text-align: center; min-width: 140px;
        }
        .badge-architect {
            background: #4361ee; color: #fff; text-transform: uppercase;
            font-size: 0.65rem; font-weight: 800; padding: 6px 12px;
            border-radius: 6px; letter-spacing: 1px; display: inline-block;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <?php include('includes/header.php'); ?>
            <div class="content-wrapper">
                
                <!-- SLIM EXECUTIVE HEADER -->
                <div class="executive-header" data-aos="fade-down">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="badge-architect">Master Control Console</div>
                            <h2 class="fw-800 mb-2" style="letter-spacing: -1px;">Architectural Infrastructure Control</h2>
                            <p class="text-white text-opacity-75 mb-0 fw-500" style="font-size: 0.95rem;">
                                Real-time oversight across administrative tiers, security audit logs, and global hostel master data.
                            </p>
                        </div>
                        <div class="col-lg-4 text-end d-none d-lg-block">
                            <div class="clock-box d-inline-block">
                                <div id="liveClock" style="font-size: 1.4rem; font-weight: 700;">00:00:00</div>
                                <div style="font-size: 0.65rem; font-weight: 700; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px;">Session Time (EAT)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COMPACT ECOSYSTEM MINI-MAP -->
                <div class="row g-3 mb-5" data-aos="fade-up">
                    <div class="col-md-4">
                        <div class="eco-node-mini">
                            <div class="text-primary mb-2"><i class="fas fa-crown"></i></div>
                            <h6 class="fw-800 small mb-1">Super Root Access</h6>
                            <p style="font-size: 0.7rem;" class="text-muted mb-0">System configuration & Global logs</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="eco-node-mini">
                            <div class="text-info mb-2"><i class="fas fa-user-shield"></i></div>
                            <h6 class="fw-800 small mb-1">Tiered Administrators</h6>
                            <p style="font-size: 0.7rem;" class="text-muted mb-0">Block oversight & verificaton hub</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="eco-node-mini">
                            <div class="text-success mb-2"><i class="fas fa-graduation-cap"></i></div>
                            <h6 class="fw-800 small mb-1">Student Interface</h6>
                            <p style="font-size: 0.7rem;" class="text-muted mb-0">Self-service booking & residency</p>
                        </div>
                    </div>
                </div>


                <!-- STATS GRID -->
                <div class="row g-4 mb-5">
                    <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="0">
                        <div class="ecosystem-node text-center h-100 shadow-sm" style="border-bottom: 4px solid #4361ee;">
                            <div class="text-primary mb-2"><i class="fas fa-users fa-2x"></i></div>
                            <div class="metric-label fw-800 text-muted small">TOTAL RESIDENTS</div>
                            <div class="metric-value h2 fw-800 text-dark"><?php echo $total_students; ?></div>
                            <div class="progress mt-3" style="height: 4px;"><div class="progress-bar" style="width: 100%"></div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="100">
                        <div class="ecosystem-node text-center h-100 shadow-sm" style="border-bottom: 4px solid #2ec4b6;">
                            <div class="text-success mb-2"><i class="fas fa-door-open fa-2x"></i></div>
                            <div class="metric-label fw-800 text-muted small">TOTAL ROOMS</div>
                            <div class="metric-value h2 fw-800 text-dark"><?php echo $total_rooms; ?></div>
                            <div class="progress mt-3" style="height: 4px;"><div class="progress-bar bg-success" style="width: 100%"></div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="200">
                        <div class="ecosystem-node text-center h-100 shadow-sm" style="border-bottom: 4px solid #e71d36;">
                            <div class="text-danger mb-2"><i class="fas fa-bullhorn fa-2x"></i></div>
                            <div class="metric-label fw-800 text-muted small">TOTAL ISSUES</div>
                            <div class="metric-value h2 fw-800 text-dark"><?php echo $total_complaints; ?></div>
                            <div class="progress mt-3" style="height: 4px;"><div class="progress-bar bg-danger" style="width: 100%"></div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" data-aos="zoom-in" data-aos-delay="300">
                        <div class="ecosystem-node text-center h-100 shadow-sm" style="border-bottom: 4px solid #ffd700;">
                            <div class="text-warning mb-2"><i class="fas fa-user-shield fa-2x"></i></div>
                            <div class="metric-label fw-800 text-muted small">ADMIN TIERS</div>
                            <div class="metric-value h2 fw-800 text-dark"><?php echo count($admins); ?></div>
                            <div class="progress mt-3" style="height: 4px;"><div class="progress-bar bg-warning" style="width: 100%"></div></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- ADMIN LIST -->
                    <div class="col-lg-8" data-aos="fade-right" id="debtor-section">
                        <div class="super-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-800 mb-0">Identity & Debtor Repository</h5>
                                <div class="badge bg-light text-dark fw-800 rounded-pill px-3 py-2 border">ACTIVE REPOSITORY</div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table-mission">
                                    <thead>
                                        <tr>
                                            <th>Security Principal</th>
                                            <th>Email Protocol</th>
                                            <th>Assigned Block</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($admins) > 0): foreach($admins as $admin): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="admin-avatar <?php echo $admin['status'] == 'active' ? 'bg-primary' : 'bg-warning'; ?>">
                                                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-800 text-dark"><?php echo htmlspecialchars($admin['username']); ?></div>
                                                        <div class="small text-muted fw-600">ID: #<?php echo $admin['id']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small fw-700 text-secondary"><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td>
                                                <?php if($admin['assigned_block']): ?>
                                                    <span class="block-badge"><i class="fas fa-building me-1"></i> Block <?php echo htmlspecialchars($admin['assigned_block']); ?></span>
                                                <?php else: ?>
                                                    <span class="block-badge text-primary bg-primary-subtle"><i class="fas fa-globe me-1"></i> FULL SYSTEM</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-3 py-2 fw-800 <?php echo $admin['status'] == 'active' ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning'; ?>">
                                                    <?php echo strtoupper($admin['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm rounded-circle shadow-sm" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                                    <ul class="dropdown-menu border-0 shadow-lg p-2 rounded-3">
                                                        <li><a class="dropdown-item fw-700 small rounded-2 mb-1" href="javascript:void(0)" onclick="editAdminAccess('<?php echo $admin['id']; ?>', '<?php echo htmlspecialchars($admin['username']); ?>', '<?php echo $admin['status']; ?>', '<?php echo $admin['email']; ?>', '<?php echo $admin['assigned_block']; ?>')"><i class="fas fa-key me-2 text-primary"></i> Edit Access</a></li>
                                                        <li><a class="dropdown-item fw-700 small text-danger rounded-2" href="javascript:void(0)" onclick="confirmDelete(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')"><i class="fas fa-user-slash me-2"></i> Terminate</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="5" class="text-center py-5">
                                            <div class="opacity-50 mb-3"><i class="fas fa-users-slash fa-3x"></i></div>
                                            <div class="text-muted fw-700">No administrative principals detected.</div>
                                        </td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- SECURITY AUDIT -->
                    <div class="col-lg-4" data-aos="fade-left">
                        <div class="super-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-800 mb-0">Security Audit Feed</h5>
                                <i class="fas fa-stream text-muted"></i>
                            </div>
                            <div class="audit-feed" style="max-height: 520px; overflow-y: auto;">
                                <?php if(count($audit_logs) > 0): foreach($audit_logs as $log): ?>
                                <div class="audit-entry shadow-sm">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-primary bg-opacity-10 text-primary small fw-800"><?php echo strtoupper($log['action']); ?></span>
                                        <small class="text-muted fw-800"><?php echo date('H:i', strtotime($log['created_at'])); ?></small>
                                    </div>
                                    <div class="small fw-700 text-dark mb-1 lh-sm"><?php echo htmlspecialchars($log['details']); ?></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted fw-600">User: <?php echo htmlspecialchars($log['username']); ?></div>
                                        <div class="small text-muted fw-600" style="font-size: 0.65rem;">IP: <?php echo $log['ip_address']; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                <div class="text-center py-5 text-muted fw-700">No security events logged.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Admin Registration Modal -->
    <div class="modal fade" id="newAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-user-shield me-2"></i>Deploy Block Debtor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="adminRegisterForm">
                        <div class="mb-3">
                            <label class="form-label fw-800 small text-muted">IDENTIFIER NAME</label>
                            <input type="text" name="username" class="form-control rounded-3 border-light bg-light py-2 px-3 fw-600" placeholder="e.g. jdoe_admin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-800 small text-muted">EMAIL PROTOCOL</label>
                            <input type="email" name="email" class="form-control rounded-3 border-light bg-light py-2 px-3 fw-600" placeholder="admin@domain.com" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-800 small text-muted">ACCESS CLEARANCE</label>
                                <select name="status" class="form-select rounded-3 border-light bg-light py-2 px-3 fw-600">
                                    <option value="active">Active Tier</option>
                                    <option value="pending">Pending Validation</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-800 small text-muted">DEBTOR BLOCK LIMIT</label>
                                <select name="assigned_block" class="form-select rounded-3 border-light bg-light py-2 px-3 fw-600">
                                    <option value="none">Full System Access</option>
                                    <option value="1">Block 1 Only</option>
                                    <option value="2">Block 2 Only</option>
                                    <option value="3">Block 3 Only</option>
                                    <option value="4">Block 4 Only</option>
                                    <option value="5">Block 5 Only</option>
                                    <option value="6">Block 6 Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-800 small text-muted">SECURITY KEY</label>
                            <input type="password" name="password" class="form-control rounded-3 border-light bg-light py-2 px-3 fw-600" placeholder="Minimum 8 characters" required>
                            <input type="hidden" name="confirm_password" id="hidden_confirm">
                        </div>
                        <div id="registerMessage"></div>
                        <button type="submit" id="deployBtn" class="btn btn-primary w-100 py-3 fw-800 rounded-3 shadow-sm">EXECUTE DEPLOYMENT</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Access Modal -->
    <div class="modal fade" id="editAccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-key me-2"></i>Edit Access Clearance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editAccessForm">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-800 small text-muted">PRINCIPAL</label>
                                <input type="text" id="edit_username" name="username" class="form-control rounded-3 border-0 bg-light py-2 px-3 fw-700" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-800 small text-muted">EMAIL</label>
                                <input type="email" id="edit_email" name="email" class="form-control rounded-3 border-0 bg-light py-2 px-3 fw-700">
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-800 small text-muted">STATUS UPDATE</label>
                                <select name="status" id="edit_status" class="form-select rounded-3 border-light py-2 px-3 fw-600">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-800 small text-muted">BLOCK PERMISSION</label>
                                <select name="assigned_block" id="edit_block" class="form-select rounded-3 border-light py-2 px-3 fw-600">
                                    <option value="none">Full System Access</option>
                                    <option value="1">Block 1 Only</option>
                                    <option value="2">Block 2 Only</option>
                                    <option value="3">Block 3 Only</option>
                                    <option value="4">Block 4 Only</option>
                                    <option value="5">Block 5 Only</option>
                                    <option value="6">Block 6 Only</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-3 fw-800 rounded-3">UPDATE CLEARANCE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        AOS.init({ duration: 800, once: true });
        
        // Real-time Professional Clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour12: false });
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();
        $('#adminRegisterForm').submit(function(e) {
            e.preventDefault();
            const pass = $(this).find('input[name="password"]').val();
            $('#hidden_confirm').val(pass); // Set confirm password to match
            
            const formData = new FormData(this);
            const btn = $('#deployBtn');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>DEPLOYING...');
            
            fetch('ajax/register-admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({icon: 'success', title: 'Deployed!', text: data.message}).then(() => location.reload());
                } else {
                    Swal.fire({icon: 'error', title: 'Deployment Failure', text: data.message});
                    btn.prop('disabled', false).html('EXECUTE DEPLOYMENT');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'System communication failure.', 'error');
                btn.prop('disabled', false).html('EXECUTE DEPLOYMENT');
            });
        });

        function editAdminAccess(id, username, status, email, block) {
            $('#edit_id').val(id);
            $('#edit_username').val(username);
            $('#edit_email').val(email);
            $('#edit_status').val(status);
            $('#edit_block').val(block || 'none');
            new bootstrap.Modal('#editAccessModal').show();
        }

        $('#editAccessForm').submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('ajax/update-admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) Swal.fire('Updated', data.message, 'success').then(() => location.reload());
                else Swal.fire('Error', data.message, 'error');
            });
        });

        function confirmDelete(id, username) {
            Swal.fire({
                title: 'Terminate Access?',
                html: `Principal <strong>${username}</strong> will be removed from registry.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, terminate'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('ajax/delete-admin-ajax.php?id=' + id, {method: 'POST'})
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) Swal.fire('Terminated', 'Access has been revoked.', 'success').then(() => location.reload());
                        else Swal.fire('Error', data.message, 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>