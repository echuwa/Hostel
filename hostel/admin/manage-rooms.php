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

// Fetch rooms with occupancy
$room_query = "SELECT r.*, 
                (SELECT COUNT(*) FROM registration reg WHERE reg.roomno = r.room_no) AS occupied
               FROM rooms r
               ORDER BY r.room_no";
$stmt = $mysqli->prepare($room_query);
$stmt->execute();
$res = $stmt->get_result();

$rooms_by_block = [];
while ($room = $res->fetch_object()) {
    if (preg_match('/^([A-Z0-9]+[A-Z])-/i', $room->room_no, $m)) {
        $block = strtoupper($m[1]);
    } else {
        $block = 'Other';
    }
    $room->block = $block;
    $room->is_full = ($room->occupied >= $room->seater);
    $room->is_empty = ($room->occupied == 0);
    $rooms_by_block[$block][] = $room;
}
ksort($rooms_by_block);
$stmt->close();
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

        /* Block structure styles */
        .block-section {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .block-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #eef2f7 100%);
            border-bottom: 1px solid #e9ecef;
            color: #2d3748;
            padding: 15px 20px;
            font-weight: 800;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .block-stats {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .block-stats span {
            color: #4361ee;
        }
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            padding: 20px;
            background: #fafbff;
        }
        .room-card {
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
            position: relative;
            background: #fff;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .room-card:hover {
            box-shadow: 0 8px 15px rgba(0,0,0,0.08);
            transform: translateY(-4px);
            border-color: #cbd5e1;
        }
        .room-full { border-top: 5px solid #ef4444; }
        .room-empty { border-top: 5px solid #10b981; }
        .room-partial { border-top: 5px solid #f59e0b; }
        
        .room-number {
            font-size: 1.3rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .room-meta {
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            margin: 10px auto 15px;
        }
        .status-full { background: #fee2e2; color: #b91c1c; }
        .status-empty { background: #d1fae5; color: #047857; }
        .status-partial { background: #fef3c7; color: #b45309; }
        
        .room-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: auto;
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-edit { background: #eff6ff; color: #3b82f6; }
        .btn-edit:hover { background: #3b82f6; color: #fff; }
        .btn-delete { background: #fef2f2; color: #ef4444; }
        .btn-delete:hover { background: #ef4444; color: #fff; }
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
                            <i class="fas fa-layer-group"></i>
                            Manage Rooms
                        </h1>
                    </div>
                    <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                        <a href="create-room.php" class="btn btn-primary" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); border: none; padding: 10px 20px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 15px rgba(67,97,238,0.2); color: white;">
                            <i class="fas fa-layer-group"></i> Generate Block Rooms
                        </a>
                        <div class="date-filter" style="background: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 8px; color: #4a5568; font-weight: 500;">
                            <i class="fas fa-calendar-alt" style="color: #4361ee;"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Table Panel -->
                <div class="card-panel">
                    <div class="card-header" style="border-bottom: 2px solid #f0f2f5; padding-bottom: 15px;">
                        <div class="card-title" style="font-size: 1.1rem; font-weight: 700; color: #2d3748;">All Rooms Details</div>
                    </div>
                    
                    <div class="card-body">
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($rooms_by_block)): ?>
                            <div class="alert alert-info" style="border-radius:10px;"><i class="fas fa-info-circle me-2"></i>No rooms found. Get started by generating a block.</div>
                        <?php else: ?>
                            <?php foreach ($rooms_by_block as $block_name => $block_rooms): ?>
                            <div class="block-section">
                                <div class="block-header">
                                    <div>
                                        <i class="fas fa-building me-2" style="color:#4361ee;"></i>
                                        <?php echo ($block_name === 'Other') ? 'General Rooms' : 'Block ' . htmlspecialchars($block_name); ?>
                                    </div>
                                    <div class="block-stats">
                                        <?php
                                        $total_rooms = count($block_rooms);
                                        $total_capacity = 0;
                                        $total_occupied = 0;
                                        foreach ($block_rooms as $r) {
                                            $total_capacity += $r->seater;
                                            $total_occupied += $r->occupied;
                                        }
                                        echo "Rooms: <span>$total_rooms</span> &bull; Occupancy: <span>$total_occupied / $total_capacity</span>";
                                        ?>
                                    </div>
                                </div>
                                <div class="room-grid">
                                    <?php foreach ($block_rooms as $rm): ?>
                                    <?php
                                    if ($rm->is_empty) {
                                        $status_class = 'room-empty';
                                        $badge_class = 'status-empty';
                                        $status_text = 'EMPTY';
                                    } elseif ($rm->is_full) {
                                        $status_class = 'room-full';
                                        $badge_class = 'status-full';
                                        $status_text = 'FULL';
                                    } else {
                                        $status_class = 'room-partial';
                                        $badge_class = 'status-partial';
                                        $status_text = 'PARTIAL (' . $rm->occupied . '/' . $rm->seater . ')';
                                    }
                                    ?>
                                    <div class="room-card <?php echo $status_class; ?>">
                                        <div class="room-number"><?php echo htmlspecialchars($rm->room_no); ?></div>
                                        <div class="status-badge <?php echo $badge_class; ?>">
                                            <i class="fas <?php echo $rm->is_empty ? 'fa-door-open' : ($rm->is_full ? 'fa-ban' : 'fa-users'); ?>"></i>
                                            <?php echo $status_text; ?>
                                        </div>
                                        <div class="room-meta">
                                            <span><i class="fas fa-bed text-muted"></i> <?php echo $rm->seater; ?> Seater</span>
                                            <span><i class="fas fa-money-bill-wave text-muted"></i> Tsh. <?php echo number_format($rm->fees); ?>/=</span>
                                        </div>
                                        <div class="room-actions">
                                            <a href="edit-room.php?id=<?php echo $rm->id; ?>" class="btn-action btn-edit" title="Edit Room">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage-rooms.php?del=<?php echo $rm->id; ?>" class="btn-action btn-delete" title="Delete Room" onclick="return confirm('Are you sure you want to delete room <?php echo htmlspecialchars($rm->room_no, ENT_QUOTES); ?>?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Remove datatables as we use block layout -->
</body>
</html>