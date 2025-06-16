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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #3a7bd5;
            --secondary-color: #00d2ff;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .table th {
            background-color: #f1f5fd;
            color: var(--primary-color);
        }
        
        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .btn-edit {
            color: var(--primary-color);
        }
        
        .btn-delete {
            color: #dc3545;
        }
        
        .btn-action:hover {
            background-color: rgba(0,0,0,0.05);
            transform: scale(1.1);
        }
        
        .badge-seater {
            background-color: #e3f2fd;
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0"><i class="fas fa-door-open me-2"></i> Manage Rooms</h4>
                                <a href="create-room.php" class="btn btn-light">
                                    <i class="fas fa-plus me-1"></i> Add New Room
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="rooms-table" class="table table-hover" style="width:100%">
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
                                                        <i class="fas fa-user<?php echo $row->seater > 1 ? '-friends' : ''; ?> me-1"></i>
                                                        <?php echo $row->seater; ?> Seater
                                                    </span>
                                                </td>
                                                <td><?php echo $row->room_no; ?></td>
                                                <td>₹<?php echo number_format($row->fees); ?></td>
                                                <td><?php echo date('d M Y', strtotime($row->posting_date)); ?></td>
                                                <td>
                                                    <a href="edit-room.php?id=<?php echo $row->id; ?>" class="btn-action btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage-rooms.php?del=<?php echo $row->id; ?>" 
                                                       class="btn-action btn-delete" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this room?');">
                                                        <i class="fas fa-trash-alt"></i>
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
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#rooms-table').DataTable({
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search rooms...",
                }
            });
        });
    </script>
</body>
</html>