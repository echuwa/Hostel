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

// Unallocate student from room
if(isset($_GET['unallocate'])) {
    $reg = $_GET['unallocate'];
    $adn = "DELETE FROM registration WHERE regno=?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('s', $reg);
    $stmt->execute();
    $stmt->close();   
    $_SESSION['success'] = "Student unallocated from room successfully";
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
    if (preg_match('/^(\d+)([A-Z]+)-/i', $room->room_no, $m)) {
        $block = 'Block ' . $m[1];
        $side = 'Side ' . strtoupper($m[2]);
    } else {
        $block = 'General';
        $side = 'General Wing';
    }
    $room->block = $block;
    $room->side = $side;
    $room->is_full = ($room->occupied >= $room->seater);
    $room->is_empty = ($room->occupied == 0);
    $rooms_by_block[$block][$side][] = $room;
}
ksort($rooms_by_block);
foreach($rooms_by_block as $b => $s) {
    ksort($rooms_by_block[$b]);
}
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
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Room Grid matching Student Reg */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
            padding: 14px;
            background: #fafbff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .room-card {
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
            border: 2px solid #e2e8f0;
            background: #fff;
            position: relative;
            transition: all 0.2s ease;
        }
        /* Room Card Interactivity */
        .room-card:hover { border-color: #4361ee; box-shadow: 0 4px 15px rgba(67,97,238,0.2); transform: translateY(-2px); cursor: pointer; }
        .room-card.room-full { background: #f8fafc; border-color: #cbd5e1; }
        
        .room-card .room-number {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 6px;
        }
        
        /* Modal Style */
        .modal-content { border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .modal-header { background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%); color: white; border-top-left-radius: 20px; border-top-right-radius: 20px; padding: 20px 30px; }
        .student-item {
            display: flex; align-items: center; justify-content: space-between;
            background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 10px;
            border: 1px solid #e2e8f0;
        }
        .student-name { font-weight: 700; color: #1e293b; }
        .student-reg { font-size: 0.8rem; color: #64748b; font-family: monospace; }
        .btn-remove-stud { color: #ef4444; background: #fee2e2; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border:none; transition: all 0.2s; }
        .btn-remove-stud:hover { background: #ef4444; color: #fff; }

        .room-card .room-meta {
            font-size: 0.72rem;
            color: #718096;
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-bottom: 8px;
        }
        .room-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .avail-badge {
            background: #c6f6d5;
            color: #276749;
        }
        .full-badge {
            background: #fed7d7;
            color: #c53030;
        }
        
        .room-actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 5px;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
        .btn-action {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.8rem;
        }
        .btn-edit { background: #eff6ff; color: #3b82f6; }
        .btn-edit:hover { background: #3b82f6; color: #fff; }
        .btn-delete { background: #fef2f2; color: #ef4444; }
        .btn-delete:hover { background: #ef4444; color: #fff; }

        /* Custom Tabs & Pills Styling */
        .custom-nav-tabs { border-bottom: 2px solid #e2e8f0; gap: 8px; margin-bottom: 20px; }
        .custom-nav-tabs .nav-link { border: none; color: #64748b; font-weight: 700; border-radius: 10px 10px 0 0; padding: 12px 24px; transition: all 0.3s ease; font-size: 1.05rem; }
        .custom-nav-tabs .nav-link:hover { color: #4361ee; background: #f8fafc; }
        .custom-nav-tabs .nav-link.active { color: #4361ee; background: transparent; border-bottom: 3px solid #4361ee; }
        
        .custom-nav-pills { gap: 10px; background: #f1f5f9; padding: 8px; border-radius: 14px; display: inline-flex; flex-wrap: wrap; margin-bottom: 25px; }
        .custom-nav-pills .nav-link { border-radius: 10px; font-weight: 700; color: #475569; padding: 10px 28px; transition: all 0.3s ease; font-size: 0.95rem; }
        .custom-nav-pills .nav-link:hover { background: #e2e8f0; color: #1e293b; }
        .custom-nav-pills .nav-link.active { background: #4361ee; color: #fff; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.35); }
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
                            
                            <!-- Blocks Tabs -->
                            <ul class="nav nav-tabs custom-nav-tabs mb-4" id="blockTabs" role="tablist">
                                <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo $i===0?'active':''; ?>" style="font-weight: 600;" id="tab-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" data-bs-toggle="tab" data-bs-target="#pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" type="button" role="tab"><?php echo htmlspecialchars($block_name); ?></button>
                                    </li>
                                <?php $i++; endforeach; ?>
                            </ul>

                            <!-- Blocks Content -->
                            <div class="tab-content" id="blockTabsContent">
                                <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): ?>
                                    <div class="tab-pane fade <?php echo $i===0?'show active':''; ?>" id="pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" role="tabpanel">
                                        
                                        <!-- Sides Pills -->
                                        <ul class="nav nav-pills custom-nav-pills mb-4 mt-2" id="pills-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" role="tablist">
                                            <?php $j=0; foreach ($block_wings as $side_name => $side_rooms): ?>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link <?php echo $j===0?'active':''; ?>" id="pill-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name . $side_name); ?>" data-bs-toggle="pill" data-bs-target="#spane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name . $side_name); ?>" type="button" role="tab"><?php echo htmlspecialchars($side_name); ?></button>
                                                </li>
                                            <?php $j++; endforeach; ?>
                                        </ul>
                                        
                                        <!-- Sides Content -->
                                        <div class="tab-content" id="pills-Content-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>">
                                            <?php $j=0; foreach ($block_wings as $side_name => $side_rooms): ?>
                                                <div class="tab-pane fade <?php echo $j===0?'show active':''; ?>" id="spane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name . $side_name); ?>" role="tabpanel">
                                                    
                                                    <div class="room-grid border rounded p-3 bg-light">
                                                            <?php foreach ($side_rooms as $rm): ?>
                                                            <?php
                                                            $is_full = ($rm->occupied >= $rm->seater);
                                                            $remaining = $rm->seater - $rm->occupied;
                                                            $status_class = $is_full ? 'room-full' : 'room-available';
                                                            ?>
                                                            <div class="room-card <?php echo $status_class; ?>" onclick="showRoomDetails('<?php echo $rm->room_no; ?>', <?php echo $rm->seater; ?>)">
                                                                <div class="room-number"><?php echo htmlspecialchars($rm->room_no); ?></div>
                                                                
                                                                <div class="room-meta">
                                                                    <span><i class="fas fa-users"></i> <?php echo $rm->seater; ?> Bed</span>
                                                                    <span><i class="fas fa-money-bill-wave"></i> <?php echo number_format($rm->fees); ?>/=</span>
                                                                </div>

                                                                <?php if ($is_full): ?>
                                                                    <div class="room-badge full-badge"><i class="fas fa-ban"></i> FULL</div>
                                                                <?php else: ?>
                                                                    <div class="room-badge avail-badge"><i class="fas fa-check-circle"></i> <?php echo $remaining; ?> Left</div>
                                                                <?php endif; ?>

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
                                            <?php $j++; endforeach; ?>
                                        </div>
                                        
                                    </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Room Details Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title FW-BOLD"><i class="fas fa-door-open me-2"></i> Room <span id="m-room-no"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fw-bold">Occupancy:</span>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3" id="m-occupancy"></span>
                    </div>
                    <div id="m-student-list">
                        <!-- Loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    function showRoomDetails(roomNo, seater) {
        document.getElementById('m-room-no').textContent = roomNo;
        const listDiv = document.getElementById('m-student-list');
        listDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
        
        const myModal = new bootstrap.Modal(document.getElementById('roomModal'));
        myModal.show();
        
        fetch('get_room_students.php?room_no=' + roomNo)
            .then(response => response.json())
            .then(data => {
                document.getElementById('m-occupancy').textContent = data.length + ' / ' + seater + ' Occupied';
                
                if (data.length === 0) {
                    listDiv.innerHTML = '<div class="alert alert-light text-center py-4 border-dashed rounded-4"><i class="fas fa-ghost fa-2x mb-3 text-muted opacity-25"></i><p class="mb-0 text-muted">This room is currently empty</p></div>';
                } else {
                    let html = '';
                    data.forEach(student => {
                        html += `
                        <div class="student-item animate__animated animate__fadeInUp">
                            <div class="d-flex align-items-center">
                                <div class="avatar-small me-3" style="width:40px; height:40px; background: #e0e7ff; color:#4361ee; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                    ${student.firstName.charAt(0)}
                                </div>
                                <div>
                                    <div class="student-name">${student.firstName} ${student.lastName}</div>
                                    <div class="student-reg">${student.regNo}</div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="student-details.php?regno=${student.regNo}" class="btn-action btn-edit" title="View Profile"><i class="fas fa-user"></i></a>
                                <button onclick="removeStudent('${student.regNo}', '${roomNo}')" class="btn-remove-stud" title="Unallocate Room"><i class="fas fa-user-minus"></i></button>
                            </div>
                        </div>`;
                    });
                    listDiv.innerHTML = html;
                }
            });
    }

    function removeStudent(regNo, roomNo) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to remove this student from room ${roomNo}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove him!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `manage-rooms.php?unallocate=${regNo}`;
            }
        })
    }
    </script>
</body>
</html>