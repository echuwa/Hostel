<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();
?>

<!DOCTYPE html>
<html lang="en">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints | Hostel Management</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="admin/css/modern.css">
    
    <style>
        body { background-color: #f5f6fa; font-family: 'Plus Jakarta Sans', sans-serif; }
        .complaint-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .complaint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            border-color: #4361ee;
        }
        .icon-circle {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-right: 20px;
        }
        .status-badge {
            padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;
        }
        .modal-content { border-radius: 24px; border: none; }
        .info-panel { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 15px; }
        .info-label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .info-desc { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">My Complaints</h2>
                        <p class="text-muted small mb-0">Track the status of your reported issues</p>
                    </div>
                    <a href="register-complaint.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fas fa-plus me-2"></i>New Complaint
                    </a>
                </div>

                <div class="mb-4">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" id="complaintSearch" class="form-control ps-5 py-3 border-0 shadow-sm rounded-4" placeholder="Search my complaints...">
                    </div>
                </div>

                <div id="complaintList">
                    <?php
                    $aid = $_SESSION['user_id'] ?? $_SESSION['id'];
                    $ret = "SELECT * FROM complaints WHERE userId=? ORDER BY registrationDate DESC";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if($res->num_rows > 0):
                        while($row = $res->fetch_object()):
                            $status = $row->complaintStatus ?: 'New';
                            $iconClass = 'fas fa-exclamation-circle';
                            $bgClass = 'bg-primary-subtle text-primary';
                            
                            if($status === 'In Process') {
                                $iconClass = 'fas fa-spinner fa-spin';
                                $bgClass = 'bg-warning-subtle text-warning';
                            } elseif($status === 'Closed') {
                                $iconClass = 'fas fa-check-circle';
                                $bgClass = 'bg-success-subtle text-success';
                            }
                    ?>
                    <div class="complaint-card" onclick='viewDetails(<?php echo json_encode($row); ?>)'>
                        <div class="d-flex align-items-center">
                            <div class="icon-circle <?php echo $bgClass; ?>">
                                <i class="<?php echo $iconClass; ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($row->complaintType); ?></h6>
                                <div class="small text-muted">Ticket #<?php echo $row->ComplainNumber; ?> • <?php echo date('d M Y', strtotime($row->registrationDate)); ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="status-badge <?php echo $bgClass; ?> mb-1">
                                <?php echo strtoupper($status); ?>
                            </div>
                            <div class="small text-muted" style="font-size: 0.7rem;">Click to view</div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                        <i class="fas fa-clipboard-check text-muted fs-1 mb-3"></i>
                        <h5 class="fw-bold">No complaints yet</h5>
                        <p class="text-muted">You haven't submitted any complaints at the moment.</p>
                        <a href="register-complaint.php" class="btn btn-outline-primary rounded-pill px-4">File a complaint</a>
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
        document.getElementById('mStatus').className = (data.complaintStatus === 'Closed') ? 'text-success fw-bold' : ((data.complaintStatus === 'In Process') ? 'text-warning fw-bold' : 'text-primary fw-bold');
        
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
        $(".complaint-card").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    </script>
</body>
</html>
</html>