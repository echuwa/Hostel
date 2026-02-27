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
    <title>My Complaints | Hostel Management</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/student-modern.css">
    
    <style>
        .complaint-card-modern {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .complaint-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }
        .icon-box {
            width: 56px; height: 56px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-right: 20px;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeInLeft">
                    <div>
                        <h2 class="section-title">My Complaints</h2>
                        <p class="section-subtitle">Track and manage your submitted support tickets</p>
                    </div>
                    <a href="register-complaint.php" class="btn-modern btn-modern-primary px-4 shadow-lg">
                        <i class="fas fa-plus me-2"></i> File New Complaint
                    </a>
                </div>

                <div class="mb-5 animate__animated animate__fadeInUp">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-4 text-muted"></i>
                        <input type="text" id="complaintSearch" class="form-control rounded-pill ps-5 py-3 border-0 shadow-sm" placeholder="Search by ticket number or complaint type...">
                    </div>
                </div>

                <div id="complaintList" class="row">
                    <?php
                    $aid = $_SESSION['user_id'] ?? $_SESSION['id'];
                    $ret = "SELECT c.*, 
                            (SELECT complaintRemark FROM complainthistory WHERE complaintid = c.id ORDER BY postingDate DESC LIMIT 1) as adminRemark,
                            (SELECT postingDate FROM complainthistory WHERE complaintid = c.id ORDER BY postingDate DESC LIMIT 1) as adminRemarkDate
                            FROM complaints c WHERE c.userId=? ORDER BY c.registrationDate DESC";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if($res->num_rows > 0):
                        while($row = $res->fetch_object()):
                            $status = $row->complaintStatus ?: 'New';
                            $iconClass = 'fas fa-exclamation-circle';
                            $themeColor = 'primary';
                            
                            if(strtolower($status) === 'in process' || strtolower($status) === 'in progress') {
                                $iconClass = 'fas fa-spinner fa-spin';
                                $themeColor = 'warning';
                            } elseif(strtolower($status) === 'closed') {
                                $iconClass = 'fas fa-check-circle';
                                $themeColor = 'success';
                            }
                    ?>
                    <div class="col-12 animate__animated animate__fadeInUp">
                        <div class="complaint-card-modern" onclick='viewDetails(<?php echo json_encode($row); ?>)'>
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-<?php echo $themeColor; ?>-light text-<?php echo $themeColor; ?>">
                                        <i class="<?php echo $iconClass; ?>"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-800 text-dark"><?php echo htmlspecialchars($row->complaintType); ?></h5>
                                        <div class="small text-muted fw-600">
                                            <span class="text-primary">#<?php echo $row->ComplainNumber; ?></span> • 
                                            <i class="far fa-calendar-alt ms-1 me-1"></i> <?php echo date('d M Y', strtotime($row->registrationDate)); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-end d-none d-sm-block">
                                        <?php if($row->adminRemark): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-800">REPLIED</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="badge-modern badge-modern-<?php echo $themeColor; ?> px-4 py-2">
                                        <?php echo strtoupper($status); ?>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted opacity-50 ms-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="bg-gray-light d-inline-flex p-4 rounded-circle mb-4">
                            <i class="fas fa-clipboard-list text-gray fa-3x"></i>
                        </div>
                        <h4 class="fw-800">No Complaints Found</h4>
                        <p class="text-muted mb-4">You haven't submitted any complaints yet. If you have any issues,<br>feel free to report them using the button above.</p>
                        <a href="register-complaint.php" class="btn-modern btn-modern-primary d-inline-flex px-5 shadow-lg">
                            File a Complaint
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">Complaint Details</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="info-panel">
                        <span class="info-label">Current Status</span>
                        <div id="mStatus" class="fw-bold" style="font-size: 1.1rem;"></div>
                    </div>

                    <div class="info-panel">
                        <span class="info-label">Your Problem Description</span>
                        <div id="mDesc" class="info-desc" style="white-space: pre-wrap; line-height: 1.6;"></div>
                    </div>

                    <div id="remarkSection" class="p-3 border border-success border-opacity-25 rounded-4 mb-3" style="background: #f0fdf4; display:none;">
                        <span class="info-label text-success">Admin Response</span>
                        <div id="mRemark" class="info-desc" style="color: #166534;"></div>
                        <div id="mRemarkDate" class="text-muted mt-2" style="font-size: 0.7rem;"></div>
                    </div>

                    <button type="button" class="btn btn-light w-100 rounded-pill py-3 fw-bold" data-bs-dismiss="modal">Close Details</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function viewDetails(data) {
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        
        document.getElementById('mStatus').innerText = (data.complaintStatus || 'New').toUpperCase();
        
        const status = (data.complaintStatus || 'New').toLowerCase();
        let statusClass = 'text-primary fw-bold';
        if(status === 'closed') statusClass = 'text-success fw-bold';
        else if(status === 'in process' || status === 'in progress') statusClass = 'text-warning fw-bold';
        
        document.getElementById('mStatus').className = statusClass;
        document.getElementById('mDesc').innerText = data.complaintDetails;
        
        const remarkSec = document.getElementById('remarkSection');
        if(data.adminRemark) {
            remarkSec.style.display = 'block';
            document.getElementById('mRemark').innerText = data.adminRemark;
            document.getElementById('mRemarkDate').innerText = 'Replied on: ' + (data.adminRemarkDate || 'N/A');
        } else {
            remarkSec.style.display = 'none';
        }
        
        modal.show();
    }

    $("#complaintSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".complaint-card-modern").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    </script>
</body>
</html>