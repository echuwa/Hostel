<?php
// ==================== INITIALIZATION ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true,
    'cookie_lifetime' => 86400
]);

$required_files = ['includes/config.php', 'includes/checklogin.php'];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("System error: Required file missing ($file). Contact administrator.");
    }
}

require_once('includes/config.php');
require_once('includes/checklogin.php');

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    die("Database connection error. Please try again later.");
}

if ($mysqli->connect_errno) {
    die("Database connection failed: " . htmlspecialchars($mysqli->connect_error));
}

try {
    check_login();
} catch (Exception $e) {
    die("Authentication error: " . htmlspecialchars($e->getMessage()));
}

session_regenerate_id(true);

// ==================== GLOBAL VARIABLES ====================
$errors = [];
$user = null;
$rooms = [];
$courses = [];
$states = [];
$has_booking = false;
$form_data = [];

// ==================== FORM PROCESSING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid form submission. Please try again.";
    } else {
        $form_data = [
            'roomno' => filter_input(INPUT_POST, 'room', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'seater' => filter_input(INPUT_POST, 'seater', FILTER_VALIDATE_INT),
            'feespm' => filter_input(INPUT_POST, 'fpm', FILTER_VALIDATE_FLOAT),
            'foodstatus' => filter_input(INPUT_POST, 'foodstatus', FILTER_VALIDATE_INT),
            'stayfrom' => filter_input(INPUT_POST, 'stayf', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'duration' => filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 10]
            ]),
            'course' => filter_input(INPUT_POST, 'course', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'regno' => filter_input(INPUT_POST, 'regno', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'firstName' => filter_input(INPUT_POST, 'fname', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'middleName' => filter_input(INPUT_POST, 'mname', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'lastName' => filter_input(INPUT_POST, 'lname', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'gender' => filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'contactno' => preg_replace('/[^0-9]/', '', $_POST['contact'] ?? ''),
            'emailid' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
            'egycontactno' => preg_replace('/[^0-9]/', '', $_POST['econtact'] ?? ''),
            'guardianName' => filter_input(INPUT_POST, 'gname', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'guardianRelation' => filter_input(INPUT_POST, 'grelation', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'guardianContactno' => preg_replace('/[^0-9]/', '', $_POST['gcontact'] ?? ''),
            'corresAddress' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'corresCountry' => filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'corresState' => filter_input(INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'pmntAddress' => filter_input(INPUT_POST, 'paddress', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'pmntCountry' => filter_input(INPUT_POST, 'pcountry', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'pmntState' => filter_input(INPUT_POST, 'pstate', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'adcheck' => isset($_POST['adcheck']) ? 1 : 0
        ];

        // Fields that are allowed to be empty or zero
        $optional_fields = ['middleName', 'adcheck'];
        $zero_ok_fields  = ['foodstatus']; // these can legitimately be 0

        foreach ($form_data as $key => $value) {
            if (in_array($key, $optional_fields)) continue;
            if (in_array($key, $zero_ok_fields)) {
                if ($value === null || $value === false || $value === '') {
                    $errors[] = ucfirst($key) . " is required";
                }
            } else {
                if (empty($value) && $value !== false) {
                    $errors[] = ucfirst($key) . " is required";
                }
            }
        }

        if (strlen($form_data['contactno']) < 10) $errors[] = "Invalid contact number (minimum 10 digits)";
        if (strlen($form_data['egycontactno']) < 10) $errors[] = "Invalid emergency contact number (minimum 10 digits)";
        if (strlen($form_data['guardianContactno']) < 10) $errors[] = "Invalid guardian contact number (minimum 10 digits)";
        if (empty($form_data['roomno'])) $errors[] = "Invalid room selection";
        if ($form_data['duration'] === false) $errors[] = "Invalid duration selection";

        if (empty($errors)) {
            $room_check = $mysqli->prepare("SELECT (seater - (SELECT COUNT(*) FROM registration WHERE roomno = ?)) as available FROM rooms WHERE room_no = ? FOR UPDATE");
            if ($room_check === false) {
                $errors[] = "System error: Unable to check room availability";
            } else {
                $room_check->bind_param('ss', $form_data['roomno'], $form_data['roomno']);
                if (!$room_check->execute()) {
                    $errors[] = "Database error: " . $room_check->error;
                } else {
                    $room_check->bind_result($availability);
                    $room_check->fetch();
                    $room_check->close();

                    if ($availability < 1) {
                        $errors[] = "Selected room is no longer available";
                    } else {
                        $mysqli->begin_transaction();

                        try {
                            $query = "INSERT INTO registration(
                                roomno, seater, feespm, foodstatus, stayfrom, duration, 
                                course, regno, firstName, middleName, lastName, gender, 
                                contactno, emailid, egycontactno, guardianName, 
                                guardianRelation, guardianContactno, corresAddress, 
                                corresCountry, corresState, pmntAddress, pmntCountry, pmntState
                            ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = $mysqli->prepare($query);
                            if ($stmt === false) {
                                throw new Exception("Prepare failed: " . $mysqli->error);
                            }
                            
                            $bind = $stmt->bind_param(
                                'siidssisssssisssisssisss', 
                                $form_data['roomno'], $form_data['seater'], $form_data['feespm'], 
                                $form_data['foodstatus'], $form_data['stayfrom'], $form_data['duration'], 
                                $form_data['course'], $form_data['regno'], $form_data['firstName'], 
                                $form_data['middleName'], $form_data['lastName'], $form_data['gender'], 
                                $form_data['contactno'], $form_data['emailid'], $form_data['egycontactno'], 
                                $form_data['guardianName'], $form_data['guardianRelation'], $form_data['guardianContactno'], 
                                $form_data['corresAddress'], $form_data['corresCountry'], $form_data['corresState'], 
                                $form_data['pmntAddress'], $form_data['pmntCountry'], $form_data['pmntState']
                            );
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Execute failed: " . $stmt->error);
                            }
                            $stmt->close();
                            $mysqli->commit();

                            $_SESSION['booking_success'] = [
                                'room' => $form_data['roomno'],
                                'fees' => $form_data['feespm']
                            ];
                            header("Location: book-hostel.php?success=1");
                            exit();
                        } catch (Exception $e) {
                            $mysqli->rollback();
                            $errors[] = "System error: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        $_SESSION['form_data'] = $form_data;
        header("Location: book-hostel.php");
        exit();
    }
}

// ==================== DATA FETCHING ====================
try {
    $uid = $_SESSION['login'];
    
    $user_query = "SELECT * FROM userregistration WHERE email = ? OR regNo = ? LIMIT 1";
    $stmt = $mysqli->prepare($user_query);
    if ($stmt === false) throw new Exception("Prepare failed: " . $mysqli->error);
    
    $stmt->bind_param('ss', $uid, $uid);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $user = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$user) throw new Exception("User details not found");

    $booking_check = "SELECT id FROM registration WHERE emailid = ? OR regno = ? LIMIT 1";
    $stmt = $mysqli->prepare($booking_check);
    if ($stmt === false) throw new Exception("Prepare failed: " . $mysqli->error);
    
    $stmt->bind_param('ss', $uid, $uid);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $stmt->store_result();
    $has_booking = $stmt->num_rows > 0;
    $stmt->close();

    // Fetch ALL rooms with occupancy count (including full ones)
    $rooms = [];
    $room_query = "SELECT r.room_no, r.seater, r.fees,
                    (SELECT COUNT(*) FROM registration reg WHERE reg.roomno = r.room_no) AS occupied
                   FROM rooms r
                   ORDER BY r.room_no";
    $stmt = $mysqli->prepare($room_query);
    if (!$stmt) throw new Exception("Prepare failed: " . $mysqli->error);
    
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $res = $stmt->get_result();
    while ($room = $res->fetch_object()) {
        if (preg_match('/^(\d+)([A-Z]+)-/i', $room->room_no, $m)) {
            $room->block = 'Block ' . $m[1];
            $room->side = 'Side ' . strtoupper($m[2]);
        } else {
            $room->block = 'General';
            $room->side = 'General Wing';
        }
        $room->is_full = ($room->occupied >= $room->seater);
        $rooms[] = $room;
    }
    $stmt->close();

    // Group rooms by block
    $rooms_by_block = [];
    foreach ($rooms as $rm) {
        $rooms_by_block[$rm->block][$rm->side][] = $rm;
    }
    ksort($rooms_by_block);
    foreach($rooms_by_block as $b => $s) {
        ksort($rooms_by_block[$b]);
    }

    $courses = [];
    $course_query = "SELECT * FROM courses";
    $stmt = $mysqli->prepare($course_query);
    if ($stmt === false) throw new Exception("Prepare failed: " . $mysqli->error);
    
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $res = $stmt->get_result();
    while ($course = $res->fetch_object()) $courses[] = $course;
    $stmt->close();

    $states = [];
    $state_query = "SELECT * FROM states";
    $stmt = $mysqli->prepare($state_query);
    if ($stmt === false) throw new Exception("Prepare failed: " . $mysqli->error);
    
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $res = $stmt->get_result();
    while ($state = $res->fetch_object()) $states[] = $state;
    $stmt->close();

} catch (Exception $e) {
    die("System error: " . htmlspecialchars($e->getMessage()));
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hostel Registration</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .form-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-section h4 {
            color: #28a745;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .icon-input {
            position: relative;
        }
        .icon-input i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        .icon-input input, .icon-input select, .icon-input textarea {
            padding-left: 40px;
        }
        .btn-submit {
            background-color: #28a745;
            border-color: #28a745;
            padding: 10px 25px;
            font-weight: 600;
        }
        .btn-submit:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .loader {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #3498db;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .was-validated .form-control:invalid, .form-control.is-invalid {
            background-position: right calc(0.375em + 2.5rem) center;
        }

        /* Success overlay */
        .success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 9999;
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .success-overlay.show { display: flex; }
        .success-modal {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: popIn 0.4s cubic-bezier(.175,.885,.32,1.275);
        }
        @keyframes popIn {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .success-check {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #06d6a0, #0ab575);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem; color: #fff;
            box-shadow: 0 10px 30px rgba(6,214,160,.35);
        }
        .success-modal h3 { font-size: 1.4rem; font-weight: 800; color: #1a202c; margin-bottom: 8px; }
        .success-modal p  { color: #718096; font-size: 0.9rem; margin-bottom: 20px; }

        /* ============ ROOM BLOCK GRID ============ */
        .block-section {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .block-header {
            background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
            color: #fff;
            padding: 10px 18px;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .block-stats {
            background: rgba(255,255,255,0.25);
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
            padding: 14px;
            background: #fafbff;
        }
        .room-card {
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
            border: 2px solid #e2e8f0;
            position: relative;
            transition: all 0.2s ease;
            background: #fff;
        }
        .room-available {
            cursor: pointer;
            border-color: #c6f6d5;
        }
        .room-available:hover {
            border-color: #38a169;
            box-shadow: 0 4px 15px rgba(56,161,105,0.2);
            transform: translateY(-2px);
        }
        .room-selected {
            border-color: #4361ee !important;
            background: #eef2ff !important;
            box-shadow: 0 4px 18px rgba(67,97,238,0.25) !important;
        }
        .room-full {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f8f9fa;
            border-color: #dee2e6;
        }
        .room-number {
            font-size: 1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 6px;
        }
        .room-meta {
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
        }
        .full-badge {
            background: #fed7d7;
            color: #c53030;
        }
        .avail-badge {
            background: #c6f6d5;
            color: #276749;
        }
        .room-selected .avail-badge {
            background: #4361ee;
            color: #fff;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: #fff; border: none; text-decoration: none;
            padding: 11px 24px; border-radius: 10px;
            font-weight: 700; font-size: 0.88rem;
            margin: 4px; cursor: pointer; transition: all 0.2s; display: inline-block;
        }
        .btn-modal-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(67,97,238,.3); color: #fff;}
        .btn-modal-secondary {
            background: #f0f2f5; color: #4a5568; border: none; text-decoration: none;
            padding: 11px 24px; border-radius: 10px;
            font-weight: 600; font-size: 0.88rem;
            margin: 4px; cursor: pointer; transition: all 0.2s; display: inline-block;
        }
        .btn-modal-secondary:hover { background: #e2e8f0; color: #2d3748;}

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
    <!-- Success Popup Modal -->
    <div class="success-overlay" id="bookSuccessOverlay">
        <div class="success-modal">
            <div class="success-check"><i class="fas fa-check"></i></div>
            <h3>Booking Successful!</h3>
            <p>You have successfully booked room number <strong><span id="bk-room"><?php echo isset($_SESSION['booking_success']['room']) ? htmlspecialchars($_SESSION['booking_success']['room']) : ''; ?></span></strong>.</p>
            
            <div style="background: #f8f9fa; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                <i class="fas fa-file-contract fa-2x text-primary mb-2"></i>
                <h6>Download Contract / Mkataba</h6>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Please download your tenancy contract, sign it, and bring it to the management office.</p>
            </div>

            <div>
                <a href="room-details.php" class="btn-modal-primary">
                    <i class="fas fa-download me-1"></i> Download Mkataba
                </a>
                <a href="dashboard.php" class="btn-modal-secondary">
                    <i class="fas fa-home me-1"></i> Go to Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-md-12">
                        <h2 class="page-title">
                            <i class="fas fa-bed me-2"></i>Request Room
                        </h2>
                        
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($user->fee_status != 1): ?>
                            <div class="alert alert-danger shadow-sm">
                                <h4 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle"></i> Fee Payment Required
                                </h4>
                                <p>You cannot request a room because your fee status is incomplete. Please contact the administration or Bursar to complete your fee payments before reserving a room.</p>
                            </div>
                        <?php elseif($has_booking): ?>
                            <div class="alert alert-info">
                                <h4 class="alert-heading">
                                    <i class="fas fa-info-circle"></i> Hostel Already Booked
                                </h4>
                                <p>You have already booked a hostel room. View your room details below.</p>
                                <hr>
                                <div class="text-center">
                                    <a href="room-details.php" class="btn btn-success">
                                        <i class="fas fa-bed me-1"></i> View My Room
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-edit me-2"></i>Room Request Form
                            </div>
                            <div class="card-body">
                                <form method="post" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    
                                    <!-- Room Information Section -->
                                    <div class="form-section animate__animated animate__fadeIn">
                                        <h4><i class="fas fa-door-open me-2"></i>Room Information</h4>
                                        
                                        <!-- ROOM SELECTION BY BLOCK -->
                                        <div class="col-12">
                                            <label class="form-label fw-bold"><i class="fas fa-building me-1"></i> Select Room</label>
                                            <input type="hidden" name="room" id="room" required>
                                            <div id="room-availability-status" class="small mt-1"></div>
                                            
                                            <?php if (empty($rooms_by_block)): ?>
                                                <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No rooms available at the moment.</div>
                                            <?php else: ?>
                                                
                                                <!-- Blocks Tabs -->
                                                <ul class="nav nav-tabs custom-nav-tabs" id="blockTabs" role="tablist">
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
                                                            <ul class="nav nav-pills custom-nav-pills mb-3" id="pills-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$block_name); ?>" role="tablist">
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
                                                                            $is_full = $rm->is_full;
                                                                            $is_selected = isset($_SESSION['form_data']['roomno']) && $_SESSION['form_data']['roomno'] == $rm->room_no;
                                                                            $remaining = $rm->seater - $rm->occupied;
                                                                            ?>
                                                                            <div class="room-card <?php echo $is_full ? 'room-full' : 'room-available'; ?> <?php echo $is_selected ? 'room-selected' : ''; ?>"
                                                                                onclick="<?php echo $is_full ? '' : 'selectRoom(this, \'' . htmlspecialchars($rm->room_no, ENT_QUOTES) . '\', ' . $rm->seater . ', ' . $rm->fees . ')'; ?>"
                                                                                title="<?php echo $is_full ? 'Room Full' : 'Click to select'; ?>">
                                                                                <div class="room-number"><?php echo htmlspecialchars($rm->room_no); ?></div>
                                                                                <div class="room-meta">
                                                                                    <span><i class="fas fa-users"></i> <?php echo $rm->seater; ?> Bed</span>
                                                                                    <span><i class="fas fa-money-bill-wave"></i> <?php echo number_format($rm->fees); ?>/=</span>
                                                                                </div>
                                                                                <?php if ($is_full): ?>
                                                                                    <div class="room-badge full-badge"><i class="fas fa-times-circle"></i> FULL</div>
                                                                                <?php else: ?>
                                                                                    <div class="room-badge avail-badge"><i class="fas fa-check-circle"></i> <?php echo $remaining; ?> Left</div>
                                                                                <?php endif; ?>
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
                                            
                                            <div class="invalid-feedback d-block" id="room-error" style="display:none !important;"></div>
                                        </div>

                                        <div class="row g-3 mt-0" id="room-details-row" style="display:none !important;">
                                        <div class="col-md-12"><div class="alert alert-info py-2" id="selected-room-info"></div></div>
                                        </div>

                                        <div class="col-md-0" style="display:none;"><!-- placeholder --></div>
                                            
                                            <!-- Seater (hidden, filled by JS) -->
                                            <div class="col-md-6" style="display:none;">
                                                <input type="text" name="seater" id="seater" class="form-control" 
                                                    value="<?php echo isset($_SESSION['form_data']['seater']) ? htmlspecialchars($_SESSION['form_data']['seater']) : ''; ?>" readonly>
                                            </div>
                                            
                                            <!-- Fees -->
                                            <div class="col-md-6">
                                                <label for="fpm" class="form-label">Fee Per Student (TSH)</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <input type="text" name="fpm" id="fpm" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['feespm']) ? htmlspecialchars($_SESSION['form_data']['feespm']) : ''; ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Food Status -->
                                            <div class="col-md-6">
                                                <label class="form-label">Food Status</label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="foodstatus" id="withoutFood" value="0" 
                                                            <?php echo !isset($_SESSION['form_data']['foodstatus']) || $_SESSION['form_data']['foodstatus'] == 0 ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="withoutFood">
                                                            Without Food
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="foodstatus" id="withFood" value="1"
                                                            <?php echo isset($_SESSION['form_data']['foodstatus']) && $_SESSION['form_data']['foodstatus'] == 1 ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="withFood">
                                                            With Food (TSH.2000/month)
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Stay From -->
                                            <div class="col-md-6">
                                                <label for="stayf" class="form-label">Stay From</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <input type="date" name="stayf" id="stayf" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['stayfrom']) ? htmlspecialchars($_SESSION['form_data']['stayfrom']) : date('Y-m-d'); ?>" required>
                                                    <div class="invalid-feedback">
                                                        Please select stay from date.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Duration - Semester Based -->
                                            <div class="col-md-6">
                                                <label for="duration" class="form-label">Duration (Semester)</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-calendar-check"></i>
                                                    <select name="duration" id="duration" class="form-control" required>
                                                        <option value="">-- Choose Semester --</option>
                                                        <?php
                                                        $semester_options = [
                                                            ['value' => 5,  'label' => 'Semester 1 (5 months)',    'sub' => 'Feb – Jun'],
                                                            ['value' => 5,  'label' => 'Semester 2 (5 months)',    'sub' => 'Jul – Nov'],
                                                            ['value' => 10, 'label' => 'Full Academic Year (10 months)', 'sub' => 'Feb – Nov'],
                                                        ];
                                                        // De-duplicate keys to allow separate selection
                                                        $sem_keys = [5, 6, 10];
                                                        $sem_list = [
                                                            ['value' => 5,  'label' => 'Semester 1  (5 months · Feb – Jun)'],
                                                            ['value' => 5,  'label' => 'Semester 2  (5 months · Jul – Nov)', 'key' => '5b'],
                                                            ['value' => 10, 'label' => 'Full Academic Year  (10 months · Feb – Nov)'],
                                                        ];
                                                        // Use simple defined list
                                                        $saved_dur = isset($_SESSION['form_data']['duration']) ? intval($_SESSION['form_data']['duration']) : 0;
                                                        ?>
                                                        <option value="5" <?php echo $saved_dur === 5 ? 'selected' : ''; ?>>📅 Semester 1 &nbsp;(5 months · Feb – Jun)</option>
                                                        <option value="5" <?php echo $saved_dur === 5 ? 'selected' : ''; ?>>📅 Semester 2 &nbsp;(5 months · Jul – Nov)</option>
                                                        <option value="10" <?php echo $saved_dur === 10 ? 'selected' : ''; ?>>🎓 Full Academic Year &nbsp;(10 months · Feb – Nov)</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select a semester duration.
                                                    </div>
                                                </div>
                                                <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i>Based on the academic calendar (2 semesters per year).</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Personal Information Section -->
                                    <div class="form-section animate__animated animate__fadeIn animate__delay-1s">
                                        <h4><i class="fas fa-user-circle me-2"></i>Personal Information</h4>
                                        
                                        <div class="row g-3">
                                            <!-- Course -->
                                            <div class="col-md-6">
                                                <label for="course" class="form-label">Course</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <select name="course" id="course" class="form-control" required>
                                                        <option value="">Select Course</option>
                                                        <?php foreach($courses as $course): ?>
                                                        <option value="<?php echo htmlspecialchars($course->course_fn); ?>"
                                                            <?php echo isset($_SESSION['form_data']['course']) && $_SESSION['form_data']['course'] == $course->course_fn ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($course->course_fn); ?> (<?php echo htmlspecialchars($course->course_sn); ?>)
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select your course.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Registration Number -->
                                            <div class="col-md-6">
                                                <label for="regno" class="form-label">Registration Number</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-id-card"></i>
                                                    <input type="text" name="regno" id="regno" class="form-control" 
                                                        value="<?php echo htmlspecialchars($user->regNo); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- First Name -->
                                            <div class="col-md-4">
                                                <label for="fname" class="form-label">First Name</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-user"></i>
                                                    <input type="text" name="fname" id="fname" class="form-control" 
                                                        value="<?php echo htmlspecialchars($user->firstName); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Middle Name -->
                                            <div class="col-md-4">
                                                <label for="mname" class="form-label">Middle Name</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-user"></i>
                                                    <input type="text" name="mname" id="mname" class="form-control" 
                                                        value="<?php echo htmlspecialchars($user->middleName); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Last Name -->
                                            <div class="col-md-4">
                                                <label for="lname" class="form-label">Last Name</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-user"></i>
                                                    <input type="text" name="lname" id="lname" class="form-control" 
                                                        value="<?php echo htmlspecialchars($user->lastName); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Gender -->
                                            <div class="col-md-6">
                                                <label for="gender" class="form-label">Gender</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-venus-mars"></i>
                                                    <input type="text" name="gender" class="form-control" 
                                                        value="<?php echo htmlspecialchars(ucfirst($user->gender)); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Contact Number -->
                                            <div class="col-md-6">
                                                <label for="contact" class="form-label">Contact Number</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-phone"></i>
                                                    <input type="text" name="contact" id="contact" class="form-control" 
                                                        value="<?php echo htmlspecialchars($user->contactNo); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Email -->
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email Address</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-envelope"></i>
                                                    <input type="email" name="email" id="email" class="form-control" 
                                                        value="<?php echo htmlspecialchars($user->email); ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Emergency Contact -->
                                            <div class="col-md-6">
                                                <label for="econtact" class="form-label">Emergency Contact</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-phone-alt"></i>
                                                    <input type="text" name="econtact" id="econtact" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['egycontactno']) ? htmlspecialchars($_SESSION['form_data']['egycontactno']) : ''; ?>" 
                                                        required maxlength="15" pattern="[0-9]{10,15}">
                                                    <div class="invalid-feedback">
                                                        Please provide a valid 10-15 digit emergency contact number.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Guardian Name -->
                                            <div class="col-md-4">
                                                <label for="gname" class="form-label">Guardian Name</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-user-shield"></i>
                                                    <input type="text" name="gname" id="gname" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['guardianName']) ? htmlspecialchars($_SESSION['form_data']['guardianName']) : ''; ?>" required>
                                                    <div class="invalid-feedback">
                                                        Please provide guardian's name.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                             <!-- Guardian Relation (dropdown) -->
                                            <div class="col-md-4">
                                                <label for="grelation" class="form-label">Relation</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-users"></i>
                                                    <select name="grelation" id="grelation" class="form-control" required>
                                                        <option value="">-- Select Relation --</option>
                                                        <?php
                                                        $grel_options = ['Father','Mother','Brother','Sister','Uncle','Aunt','Spouse','Grandparent','Guardian','Other'];
                                                        $saved_grel = $_SESSION['form_data']['guardianRelation'] ?? '';
                                                        foreach ($grel_options as $gr):
                                                        ?>
                                                        <option value="<?php echo $gr; ?>" <?php echo $saved_grel === $gr ? 'selected' : ''; ?>><?php echo $gr; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select your relation with the guardian.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Guardian Contact -->
                                            <div class="col-md-4">
                                                <label for="gcontact" class="form-label">Guardian Contact</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-phone"></i>
                                                    <input type="text" name="gcontact" id="gcontact" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['guardianContactno']) ? htmlspecialchars($_SESSION['form_data']['guardianContactno']) : ''; ?>" 
                                                        required maxlength="15" pattern="[0-9]{10,15}">
                                                    <div class="invalid-feedback">
                                                        Please provide guardian's 10-15 digit contact number.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Address Information Section -->
                                    <div class="form-section animate__animated animate__fadeIn animate__delay-2s">
                                        <h4><i class="fas fa-map-marker-alt me-2"></i>Address Information</h4>
                                        
                                        <!-- Corresponding Address -->
                                        <h5 class="mb-3">Correspondence Address</h5>
                                        <div class="row g-3">
                                            <!-- Address -->
                                            <div class="col-12">
                                                <label for="address" class="form-label">Full Address</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-map-pin"></i>
                                                    <textarea name="address" id="address" class="form-control" rows="3" required><?php 
                                                        echo isset($_SESSION['form_data']['corresAddress']) ? htmlspecialchars($_SESSION['form_data']['corresAddress']) : ''; 
                                                    ?></textarea>
                                                    <div class="invalid-feedback">
                                                        Please provide your correspondence address.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Country (dropdown) -->
                                            <div class="col-md-4">
                                                <label for="country" class="form-label">Country</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-globe"></i>
                                                    <select name="country" id="country" class="form-control" required>
                                                        <option value="">-- Select Country --</option>
                                                        <?php
                                                        $countries_list = [
                                                            'Tanzania','Kenya','Uganda','Rwanda','Burundi',
                                                            'Ethiopia','Somalia','South Sudan','Democratic Republic of Congo',
                                                            'Mozambique','Malawi','Zambia','Zimbabwe','Other'
                                                        ];
                                                        $saved_country = $_SESSION['form_data']['corresCountry'] ?? '';
                                                        foreach ($countries_list as $cn):
                                                        ?>
                                                        <option value="<?php echo $cn; ?>" <?php echo $saved_country === $cn ? 'selected' : ''; ?>><?php echo $cn; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select your country.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- State -->
                                            <div class="col-md-4">
                                                <label for="state" class="form-label">State</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-map"></i>
                                                    <select name="state" id="state" class="form-control" required>
                                                        <option value="">Select State</option>
                                                        <?php foreach($states as $state): ?>
                                                        <option value="<?php echo htmlspecialchars($state->State); ?>"
                                                            <?php echo isset($_SESSION['form_data']['corresState']) && $_SESSION['form_data']['corresState'] == $state->State ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($state->State); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select your state.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Permanent Address -->
                                        <div class="mt-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="adcheck" id="adcheck" value="1"
                                                    <?php echo isset($_SESSION['form_data']['adcheck']) && $_SESSION['form_data']['adcheck'] == 1 ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="adcheck">
                                                    Permanent Address same as Correspondence Address
                                                </label>
                                            </div>
                                            
                                            <h5 class="mb-3">Permanent Address</h5>
                                            <div class="row g-3">
                                                <!-- Address -->
                                                <div class="col-12">
                                                    <label for="paddress" class="form-label">Full Address</label>
                                                    <div class="icon-input">
                                                        <i class="fas fa-map-pin"></i>
                                                        <textarea name="paddress" id="paddress" class="form-control" rows="3" required><?php 
                                                            echo isset($_SESSION['form_data']['pmntAddress']) ? htmlspecialchars($_SESSION['form_data']['pmntAddress']) : ''; 
                                                        ?></textarea>
                                                        <div class="invalid-feedback">
                                                            Please provide your permanent address.
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Country (dropdown) -->
                                                <div class="col-md-4">
                                                    <label for="pcountry" class="form-label">Country</label>
                                                    <div class="icon-input">
                                                        <i class="fas fa-globe"></i>
                                                        <select name="pcountry" id="pcountry" class="form-control" required>
                                                            <option value="">-- Select Country --</option>
                                                            <?php
                                                            $saved_pcountry = $_SESSION['form_data']['pmntCountry'] ?? '';
                                                            foreach ($countries_list as $cn2):
                                                            ?>
                                                            <option value="<?php echo $cn2; ?>" <?php echo $saved_pcountry === $cn2 ? 'selected' : ''; ?>><?php echo $cn2; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Please select your country.
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- State -->
                                                <div class="col-md-4">
                                                    <label for="pstate" class="form-label">State</label>
                                                    <div class="icon-input">
                                                        <i class="fas fa-map"></i>
                                                        <select name="pstate" id="pstate" class="form-control" required>
                                                            <option value="">Select State</option>
                                                            <?php foreach($states as $state): ?>
                                                            <option value="<?php echo htmlspecialchars($state->State); ?>"
                                                                <?php echo isset($_SESSION['form_data']['pmntState']) && $_SESSION['form_data']['pmntState'] == $state->State ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($state->State); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Please select your state.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Form Buttons -->
                                    <div class="d-flex justify-content-end gap-3 mt-4">
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="fas fa-redo me-1"></i> Reset
                                        </button>
                                        <button type="submit" name="submit" class="btn btn-primary btn-submit">
                                            <i class="fas fa-paper-plane me-1"></i> Submit Registration
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script>
    // ============ ROOM CARD SELECTION ============
    function selectRoom(el, roomNo, seater, fees) {
        // Deselect all
        document.querySelectorAll('.room-card.room-available').forEach(function(c) {
            c.classList.remove('room-selected');
        });
        // Select clicked
        el.classList.add('room-selected');
        // Set hidden input values
        document.getElementById('room').value = roomNo;
        document.getElementById('seater').value = seater;
        document.getElementById('fpm').value = fees;
        // Clear error
        document.getElementById('room-error').style.setProperty('display','none','important');
        // Show info banner
        var infoDiv = document.getElementById('selected-room-info');
        var infoRow = document.getElementById('room-details-row');
        if (infoDiv && infoRow) {
            infoDiv.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> <strong>Room ' + roomNo + '</strong> selected &bull; ' + seater + ' Bed &bull; TSH ' + fees.toLocaleString() + '/= per student';
            infoRow.style.setProperty('display','flex','important');
        }
    }

    // Form validation including room selection check
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                var roomVal = document.getElementById('room') ? document.getElementById('room').value : '';
                if (!roomVal) {
                    event.preventDefault();
                    event.stopPropagation();
                    var errEl = document.getElementById('room-error');
                    if (errEl) {
                        errEl.textContent = 'Please select a room from the list above.';
                        errEl.style.setProperty('display','block','important');
                        errEl.scrollIntoView({behavior:'smooth', block:'center'});
                    }
                    return;
                }
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    <?php if(isset($_GET['success']) && isset($_SESSION['booking_success'])): ?>
    window.addEventListener('DOMContentLoaded', function() {
        var overlay = document.getElementById('bookSuccessOverlay');
        if (overlay) overlay.classList.add('show');
    });
    <?php unset($_SESSION['booking_success']); endif; ?>
    
    $(document).ready(function() {
        if ($('#adcheck').is(':checked')) {
            copyAddressFields();
        }
        
        $('#adcheck').change(function() {
            if ($(this).is(':checked')) {
                copyAddressFields();
            }
        });
        
        function copyAddressFields() {
            $('#paddress').val($('#address').val());
            // Copy country dropdown
            $('#pcountry').val($('#country').val());
            // Copy state dropdown
            $('#pstate').val($('#state').val());
        }
        
        $('input[name="econtact"], input[name="gcontact"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        if (!$('#stayf').val()) {
            var today = new Date().toISOString().split('T')[0];
            $('#stayf').val(today);
        }

        // Pre-select room if form_data session restored
        <?php if(isset($_SESSION['form_data']['roomno']) && !empty($_SESSION['form_data']['roomno'])): ?>
        var savedRoom = "<?php echo addslashes($_SESSION['form_data']['roomno']); ?>";
        if (savedRoom) {
            var cards = document.querySelectorAll('.room-available');
            cards.forEach(function(card) {
                if (card.querySelector('.room-number') && card.querySelector('.room-number').textContent.trim() === savedRoom) {
                    card.classList.add('room-selected');
                }
            });
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>
<?php
unset($_SESSION['form_data']);
?>