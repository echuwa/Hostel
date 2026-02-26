<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
	$id=$_GET['del'];
	$adn="delete from userregistration where regNo=?";
		$stmt= $mysqli->prepare($adn);
		$stmt->bind_param('s',$id);
        $stmt->execute();
        $stmt->close();	   
}

if(isset($_GET['approve']))
{
	$id=$_GET['approve'];
	$adn="UPDATE userregistration SET status='Active' WHERE regNo=?";
	$stmt= $mysqli->prepare($adn);
	$stmt->bind_param('s',$id);
    if($stmt->execute()) {
        echo "<script>alert('Student account activated successfully');</script>";
    }
    $stmt->close();	   
}

if(isset($_GET['toggle_fee']))
{
	$id=$_GET['toggle_fee'];
	$adn="UPDATE userregistration SET fee_status = NOT fee_status WHERE regNo=?";
	$stmt= $mysqli->prepare($adn);
	$stmt->bind_param('s',$id);
    if($stmt->execute()) {
        echo "<script>alert('Fee status updated successfully');</script>";
        echo "<script>window.location.href='manage-students.php';</script>";
    }
    $stmt->close();	   
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="theme-color" content="#f5f6fa">
	<title>Manage Students | HostelMS</title>
    
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
                            <i class="fas fa-users-cog"></i>
                            Manage Students
                        </h1>
                    </div>
                    <div class="header-right">
                        <div class="date-filter">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Table Panel -->
                <div class="card-panel">
                    <div class="card-header">
                        <div class="card-title">Registered Students Details</div>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="zctb" class="table table-modern" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Reg No</th>
                                    <th>Contact</th>
                                    <th>Room</th>
                                    <th>Seater</th>
                                    <th>Fee Status</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php	
                                $ret="SELECT u.regNo, u.firstName, u.middleName, u.lastName, u.contactNo, u.status, u.fee_status, u.payment_status, r.roomno, r.seater, r.stayfrom FROM userregistration u LEFT JOIN registration r ON u.regNo = r.regno";
                                $stmt= $mysqli->prepare($ret) ;
                                $stmt->execute() ;
                                $res=$stmt->get_result();
                                $cnt=1;
                                while($row=$res->fetch_object())
                                {
                                ?>
                                <tr>
                                    <td><?php echo $cnt;?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo $row->firstName;?> <?php echo $row->lastName;?></div>
                                        <small style="opacity:0.7;"><?php echo $row->middleName;?></small>
                                    </td>
                                    <td><?php echo $row->regNo;?></td>
                                    <td><?php echo $row->contactNo;?></td>
                                    <td><?php echo $row->roomno ? $row->roomno : '<span style="opacity:0.5">-</span>';?></td>
                                    <td><?php echo $row->seater ? $row->seater : '<span style="opacity:0.5">-</span>';?></td>
                                    <td>
                                        <?php if($row->fee_status == 1): ?>
                                            <span class="badge-status" style="background:var(--success-light); color:var(--success);">Eligible</span>
                                        <?php else: ?>
                                            <span class="badge-status" style="background:var(--danger-light); color:var(--danger);">Ineligible</span>
                                        <?php endif; ?>
                                        <div class="small" style="font-size: 0.7rem; margin-top: 3px;"><?php echo $row->payment_status; ?></div>
                                    </td>
                                    <td>
                                        <?php if(strtolower($row->status) == 'active'): ?>
                                            <span class="badge-status active">Active</span>
                                        <?php elseif(strtolower($row->status) == 'pending'): ?>
                                            <span class="badge-status pending">Pending</span>
                                        <?php else: ?>
                                            <span class="badge-status blocked"><?php echo $row->status; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;">
                                            <a href="manage-students.php?toggle_fee=<?php echo $row->regNo;?>" class="action-btn" title="Toggle Fee Status" style="background:#eef2ff; color:#4361ee;" onclick="return confirm('Do you want to update the fee status for this student?');">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <a href="student-details.php?regno=<?php echo $row->regNo;?>" class="action-btn" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if(strtolower($row->status) != 'active'): ?>
                                                <a href="manage-students.php?approve=<?php echo $row->regNo;?>" class="action-btn approve" title="Approve Student" onclick="return confirm('Do you want to activate this student?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="manage-students.php?del=<?php echo $row->regNo;?>" class="action-btn delete" title="Delete Record" onclick="return confirm('Do you want to delete?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

	<!-- Loading Scripts -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
	
    <script>
    $(document).ready(function() {
        $('#zctb').DataTable({
            "language": {
                "search": "",
                "searchPlaceholder": "Search students..."
            },
            "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    });
    </script>
</body>
</html>
