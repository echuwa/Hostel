<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
	$id=intval($_GET['del']);
	$adn="delete from registration where regNo=?";
		$stmt= $mysqli->prepare($adn);
		$stmt->bind_param('i',$id);
        $stmt->execute();
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
	<title>Student Feedback | HostelMS</title>
    
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
                            <i class="fas fa-comment-dots"></i>
                            Student Feedback
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
                        <div class="card-title" style="font-size: 1.1rem; font-weight: 700; color: #2d3748;">All Student Feedbacks</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="table-responsive">
                        <table id="zctb" class="table table-modern" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Reg No</th>
                                    <th>Room No</th>
                                    <th>Seater</th>
                                    <th>Feedback Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php	
                                $aid=$_SESSION['id'];
                                $ret="select * from registration
                                JOIN userregistration on userregistration.email=registration.emailid
                                join feedback on userregistration.id=feedback.userId";
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
                                    <td><?php echo $row->regno;?></td>
                                    <td><?php echo $row->roomno;?></td>
                                    <td><?php echo $row->seater;?></td>
                                    <td><?php echo $row->postinDate;?></td>
                                    <td>
                                        <a href="feedback-details.php?regno=<?php echo $row->regno;?>&uid=<?php echo $row->userId;?>" class="action-btn" title="View Full Details" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                "searchPlaceholder": "Search feedbacks..."
            },
            "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    });
    </script>
</body>
</html>
