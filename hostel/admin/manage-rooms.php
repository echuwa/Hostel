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
               FROM rooms r";

if(isset($_SESSION['assigned_block']) && !empty($_SESSION['assigned_block'])) {
    $block = $_SESSION['assigned_block'];
    $room_query .= " WHERE r.room_no LIKE '$block%'";
}

$room_query .= " ORDER BY r.room_no";
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Room Inventory | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Unified Admin CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .room-card-modern {
            background: #fff; border-radius: 18px; padding: 20px;
            border: 1px solid #f1f5f9; position: relative;
            transition: all 0.3s; cursor: pointer; height: 100%;
        }
        .room-card-modern:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: 0 10px 25px rgba(67, 97, 238, 0.08); }
        
        .room-num { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
        .room-stat { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 15px; }
        
        .occupancy-bar { height: 8px; border-radius: 10px; background: #f1f5f9; overflow: hidden; margin-bottom: 15px; }
        .occupancy-fill { height: 100%; border-radius: 10px; transition: width 0.5s; }
        
        .room-tag {
            position: absolute; top: 15px; right: 15px;
            padding: 4px 10px; border-radius: 50px; font-size: 0.65rem; font-weight: 800;
        }
        .tag-available { background: #dcfce7; color: #16a34a; }
        .tag-full { background: #fee2e2; color: #ef4444; }
        
        .nav-tabs-custom { gap: 10px; border: none; margin-bottom: 30px; }
        .nav-tabs-custom .nav-link { 
            border: none; border-radius: 12px; font-weight: 700; 
            padding: 12px 25px; color: var(--gray); background: #f8fafc;
        }
        .nav-tabs-custom .nav-link.active { background: var(--primary); color: #fff; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Room Inventory Management</h2>
                        <p class="text-muted fw-600 mb-0">Monitor workspace allocation, maintenance states, and occupancy metrics.</p>
                    </div>
                    <div>
                        <a href="create-room.php" class="btn btn-modern btn-modern-primary">
                            <i class="fas fa-plus-circle"></i> Create New Room
                        </a>
                    </div>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success rounded-4 border-0 shadow-sm p-3 mb-5 fw-600 animate__animated animate__fadeInDown">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- BLOCK TABS -->
                <ul class="nav nav-tabs nav-tabs-custom" id="blockTabs" role="tablist">
                    <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): ?>
                        <li class="nav-item">
                            <button class="nav-link <?php echo $i===0?'active':''; ?>" id="t-<?php echo preg_replace('/[^A-Za-z0-9]/','',$block_name); ?>" data-bs-toggle="tab" data-bs-target="#p-<?php echo preg_replace('/[^A-Za-z0-9]/','',$block_name); ?>"><?php echo $block_name; ?></button>
                        </li>
                    <?php $i++; endforeach; ?>
                </ul>

                <div class="tab-content" id="blockTabsContent">
                    <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): ?>
                        <div class="tab-pane fade <?php echo $i===0?'show active':''; ?>" id="p-<?php echo preg_replace('/[^A-Za-z0-9]/','',$block_name); ?>">
                            
                            <?php foreach ($block_wings as $side_name => $side_rooms): ?>
                                <div class="mb-5">
                                    <h5 class="fw-800 text-dark mb-4 ms-2"><i class="fas fa-caret-right text-primary me-2"></i><?php echo $side_name; ?></h5>
                                    
                                    <div class="row g-4">
                                        <?php foreach ($side_rooms as $rm): 
                                            $perc = ($rm->occupied / $rm->seater) * 100;
                                            $color = $perc >= 100 ? 'bg-danger' : ($perc >= 50 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <div class="room-card-modern" onclick="showRoomDetails('<?php echo $rm->room_no; ?>', <?php echo $rm->seater; ?>)">
                                                <span class="room-tag <?php echo $rm->is_full ? 'tag-full' : 'tag-available'; ?>">
                                                    <?php echo $rm->is_full ? 'CAPACITY REACHED' : ($rm->seater - $rm->occupied) . ' VACANCY'; ?>
                                                </span>
                                                
                                                <div class="room-num"><?php echo $rm->room_no; ?></div>
                                                <div class="room-stat"><?php echo $rm->seater; ?> Seater Unit</div>
                                                
                                                <div class="d-flex justify-content-between small fw-800 text-muted mb-2">
                                                    <span>Occupancy</span>
                                                    <span><?php echo $rm->occupied; ?>/<?php echo $rm->seater; ?></span>
                                                </div>
                                                <div class="occupancy-bar">
                                                    <div class="occupancy-fill <?php echo $color; ?>" style="width: <?php echo $perc; ?>%"></div>
                                                </div>
                                                
                                                <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top">
                                                    <div class="fw-800 text-success small">TSH <?php echo number_format($rm->fees); ?>/mo</div>
                                                    <div class="d-flex gap-2">
                                                        <a href="edit-room.php?id=<?php echo $rm->id; ?>" class="btn btn-light btn-sm rounded-circle p-0" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;" onclick="event.stopPropagation();">
                                                            <i class="fas fa-edit text-primary x-small"></i>
                                                        </a>
                                                        <a href="manage-rooms.php?del=<?php echo $rm->id; ?>" class="btn btn-light btn-sm rounded-circle p-0" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;" onclick="event.stopPropagation(); return confirm('Delete room confirmation');">
                                                            <i class="fas fa-trash-alt text-danger x-small"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                        </div>
                    <?php $i++; endforeach; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Room Details Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white p-4 border-0">
                    <h5 class="modal-title fw-800"><i class="fas fa-door-open me-2"></i> Room Control Console</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="m-student-list">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function showRoomDetails(roomNo, seater) {
        const listDiv = document.getElementById('m-student-list');
        listDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        
        const myModal = new bootstrap.Modal(document.getElementById('roomModal'));
        myModal.show();
        
        fetch('get_room_students.php?room_no=' + roomNo)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    listDiv.innerHTML = '<div class="text-center py-5"><i class="fas fa-inbox fa-3x text-light mb-3"></i><p class="text-muted fw-600">This unit is currently vacant</p></div>';
                } else {
                    let html = `<h6 class="fw-800 mb-4 px-2">Assigned Residents (${data.length}/${seater})</h6>`;
                    data.forEach(student => {
                        html += `
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-4 mb-2">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-800 me-3" style="width:36px; height:36px;">
                                    ${student.firstName.charAt(0)}
                                </div>
                                <div>
                                    <div class="fw-800 text-dark small">${student.firstName} ${student.lastName}</div>
                                    <div class="text-muted" style="font-size:0.7rem;">${student.regNo}</div>
                                </div>
                            </div>
                            <a href="student-details.php?id=${student.id}" class="btn btn-white btn-sm rounded-pill fw-800 px-3">View</a>
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