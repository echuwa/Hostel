<?php 
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Code for batch adding rooms
if(isset($_POST['submit'])) {
    $seater = intval($_POST['seater']);
    $fees   = intval($_POST['fee']);
    
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
    <link rel="stylesheet" href="css/modern.css">
    
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
            min-height: 100vh;
            padding: 30px 0;
        }

        /* ── Card ── */
        .room-form-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(67,97,238,.12);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        /* ── Card header ── */
        .room-card-header {
            background: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
            padding: 32px 36px;
            position: relative;
            overflow: hidden;
        }

        .room-card-header::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,.08);
            border-radius: 50%;
        }

        .room-card-header::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -40px;
            width: 180px; height: 180px;
            background: rgba(255,255,255,.06);
            border-radius: 50%;
        }

        .room-card-header h2 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .room-card-header p {
            color: rgba(255,255,255,.75);
            margin: 6px 0 0;
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
                    <div class="room-form-card">
                        
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

                                    <!-- Fee -->
                                    <div class="col-md-4">
                                        <label class="form-label">Fee (Per Student) *</label>
                                        <div class="position-relative">
                                            <span class="currency-prefix">Tsh.</span>
                                            <input type="number" class="form-control currency-field" 
                                                   name="fee" id="fee" required min="1"
                                                   placeholder="e.g. 178500" value="178500" oninput="updatePreview()">
                                        </div>
                                    </div>
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
                                            <div class="p-label" style="color: #4361ee;">Room Total Fee</div>
                                            <div class="p-val" id="pv-total-fee" style="color: #4361ee;">Tsh. 0/=</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <button type="reset" class="btn-back" onclick="setTimeout(updatePreview, 100)">
                                        <i class="fas fa-redo"></i> Reset Form
                                    </button>
                                    <button type="submit" name="submit" class="btn-create" id="createBtn">
                                        <i class="fas fa-cogs"></i> Generate Rooms
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Already Added Rooms Display -->
                    <div class="room-form-card mt-5">
                        <div class="room-card-header" style="padding: 24px 36px; background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                            <h2><i class="fas fa-list"></i> Already Added Rooms</h2>
                            <p>View the existing room layout to avoid duplicating rooms.</p>
                        </div>
                        <div class="room-card-body">
                            <?php if (empty($rooms_by_block)): ?>
                                <div class="alert alert-info border-0 shadow-sm" style="border-radius:10px;"><i class="fas fa-info-circle me-2"></i>No rooms have been added to the system yet. Use the form above to generate your first blocks!</div>
                            <?php else: ?>
                                <!-- Blocks Tabs -->
                                <ul class="nav custom-nav-tabs" id="blockTabs" role="tablist">
                                    <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php echo $i===0?'active':''; ?>" id="tab-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" data-bs-toggle="tab" data-bs-target="#pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" type="button" role="tab"><?php echo htmlspecialchars($block_name); ?></button>
                                        </li>
                                    <?php $i++; endforeach; ?>
                                </ul>

                                <!-- Blocks Content -->
                                <div class="tab-content pt-3" id="blockTabsContent">
                                    <?php $i=0; foreach ($rooms_by_block as $block_name => $block_wings): ?>
                                        <div class="tab-pane fade <?php echo $i===0?'show active':''; ?>" id="pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" role="tabpanel">
                                            
                                            <!-- Sides Pills -->
                                            <ul class="nav custom-nav-pills mb-3" id="pills-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" role="tablist">
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
                                                            <div class="room-card" title="Room already exists">
                                                                <div class="room-number"><?php echo htmlspecialchars($rm->room_no); ?></div>
                                                                <div class="room-meta">
                                                                    <span><i class="fas fa-users"></i> <?php echo $rm->seater; ?> Bed</span>
                                                                    <span><i class="fas fa-money-bill-wave"></i> <?php echo number_format($rm->fees); ?>/=</span>
                                                                </div>
                                                                <div class="room-badge avail-badge"><i class="fas fa-check-circle"></i> ADDED</div>
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
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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

        // Live preview update
        function updatePreview() {
            const blockInput = document.querySelector('input[name="block"]:checked');
            const sideInput  = document.querySelector('input[name="side"]:checked');
            const floorInput = document.querySelector('input[name="floor"]:checked');
            const roomCount  = parseInt(document.getElementById('room_count').value) || 0;
            const seater     = parseInt(document.getElementById('seater').value) || 0;
            const fee        = parseInt(document.getElementById('fee').value) || 0;

            const pvRange   = document.getElementById('pv-range');
            const pvCount   = document.getElementById('pv-count');
            const pvCap     = document.getElementById('pv-capacity');
            const pvTotalFee= document.getElementById('pv-total-fee');

            if(blockInput && sideInput && floorInput && roomCount > 0) {
                const b = blockInput.value;
                const s = sideInput.value;
                const f = floorInput.value;

                let lastRoomFormat = (roomCount < 10) ? '0'+roomCount : roomCount;

                pvRange.innerHTML = `<i class="fas fa-door-closed"></i> ${b}${s}-${f}01 &nbsp; <i class="fas fa-long-arrow-alt-right" style="color:#fff;"></i> &nbsp; <i class="fas fa-door-closed"></i> ${b}${s}-${f}${lastRoomFormat}`;
                pvCount.textContent = roomCount + ' Rooms';
                pvCap.textContent = (roomCount * seater) + ' Beds';
                
                let roomTotal = seater * fee;
                pvTotalFee.textContent = roomTotal > 0 ? 'Tsh. ' + roomTotal.toLocaleString() + '/=' : 'Tsh. 0/=';
            } else {
                pvRange.textContent = 'Please select Block, Side, and Floor';
                pvCount.textContent = '0 Rooms';
                pvCap.textContent = '0 Beds';
                pvTotalFee.textContent = 'Tsh. 0/=';
            }
        }

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
    </script>
</body>
</html>