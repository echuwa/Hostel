<?php 
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Code for add room
if(isset($_POST['submit'])) {
    $seater = intval($_POST['seater']);
    $roomno = intval($_POST['rmno']);
    $fees = intval($_POST['fee']);
    
    // Check if room already exists
    $sql = "SELECT room_no FROM rooms WHERE room_no=?";
    $stmt1 = $mysqli->prepare($sql);
    $stmt1->bind_param('i', $roomno);
    $stmt1->execute();
    $stmt1->store_result(); 
    $row_cnt = $stmt1->num_rows;
    
    if($row_cnt > 0) {
        $_SESSION['error'] = "Room already exists";
    } else {
        $query = "INSERT INTO rooms (seater, room_no, fees) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iii', $seater, $roomno, $fees);
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Room has been added successfully";
            header("Location: manage-rooms.php");
            exit();
        } else {
            $_SESSION['error'] = "Error adding room. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="theme-color" content="#f5f6fa">
	<title>Create Room | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern CSS -->
	<link rel="stylesheet" href="css/modern.css">
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
                            Create Room
                        </h1>
                    </div>
                    <div class="header-right">
                        <div class="date-filter">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card-panel">
                            <div class="card-header">
                                <div class="card-title">Add New Room</div>
                            </div>
                            
                            <div class="card-body">
                                <?php if(isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success">
                                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="post" class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Seater Type</label>
                                        <select name="seater" class="form-select form-control" required style="height:auto;">
                                            <option value="" selected disabled>Select Seater Type</option>
                                            <option value="1">Single Seater</option>
                                            <option value="2">Two Seater</option>
                                            <option value="3">Three Seater</option>
                                            <option value="4">Four Seater</option>
                                            <option value="5">Five Seater</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="rmno" class="form-label">Room Number</label>
                                        <input type="number" class="form-control" name="rmno" id="rmno" required placeholder="Ex: 101">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label for="fee" class="form-label">Fee (Per Student)</label>
                                        <div class="input-group">
                                            <span class="input-group-text" style="background:#f8f9fa; border:1px solid #e0e0e0; border-right:none; color:var(--text-muted);">TJS</span>
                                            <input type="number" class="form-control" name="fee" id="fee" required placeholder="Ex: 2000">
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-4">
                                        <button type="submit" name="submit" class="btn btn-primary" style="background:var(--primary); border:none; padding:10px 25px; border-radius:8px;">
                                            <i class="fas fa-plus-circle me-1"></i> Create Room
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>