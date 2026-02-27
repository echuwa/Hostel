<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Logs | Hostel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-modern.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="mb-5 animate__animated animate__fadeInLeft">
                    <h2 class="section-title">Security Access Logs</h2>
                    <p class="section-subtitle">Track your account login history and IP geography</p>
                </div>

                <div class="card-modern border-0 animate__animated animate__fadeInUp">
                    <div class="card-header-modern bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-800 mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Login History</h5>
                            <span class="badge-modern badge-modern-primary">Real-time Tracking</span>
                        </div>
                    </div>
                    <div class="card-body-modern p-0">
                        <div class="table-responsive">
                            <table id="accessTable" class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-muted small fw-800">SNO</th>
                                        <th class="py-3 text-muted small fw-800">EMAIL</th>
                                        <th class="py-3 text-muted small fw-800">IP ADDRESS</th>
                                        <th class="py-3 text-muted small fw-800">LOCATION</th>
                                        <th class="py-3 text-muted small fw-800 text-end pe-4">TIME STAMP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php	
                                    $aid = $_SESSION['user_id'] ?? $_SESSION['id'];
                                    $ret = "SELECT * FROM userlog WHERE userId=? ORDER BY loginTime DESC";
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->bind_param('i', $aid);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    $cnt = 1;
                                    while($row = $res->fetch_object()):
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-700 text-muted"><?php echo $cnt; ?></td>
                                        <td class="fw-600"><?php echo $row->userEmail; ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border rounded-pill px-3">
                                                <i class="fas fa-network-wired me-1 opacity-50"></i><?php echo $row->userIp; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-map-marker-alt text-danger me-2 opacity-50"></i>
                                                <div>
                                                    <div class="fw-700 small"><?php echo $row->city ?: 'Unknown City'; ?></div>
                                                    <div class="text-muted smaller fw-600 text-uppercase" style="font-size: 0.65rem;"><?php echo $row->country ?: 'Unknown Country'; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="fw-800 text-dark"><?php echo date('d M, Y', strtotime($row->loginTime)); ?></div>
                                            <div class="small text-muted fw-600"><?php echo date('h:i A', strtotime($row->loginTime)); ?></div>
                                        </td>
                                    </tr>
                                    <?php $cnt++; endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#accessTable').DataTable({
                "pageLength": 10,
                "order": [[4, "desc"]],
                "language": {
                    "search": "Search logs:",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        });
    </script>
</body>
</html>
