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

require_once('includes/config.php');
require_once('includes/checklogin.php');

check_login();

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

        // Validation logic (keeping original robustness)
        $required = ['roomno', 'seater', 'feespm', 'stayfrom', 'duration', 'course', 'regno', 'firstName', 'lastName', 'gender', 'contactno', 'emailid', 'egycontactno', 'guardianName', 'guardianRelation', 'guardianContactno', 'corresAddress', 'corresCountry', 'corresState', 'pmntAddress', 'pmntCountry', 'pmntState'];
        
        foreach ($required as $field) {
            if (empty($form_data[$field])) $errors[] = ucfirst($field) . " is required.";
        }

        if (empty($errors)) {
            $user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
            $pay_check = $mysqli->prepare("SELECT fees_paid, accommodation_paid FROM userregistration WHERE id = ?");
            $pay_check->bind_param('i', $user_id);
            $pay_check->execute();
            $pay_res = $pay_check->get_result()->fetch_object();
            $pay_check->close();

            if (!$pay_res || $pay_res->fees_paid < 750000 || $pay_res->accommodation_paid < 178500) {
                $errors[] = "❌ Usajili wa chumba umekataliwa: Hujakamilisha malipo ya kutosha. Unatakiwa uwe umelipa angalau TSH 750,000 (Ada) na TSH 178,500 (Accommodation).";
            } else {
                // Room availability and transaction
                $room_check = $mysqli->prepare("SELECT (seater - (SELECT COUNT(*) FROM registration WHERE roomno = ?)) as available FROM rooms WHERE room_no = ?");
                $room_check->bind_param('ss', $form_data['roomno'], $form_data['roomno']);
                $room_check->execute();
                $avail = 0; $room_check->bind_result($avail); $room_check->fetch();
                $room_check->close();

                if ($avail < 1) {
                    $errors[] = "The selected room is no longer available.";
                } else {
                    $mysqli->begin_transaction();
                    try {
                        $query = "INSERT INTO registration(roomno, seater, feespm, foodstatus, stayfrom, duration, course, regno, firstName, middleName, lastName, gender, contactno, emailid, egycontactno, guardianName, guardianRelation, guardianContactno, corresAddress, corresCountry, corresState, pmntAddress, pmntCountry, pmntState) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $mysqli->prepare($query);
                        // Corrected parameter types: 
                        // roomno(s), seater(i), feespm(d), foodstatus(i), stayfrom(s), duration(i), 
                        // course(s), regno(s), firstName(s), middleName(s), lastName(s), gender(s), 
                        // contactno(s), emailid(s), egycontactno(s), guardianName(s), guardianRelation(s), 
                        // guardianContactno(s), corresAddress(s), corresCountry(s), corresState(s), 
                        // pmntAddress(s), pmntCountry(s), pmntState(s)
                        $types = "sidisiss ssss ssss ssss sss"; // Simplified grouping for counting: 5+4+4+4+4+3 = 24
                        // Actually let's just write them clearly:
                        $stmt->bind_param('sidisi' . 'ssssss' . 'ssssss' . 'ssssss', 
                            $form_data['roomno'], 
                            $form_data['seater'], 
                            $form_data['feespm'], 
                            $form_data['foodstatus'], 
                            $form_data['stayfrom'], 
                            $form_data['duration'], 
                            $form_data['course'], 
                            $form_data['regno'], 
                            $form_data['firstName'], 
                            $form_data['middleName'], 
                            $form_data['lastName'], 
                            $form_data['gender'], 
                            $form_data['contactno'], 
                            $form_data['emailid'], 
                            $form_data['egycontactno'], 
                            $form_data['guardianName'], 
                            $form_data['guardianRelation'], 
                            $form_data['guardianContactno'], 
                            $form_data['corresAddress'], 
                            $form_data['corresCountry'], 
                            $form_data['corresState'], 
                            $form_data['pmntAddress'], 
                            $form_data['pmntCountry'], 
                            $form_data['pmntState']
                        );
                        $stmt->execute();
                        $stmt->close();
                        $mysqli->commit();

                        $_SESSION['booking_success'] = ['room' => $form_data['roomno']];
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
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        $_SESSION['form_data'] = $form_data;
        header("Location: book-hostel.php");
        exit();
    }
}

// ==================== DATA FETCHING ====================
$uid = $_SESSION['login'];
$user = $mysqli->query("SELECT * FROM userregistration WHERE email = '$uid' OR regNo = '$uid'")->fetch_object();

$booking_exists = $mysqli->query("SELECT id FROM registration WHERE emailid = '$uid' OR regno = '$uid'")->num_rows > 0;

if (!$booking_exists && ($user->fees_paid < 750000 || $user->accommodation_paid < 178500)) {
    header("Location: pay-fees.php?msg=eligibility");
    exit();
}

$rooms_by_block = [];
$res = $mysqli->query("SELECT r.*, (SELECT COUNT(*) FROM registration reg WHERE reg.roomno = r.room_no) AS occupied FROM rooms r ORDER BY r.room_no");
while ($rm = $res->fetch_object()) {
    $block = preg_match('/^(\d+)([A-Z]+)-/i', $rm->room_no, $m) ? 'Block ' . $m[1] : 'General';
    $side = preg_match('/^(\d+)([A-Z]+)-/i', $rm->room_no, $m) ? 'Side ' . strtoupper($m[2]) : 'General Wing';
    $rm->is_full = ($rm->occupied >= $rm->seater);
    $rooms_by_block[$block][$side][] = $rm;
}
ksort($rooms_by_block);

$courses = $mysqli->query("SELECT * FROM courses");
$states = $mysqli->query("SELECT * FROM states");

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Registration | HostelMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .block-section { border-radius: 20px; overflow: hidden; margin-bottom: 25px; border: 1px solid #e2e8f0; background: #fff; }
        .block-header { background: #f8fafc; padding: 15px 25px; border-bottom: 1px solid #e2e8f0; font-weight: 800; display: flex; align-items: center; justify-content: space-between; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; padding: 20px; }
        .room-card { border-radius: 16px; padding: 15px; text-align: center; border: 2px solid #f1f5f9; background: #fff; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; position: relative; }
        .room-card:hover:not(.room-full) { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(67, 97, 238, 0.1); }
        .room-card.room-selected { border-color: var(--primary); background: rgba(67, 97, 238, 0.05); }
        .room-card.room-selected::after { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: 10px; right: 10px; color: var(--primary); font-size: 1.15rem; }
        .room-card.room-full { opacity: 0.5; background: #f8fafc; cursor: not-allowed; border-color: #e2e8f0; }
        .room-number { font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .room-meta { font-size: 0.75rem; color: #64748b; margin-bottom: 10px; }
        .room-badge { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; }
        .full-badge { background: #fee2e2; color: #ef4444; }
        .avail-badge { background: #dcfce7; color: #16a34a; }
        
        .success-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; }
        .success-overlay.show { display: flex; }
        .success-modal { background: #fff; border-radius: 30px; padding: 45px; max-width: 500px; width: 90%; text-align: center; animation: zoomIn 0.5s; }
        .success-icon { width: 90px; height: 90px; background: var(--success); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 30px; box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3); }

        .form-section-head { display: flex; align-items: center; gap: 15px; margin: 40px 0 25px; }
        .form-section-head i { width: 45px; height: 45px; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .form-section-head h4 { margin: 0; font-weight: 800; color: #1e293b; }
        
        .read-only-field { background-color: #f8fafc !important; cursor: not-allowed; color: #64748b !important; border: 1px solid #e2e8f0 !important; }
    </style>
</head>
<body>
    <div class="success-overlay" id="successOverlay">
        <div class="success-modal">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h2 class="fw-800 mb-2">Room Requested!</h2>
            <p class="text-muted mb-4">Your request for Room <strong id="res-room"></strong> has been submitted. Our management will review and allocate your room shortly.</p>
            <div class="d-grid gap-3">
                <a href="dashboard.php" class="btn-modern btn-modern-primary py-3 justify-content-center">Go to Dashboard</a>
                <a href="room-details.php" class="btn-modern btn-modern-outline py-3 justify-content-center">Check Allocation Status</a>
            </div>
        </div>
    </div>

    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="mb-5 animate__animated animate__fadeInLeft">
                    <h2 class="section-title">Hostel Room Request</h2>
                    <p class="section-subtitle">Select your preferred block and room to complete your registration</p>
                </div>

                <?php if($booking_exists): ?>
                    <div class="card-modern border-0 text-center p-5 animate__animated animate__zoomIn">
                        <div class="bg-primary-light p-4 rounded-circle d-inline-flex mb-4">
                            <i class="fas fa-check-double fa-3x text-primary"></i>
                        </div>
                        <h3 class="fw-800">Booking Already Submitted</h3>
                        <p class="text-muted mx-auto mb-4" style="max-width: 500px;">You have already submitted a room request. Please visit the Room Details page to track your allocation status.</p>
                        <a href="room-details.php" class="btn-modern btn-modern-primary d-inline-flex mx-auto px-5">View Room Status</a>
                    </div>
                <?php else: ?>
                    <form method="post" id="bookForm" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="room" id="room" required>
                        <input type="hidden" name="seater" id="seater" required>
                        <input type="hidden" name="fpm" id="fpm" required>

                        <!-- ROOM SELECTION AREA -->
                        <div class="form-section-head">
                            <i class="fas fa-layer-group"></i>
                            <h4>Step 1: Choose Your Room</h4>
                        </div>

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card-modern border-0 p-4 mb-4">
                            <ul class="nav nav-pills custom-nav-pills mb-4" id="blockTabs" role="tablist">
                                <?php $i=0; foreach($rooms_by_block as $block => $wings): ?>
                                    <li class="nav-item">
                                        <button class="nav-link <?php echo $i==0?'active':''; ?>" id="t-<?php echo str_replace(' ','',$block); ?>" data-bs-toggle="pill" data-bs-target="#p-<?php echo str_replace(' ','',$block); ?>" type="button"><?php echo $block; ?></button>
                                    </li>
                                <?php $i++; endforeach; ?>
                            </ul>

                            <div class="tab-content" id="blockContent">
                                <?php $i=0; foreach($rooms_by_block as $block => $wings): ?>
                                    <div class="tab-pane fade <?php echo $i==0?'show active':''; ?>" id="p-<?php echo str_replace(' ','',$block); ?>">
                                        <ul class="nav nav-tabs custom-nav-tabs mb-4 px-2" role="tablist">
                                            <?php $j=0; foreach($wings as $wing => $rooms): ?>
                                                <li class="nav-item">
                                                    <button class="nav-link <?php echo $j==0?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#w-<?php echo str_replace(' ','',$block.$wing); ?>" type="button"><?php echo $wing; ?></button>
                                                </li>
                                            <?php $j++; endforeach; ?>
                                        </ul>
                                        <div class="tab-content">
                                            <?php $j=0; foreach($wings as $wing => $rooms): ?>
                                                <div class="tab-pane fade <?php echo $j==0?'show active':''; ?>" id="w-<?php echo str_replace(' ','',$block.$wing); ?>">
                                                    <div class="room-grid">
                                                        <?php foreach($rooms as $rm): ?>
                                                            <div class="room-card <?php echo $rm->is_full ? 'room-full' : ''; ?>" 
                                                                onclick="<?php echo $rm->is_full ? '' : "selectRoom(this, '$rm->room_no', $rm->seater, $rm->fees)"; ?>">
                                                                <div class="room-number"><?php echo $rm->room_no; ?></div>
                                                                <div class="room-meta">
                                                                    <span><?php echo $rm->seater; ?> Bed</span><br>
                                                                    <span>TSH <?php echo number_format($rm->fees); ?></span>
                                                                </div>
                                                                <div class="room-badge <?php echo $rm->is_full?'full-badge':'avail-badge'; ?>">
                                                                    <?php echo $rm->is_full?'FULL':($rm->seater - $rm->occupied).' Left'; ?>
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
                        </div>

                        <!-- STAY DETAILS -->
                        <div class="form-section-head">
                            <i class="fas fa-calendar-alt"></i>
                            <h4>Step 2: Stay Information</h4>
                        </div>
                        <div class="card-modern border-0 p-5">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Stay From</label>
                                        <input type="date" name="stayf" class="form-control form-control-modern" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Semester Duration</label>
                                        <select name="duration" class="form-select form-control-modern" required>
                                            <option value="">Select Semester Plan</option>
                                            <option value="5">Semester 1 (5 Months · Feb-Jun)</option>
                                            <option value="5" style="border-top:1px dashed #eee">Semester 2 (5 Months · Jul-Nov)</option>
                                            <option value="10">Full Academic Year (10 Months)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Food Service</label>
                                        <div class="d-flex gap-4 mt-2">
                                            <div class="form-check custom-check">
                                                <input class="form-check-input" type="radio" name="foodstatus" id="foodNo" value="0" checked>
                                                <label class="form-check-label fw-600" for="foodNo">Self Catering</label>
                                            </div>
                                            <div class="form-check custom-check">
                                                <input class="form-check-input" type="radio" name="foodstatus" id="foodYes" value="1">
                                                <label class="form-check-label fw-600" for="foodYes">Hostel Dining</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PERSONAL & GUARDIAN INFO -->
                        <div class="form-section-head">
                            <i class="fas fa-user-shield"></i>
                            <h4>Step 3: Personal & Guardian Info</h4>
                        </div>
                        <div class="card-modern border-0 p-5">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Your Course</label>
                                        <select name="course" class="form-select form-control-modern" required>
                                            <option value="">Select Course</option>
                                            <?php $courses->data_seek(0); while($c = $courses->fetch_object()): ?>
                                                <option value="<?php echo $c->course_fn; ?>"><?php echo $c->course_sn; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Registration Number</label>
                                        <input type="text" name="regno" class="form-control form-control-modern read-only-field" readonly value="<?php echo $user->regNo; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Mobile Number</label>
                                        <input type="text" name="contact" class="form-control form-control-modern read-only-field" readonly value="<?php echo $user->contactNo; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Full Name</label>
                                        <input type="text" name="fname" class="form-control form-control-modern read-only-field" readonly value="<?php echo $user->firstName.' '.$user->lastName; ?>">
                                        <input type="hidden" name="mname" value="<?php echo $user->middleName; ?>">
                                        <input type="hidden" name="lname" value="<?php echo $user->lastName; ?>">
                                        <input type="hidden" name="gender" value="<?php echo $user->gender; ?>">
                                        <input type="hidden" name="email" value="<?php echo $user->email; ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Guardian Full Name</label>
                                        <input type="text" name="gname" class="form-control form-control-modern" required placeholder="Full name of parent/guardian">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Guardian Relation</label>
                                        <select name="grelation" class="form-select form-control-modern" required>
                                            <option value="">Select Relation</option>
                                            <option value="Father">Father</option>
                                            <option value="Mother">Mother</option>
                                            <option value="Brother">Brother</option>
                                            <option value="Sister">Sister</option>
                                            <option value="Uncle">Uncle</option>
                                            <option value="Aunt">Aunt</option>
                                            <option value="Guardian">Legal Guardian</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Guardian Mobile Number</label>
                                        <input type="tel" name="gcontact" class="form-control form-control-modern" required placeholder="e.g. 255712345678">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Emergency Contact Number</label>
                                        <input type="tel" name="econtact" class="form-control form-control-modern" required placeholder="In case of emergency">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ADDRESS INFO -->
                        <div class="form-section-head">
                            <i class="fas fa-map-marker-alt"></i>
                            <h4>Step 4: Contact Addresses</h4>
                        </div>
                        <div class="card-modern border-0 p-5 mb-5">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Correspondence Address</label>
                                        <textarea name="address" id="cor-addr" class="form-control form-control-modern" rows="3" required placeholder="P.O Box / Street Name..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Country</label>
                                        <select name="country" id="cor-country" class="form-select form-control-modern" required>
                                            <option value="">Choose Country</option>
                                            <option value="Tanzania">Tanzania</option>
                                            <option value="Kenya">Kenya</option>
                                            <option value="Uganda">Uganda</option>
                                            <option value="Rwanda">Rwanda</option>
                                            <option value="Zambia">Zambia</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Region / State</label>
                                        <select name="state" id="cor-state" class="form-select form-control-modern" required>
                                            <option value="">Select Region</option>
                                            <?php $states->data_seek(0); while($s = $states->fetch_object()): ?>
                                                <option value="<?php echo $s->State; ?>"><?php echo $s->State; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 pt-4">
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="copyAddr">
                                        <label class="form-check-label fw-800 text-primary" for="copyAddr">Permanent address is same as above</label>
                                    </div>
                                    <hr class="mb-5 opacity-25">
                                    
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Permanent Address</label>
                                        <textarea name="paddress" id="perm-addr" class="form-control form-control-modern" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Permanent Country</label>
                                        <select name="pcountry" id="perm-country" class="form-select form-control-modern" required>
                                            <option value="">Choose Country</option>
                                            <option value="Tanzania">Tanzania</option>
                                            <option value="Kenya">Kenya</option>
                                            <option value="Uganda">Uganda</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Permanent State / Region</label>
                                        <select name="pstate" id="perm-state" class="form-select form-control-modern" required>
                                            <option value="">Select Region</option>
                                            <?php $states->data_seek(0); while($s = $states->fetch_object()): ?>
                                                <option value="<?php echo $s->State; ?>"><?php echo $s->State; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTIONS -->
                        <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm mb-5 sticky-bottom border" style="bottom: 20px;">
                            <div class="small text-muted fw-600">
                                <i class="fas fa-info-circle me-1"></i> Ensure all selection are correct before submitting
                            </div>
                            <button type="submit" name="submit" class="btn-modern btn-modern-primary px-5 py-3 shadow-lg">
                                <i class="fas fa-paper-plane me-2"></i> SUBMIT ROOM REQUEST
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRoom(card, room, seater, fees) {
            document.querySelectorAll('.room-card').forEach(c => c.classList.remove('room-selected'));
            card.classList.add('room-selected');
            document.getElementById('room').value = room;
            document.getElementById('seater').value = seater;
            document.getElementById('fpm').value = fees;
        }

        $(document).ready(function() {
            $('#copyAddr').change(function() {
                if(this.checked) {
                    $('#perm-addr').val($('#cor-addr').val());
                    $('#perm-country').val($('#cor-country').val());
                    $('#perm-state').val($('#cor-state').val());
                }
            });

            <?php if(isset($_GET['success'])): ?>
                $('#res-room').text("<?php echo $_SESSION['booking_success']['room'] ?? ''; ?>");
                $('#successOverlay').addClass('show');
            <?php unset($_SESSION['booking_success']); endif; ?>
        });
    </script>
</body>
</html>