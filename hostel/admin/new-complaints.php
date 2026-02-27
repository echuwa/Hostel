<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Get count of new complaints for the badge
$new_complaints_query = "SELECT count(*) FROM complaints WHERE complaintStatus IS NULL";
$stmt = $mysqli->prepare($new_complaints_query);
$stmt->execute();
$stmt->bind_result($new_complaints_count);
$stmt->fetch();
$stmt->close();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#f5f6fa">
    <title>New Complaints | HostelMS</title>
    
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
            background: #fef2f2; color: #ef4444; 
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

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">New Complaints</h2>
                        <p class="text-muted small mb-0">Manage newly submitted student issues</p>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-4 position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="complaintSearch" class="form-control ps-5 py-3 border-0 shadow-sm" placeholder="Search by student or complaint type..." style="border-radius: 16px;">
                </div>

                <!-- List -->
                <div id="complaintList">
                    <?php  
                    $ret="SELECT c.*, u.id as studentId, u.firstName, u.lastName, u.regNo, u.gender, u.fee_status, u.status, (SELECT roomno FROM registration WHERE emailid = u.email ORDER BY id DESC LIMIT 1) as roomno, (SELECT seater FROM registration WHERE emailid = u.email ORDER BY id DESC LIMIT 1) as seater FROM complaints c JOIN userregistration u ON c.userId = u.id WHERE c.complaintStatus is null || c.complaintStatus='New' ORDER BY c.registrationDate DESC";
                    $res=$mysqli->query($ret);
                    if($res->num_rows > 0):
                        while($row=$res->fetch_object()):
                    ?>
                    <div class="complaint-item-row" onclick='openComplaintInfo(<?php echo json_encode($row); ?>)'>
                        <div class="d-flex align-items-center">
                            <div class="complaint-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    <span class="text-primary cursor-pointer" onclick="event.stopPropagation(); openStudentInfo(<?php echo htmlspecialchars(json_encode([
                                        'id' => $row->studentId,
                                        'firstName' => $row->firstName,
                                        'lastName' => $row->lastName,
                                        'regNo' => $row->regNo,
                                        'roomno' => $row->roomno,
                                        'seater' => $row->seater,
                                        'gender' => $row->gender,
                                        'fee_status' => $row->fee_status,
                                        'status' => $row->status
                                    ])); ?>)">
                                        <?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?>
                                    </span>
                                </h6>
                                <small class="text-muted">Room <?php echo $row->roomno ?: 'N/A'; ?> • <?php echo $row->complaintType; ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold small text-primary mb-1">#<?php echo $row->ComplainNumber; ?></div>
                            <small class="text-muted" style="font-size:0.7rem;"><?php echo date('d-m-Y', strtotime($row->registrationDate)); ?></small>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <div class="bg-white rounded-4 p-5 text-center shadow-sm">
                        <i class="fas fa-check-circle text-success fs-1 mb-3"></i>
                        <h4 class="fw-bold">No New Complaints!</h4>
                        <p class="text-muted">Great job! All student issues have been addressed.</p>
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
                                <option value="In Process">Mark In Process</option>
                                <option value="Closed">Mark Closed</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Remark / Reply</label>
                            <textarea name="remark" id="mRemark" class="form-control rounded-3 border-0 bg-light" rows="4" placeholder="Type your reply to the student..."></textarea>
                        </div>
                        <button type="button" onclick="submitAction()" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">Update Complaint</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Info Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg border-0" style="border-radius: 24px;">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div id="mAvatar" class="mx-auto student-avatar mb-3 d-flex align-items-center justify-content-center fw-bold" style="width:70px; height:70px; border-radius:20px; font-size:1.5rem;"></div>
                        <h4 id="mName" class="fw-bold mb-1"></h4>
                        <span id="mReg" class="badge bg-light text-muted px-3"></span>
                    </div>

                    <div class="info-card">
                        <label class="info-label">Room Allocation</label>
                        <div id="mInfoRoom" class="info-val text-primary"></div>
                    </div>
                    <div class="info-card">
                        <label class="info-label">Payment Status</label>
                        <div id="mInfoPayment" class="info-val"></div>
                    </div>
                    <div class="info-card">
                        <label class="info-label">Account Status</label>
                        <div id="mInfoStatus" class="info-val"></div>
                    </div>

                    <div class="mt-4">
                        <a id="viewProfileBtn" href="#" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">View Full Profile</a>
                        <button type="button" class="btn btn-light w-100 rounded-pill py-2 mt-2 fw-bold" data-bs-dismiss="modal">Close</button>
                    </div>
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
        // Mark as read immediately
        $.post('ajax/complaint-actions.php', {action: 'mark_read', id: data.id});
        
        window.location.href = `complaint-details.php?id=${data.id}`;
    }

    function openStudentInfo(data) {
        const modal = new bootstrap.Modal(document.getElementById('infoModal'));
        
        // Setup Avatar
        const avatar = document.getElementById('mAvatar');
        const isFemale = data.gender === 'female';
        avatar.style.background = isFemale ? '#fff1f2' : '#eff6ff';
        avatar.style.color = isFemale ? '#e11d48' : '#3b82f6';
        avatar.innerText = data.firstName.charAt(0) + data.lastName.charAt(0);
        
        document.getElementById('mName').innerText = data.firstName + ' ' + data.lastName;
        document.getElementById('mReg').innerText = data.regNo;
        document.getElementById('mInfoRoom').innerText = data.roomno ? `Room ${data.roomno} (${data.seater} Seater)` : 'Not Assigned';
        
        const payEl = document.getElementById('mInfoPayment');
        payEl.innerText = data.fee_status == 1 ? 'Eligible' : 'Ineligible';
        payEl.className = `info-val fw-bold text-${data.fee_status == 1 ? 'success' : 'danger'}`;
        
        const statEl = document.getElementById('mInfoStatus');
        const isActive = data.status?.toLowerCase() === 'active';
        statEl.innerText = isActive ? 'Active' : (data.status || 'Pending');
        statEl.className = `info-val fw-bold text-${isActive ? 'success' : 'warning'}`;
        
        document.getElementById('viewProfileBtn').href = `student-details.php?id=${data.id}`;
        
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