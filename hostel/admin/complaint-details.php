<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_POST['submit'])) {
    $cid = intval($_GET['cid']);
    $cstatus = $_POST['cstatus'];
    $redproblem = $_POST['remark'];

    $query = "INSERT INTO complainthistory(complaintid, compalintStatus, complaintRemark) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iss', $cid, $cstatus, $redproblem);
    $stmt->execute();

    $query1 = "UPDATE complaints SET complaintStatus=? WHERE id=?";
    $stmt1 = $mysqli->prepare($query1);
    $stmt1->bind_param('si', $cstatus, $cid);
    $stmt1->execute();
    
    $_SESSION['msg'] = "Complaint Updated Successfully";
    header("Location: complaint-details.php?cid=" . $cid);
    exit();
}

$cid = intval($_GET['cid']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f6fa">
    <title>Complaint Details | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .detail-card { 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            border: none; 
            margin-bottom: 25px; 
            background: #fff;
            overflow: hidden;
        }
        .detail-card .card-header { 
            background: linear-gradient(135deg, #4361ee, #7b2ff7); 
            color: #fff; 
            font-weight: 700; 
            font-size: 1.05rem; 
            padding: 15px 25px;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-card .card-body { padding: 30px; }
        .detail-table th { 
            background: #f8f9fc; 
            font-weight: 600; 
            color: #495057; 
            width: 35%; 
            padding: 12px 15px;
            vertical-align: middle;
        }
        .detail-table td { 
            color: #2d3748; 
            padding: 12px 15px;
            vertical-align: middle;
        }
        .history-table th { 
            background: #e8f4f8; 
            color: #2d3748;
            font-weight: 600;
        }
        .history-table td { vertical-align: middle; }
        .btn-action { 
            background: linear-gradient(135deg, #4361ee, #7b2ff7); 
            color: #fff; 
            border: none; 
            border-radius: 8px; 
            padding: 12px 28px; 
            font-weight: 600; 
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(67,97,238,.2);
        }
        .btn-action:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,97,238,.3);
            color: #fff; 
        }
        .section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #a0aec0;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        
        @media print {
            .sidebar, .content-header, .mb-4.d-flex, .btn-action, .modal { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; }
            .detail-card { box-shadow: none !important; border: 1px solid #ddd !important; }
            .card-header { background: #f8f9fc !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { background: #fff !important; }
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
                <div class="content-header">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-desktop"></i> Complaint Details
                        </h1>
                    </div>
                    <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                        <div class="date-filter" style="background: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 8px; color: #4a5568; font-weight: 500;">
                            <i class="fas fa-calendar-alt" style="color: #4361ee;"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <button onclick="window.history.back()" class="btn btn-light" style="border-radius:8px; font-weight:600; color:#4a5568; border:1px solid #e2e8f0;">
                        <i class="fas fa-arrow-left me-2"></i> Go Back
                    </button>
                    <button onclick="window.print()" class="btn btn-light" style="border-radius:8px; font-weight:600; color:#4361ee; border:1px solid #e2e8f0;">
                        <i class="fas fa-print me-2"></i> Print Details
                    </button>
                </div>

                <?php if(isset($_SESSION['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php	
                $ret="SELECT c.*, u.firstName, u.lastName, u.email, (SELECT roomno FROM registration WHERE emailid = u.email ORDER BY id DESC LIMIT 1) as roomno FROM complaints c LEFT JOIN userregistration u ON c.userId = u.id WHERE c.id=?";
                $stmt= $mysqli->prepare($ret);
                $stmt->bind_param('i',$cid);
                $stmt->execute();
                $res=$stmt->get_result();
                
                if($row=$res->fetch_object()): 
                    $cstatus = $row->complaintStatus;
                ?>
                <!-- Complaint Detail Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <div>
                            <i class="fas fa-hashtag me-1"></i> <?php echo htmlspecialchars($row->ComplainNumber); ?>
                        </div>
                        <div style="font-size: 0.9rem; font-weight: 500;">
                            Filed on: <?php echo date('F d, Y', strtotime($row->registrationDate)); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <!-- Complaint Info -->
                            <div class="col-md-6 mb-4 mb-md-0">
                                <div class="section-title"><i class="fas fa-info-circle me-2"></i> Complaint Information</div>
                                <table class="table table-bordered detail-table">
                                    <tr>
                                        <th>Complaint Type</th>
                                        <td><?php echo htmlspecialchars($row->complaintType); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Current Status</th>
                                        <td>
                                            <?php
                                            if(empty($cstatus)):
                                                echo '<span class="badge" style="background-color:rgba(231, 74, 59, 0.1); color:#e74a3b; font-size:0.85em; padding:6px 12px; border-radius:20px;">New</span>';
                                            elseif(strtolower($cstatus)=='in process' || strtolower($cstatus)=='in progress'):
                                                echo '<span class="badge" style="background-color:rgba(246, 194, 62, 0.15); color:#d9a300; font-size:0.85em; padding:6px 12px; border-radius:20px;">In Process</span>';
                                            elseif(strtolower($cstatus)=='closed'):
                                                echo '<span class="badge" style="background-color:rgba(28, 200, 138, 0.1); color:#1cc88a; font-size:0.85em; padding:6px 12px; border-radius:20px;">Closed</span>';
                                            else:
                                                echo '<span class="badge bg-secondary" style="font-size:0.85em; padding:6px 12px; border-radius:20px;">'.htmlspecialchars($cstatus).'</span>';
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Attachment</th>
                                        <td>
                                            <?php if(!empty($row->complaintDoc)): ?>
                                                <a href="../comnplaintdoc/<?php echo htmlspecialchars($row->complaintDoc); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius:6px;">
                                                    <i class="fas fa-download me-1"></i> View / Download
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">No attachment</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Student Info -->
                            <div class="col-md-6">
                                <div class="section-title"><i class="fas fa-user-graduate me-2"></i> Student Information</div>
                                <table class="table table-bordered detail-table">
                                    <tr>
                                        <th>Student Name</th>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email Address</th>
                                        <td><?php echo htmlspecialchars($row->email ?? '—'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Room Allocated</th>
                                        <td>
                                            <?php if($row->roomno): ?>
                                                <span class="badge bg-primary" style="font-size: 0.9em; border-radius: 6px;"><i class="fas fa-door-closed me-1"></i> Room <?php echo htmlspecialchars($row->roomno); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="section-title"><i class="fas fa-align-left me-2"></i> Detailed Description</div>
                        <div style="background: #f8f9fc; border-radius: 10px; padding: 20px; color: #2d3748; line-height: 1.6; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                            <?php echo nl2br(htmlspecialchars($row->complaintDetails ?? 'No description provided by the student.')); ?>
                        </div>

                        <!-- History -->
                        <div class="section-title"><i class="fas fa-history me-2"></i> Action History</div>
                        <?php
                        $query = "SELECT * FROM complainthistory WHERE complaintid=? ORDER BY postingDate DESC";
                        $stmt1 = $mysqli->prepare($query);
                        $stmt1->bind_param('i', $cid);
                        $stmt1->execute();
                        $res1 = $stmt1->get_result();
                        
                        if($res1->num_rows > 0): 
                        ?>
                        <div class="table-responsive mb-4">
                            <table class="table history-table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="20%">Date & Time</th>
                                        <th width="15%">Status Change</th>
                                        <th width="65%">Admin Reply / Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row1 = $res1->fetch_object()): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y H:i:s', strtotime($row1->postingDate)); ?></td>
                                        <td><span class="badge bg-dark"><?php echo htmlspecialchars($row1->compalintStatus); ?></span></td>
                                        <td><?php echo nl2br(htmlspecialchars($row1->complaintRemark)); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-light text-center" style="border: 1px dashed #cbd5e1; color: #64748b;">
                                <i class="fas fa-inbox mb-2" style="font-size: 24px; opacity: 0.5;"></i><br>
                                No actions have been taken on this complaint yet.
                            </div>
                        <?php endif; ?>

                        <!-- Action Button -->
                        <?php if(empty($cstatus) || strtolower($cstatus) == 'in process' || strtolower($cstatus) == 'in progress'): ?>
                        <div class="text-end mt-4 pt-3" style="border-top: 1px solid #e2e8f0;">
                            <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#takeActionModal">
                                <i class="fas fa-reply me-2"></i> Reply to Student & Update Status
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-danger" style="border-radius: 10px;">
                        <i class="fas fa-exclamation-triangle me-2"></i> Complaint not found or you don't have access.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Take Action Modal -->
    <div class="modal fade" id="takeActionModal" tabindex="-1" aria-labelledby="takeActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <div class="modal-header" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); color: white; border-radius: 15px 15px 0 0; padding: 20px;">
                    <h5 class="modal-title" id="takeActionModalLabel"><i class="fas fa-edit me-2"></i> Update Complaint Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body" style="padding: 25px;">
                        <div class="mb-4">
                            <label class="form-label fw-bold" style="color: #4a5568;">New Status <span class="text-danger">*</span></label>
                            <select name="cstatus" class="form-select" style="border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 15px;" required>
                                <option value="" selected disabled>-- Select appropriate status --</option>
                                <option value="In Process">In Process</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="color: #4a5568;">Reply Message <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea name="remark" id="remark" placeholder="Type your reply to the student here..." rows="5" class="form-control" style="border-radius: 8px; border: 1px solid #e2e8f0; padding: 15px; resize: none;"></textarea>
                            <div class="form-text text-muted">This message will be directly visible to the student when they check their complaint details.</div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #f8f9fc; padding: 15px 25px;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Cancel</button>
                        <button type="submit" name="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); border: none; border-radius: 8px; font-weight: 600; padding: 8px 20px;">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
