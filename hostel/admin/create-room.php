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
    $fees   = intval($_POST['fee']);
    $floor  = isset($_POST['floor']) ? intval($_POST['floor']) : 1;
    $type   = isset($_POST['room_type']) ? htmlspecialchars(trim($_POST['room_type'])) : 'Standard';
    
    // Check if room already exists
    $sql = "SELECT room_no FROM rooms WHERE room_no=?";
    $stmt1 = $mysqli->prepare($sql);
    $stmt1->bind_param('i', $roomno);
    $stmt1->execute();
    $stmt1->store_result(); 
    $row_cnt = $stmt1->num_rows;
    
    if($row_cnt > 0) {
        $_SESSION['error'] = "Room #$roomno already exists. Please use a different room number.";
    } else {
        $query = "INSERT INTO rooms (seater, room_no, fees) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iii', $seater, $roomno, $fees);
        
        if($stmt->execute()) {
            $_SESSION['room_success'] = [
                'roomno' => $roomno,
                'seater' => $seater,
                'fees'   => $fees,
                'type'   => $type,
                'floor'  => $floor,
            ];
            header("Location: create-room.php?created=1");
            exit();
        } else {
            $_SESSION['error'] = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Create Room | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
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
            max-width: 780px;
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

        /* ── Seater cards ── */
        .seater-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .seater-card {
            cursor: pointer;
            position: relative;
        }

        .seater-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }

        .seater-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 14px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #fafbff;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .seater-label i {
            font-size: 1.2rem;
            color: #a0aec0;
        }

        .seater-label .seater-count {
            font-size: 0.75rem;
            font-weight: 700;
            color: #718096;
        }

        .seater-card input[type="radio"]:checked + .seater-label {
            border-color: #4361ee;
            background: rgba(67,97,238,.07);
            box-shadow: 0 0 0 3px rgba(67,97,238,.1);
        }

        .seater-card input[type="radio"]:checked + .seater-label i {
            color: #4361ee;
        }

        .seater-card input[type="radio"]:checked + .seater-label .seater-count {
            color: #4361ee;
        }

        .seater-label:hover {
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
            margin: 24px 0;
        }

        /* ── Preview card ── */
        .room-preview {
            background: linear-gradient(135deg, #f7f9ff, #eef2ff);
            border: 1.5px dashed #c3d0f5;
            border-radius: 16px;
            padding: 20px;
            margin-top: 4px;
        }

        .preview-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4361ee;
            margin-bottom: 12px;
        }

        .preview-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(67,97,238,.1);
            font-size: 0.85rem;
        }

        .preview-row:last-child { border-bottom: none; }

        .preview-key {
            color: #718096;
            font-weight: 500;
        }

        .preview-val {
            font-weight: 700;
            color: #2d3748;
        }

        /* ── Buttons ── */
        .btn-create {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            border: none;
            color: #fff;
            padding: 13px 32px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,97,238,.4);
            color: #fff;
        }

        .btn-create:active { transform: translateY(0); }

        .btn-back {
            background: #f0f2f5;
            border: none;
            color: #4a5568;
            padding: 13px 24px;
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

        /* ── Alert ── */
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

        /* ── Success modal overlay ── */
        .success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 9999;
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .success-overlay.show {
            display: flex;
        }

        .success-modal {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: popIn 0.4s cubic-bezier(.175,.885,.32,1.275);
        }

        @keyframes popIn {
            from { transform: scale(0.7); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-check {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #06d6a0, #0ab575);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem;
            color: #fff;
            box-shadow: 0 10px 30px rgba(6,214,160,.35);
        }

        .success-modal h3 {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .success-modal p {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 24px;
        }

        .success-details {
            background: #f7fafc;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: left;
        }

        .success-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.85rem;
        }

        .success-detail-row:not(:last-child) {
            border-bottom: 1px solid #edf2f7;
        }

        .success-detail-row .key { color: #718096; }
        .success-detail-row .val { font-weight: 700; color: #2d3748; }

        .btn-modal-primary {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            margin: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(67,97,238,.3); }

        .btn-modal-secondary {
            background: #f0f2f5;
            color: #4a5568;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            margin: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal-secondary:hover { background: #e2e8f0; }

        /* ── Responsive ── */
        @media (max-width: 576px) {
            .room-card-header { padding: 24px 20px; }
            .room-card-body { padding: 24px 20px; }
            .seater-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>

<body>
    <!-- Success Overlay Modal -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-modal">
            <div class="success-check">
                <i class="fas fa-check"></i>
            </div>
            <h3>Room Created!</h3>
            <p>The room has been successfully added to the hostel.</p>
            <?php if(isset($_SESSION['room_success']) && isset($_GET['created'])): 
                $rs = $_SESSION['room_success'];
                unset($_SESSION['room_success']);
            ?>
            <div class="success-details">
                <div class="success-detail-row">
                    <span class="key">Room Number</span>
                    <span class="val">#<?php echo htmlspecialchars($rs['roomno']); ?></span>
                </div>
                <div class="success-detail-row">
                    <span class="key">Seater Type</span>
                    <span class="val"><?php echo htmlspecialchars($rs['seater']); ?>-Person</span>
                </div>
                <div class="success-detail-row">
                    <span class="key">Fee per Student</span>
                    <span class="val">TJS <?php echo number_format($rs['fees'] ?? 0); ?></span>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <button class="btn-modal-primary" onclick="addAnother()">
                    <i class="fas fa-plus-circle me-1"></i> Add Another Room
                </button>
                <button class="btn-modal-secondary" onclick="goToManage()">
                    <i class="fas fa-list me-1"></i> View All Rooms
                </button>
            </div>
        </div>
    </div>

    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                
                <!-- Header -->
                <div class="content-header">
                    <div class="header-left" style="display:flex; align-items:center; gap:12px;">
                        <a href="dashboard.php" class="btn-back" style="padding:8px 16px; font-size:0.85rem;">
                            <i class="fas fa-arrow-left"></i> Dashboard
                        </a>
                        <div>
                            <h1 class="page-title" style="margin:0;">
                                <i class="fas fa-door-open"></i> Create Room
                            </h1>
                        </div>
                    </div>
                    <div class="header-right">
                        <a href="manage-rooms.php" class="btn btn-outline-primary btn-sm" style="border-radius:8px;">
                            <i class="fas fa-list me-1"></i> Manage Rooms
                        </a>
                    </div>
                </div>

                <div class="room-creation-page">
                    <div class="room-form-card">
                        
                        <!-- Card Header -->
                        <div class="room-card-header">
                            <div class="header-icon-big">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <h2>Add New Room</h2>
                            <p>Configure room details. All fields marked with * are required.</p>
                        </div>

                        <!-- Card Body -->
                        <div class="room-card-body">
                            
                            <?php if(isset($_SESSION['error'])): ?>
                            <div class="custom-alert alert-danger mb-4">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                            <?php endif; ?>

                            <form method="post" id="roomForm" novalidate>
                                
                                <!-- Seater Selection -->
                                <div class="section-title">
                                    <i class="fas fa-users"></i> Seater Type *
                                </div>
                                
                                <div class="seater-grid mb-4">
                                    <?php
                                    $seater_types = [
                                        1 => ['label'=>'Single','icon'=>'fa-user'],
                                        2 => ['label'=>'Double','icon'=>'fa-user-friends'],
                                        3 => ['label'=>'Triple','icon'=>'fa-users'],
                                        4 => ['label'=>'Quad','icon'=>'fa-users'],
                                        5 => ['label'=>'5-Bed','icon'=>'fa-users'],
                                    ];
                                    foreach($seater_types as $val => $info): ?>
                                    <div class="seater-card">
                                        <input type="radio" name="seater" id="seater<?php echo $val; ?>" value="<?php echo $val; ?>" required>
                                        <label class="seater-label" for="seater<?php echo $val; ?>">
                                            <i class="fas <?php echo $info['icon']; ?>"></i>
                                            <span class="seater-count"><?php echo $info['label']; ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="form-divider"></div>

                                <!-- Room Details -->
                                <div class="section-title">
                                    <i class="fas fa-door-open"></i> Room Details
                                </div>

                                <div class="row g-3 mb-4">
                                    <!-- Room Number -->
                                    <div class="col-md-6">
                                        <label for="rmno" class="form-label">Room Number *</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-hashtag field-icon"></i>
                                            <input type="number" class="form-control" name="rmno" id="rmno" 
                                                   required min="1" max="9999"
                                                   placeholder="e.g. 101, 202, 305"
                                                   oninput="updatePreview()">
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid room number.</div>
                                    </div>

                                    <!-- Floor -->
                                    <div class="col-md-6">
                                        <label for="floor" class="form-label">Floor</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-layer-group field-icon"></i>
                                            <select name="floor" id="floor" class="form-select">
                                                <option value="0">Ground Floor</option>
                                                <option value="1" selected>1st Floor</option>
                                                <option value="2">2nd Floor</option>
                                                <option value="3">3rd Floor</option>
                                                <option value="4">4th Floor</option>
                                                <option value="5">5th Floor</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Room Type -->
                                    <div class="col-md-6">
                                        <label for="room_type" class="form-label">Room Type</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-tag field-icon"></i>
                                            <select name="room_type" id="room_type" class="form-select">
                                                <option value="Standard">Standard</option>
                                                <option value="Deluxe">Deluxe</option>
                                                <option value="Premium">Premium</option>
                                                <option value="Economy">Economy</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Fee -->
                                    <div class="col-md-6">
                                        <label for="fee" class="form-label">Fee per Student / Month *</label>
                                        <div class="position-relative">
                                            <span class="currency-prefix">TJS</span>
                                            <input type="number" class="form-control currency-field" 
                                                   name="fee" id="fee" required min="1"
                                                   placeholder="e.g. 2000"
                                                   oninput="updatePreview()">
                                        </div>
                                        <div class="invalid-feedback">Please enter the monthly fee.</div>
                                    </div>
                                </div>

                                <div class="form-divider"></div>

                                <!-- Live Preview -->
                                <div class="section-title">
                                    <i class="fas fa-eye"></i> Room Preview
                                </div>

                                <div class="room-preview mb-4" id="previewCard">
                                    <div class="preview-title"><i class="fas fa-door-open me-1"></i> Room Summary</div>
                                    <div class="preview-row">
                                        <span class="preview-key">Room Number</span>
                                        <span class="preview-val" id="pv-room">—</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-key">Seater Type</span>
                                        <span class="preview-val" id="pv-seater">—</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-key">Room Type</span>
                                        <span class="preview-val" id="pv-type">Standard</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-key">Monthly Fee / Student</span>
                                        <span class="preview-val" id="pv-fee">—</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <a href="dashboard.php" class="btn-back">
                                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                                    </a>
                                    <div class="d-flex gap-2">
                                        <button type="reset" class="btn-back" onclick="resetPreview()">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                        <button type="submit" name="submit" class="btn-create" id="createBtn">
                                            <i class="fas fa-plus-circle"></i> Create Room
                                        </button>
                                    </div>
                                </div>
                            </form>
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
        <?php if(isset($_GET['created'])): ?>
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('successOverlay').classList.add('show');
        });
        <?php endif; ?>

        function addAnother() {
            document.getElementById('successOverlay').classList.remove('show');
            window.location.href = 'create-room.php';
        }

        function goToManage() {
            window.location.href = 'manage-rooms.php';
        }

        // Live preview update
        const seaterLabels = { '1':'Single (1-Person)', '2':'Double (2-Person)', '3':'Triple (3-Person)', '4':'Quad (4-Person)', '5':'Five-Bed (5-Person)' };

        function updatePreview() {
            const rmno = document.getElementById('rmno').value;
            const fee  = document.getElementById('fee').value;
            const type = document.getElementById('room_type').value;
            const selectedSeater = document.querySelector('input[name="seater"]:checked');

            document.getElementById('pv-room').textContent = rmno ? '#' + rmno : '—';
            document.getElementById('pv-fee').textContent  = fee  ? 'TJS ' + parseInt(fee).toLocaleString() : '—';
            document.getElementById('pv-type').textContent = type;
            document.getElementById('pv-seater').textContent = selectedSeater ? seaterLabels[selectedSeater.value] : '—';
        }

        function resetPreview() {
            document.getElementById('pv-room').textContent = '—';
            document.getElementById('pv-fee').textContent  = '—';
            document.getElementById('pv-type').textContent = 'Standard';
            document.getElementById('pv-seater').textContent = '—';
        }

        // Update preview when seater is clicked
        document.querySelectorAll('input[name="seater"]').forEach(function(el) {
            el.addEventListener('change', updatePreview);
        });

        document.getElementById('room_type').addEventListener('change', updatePreview);

        // Form validation
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            const seaterPicked = document.querySelector('input[name="seater"]:checked');
            const rmno = document.getElementById('rmno').value;
            const fee  = document.getElementById('fee').value;

            if (!seaterPicked) {
                e.preventDefault();
                alert('Please select a seater type.');
                return;
            }
            if (!rmno || parseInt(rmno) < 1) {
                e.preventDefault();
                document.getElementById('rmno').classList.add('is-invalid');
                return;
            }
            if (!fee || parseInt(fee) < 1) {
                e.preventDefault();
                document.getElementById('fee').classList.add('is-invalid');
                return;
            }

            // Button loading state
            const btn = document.getElementById('createBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            btn.disabled = true;
        });

        // Clear invalid on input
        document.getElementsByName('rmno')[0].addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
        document.getElementById('fee').addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    </script>
</body>
</html>