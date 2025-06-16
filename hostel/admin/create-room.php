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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Room</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #3a7bd5;
            --secondary-color: #00d2ff;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #2c65b4, #00b7eb);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 123, 213, 0.25);
        }
        
        .seater-icon {
            font-size: 1.2rem;
            margin-right: 8px;
            color: var(--primary-color);
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
                        <div class="form-container">
                            <h2 class="form-header"><i class="fas fa-door-open me-2"></i> Add New Room</h2>
                            
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
                                    <select name="seater" class="form-select" required>
                                        <option value="" selected disabled>Select Seater Type</option>
                                        <option value="1"><i class="fas fa-user seater-icon"></i> Single Seater</option>
                                        <option value="2"><i class="fas fa-user-friends seater-icon"></i> Two Seater</option>
                                        <option value="3"><i class="fas fa-users seater-icon"></i> Three Seater</option>
                                        <option value="4"><i class="fas fa-users seater-icon"></i> Four Seater</option>
                                        <option value="5"><i class="fas fa-users seater-icon"></i> Five Seater</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="rmno" class="form-label">Room Number</label>
                                    <input type="number" class="form-control" name="rmno" id="rmno" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="fee" class="form-label">Fee (Per Student)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="fee" id="fee" required>
                                    </div>
                                </div>
                                
                                <div class="col-12 text-end mt-4">
                                    <button type="submit" name="submit" class="btn btn-submit text-white">
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Add icons to select options (fallback for browsers that don't support HTML in options)
        document.addEventListener('DOMContentLoaded', function() {
            const seaterOptions = {
                1: 'Single Seater',
                2: 'Two Seater', 
                3: 'Three Seater',
                4: 'Four Seater',
                5: 'Five Seater'
            };
            
            const select = document.querySelector('select[name="seater"]');
            select.innerHTML = '<option value="" selected disabled>Select Seater Type</option>';
            
            for(const [value, text] of Object.entries(seaterOptions)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                select.appendChild(option);
            }
        });
    </script>
</body>
</html>