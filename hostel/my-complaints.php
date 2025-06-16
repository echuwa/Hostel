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
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .complaints-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .page-header {
            border-bottom: 2px solid #f5f5f5;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-new {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        .status-resolved {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .view-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .view-btn:hover {
            background-color: #bbdefb;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="complaints-container">
                            <div class="page-header">
                                <h2><i class="fas fa-exclamation-circle me-2"></i> My Complaints</h2>
                                <p class="text-muted mb-0">List of all complaints you have submitted</p>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="complaintsTable" class="table table-hover" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Complaint Number</th>
                                            <th>Complaint Type</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $aid = $_SESSION['id'];
                                        $ret = "SELECT * FROM complaints WHERE userId=? ORDER BY registrationDate DESC";
                                        $stmt = $mysqli->prepare($ret);
                                        $stmt->bind_param('i', $aid);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        $cnt = 1;
                                        
                                        while($row = $res->fetch_object()):
                                            $statusClass = '';
                                            $statusText = $row->complaintStatus ?: 'New';
                                            
                                            if($statusText === 'New') {
                                                $statusClass = 'status-new';
                                            } elseif($statusText === 'In Progress') {
                                                $statusClass = 'status-pending';
                                            } elseif($statusText === 'Resolved') {
                                                $statusClass = 'status-resolved';
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $cnt; ?></td>
                                            <td><?php echo $row->ComplainNumber; ?></td>
                                            <td><?php echo $row->complaintType; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($row->registrationDate)); ?></td>
                                            <td>
                                                <a href="complaint-details.php?cid=<?php echo $row->id; ?>" 
                                                   class="action-btn view-btn" 
                                                   title="View Details"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                        $cnt++;
                                        endwhile;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#complaintsTable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search complaints...",
                lengthMenu: "Show _MENU_ complaints per page",
                info: "Showing _START_ to _END_ of _TOTAL_ complaints",
                infoEmpty: "No complaints found",
                infoFiltered: "(filtered from _MAX_ total complaints)"
            },
            columnDefs: [
                { orderable: false, targets: [5] }
            ]
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>