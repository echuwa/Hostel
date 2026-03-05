<?php 
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Code for batch adding rooms
if(isset($_POST['submit'])) {
    $seater = intval($_POST['seater']);
    $fees   = 178500; // Fixed accommodation fee per standard
    
    $block = intval($_POST['block']); // 1 to 6
    $side = htmlspecialchars(trim($_POST['side'])); // A or B
    $floor = htmlspecialchars(trim($_POST['floor'])); // G, 1, 2, 3
    $roomCount = intval($_POST['room_count']); // Number of rooms to generate

    $type   = isset($_POST['room_type']) ? htmlspecialchars(trim($_POST['room_type'])) : 'Standard';
    
    $addedRooms = [];
    $skippedRooms = [];

    for ($i = 1; $i <= $roomCount; $i++) {
        // Format room number, e.g., 1A-G01
        $roomNumberStr = sprintf("%d%s-%s%02d", $block, $side, $floor, $i);
        
        // Check if room already exists
        $sql = "SELECT room_no FROM rooms WHERE room_no=?";
        $stmt1 = $mysqli->prepare($sql);
        $stmt1->bind_param('s', $roomNumberStr);
        $stmt1->execute();
        $stmt1->store_result(); 
        $row_cnt = $stmt1->num_rows;
        
        if($row_cnt > 0) {
            $skippedRooms[] = $roomNumberStr;
        } else {
            $query = "INSERT INTO rooms (seater, room_no, fees) VALUES (?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('isi', $seater, $roomNumberStr, $fees);
            if($stmt->execute()) {
                $addedRooms[] = $roomNumberStr;
            }
        }
    }
    
    if(count($addedRooms) > 0) {
        $_SESSION['room_success'] = [
            'added' => count($addedRooms),
            'skipped' => count($skippedRooms),
            'block' => $block,
            'side' => $side,
            'floor' => $floor
        ];
        header("Location: create-room.php?created=1");
        exit();
    } else {
        $_SESSION['error'] = "All selected rooms already exist or an error occurred.";
    }
}

// Fetch all existing rooms to list them below the generator
$ret = "select * from rooms order by room_no asc";
$stmt = $mysqli->prepare($ret);
$stmt->execute();
$res = $stmt->get_result();
$rooms_by_block = [];

while ($row = $res->fetch_object()) {
    $room_no = $row->room_no;
    if (preg_match('/^(\d+)([A-Za-z]+)-/', $room_no, $matches)) {
        $block_name = "Block " . $matches[1];
        $side_name = "Side " . strtoupper($matches[2]);
    } else {
        $block_name = "Other Rooms";
        $side_name = "Default";
    }
    
    if (!isset($rooms_by_block[$block_name])) {
        $rooms_by_block[$block_name] = [];
    }
    if (!isset($rooms_by_block[$block_name][$side_name])) {
        $rooms_by_block[$block_name][$side_name] = [];
    }
    $rooms_by_block[$block_name][$side_name][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Create Rooms (Blocks) | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body {
            background: #f0f2f5;
            padding-top: 0;
        }

        /* ── Page layout ── */
        .room-creation-page {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .room-form-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
            height: 100%;
        }

        .room-card-header {
            background: var(--gradient-primary);
            padding: 40px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .room-card-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .room-card-header p {
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .header-icon-big {
            width: 60px; height: 60px;
            background: rgba(255,255,255,.2);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff;
            margin-bottom: 14px;
            position: relative; z-index: 1;
        }

        /* ── Card body ── */
        .room-card-body {
            padding: 36px;
        }

        /* ── Section title ── */
        .section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #4361ee;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, rgba(67,97,238,.2), transparent);
            border-radius: 2px;
        }

        /* ── Form labels ── */
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }

        /* ── Input groups with icon ── */
        .input-with-icon {
            position: relative;
        }

        .input-with-icon .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.9rem;
            pointer-events: none;
            z-index: 5;
        }

        .input-with-icon .form-control,
        .input-with-icon .form-select {
            padding-left: 40px;
        }

        .form-control, .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 0.9rem;
            color: #2d3748;
            transition: all 0.2s;
            background: #fafbff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67,97,238,.12);
            background: #fff;
            outline: none;
        }

        /* ── Grid selections (Blocks/Sides) ── */
        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 12px;
        }

        .selector-card {
            cursor: pointer;
            position: relative;
        }

        .selector-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }

        .selector-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 10px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #fafbff;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .selector-label i {
            font-size: 1.6rem;
            color: #a0aec0;
        }

        .selector-label .selector-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #4a5568;
        }

        .selector-card input[type="radio"]:checked + .selector-label {
            border-color: #4361ee;
            background: rgba(67,97,238,.07);
            box-shadow: 0 0 0 4px rgba(67,97,238,.15);
        }

        .selector-card input[type="radio"]:checked + .selector-label i,
        .selector-card input[type="radio"]:checked + .selector-label .selector-title {
            color: #4361ee;
        }

        .selector-label:hover {
            border-color: #4361ee;
            background: rgba(67,97,238,.04);
        }

        /* ── Currency prefix ── */
        .currency-prefix {
            position: absolute;
            left: 0; top: 0; bottom: 0;
            display: flex; align-items: center; justify-content: center;
            width: 52px;
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 10px 0 0 10px;
            pointer-events: none;
            z-index: 5;
        }

        .currency-field {
            padding-left: 60px !important;
        }

        /* ── Divider ── */
        .form-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 28px 0;
        }

        /* ── Preview card ── */
        .room-preview {
            background: linear-gradient(135deg, #f7f9ff, #eef2ff);
            border: 1.5px dashed #c3d0f5;
            border-radius: 16px;
            padding: 24px;
        }

        .preview-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4361ee;
            margin-bottom: 16px;
        }
        
        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }

        .preview-box {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            text-align: center;
        }

        .preview-box .p-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .preview-box .p-val {
            font-size: 1.1rem;
            font-weight: 800;
            color: #2d3748;
        }

        /* ── Buttons ── */
        .btn-create {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            border: none;
            color: #fff;
            padding: 14px 36px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(67,97,238,.3);
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,97,238,.5);
            color: #fff;
        }

        .btn-back {
            background: #f0f2f5;
            border: none;
            color: #4a5568;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #2d3748;
        }

        .custom-alert {
            border-radius: 12px;
            border: none;
            padding: 14px 18px;
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-alert.alert-danger {
            background: #fff5f5;
            color: #e53e3e;
            border-left: 4px solid #e53e3e;
        }
        
        .room-range-display {
            font-size: 1.1rem;
            font-family: monospace;
            background: #1a202c;
            color: #00ff88;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 10px;
        }

        @media (max-width: 576px) {
            .preview-grid { grid-template-columns: 1fr; }
        }

        /* Room Grid & Custom Tabs Styling */
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
        }
        .room-card .room-number {
            font-size: 1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 6px;
        }
        .room-card .room-meta {
            font-size: 0.72rem;
            color: #718096;
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-bottom: 8px;
        }
        .avail-badge {
            background: #c6f6d5;
            color: #276749;
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        /* Custom Tabs & Pills Styling */
        .custom-nav-tabs { border-bottom: 2px solid #e2e8f0; gap: 8px; margin-bottom: 20px; }
        .custom-nav-tabs .nav-link { border: none; color: #64748b; font-weight: 700; border-radius: 10px 10px 0 0; padding: 12px 24px; transition: all 0.3s ease; font-size: 1.05rem; }
        .custom-nav-tabs .nav-link:hover { color: #4361ee; background: #f8fafc; }
        .custom-nav-tabs .nav-link.active { color: #4361ee; background: transparent; border-bottom: 3px solid #4361ee; }
        
        .custom-nav-pills { gap: 10px; background: #f1f5f9; padding: 8px; border-radius: 14px; display: inline-flex; flex-wrap: wrap; margin-bottom: 25px; }
        .custom-nav-pills .nav-link { border-radius: 10px; font-weight: 700; color: #475569; padding: 10px 28px; transition: all 0.3s ease; font-size: 0.95rem; }
        .custom-nav-pills .nav-link:hover { background: #e2e8f0; color: #1e293b; }
        .custom-nav-pills .nav-link.active { background: #4361ee; color: #fff; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.35); }

        /* Modern Accordion / Drop Down View */
        .modern-accordion .accordion-item { border: none; background: transparent; margin-bottom: 12px; }
        .modern-accordion .accordion-header .accordion-button {
            background: #fff; border-radius: 12px !important; border: 1px solid #e2e8f0;
            padding: 18px 24px; font-weight: 800; color: #1e293b; transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .modern-accordion .accordion-header .accordion-button:not(.collapsed) {
            color: #4361ee; background: #f8fafc; border-color: #4361ee;
            box-shadow: 0 4px 12px rgba(67,97,238,0.1);
        }
        .modern-accordion .accordion-header .accordion-button::after { filter: grayscale(1); transform: scale(0.8); }
        .modern-accordion .accordion-body {
            padding: 20px; background: #fff; border: 1px solid #e2e8f0; border-top: none;
            border-radius: 0 0 12px 12px; margin-top: -10px;
        }

        /* Custom Scrollbar for Room Grid */
        .room-grid::-webkit-scrollbar { width: 6px; }
        .room-grid::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .room-grid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .room-grid::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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
                            <i class="fas fa-building"></i> Block Management
                        </h1>
                    </div>
                    <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                        <a href="manage-rooms.php" class="btn btn-primary" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); border: none; padding: 10px 20px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 15px rgba(67,97,238,0.2); color: white;">
                            <i class="fas fa-list"></i> Manage Rooms
                        </a>
                    </div>
                </div>

                <div class="room-creation-page">
                    <!-- Quick Stats Header -->
                    <div class="row g-4 mb-5" data-aos="fade-up">
                        <div class="col-md-3">
                            <div class="p-4 rounded-4 bg-white shadow-sm border-start border-4 border-primary">
                                <div class="small fw-800 text-muted opacity-75">TOTAL LISTED</div>
                                <div class="h3 fw-800 mb-0"><?php echo $res->num_rows; ?> <span class="small fw-600 opacity-50">Rooms</span></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-4 rounded-4 bg-white shadow-sm border-start border-4 border-success">
                                <div class="small fw-800 text-muted opacity-75">BLOCKS ACTIVE</div>
                                <div class="h3 fw-800 mb-0"><?php echo count($rooms_by_block); ?> <span class="small fw-600 opacity-50">Units</span></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <?php
                                $total_capacity = 0;
                                $res->data_seek(0);
                                while($r = $res->fetch_object()) $total_capacity += $r->seater;
                            ?>
                            <div class="p-4 rounded-4 bg-white shadow-sm border-start border-4 border-info">
                                <div class="small fw-800 text-muted opacity-75">BED CAPACITY</div>
                                <div class="h3 fw-800 mb-0"><?php echo $total_capacity; ?> <span class="small fw-600 opacity-50">Slots</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 text-end d-flex align-items-center justify-content-end">
                             <button type="button" onclick="showInventoryModal()" class="btn btn-white shadow-sm rounded-4 p-3 border-0" style="transition: all 0.3s; background: #fff;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-3">
                                        <i class="fas fa-warehouse text-primary"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="small fw-800 text-muted lh-1 mb-1">VIEW</div>
                                        <div class="fw-800 text-primary lh-1">INVENTORY</div>
                                    </div>
                                </div>
                             </button>
                        </div>
                    </div>

                    <div class="row g-4 justify-content-center">
                        <!-- Main Column: Generator Form -->
                        <div class="col-xl-7">
                            <div class="room-form-card" data-aos="fade-up">
                        
                        <!-- Card Header -->
                        <div class="room-card-header">
                            <div class="header-icon-big">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <h2>Generate Block Rooms</h2>
                            <p>Select the block, side, and floor to auto-generate multiple rooms (e.g., 1A-G01 to 1A-G15).</p>
                        </div>

                        <!-- Card Body -->
                        <div class="room-card-body">
                            
                            <?php if(isset($_SESSION['error'])): ?>
                            <div class="custom-alert alert-danger mb-4">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                            <?php endif; ?>

                            <form method="post" id="roomForm">
                                <input type="hidden" name="submit" value="1">
                                
                                <!-- Block Selection -->
                                <div class="section-title">
                                    <i class="fas fa-city"></i> Select Block *
                                </div>
                                <div class="selection-grid mb-4">
                                    <?php for($b=1; $b<=6; $b++): ?>
                                    <div class="selector-card">
                                        <input type="radio" name="block" id="block<?php echo $b; ?>" value="<?php echo $b; ?>" required onchange="updatePreview()">
                                        <label class="selector-label" for="block<?php echo $b; ?>">
                                            <i class="fas fa-building"></i>
                                            <span class="selector-title">Block <?php echo $b; ?></span>
                                        </label>
                                    </div>
                                    <?php endfor; ?>
                                </div>

                                <div class="row g-4 mb-4">
                                    <!-- Side Selection -->
                                    <div class="col-md-6">
                                        <div class="section-title">
                                            <i class="fas fa-arrows-alt-h"></i> Block Wing/Side *
                                        </div>
                                        <div class="selection-grid">
                                            <div class="selector-card">
                                                <input type="radio" name="side" id="sideA" value="A" required onchange="updatePreview()">
                                                <label class="selector-label" for="sideA" style="padding: 10px;">
                                                    <span class="selector-title">Side A</span>
                                                </label>
                                            </div>
                                            <div class="selector-card">
                                                <input type="radio" name="side" id="sideB" value="B" required onchange="updatePreview()">
                                                <label class="selector-label" for="sideB" style="padding: 10px;">
                                                    <span class="selector-title">Side B</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Floor Selection -->
                                    <div class="col-md-6">
                                        <div class="section-title">
                                            <i class="fas fa-align-justify"></i> Floor Level *
                                        </div>
                                        <div class="selection-grid">
                                            <div class="selector-card">
                                                <input type="radio" name="floor" id="floorG" value="G" required onchange="updatePreview()">
                                                <label class="selector-label" for="floorG" style="padding: 10px;">
                                                    <span class="selector-title">Ground (G)</span>
                                                </label>
                                            </div>
                                            <div class="selector-card">
                                                <input type="radio" name="floor" id="floor1" value="1" required onchange="updatePreview()">
                                                <label class="selector-label" for="floor1" style="padding: 10px;">
                                                    <span class="selector-title">Floor 1</span>
                                                </label>
                                            </div>
                                            <div class="selector-card">
                                                <input type="radio" name="floor" id="floor2" value="2" required onchange="updatePreview()">
                                                <label class="selector-label" for="floor2" style="padding: 10px;">
                                                    <span class="selector-title">Floor 2</span>
                                                </label>
                                            </div>
                                            <div class="selector-card">
                                                <input type="radio" name="floor" id="floor3" value="3" required onchange="updatePreview()">
                                                <label class="selector-label" for="floor3" style="padding: 10px;">
                                                    <span class="selector-title">Floor 3</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-divider"></div>

                                <!-- Room Setup -->
                                <div class="section-title">
                                    <i class="fas fa-cog"></i> Room Setup Details
                                </div>

                                <div class="row g-3 mb-4">
                                    <!-- Number of Rooms -->
                                    <div class="col-md-4">
                                        <label class="form-label">Rooms to Generate *</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-list-ol field-icon"></i>
                                            <input type="number" class="form-control" name="room_count" id="room_count" 
                                                   value="15" required min="1" max="50" oninput="updatePreview()">
                                        </div>
                                    </div>

                                    <!-- Seater Type -->
                                    <div class="col-md-4">
                                        <label class="form-label">Seater (Beds per Room) *</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-bed field-icon"></i>
                                            <select name="seater" id="seater" class="form-select" onchange="updatePreview()">
                                                <option value="1">1 Person</option>
                                                <option value="2">2 Persons</option>
                                                <option value="3">3 Persons</option>
                                                <option value="4" selected>4 Persons</option>
                                                <option value="5">5 Persons</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Fee (Hidden/Fixed) -->
                                    <input type="hidden" name="fee" value="178500">
                                </div>

                                <div class="form-divider"></div>

                                <!-- Live Preview -->
                                <div class="section-title">
                                    <i class="fas fa-magic"></i> Output Preview
                                </div>

                                <div class="room-preview mb-4 text-center">
                                    <div class="preview-title" style="margin-bottom: 5px;">Rooms that will be generated:</div>
                                    <div class="room-range-display" id="pv-range">Please select Block, Side, and Floor</div>
                                    
                                    <div class="preview-grid mt-4">
                                        <div class="preview-box">
                                            <div class="p-label">Total Rooms</div>
                                            <div class="p-val" id="pv-count">0 Rooms</div>
                                        </div>
                                        <div class="preview-box">
                                            <div class="p-label">Capacity (Beds)</div>
                                            <div class="p-val" id="pv-capacity">0 Beds</div>
                                        </div>
                                        <div class="preview-box" style="background: rgba(67, 97, 238, 0.05); border: 1px solid rgba(67, 97, 238, 0.2);">
                                            <div class="p-label" style="color: #4361ee;">Accommodation Fee</div>
                                            <div class="p-val" style="color: #4361ee;">Tsh. 178,500/=</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <button type="reset" class="btn-back" onclick="setTimeout(updatePreview, 100)">
                                        <i class="fas fa-redo"></i> Reset Form
                                    </button>
                                    <button type="submit" name="submit" class="btn-create" id="createBtn">
                                        <i class="fas fa-magic me-2"></i> Generate Rooms
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Hidden Template for Room Inventory (Pop-up Content) -->
                <div id="inventory-template" style="display: none;">
                    <div class="text-start p-2">
                        <?php if (empty($rooms_by_block)): ?>
                            <div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle me-2"></i>No rooms added yet.</div>
                        <?php else: ?>
                            <div class="accordion modern-accordion" id="modalAccordion">
                                <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): 
                                    $block_id = preg_replace('/[^a-zA-Z0-9]/','',$block_name);
                                ?>
                                    <div class="accordion-item shadow-none border-0 mb-3">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?php echo $i!==0?'collapsed':''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#m-collapse-<?php echo $block_id; ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                                                <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                    <span class="fw-800"><i class="fas fa-building text-primary me-2"></i><?php echo htmlspecialchars($block_name); ?></span>
                                                    <span class="badge bg-white text-primary border rounded-pill px-3"><?php 
                                                        $count = 0;
                                                        foreach($block_wings as $w) $count += count($w);
                                                        echo $count;
                                                    ?> Units</span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="m-collapse-<?php echo $block_id; ?>" class="accordion-collapse collapse <?php echo $i===0?'show':''; ?>" data-bs-parent="#modalAccordion">
                                            <div class="accordion-body p-0 pt-3">
                                                <ul class="nav nav-pills mb-3 gap-2 px-2" role="tablist">
                                                    <?php $j=0; foreach ($block_wings as $side_name => $side_rooms): ?>
                                                        <li class="nav-item">
                                                            <button class="btn btn-sm <?php echo $j===0?'btn-primary':'btn-light'; ?> rounded-pill px-3 fw-700 wing-toggle-btn" 
                                                                    data-target="m-spane-<?php echo $block_id . $j; ?>" 
                                                                    onclick="toggleWing(this, '<?php echo $block_id; ?>')">
                                                                <?php echo htmlspecialchars($side_name); ?>
                                                            </button>
                                                        </li>
                                                    <?php $j++; endforeach; ?>
                                                </ul>

                                                <div class="wing-panes-container p-2">
                                                    <?php $j=0; foreach ($block_wings as $side_name => $side_rooms): ?>
                                                        <div class="wing-pane <?php echo $j===0?'':'d-none'; ?>" id="m-spane-<?php echo $block_id . $j; ?>">
                                                            <div class="room-grid" style="max-height: 350px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px;">
                                                                <?php foreach ($side_rooms as $rm): ?>
                                                                <div class="p-2 border rounded-3 text-center bg-white shadow-sm">
                                                                    <div class="fw-800 text-primary small"><?php echo htmlspecialchars($rm->room_no); ?></div>
                                                                    <div class="text-muted" style="font-size: 0.65rem;"><i class="fas fa-bed"></i> <?php echo $rm->seater; ?> Bed</div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php $j++; endforeach; ?>
                                                </div>
                                            </div>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        AOS.init({ duration: 800, once: true });
        // Show success modal on page load if just created
        <?php if(isset($_GET['created']) && isset($_SESSION['room_success'])): 
            $rs = $_SESSION['room_success'];
            unset($_SESSION['room_success']);
        ?>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Block Generated!',
                html: '<div style="text-align:left; background:#f8f9fc; padding:15px; border-radius:10px;">' +
                      '<b>Location:</b> Block <?php echo htmlspecialchars($rs['block']); ?>, Side <?php echo htmlspecialchars($rs['side']); ?>, Floor <?php echo htmlspecialchars($rs['floor']); ?><br><br>' +
                      '<span style="color:#06d6a0;"><b><i class="fas fa-check-circle"></i> Successfully Added:</b> <?php echo $rs['added']; ?> rooms</span><br>' +
                      '<?php if($rs['skipped'] > 0){ echo "<span style=\'color:#ef233c;\'><b><i class=\'fas fa-exclamation-circle\'></i> Skipped (Already Exists):</b> " . $rs['skipped'] . " rooms</span>"; } ?>' +
                      '</div>',
                icon: 'success',
                confirmButtonColor: '#4361ee',
                confirmButtonText: 'Great, Thanks!'
            }).then((result) => {
                window.location.href = 'create-room.php';
            });
        });
        <?php endif; ?>

        function updatePreview() {
            const blockInput = document.querySelector('input[name="block"]:checked');
            const sideInput  = document.querySelector('input[name="side"]:checked');
            const floorInput = document.querySelector('input[name="floor"]:checked');
            const roomCount  = parseInt(document.getElementById('room_count').value) || 0;
            const seater     = parseInt(document.getElementById('seater').value) || 0;

            const pvRange   = document.getElementById('pv-range');
            const pvCount   = document.getElementById('pv-count');
            const pvCap     = document.getElementById('pv-capacity');

            if(blockInput && sideInput && floorInput && roomCount > 0) {
                const b = blockInput.value;
                const s = sideInput.value;
                const f = floorInput.value;
                let lastRoomFormat = (roomCount < 10) ? '0'+roomCount : roomCount;

                pvRange.innerHTML = `<i class="fas fa-door-closed"></i> ${b}${s}-${f}01 &nbsp; → &nbsp; <i class="fas fa-door-closed"></i> ${b}${s}-${f}${lastRoomFormat}`;
                pvCount.textContent = roomCount + ' Rooms';
                pvCap.textContent = (roomCount * seater) + ' Beds';
                
                document.getElementById('createBtn').disabled = false;
            } else {
                pvRange.textContent = "Please select Block, Side, and Floor";
                pvCount.textContent = "0 Rooms";
                pvCap.textContent = "0 Beds";
                document.getElementById('createBtn').disabled = true;
            }
        }
        
        // Initial run
        updatePreview();
        // Form loading state
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            const block = document.querySelector('input[name="block"]:checked');
            const side  = document.querySelector('input[name="side"]:checked');
            const floor = document.querySelector('input[name="floor"]:checked');

            if (!block || !side || !floor) {
                e.preventDefault();
                Swal.fire('Incomplete', 'Please select a Block, Side, and Floor.', 'warning');
                return;
            }

            const btn = document.getElementById('createBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;
        });

        // Init preview
        updatePreview();

        function showInventoryModal() {
            const content = document.getElementById('inventory-template').innerHTML;
            Swal.fire({
                title: '<i class="fas fa-warehouse text-primary me-2"></i>Unit Inventory Explorer',
                html: content,
                width: '800px',
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    container: 'inventory-modal-container',
                    popup: 'rounded-4'
                }
            });
        }

        function toggleWing(btn, blockId) {
            const targetId = btn.getAttribute('data-target');
            // Reset all buttons and panes in this block context
            const parent = btn.closest('.accordion-body');
            parent.querySelectorAll('.wing-toggle-btn').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-light');
            });
            parent.querySelectorAll('.wing-pane').forEach(p => p.classList.add('d-none'));
            
            // Activate selected
            btn.classList.remove('btn-light');
            btn.classList.add('btn-primary');
            document.getElementById(targetId).classList.remove('d-none');
        }
    </script>
</body>
</html>