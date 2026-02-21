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
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        /* Table overrides for light theme */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: #fff;
            border: 1px solid #e0e0e0;
            color: var(--text-main);
            border-radius: 8px;
            padding: 5px 10px;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .page-link {
            background-color: #fff;
            border-color: #e0e0e0;
            color: var(--text-muted);
        }
        
        .page-link:hover {
            background-color: var(--primary-light);
            color: var(--primary);
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
                            <i class="fas fa-exclamation-circle"></i>
                            New Complaints
                            <?php if($new_complaints_count > 0): ?>
                                <span class="badge bg-danger" style="font-size: 0.6em; vertical-align: top; margin-left:8px; padding: 5px 8px; border-radius: 20px;"><?php echo $new_complaints_count; ?> New</span>
                            <?php endif; ?>
                        </h1>
                    </div>
                    <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                        <div class="date-filter" style="background: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 8px; color: #4a5568; font-weight: 500;">
                            <i class="fas fa-calendar-alt" style="color: #4361ee;"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Table Panel -->
                <div class="card-panel">
                    <div class="card-header" style="border-bottom: 2px solid #f0f2f5; padding-bottom: 15px;">
                        <div class="card-title" style="font-size: 1.1rem; font-weight: 700; color: #2d3748;">All New Complaints Details</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="complaints-table" class="table table-modern" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>Room No</th>
                                        <th>Complaint No.</th>
                                        <th>Complaint Type</th>
                                        <th>Status</th>
                                        <th>Reg. Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php  
                                    $ret="SELECT c.*, u.firstName, u.lastName, (SELECT roomno FROM registration WHERE emailid = u.email ORDER BY id DESC LIMIT 1) as roomno FROM complaints c JOIN userregistration u ON c.userId = u.id WHERE c.complaintStatus is null";
                                    $stmt= $mysqli->prepare($ret) ;
                                    $stmt->execute();
                                    $res=$stmt->get_result();
                                    $cnt=1;
                                    while($row=$res->fetch_object()):
                                    ?>
                                    <tr>
                                        <td><?php echo $cnt++; ?></td>
                                        <td>
                                            <div style="font-weight:600;"><?php echo $row->firstName . ' ' . $row->lastName;?></div>
                                        </td>
                                        <td><?php echo $row->roomno;?></td>
                                        <td><span style="font-family:monospace; font-weight:bold; color:var(--primary);"><?php echo $row->ComplainNumber;?></span></td>
                                        <td><?php echo $row->complaintType;?></td>
                                        <td>
                                            <span class="badge" style="background-color:rgba(231, 74, 59, 0.1); color:#e74a3b; font-size:0.85em; padding:6px 12px; border-radius:20px; font-weight:600;"><i class="fas fa-exclamation-circle me-1"></i> New</span>
                                        </td>
                                        <td><?php echo date('d-m-Y', strtotime($row->registrationDate));?></td>
                                        <td>
                                            <a href="complaint-details.php?cid=<?php echo $row->id;?>" class="action-btn" title="View Full Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#complaints-table').DataTable({
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search complaints..."
                },
                "order": [[ 6, "desc" ]], // Order by reg date descending
                "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            });
        });
    </script>
</body>
</html>