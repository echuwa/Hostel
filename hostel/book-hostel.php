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
            'roomno' => filter_input(INPUT_POST, 'room', FILTER_VALIDATE_INT),
            'seater' => filter_input(INPUT_POST, 'seater', FILTER_VALIDATE_INT),
            'feespm' => filter_input(INPUT_POST, 'fpm', FILTER_VALIDATE_FLOAT),
            'foodstatus' => filter_input(INPUT_POST, 'foodstatus', FILTER_VALIDATE_INT),
            'stayfrom' => filter_input(INPUT_POST, 'stayf', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'duration' => filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 12]
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

        foreach ($form_data as $key => $value) {
            if (empty($value) && $value !== 0) {
                $errors[] = ucfirst($key) . " is required";
            }
        }

        if (strlen($form_data['contactno']) < 10) $errors[] = "Invalid contact number (minimum 10 digits)";
        if (strlen($form_data['egycontactno']) < 10) $errors[] = "Invalid emergency contact number (minimum 10 digits)";
        if (strlen($form_data['guardianContactno']) < 10) $errors[] = "Invalid guardian contact number (minimum 10 digits)";
        if ($form_data['roomno'] === false) $errors[] = "Invalid room selection";
        if ($form_data['duration'] === false) $errors[] = "Invalid duration (must be 1-12 months)";

        if (empty($errors)) {
            $room_check = $mysqli->prepare("SELECT (seater - (SELECT COUNT(*) FROM registration WHERE roomno = ?)) as available FROM rooms WHERE room_no = ? FOR UPDATE");
            if ($room_check === false) {
                $errors[] = "System error: Unable to check room availability";
            } else {
                $room_check->bind_param('ii', $form_data['roomno'], $form_data['roomno']);
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
                                'iiidssisssssisssisssisss', 
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

                            $_SESSION['success'] = "Hostel room booked successfully!";
                            header("Location: room-details.php");
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

    $rooms = [];
    $room_query = "SELECT r.room_no, r.seater, r.fees FROM rooms r
                   WHERE (SELECT COUNT(*) FROM registration reg WHERE reg.roomno = r.room_no) < r.seater";
    $stmt = $mysqli->prepare($room_query);
    if (!$stmt) throw new Exception("Prepare failed: " . $mysqli->error);
    
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $res = $stmt->get_result();
    while ($room = $res->fetch_object()) $rooms[] = $room;
    $stmt->close();

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
                        <h2 class="page-title">
                            <i class="fas fa-user-graduate me-2"></i>Hostel Registration
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
                        
                        <?php if($has_booking): ?>
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
                                <i class="fas fa-edit me-2"></i>Registration Form
                            </div>
                            <div class="card-body">
                                <form method="post" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    
                                    <!-- Room Information Section -->
                                    <div class="form-section animate__animated animate__fadeIn">
                                        <h4><i class="fas fa-door-open me-2"></i>Room Information</h4>
                                        
                                        <div class="row g-3">
                                            <!-- Room Number -->
                                            <div class="col-md-6">
                                                <label for="room" class="form-label">Room Number</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-home"></i>
                                                    <select name="room" id="room" class="form-control" onChange="getSeater(this.value); checkAvailability();" required>
                                                        <option value="">Select Room</option>
                                                        <?php foreach($rooms as $room): ?>
                                                        <option value="<?php echo htmlspecialchars($room->room_no); ?>"
                                                            <?php echo isset($_SESSION['form_data']['roomno']) && $_SESSION['form_data']['roomno'] == $room->room_no ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($room->room_no); ?> (<?php echo htmlspecialchars($room->seater); ?> seater)
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select a room.
                                                    </div>
                                                </div>
                                                <div id="room-availability-status" class="small mt-1"></div>
                                                <div id="loaderIcon" class="loader mt-1"></div>
                                            </div>
                                            
                                            <!-- Seater -->
                                            <div class="col-md-6">
                                                <label for="seater" class="form-label">Seater</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-users"></i>
                                                    <input type="text" name="seater" id="seater" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['seater']) ? htmlspecialchars($_SESSION['form_data']['seater']) : ''; ?>" readonly>
                                                </div>
                                            </div>
                                            
                                            <!-- Fees -->
                                            <div class="col-md-6">
                                                <label for="fpm" class="form-label">Fees Per Month (TSH)</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-rupee-sign"></i>
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
                                            
                                            <!-- Duration -->
                                            <div class="col-md-6">
                                                <label for="duration" class="form-label">Duration (Months)</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-clock"></i>
                                                    <select name="duration" id="duration" class="form-control" required>
                                                        <option value="">Select Duration</option>
                                                        <?php for($i=1; $i<=12; $i++): ?>
                                                        <option value="<?php echo $i; ?>"
                                                            <?php echo isset($_SESSION['form_data']['duration']) && $_SESSION['form_data']['duration'] == $i ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?>
                                                        </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select duration.
                                                    </div>
                                                </div>
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
                                            
                                            <!-- Guardian Relation -->
                                            <div class="col-md-4">
                                                <label for="grelation" class="form-label">Relation</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-users"></i>
                                                    <input type="text" name="grelation" id="grelation" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['guardianRelation']) ? htmlspecialchars($_SESSION['form_data']['guardianRelation']) : ''; ?>" required>
                                                    <div class="invalid-feedback">
                                                        Please specify your relationship with guardian.
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
                                            
                                            <!-- Country -->
                                            <div class="col-md-4">
                                                <label for="country" class="form-label">Country</label>
                                                <div class="icon-input">
                                                    <i class="fas fa-globe"></i>
                                                    <input type="text" name="country" id="country" class="form-control" 
                                                        value="<?php echo isset($_SESSION['form_data']['corresCountry']) ? htmlspecialchars($_SESSION['form_data']['corresCountry']) : ''; ?>" required>
                                                    <div class="invalid-feedback">
                                                        Please provide your country.
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
                                                
                                                <!-- Country -->
                                                <div class="col-md-4">
                                                    <label for="pcountry" class="form-label">Country</label>
                                                    <div class="icon-input">
                                                        <i class="fas fa-globe"></i>
                                                        <input type="text" name="pcountry" id="pcountry" class="form-control" 
                                                            value="<?php echo isset($_SESSION['form_data']['pmntCountry']) ? htmlspecialchars($_SESSION['form_data']['pmntCountry']) : ''; ?>" required>
                                                        <div class="invalid-feedback">
                                                            Please provide your country.
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
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    function getSeater(val) {
        if (!val) return;
        
        $.ajax({
            type: "POST",
            url: "get_seater.php",
            data: 'roomid=' + val,
            success: function(data) {
                $('#seater').val(data);
            },
            error: function() {
                $('#seater').val('');
                console.error("Error fetching seater information");
            }
        });

        $.ajax({
            type: "POST",
            url: "get_seater.php",
            data: 'rid=' + val,
            success: function(data) {
                $('#fpm').val(data);
            },
            error: function() {
                $('#fpm').val('');
                console.error("Error fetching fees information");
            }
        });
    }
    
    function checkAvailability() {
        var roomno = $("#room").val();
        if (!roomno) {
            $("#room-availability-status").html('');
            return;
        }
        
        $("#loaderIcon").show();
        $("#room-availability-status").html('');
        
        $.ajax({
            url: "check_availability.php",
            data: 'roomno=' + roomno,
            type: "POST",
            success: function(data) {
                $("#room-availability-status").html(data);
                $("#loaderIcon").hide();
            },
            error: function() {
                $("#room-availability-status").html('<span class="text-danger">Error checking availability</span>');
                $("#loaderIcon").hide();
                console.error("Error checking room availability");
            }
        });
    }
    
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
            $('#pcountry').val($('#country').val());
            $('#pstate').val($('#state').val());
        }
        
        $('input[name="econtact"], input[name="gcontact"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        if (!$('#stayf').val()) {
            var today = new Date().toISOString().split('T')[0];
            $('#stayf').val(today);
        }
    });
    </script>
</body>
</html>
<?php
unset($_SESSION['form_data']);
?>