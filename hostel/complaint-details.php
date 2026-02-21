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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Details | HostelMS</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #2e59d9;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            overflow-x: hidden;
            color: #5a5c69;
        }

        /* Sidebar & Header Setup */
        .ts-main-content {
            display: flex;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
        }

        .content-wrapper {
            flex: 1;
            padding: 1.5rem;
            transition: all 0.3s;
            width: 100%;
        }

        /* Card Styles */
        .detail-card { 
            border-radius: 0.5rem; 
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); 
            border: none; 
            margin-bottom: 25px; 
            background: #fff;
            overflow: hidden;
        }
        .detail-card .card-header { 
            background-color: var(--primary-color);
            color: #fff; 
            font-weight: 700; 
            font-size: 1.05rem; 
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-card .card-body { padding: 1.5rem; }
        
        .detail-table { margin-bottom: 0; }
        .detail-table th { 
            background: #f8f9fc; 
            font-weight: 600; 
            color: #4e73df; 
            width: 35%; 
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }
        .detail-table td { 
            color: #5a5c69; 
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }

        .history-table th { 
            background: #eaecf4; 
            color: #858796;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }
        .history-table td { vertical-align: middle; }
        
        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #858796;
            margin-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 8px;
        }

        .chat-bubble {
            background-color: #f8f9fc;
            border-left: 4px solid var(--primary-color);
            padding: 15px 20px;
            border-radius: 0.5rem;
            margin-bottom: 20px;
        }

        .action-btns .btn {
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            border-radius: 0.35rem;
        }
    </style>
</head>

<body>
    <?php include("includes/header.php");?>

    <div class="ts-main-content">
        <?php include("includes/sidebar.php");?>
        
        <div class="content-wrapper">
            <div class="container-fluid" id="print-area">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4 d-print-none">
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-alt text-primary me-2"></i>Complaint Details</h1>
                    <div class="action-btns mt-3 mt-sm-0">
                        <button onclick="window.history.back()" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Go Back
                        </button>
                        <button onclick="window.print()" class="btn btn-primary shadow-sm">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                <?php    
                $aid = $_SESSION['id'];
                $cid = intval($_GET['cid']);
                
                $ret = "SELECT * FROM complaints WHERE id=? AND userId=?";
                $stmt = $mysqli->prepare($ret);
                $stmt->bind_param('is', $cid, $aid);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if($row = $res->fetch_object()): 
                    $cstatus = $row->complaintStatus;
                ?>
                <!-- Main Detail Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <div>
                            <i class="fas fa-hashtag me-2"></i> <?php echo htmlspecialchars($row->ComplainNumber); ?>
                        </div>
                        <div style="font-size: 0.9rem;">
                            Filed on: <?php echo date('F d, Y', strtotime($row->registrationDate)); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <div class="row mb-4">
                            <!-- Basic Information -->
                            <div class="col-md-6 mb-4 mb-md-0">
                                <div class="section-title"><i class="fas fa-info-circle me-2"></i> Basic Information</div>
                                <table class="table detail-table">
                                    <tr>
                                        <th>Complaint Type</th>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row->complaintType); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Current Status</th>
                                        <td>
                                            <?php
                                            if(empty($cstatus)):
                                                echo '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fas fa-info-circle me-1"></i> New</span>';
                                            elseif(strtolower($cstatus) == 'in process' || strtolower($cstatus) == 'in progress'):
                                                echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-spinner fa-spin me-1"></i> In Process</span>';
                                            elseif(strtolower($cstatus) == 'closed'):
                                                echo '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> Closed</span>';
                                            else:
                                                echo '<span class="badge bg-secondary px-3 py-2 rounded-pill">'.htmlspecialchars($cstatus).'</span>';
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Attachment / File</th>
                                        <td>
                                            <?php if(!empty($row->complaintDoc)): ?>
                                                <a href="comnplaintdoc/<?php echo htmlspecialchars($row->complaintDoc); ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 8px;">
                                                    <i class="fas fa-download me-1"></i> View Attached File
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic"><i class="fas fa-times-circle me-1"></i> No attachment provided</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Detailed Description -->
                            <div class="col-md-6">
                                <div class="section-title"><i class="fas fa-align-left me-2"></i> Your Message</div>
                                <div class="chat-bubble">
                                    <?php echo nl2br(htmlspecialchars($row->complaintDetails ?? 'No details provided.')); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Replies & Action History -->
                        <div class="section-title mt-4"><i class="fas fa-comments me-2"></i> Admin Replies & Action History</div>
                        <?php
                        $query = "SELECT * FROM complainthistory WHERE complaintid=? ORDER BY postingDate DESC";
                        $stmt1 = $mysqli->prepare($query);
                        $stmt1->bind_param('i', $cid);
                        $stmt1->execute();
                        $res1 = $stmt1->get_result();
                        
                        if($res1->num_rows > 0): 
                        ?>
                        <div class="table-responsive">
                            <table class="table history-table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="15%">Date & Time</th>
                                        <th width="15%">Status Updated To</th>
                                        <th width="70%">Admin's Reply / Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row1 = $res1->fetch_object()): ?>
                                    <tr>
                                        <td><i class="far fa-clock me-1 text-muted"></i> <?php echo date('M d, Y h:i A', strtotime($row1->postingDate)); ?></td>
                                        <td>
                                            <span class="badge bg-secondary px-2 py-1 rounded-pill shadow-sm">
                                                <?php echo htmlspecialchars($row1->compalintStatus); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if(!empty($row1->complaintRemark)): ?>
                                                <div class="p-3 bg-white border rounded shadow-sm">
                                                    <strong class="text-primary d-block mb-1"><i class="fas fa-user-shield me-1"></i> Admin says:</strong>
                                                    <?php echo nl2br(htmlspecialchars($row1->complaintRemark)); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Status updated without a message.</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="alert text-center py-4" style="background-color: #f8f9fc; border: 1px dashed #d1d3e2; color: #858796;">
                                <i class="fas fa-inbox fa-3x mb-3 text-gray-300 d-block"></i>
                                <strong>No replies yet.</strong><br>
                                The admin has not taken any action on this complaint yet. You will see their replies here once they do.
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-danger shadow-sm border-left-danger" style="border-radius: 0.5rem;">
                        <i class="fas fa-exclamation-triangle me-2"></i> We couldn't find this complaint. It might have been deleted or doesn't belong to you.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* Print Styles */
        @media print {
            body { background: white !important; margin: 0; padding: 0; }
            .ts-main-content { margin-top: 0 !important; }
            .sidebar, .brand, .d-print-none { display: none !important; }
            .content-wrapper { flex: 0 0 100%; max-width: 100%; padding: 0; margin-left: 0 !important; }
            .detail-card { box-shadow: none !important; border: 1px solid #ddd; }
            .card-header { background-color: #f8f9fc !important; color: #333 !important; }
            .chat-bubble { border: 1px solid #ddd; }
        }
    </style>
</body>
</html>
