<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Code for updating rooms
if(isset($_POST['submit'])) {
    $seater = intval($_POST['seater']);
    $fees = intval($_POST['fees']);
    $id = intval($_GET['id']);
    
    $query="UPDATE rooms SET seater=?, fees=? WHERE id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iii', $seater, $fees, $id);
    
    if($stmt->execute()) {
        $_SESSION['success'] = "Room Details has been Updated successfully";
        header("Location: manage-rooms.php");
        exit();
    } else {
        $error = "Failed to update room.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Edit Room | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .room-form-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(67,97,238,.08);
            overflow: hidden;
            max-width: 780px;
            margin: 30px auto;
        }

        .room-card-header {
            background: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
            padding: 32px 36px;
            position: relative;
            overflow: hidden;
        }

        .room-card-header h2 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
        }

        .room-card-header p {
            color: rgba(255,255,255,.75);
            margin: 6px 0 0;
            font-size: 0.9rem;
        }

        .header-icon-big {
            width: 60px; height: 60px;
            background: rgba(255,255,255,.2);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff;
            margin-bottom: 14px;
        }

        .room-card-body {
            padding: 36px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            color: #2d3748;
            transition: all 0.2s;
            background: #fafbff;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67,97,238,.12);
            background: #fff;
        }

        .btn-update {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            border: none;
            color: #fff;
            padding: 13px 32px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(67,97,238,.2);
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,97,238,.4);
            color: #fff;
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
                            <i class="fas fa-edit"></i> Edit Room
                        </h1>
                    </div>
                    <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                        <a href="manage-rooms.php" class="btn btn-primary" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); border: none; padding: 10px 20px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 15px rgba(67,97,238,0.2); color: white;">
                            <i class="fas fa-arrow-left"></i> Back to Rooms
                        </a>
                    </div>
                </div>

                <div class="room-form-card">
                    <div class="room-card-header">
                        <div class="header-icon-big">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h2>Update Room Details</h2>
                        <p>Modify the details below to update the room.</p>
                    </div>

                    <div class="room-card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger" style="border-radius:10px;">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="roomForm">
                            <?php	
                            $id = intval($_GET['id']);
                            $ret = "SELECT * FROM rooms WHERE id=?";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->bind_param('i', $id);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            
                            if($row = $res->fetch_object()):
                            ?>
                            
                            <div class="mb-4">
                                <label for="rmno" class="form-label">Room Number</label>
                                <input type="text" class="form-control" name="rmno" id="rmno" value="<?php echo htmlspecialchars($row->room_no); ?>" disabled>
                                <small class="text-muted"><i class="fas fa-info-circle"></i> Room number cannot be changed.</small>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="seater" class="form-label">Seater (Beds) *</label>
                                    <input type="number" class="form-control" name="seater" id="seater" value="<?php echo intval($row->seater); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="fees" class="form-label">Fee (Per Student) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="background: #fafbff; border: 1.5px solid #e2e8f0; border-right: none;">Tsh.</span>
                                        <input type="number" class="form-control" name="fees" id="fees" value="<?php echo intval($row->fees); ?>" required style="border-left: none; border-right: none;">
                                        <span class="input-group-text" style="background: #fafbff; border: 1.5px solid #e2e8f0; border-left: none;">/=</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" name="submit" class="btn-update" id="updateBtn">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>

                            <?php else: ?>
                            <div class="alert alert-warning">Room not found.</div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Form loading state
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('updateBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;
        });
    </script>
</body>
</html>