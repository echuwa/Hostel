<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Metrics logic
$today = date('Y-m-d');
$logins_today = $mysqli->query("SELECT COUNT(*) FROM userlog WHERE DATE(loginTime) = '$today'")->fetch_row()[0];
$unique_ips = $mysqli->query("SELECT COUNT(DISTINCT userIp) FROM userlog")->fetch_row()[0];
$top_country = $mysqli->query("SELECT country, COUNT(*) as cnt FROM userlog GROUP BY country ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$top_country_name = $top_country['country'] ?? 'N/A';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#4361ee">
    <title>Access Logs | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        body { background-color: #f8fafc; }
        .log-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .metric-mini-card {
            background: #fff;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 15px;
            height: 100%;
        }
        .metric-icon {
            width: 45px; height: 45px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .table-modern thead th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.5px;
            padding: 15px 20px;
            border: none;
        }
        .table-modern tbody td {
            padding: 15px 20px;
            vertical-align: middle;
            color: #1e293b;
            font-size: 0.9rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .ip-badge {
            background: #eff6ff;
            color: #3b82f6;
            padding: 4px 10px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .time-text { font-weight: 600; color: #475569; }
        .date-text { font-size: 0.75rem; color: #94a3b8; }
        
        /* DataTables Customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 15px;
            background: #fdfdfd;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 10px;
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
                
                <!-- Header -->
                <div class="content-header mb-4">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-fingerprint"></i>
                            Security Access Logs
                        </h1>
                        <p class="text-muted small">Monitor all system login attempts and session activities</p>
                    </div>
                    <div class="header-right">
                        <div class="date-display p-2 px-3 bg-white rounded-4 shadow-sm">
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            <span class="fw-bold small"><?php echo date('d M Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="metric-mini-card">
                            <div class="metric-icon bg-primary-subtle text-primary">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo $logins_today; ?></h6>
                                <small class="text-muted">Logins Today</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-mini-card">
                            <div class="metric-icon bg-success-subtle text-success">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo $unique_ips; ?></h6>
                                <small class="text-muted">Unique Network IPs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-mini-card">
                            <div class="metric-icon bg-warning-subtle text-warning">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo $top_country_name; ?></h6>
                                <small class="text-muted">Top Traffic Origin</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Panel -->
                <div class="log-card p-4">
                    <div class="table-responsive">
                        <table id="access-table" class="table table-modern" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User Context</th>
                                    <th>Connection Info</th>
                                    <th>Location</th>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ret = "SELECT * FROM userlog ORDER BY loginTime DESC";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                $cnt = 1;
                                
                                while($row = $res->fetch_object()):
                                ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row->userEmail); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">UID: <?php echo $row->userId; ?></div>
                                    </td>
                                    <td>
                                        <span class="ip-badge"><?php echo $row->userIp; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-map-pin text-danger-emphasis" style="font-size: 0.7rem;"></i>
                                            <span><?php echo htmlspecialchars($row->city . ', ' . $row->country); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-text"><?php echo date('h:i A', strtotime($row->loginTime)); ?></div>
                                        <div class="date-text"><?php echo date('d M, Y', strtotime($row->loginTime)); ?></div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-light rounded-3" onclick='showLogDetail(<?php echo json_encode($row); ?>)'>
                                            <i class="fas fa-info-circle text-primary"></i>
                                        </button>
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

    <!-- Details Modal -->
    <div class="modal fade" id="logModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Session Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="p-4 rounded-4 mb-3 text-center" style="background: #f8fafc;">
                        <i class="fas fa-user-shield fa-2x text-primary mb-3"></i>
                        <h6 id="modalEmail" class="fw-bold mb-1"></h6>
                        <p id="modalTime" class="text-muted small mb-0"></p>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-4">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">IP Address</label>
                                <span id="modalIp" class="fw-bold color-primary"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-4">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">User ID</label>
                                <span id="modalUid" class="fw-bold color-primary"></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-4">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Origin Location</label>
                                <span id="modalLoc" class="fw-bold"></span>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 rounded-pill py-3 fw-bold mt-4 shadow-sm" data-bs-dismiss="modal">
                        Got it, Thanks
                    </button>
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
            $('#access-table').DataTable({
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search logs..."
                },
                "order": [[ 4, "desc" ]],
                "dom": '<"d-flex justify-content-between align-items-center mb-4"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>'
            });
        });

        function showLogDetail(data) {
            const modal = new bootstrap.Modal(document.getElementById('logModal'));
            document.getElementById('modalEmail').innerText = data.userEmail;
            document.getElementById('modalTime').innerText = 'Logged in at ' + data.loginTime;
            document.getElementById('modalIp').innerText = data.userIp;
            document.getElementById('modalUid').innerText = data.userId;
            document.getElementById('modalLoc').innerText = data.city + ', ' + data.country;
            modal.show();
        }
    </script>
</body>
</html>
