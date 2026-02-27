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
    <meta name="theme-color" content="#f5f6fa">
    <title>All Complaints | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .custom-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .card-header {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: white;
            padding: 18px 25px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
        }
        .table-responsive {
            padding: 20px;
        }
        .table th {
            font-weight: 600;
            color: #4a5568;
            background-color: #f8f9fc;
            border-bottom: 2px solid #e2e8f0;
        }
        .table td {
            vertical-align: middle;
            color: #2d3748;
            border-bottom: 1px solid #edf2f7;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .badge.bg-new { background-color: rgba(231, 74, 59, 0.1) !important; color: #e74a3b !important; }
        .badge.bg-process { background-color: rgba(246, 194, 62, 0.15) !important; color: #d9a300 !important; }
        .badge.bg-closed { background-color: rgba(28, 200, 138, 0.1) !important; color: #1cc88a !important; }
        
        .btn-view {
            background: #f8f9fc;
            color: #4361ee;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 10px;
            transition: all 0.3s;
        }
        .btn-view:hover {
            background: #4361ee;
            color: white;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
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
                            <i class="fas fa-list-alt"></i> All Complaints
                        </h1>
                    </div>
                </div>

                <div class="custom-card mt-4">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list me-2"></i> Complaint Records
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="complaintsTable" class="table table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>Sno.</th>
                                        <th>Student Name</th>
                                        <th>Room No</th>
                                        <th>Complaint No.</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Reg. Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php	
                                    $aid=$_SESSION['id'];
                                    if(isset($_SESSION['assigned_block']) && !empty($_SESSION['assigned_block'])) {
                                        $block = $_SESSION['assigned_block'];
                                        $ret = "SELECT c.*, u.firstName, u.lastName, r.roomno 
                                                FROM complaints c 
                                                LEFT JOIN userregistration u ON c.userId = u.id 
                                                JOIN registration r ON u.regNo = r.regno 
                                                WHERE r.roomno LIKE '$block%' 
                                                ORDER BY c.registrationDate DESC";
                                    } else {
                                        $ret="SELECT c.*, u.firstName, u.lastName, (SELECT roomno FROM registration WHERE regno = u.regNo ORDER BY id DESC LIMIT 1) as roomno FROM complaints c LEFT JOIN userregistration u ON c.userId = u.id ORDER BY c.registrationDate DESC";
                                    }
                                    $stmt= $mysqli->prepare($ret);
                                    $stmt->execute();
                                    $res=$stmt->get_result();
                                    $cnt=1;
                                    while($row=$res->fetch_object()) {
                                    ?>
                                    <tr>
                                        <td><?php echo $cnt;?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars(($row->firstName ?? 'Unknown') . ' ' . ($row->lastName ?? 'Student'));?></td>
                                        <td>
                                            <?php if($row->roomno): ?>
                                                <span class="badge bg-light text-dark border"><i class="fas fa-door-closed text-primary me-1"></i> <?php echo htmlspecialchars($row->roomno); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted"><small>Not Assigned</small></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="text-primary fw-medium">#<?php echo htmlspecialchars($row->ComplainNumber);?></span></td>
                                        <td><?php echo htmlspecialchars($row->complaintType);?></td>
                                        <td>
                                            <?php 
                                            $cstatus=$row->complaintStatus;
                                            if(empty($cstatus)):
                                                echo '<span class="badge bg-new"><i class="fas fa-exclamation-circle me-1"></i> New</span>';
                                            elseif(strtolower($cstatus)=='in process' || strtolower($cstatus)=='in progress'):
                                                echo '<span class="badge bg-process"><i class="fas fa-spinner fa-spin me-1"></i> In Process</span>';
                                            elseif(strtolower($cstatus)=='closed' || strtolower($cstatus)=='resolved'):
                                                echo '<span class="badge bg-closed"><i class="fas fa-check-circle me-1"></i> Closed</span>';
                                            else:
                                                echo '<span class="badge bg-secondary">'.htmlspecialchars($cstatus).'</span>';
                                            endif;
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row->registrationDate));?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="complaint-details.php?cid=<?php echo $row->id;?>" class="btn btn-sm btn-view" title="View Details">
                                                    <i class="fas fa-desktop"></i>
                                                </a>
                                                <button onclick="deleteComplaint(<?php echo $row->id;?>)" class="btn btn-sm btn-outline-danger" title="Delete Complaint" style="border-radius: 6px;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                        $cnt=$cnt+1;
                                    } ?>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            $('#complaintsTable').DataTable({
                "language": {
                    "search": "Filter records:",
                    "lengthMenu": "Display _MENU_ records per page",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No records available",
                    "infoFiltered": "(filtered from _MAX_ total records)"
                },
                "order": [[ 0, "asc" ]],
                "pageLength": 10,
                "drawCallback": function(settings) {
                    $('.dataTables_paginate > .pagination').addClass('pagination-sm');
                }
            });
        });

        function deleteComplaint(cid) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This complaint and its history will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef233c',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!',
                padding: '2em',
                customClass: {
                    container: 'modern-swal-container',
                    popup: 'modern-swal-popup'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/complaint-actions.php',
                        type: 'POST',
                        data: {
                            action: 'delete_complaint',
                            cid: cid
                        },
                        success: function(response) {
                            const res = JSON.parse(response);
                            if(res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'The complaint has been removed.',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', res.msg || 'Failed to delete complaint.', 'error');
                            }
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
