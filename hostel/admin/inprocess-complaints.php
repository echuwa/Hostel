<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#f5f6fa">
    <title>In Process Complaints | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .complaint-item-row { 
            background: #fff; 
            margin-bottom: 12px; 
            padding: 16px 24px; 
            border-radius: 16px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .complaint-item-row:hover {
            border-color: #4361ee;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }
        .complaint-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: #fffbeb; color: #d97706; 
            font-size: 1.2rem; margin-right: 18px;
        }
        .info-card { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 10px; }
        .info-label { display: block; font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 3px; }
        .info-val { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
        .modal-content { border-radius: 24px; border: none; }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">In Process Complaints</h2>
                        <p class="text-muted small mb-0">Track and resolve active student issues</p>
                    </div>
                </div>

                <div class="mb-4 position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="complaintSearch" class="form-control ps-5 py-3 border-0 shadow-sm" placeholder="Search complaints..." style="border-radius: 16px;">
                </div>

                <div id="complaintList">
                    <?php  
                    $ret="SELECT c.*, u.firstName, u.lastName, (SELECT roomno FROM registration WHERE emailid = u.email ORDER BY id DESC LIMIT 1) as roomno FROM complaints c JOIN userregistration u ON c.userId = u.id WHERE c.complaintStatus IN ('In Process', 'In Progress') ORDER BY c.registrationDate DESC";
                    $res=$mysqli->query($ret);
                    if($res->num_rows > 0):
                        while($row=$res->fetch_object()):
                    ?>
                    <div class="complaint-item-row" onclick='openComplaintInfo(<?php echo json_encode($row); ?>)'>
                        <div class="d-flex align-items-center">
                            <div class="complaint-icon">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row->complaintType); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?> • Room <?php echo $row->roomno; ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold small text-warning mb-1">IN PROCESS</div>
                            <small class="text-muted" style="font-size:0.7rem;"><?php echo date('d-m-Y', strtotime($row->registrationDate)); ?></small>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <div class="bg-white rounded-4 p-5 text-center shadow-sm">
                        <i class="fas fa-tasks text-muted fs-1 mb-3"></i>
                        <h4 class="fw-bold">No In-Process Complaints</h4>
                        <p class="text-muted">Currently there are no complaints being worked on.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Complaint Modal -->
    <div class="modal fade" id="complaintModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">Complaint Details</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="info-card">
                                <label class="info-label">Student</label>
                                <div id="mStudent" class="info-val"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-card">
                                <label class="info-label">Room</label>
                                <div id="mRoom" class="info-val"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card mb-4">
                        <label class="info-label">Problem Description</label>
                        <div id="mDesc" class="info-val" style="font-weight: 500; font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;"></div>
                    </div>

                    <form id="actionForm">
                        <input type="hidden" id="mCid" name="cid">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Update Status</label>
                            <select name="cstatus" id="mStatus" class="form-select rounded-3 border-0 bg-light" required>
                                <option value="In Process" selected>Keep In Process</option>
                                <option value="Closed">Mark Closed</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Remark / Reply</label>
                            <textarea name="remark" id="mRemark" class="form-control rounded-3 border-0 bg-light" rows="4" placeholder="Update student on progress..."></textarea>
                        </div>
                        <button type="button" onclick="submitAction()" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">Update Complaint Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    function openComplaintInfo(data) {
        document.getElementById('mCid').value = data.id;
        document.getElementById('mStudent').innerText = data.firstName + ' ' + data.lastName;
        document.getElementById('mRoom').innerText = 'Room ' + data.roomno;
        document.getElementById('mDesc').innerText = data.complaintDetails;
        
        const modal = new bootstrap.Modal(document.getElementById('complaintModal'));
        modal.show();
    }

    function submitAction() {
        const formData = {
            action: 'update_complaint',
            cid: $('#mCid').val(),
            cstatus: $('#mStatus').val(),
            remark: $('#mRemark').val()
        };

        $.post('ajax/complaint-actions.php', formData, function(res) {
            const data = JSON.parse(res);
            if(data.status === 'success') {
                Swal.fire('Success', 'Complaint updated successfully', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', 'Failed to update: ' + data.msg, 'error');
            }
        });
    }

    $("#complaintSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".complaint-item-row").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    </script>
</body>
</html>
