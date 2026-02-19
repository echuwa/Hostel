<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Delete room functionality
if(isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $adn = "DELETE FROM rooms WHERE id=?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();   
    $_SESSION['success'] = "Room deleted successfully";
    header("Location: manage-rooms.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="theme-color" content="#f5f6fa">
	<title>Manage Rooms | HostelMS</title>
    
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

        /* Specific badge style for this page */
        .badge-seater {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
                            <i class="fas fa-door-open"></i>
                            Manage Rooms
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
                        <div class="card-title">All Rooms Details</div>
                        <a href="create-room.php" class="btn btn-primary btn-sm" style="background:var(--primary); border:none; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-plus"></i> Add New Room
                        </a>
                    </div>
                    
                    <div class="card-body">
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table id="rooms-table" class="table table-modern" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Seater</th>
                                        <th>Room No.</th>
                                        <th>Fees (PM)</th>
                                        <th>Created On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ret = "SELECT * FROM rooms";
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    $cnt = 1;
                                    
                                    while($row = $res->fetch_object()):
                                    ?>
                                    <tr>
                                        <td><?php echo $cnt++; ?></td>
                                        <td>
                                            <span class="badge-seater">
                                                <i class="fas fa-user<?php echo $row->seater > 1 ? '-friends' : ''; ?>"></i>
                                                <?php echo $row->seater; ?> Seater
                                            </span>
                                        </td>
                                        <td><?php echo $row->room_no; ?></td>
                                        <td><?php echo $row->fees; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row->posting_date)); ?></td>
                                        <td>
                                            <div style="display:flex; gap:10px;">
                                                <a href="edit-room.php?id=<?php echo $row->id; ?>" class="action-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="manage-rooms.php?del=<?php echo $row->id; ?>" 
                                                   class="action-btn delete" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this room?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
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
            $('#rooms-table').DataTable({
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search rooms..."
                },
                "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            });
        });
    </script>
</body>
</html>