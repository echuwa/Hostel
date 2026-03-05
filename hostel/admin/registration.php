<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('includes/config.php');

function generateControlNumber() {
    return "99" . rand(10, 99) . date('md') . rand(100, 999) . rand(1000, 9999);
}

if(isset($_POST['submit'])) {
    // Generate registration number
    $year = date('y'); // Last two digits of current year
    $quarter = ceil(date('n') / 3); // Get current quarter (1-4)
    $prefix = "T{$year}-0{$quarter}-";
    
    // Get the highest existing registration number for this quarter
    $result = $mysqli->query("SELECT MAX(regNo) FROM userregistration WHERE regNo LIKE '$prefix%'");
    $row = $result->fetch_array();
    $lastRegNo = $row[0] ?? null;
    
    if ($lastRegNo) {
        $lastNumber = intval(substr($lastRegNo, strlen($prefix)));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $regno = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    
    // Sanitize inputs with proper null checks
    $fname = isset($_POST['fname']) ? htmlspecialchars(trim($_POST['fname'])) : '';
    $mname = isset($_POST['mname']) ? htmlspecialchars(trim($_POST['mname'])) : '';
    $lname = isset($_POST['lname']) ? htmlspecialchars(trim($_POST['lname'])) : '';
    $gender = $_POST['gender'] ?? '';
    $contactno = isset($_POST['contact']) ? '255' . preg_replace('/[^0-9]/', '', $_POST['contact']) : '';
    $emailid = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    
    // GPS Data
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
    $city = !empty($_POST['city']) ? htmlspecialchars($_POST['city']) : null;
    $state_gps = !empty($_POST['state_gps']) ? htmlspecialchars($_POST['state_gps']) : null;
    $country_gps = !empty($_POST['country_gps']) ? htmlspecialchars($_POST['country_gps']) : null;
    $location_captured_at = (!empty($latitude) && !empty($longitude)) ? date('Y-m-d H:i:s') : null;
    
    // Validate inputs
    $errors = [];
    if(empty($fname)) $errors[] = "First name is required";
    if(empty($lname)) $errors[] = "Last name is required";
    if(empty($gender)) $errors[] = "Gender is required";
    if(!preg_match('/^255\d{9}$/', $contactno)) $errors[] = "Contact number must be 12 digits starting with 255 (255XXXXXXXXX)";
    if(!filter_var($emailid, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if(strlen($_POST['password'] ?? '') < 6) $errors[] = "Password must be at least 6 characters";
    if(($_POST['password'] ?? '') !== ($_POST['cpassword'] ?? '')) $errors[] = "Passwords do not match";

    if(empty($errors)) {
        // Check if email already exists
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM userregistration WHERE email=?");
        $stmt->bind_param('s', $emailid);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if($count > 0) {
            $_SESSION['error'] = "Email already registered. Please use a different email.";
        } else {
            // Generate Control Numbers
            $fee_ctrl = generateControlNumber();
            $acc_ctrl = generateControlNumber();
            $reg_ctrl = generateControlNumber();

            // All new admin registrations start with 0 payment. Students pay via their account.
            $fees_paid = 0;
            $accommodation_paid = 0;
            $registration_paid = 0;
            $payment_status = "Pending";
            $fee_status = 0;

            $query = "INSERT INTO userregistration(regNo,firstName,middleName,lastName,gender,contactNo,email,password,fees_paid,accommodation_paid,registration_paid,payment_status,fee_status,fee_control_no,acc_control_no,reg_control_no,latitude,longitude,city,state,country,location_captured_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $mysqli->prepare($query);
            if(!$stmt) {
                $_SESSION['error'] = "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
            } else {
                $stmt->bind_param('ssssssssdddsisssddssss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $password, $fees_paid, $accommodation_paid, $registration_paid, $payment_status, $fee_status, $fee_ctrl, $acc_ctrl, $reg_ctrl, $latitude, $longitude, $city, $state_gps, $country_gps, $location_captured_at);
                
                if($stmt->execute()) {
                    $room = isset($_POST['room']) ? htmlspecialchars(trim($_POST['room'])) : '';
                    $seater = isset($_POST['seater']) ? intval($_POST['seater']) : 0;
                    $fpm = isset($_POST['fpm']) ? intval($_POST['fpm']) : 0;
                    $foodstatus = 0; // Default to 0 as requested
                    $stayf = isset($_POST['stayf']) ? htmlspecialchars(trim($_POST['stayf'])) : '';
                    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
                    
                    if (!empty($room)) {
                        // Adding missing mandatory fields for registration table to avoid SQL errors
                        $empty = "";
                        $zero = 0;
                        $regQuery = "INSERT INTO registration(roomno, seater, feespm, foodstatus, stayfrom, duration, regno, firstName, middleName, lastName, gender, contactno, emailid, course, egycontactno, guardianName, guardianRelation, guardianContactno, corresAddress, corresCountry, corresState, pmntAddress, pmntCountry, pmntState) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        $regStmt = $mysqli->prepare($regQuery);
                        if($regStmt) {
                            $regStmt->bind_param('siiisissssssssssssssssss', 
                                $room, $seater, $fpm, $foodstatus, $stayf, $duration, $regno, $fname, $mname, $lname, $gender, $contactno, $emailid,
                                $empty, // course
                                $empty, // egycontactno
                                $empty, // guardianName
                                $empty, // guardianRelation
                                $empty, // guardianContactno
                                $empty, // corresAddress
                                $country_gps ?: 'Tanzania', // corresCountry
                                $state_gps ?: $empty, // corresState
                                $empty, // pmntAddress
                                $country_gps ?: 'Tanzania', // pmntCountry
                                $state_gps ?: $empty  // pmntState
                            );
                            $regStmt->execute();
                            $regStmt->close();
                        }
                    }
                    
                    $_SESSION['email_for_login']    = $emailid;
                    $_SESSION['registration_number'] = $regno;
                    $_SESSION['reg_success'] = [
                        'name'  => "$fname $lname",
                        'regno' => $regno,
                        'email' => $emailid,
                    ];
                    header("Location: registration.php?registered=1");
                    exit();
                } else {
                    $_SESSION['error'] = "Execution failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
}

// Fetch ALL rooms with occupancy count (including full ones)
$rooms = [];
$room_query = "SELECT r.room_no, r.seater, r.fees,
                (SELECT COUNT(*) FROM registration reg WHERE reg.roomno = r.room_no) AS occupied
               FROM rooms r
               ORDER BY r.room_no";
if($stmt = $mysqli->prepare($room_query)) {
    $stmt->execute();
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
}

// Group rooms by block and side
$rooms_by_block = [];
foreach ($rooms as $rm) {
    $rooms_by_block[$rm->block][$rm->side][] = $rm;
}
ksort($rooms_by_block);
foreach($rooms_by_block as $b => $s) {
    ksort($rooms_by_block[$b]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | Hostel Management</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Styling -->
	<link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .reg-card { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden; }
        .form-section-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
        .form-section-title i { color: #4361ee; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.95rem; transition: all 0.2s; }
        .form-control:focus, .form-select:focus { background: #fff; border-color: #4361ee; box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }
        .room-picker-btn { background: #eff6ff; color: #3b82f6; border: 2px dashed #3b82f6; border-radius: 16px; padding: 24px; text-align: center; cursor: pointer; transition: 0.2s; width: 100%; border-style: dashed !important; }
        .room-picker-btn:hover { background: #dbeafe; transform: scale(1.01); }
        .success-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 9999; backdrop-filter: blur(8px); align-items: center; justify-content: center; }
        .success-overlay.show { display: flex; }
        .success-modal { background: #fff; border-radius: 30px; padding: 40px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,.3); }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding: 10px; }
        .room-card-mini { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: center; cursor: pointer; transition: 0.2s; }
        .room-card-mini:hover:not(.full) { border-color: #4361ee; background: #f8fafc; }
        .room-card-mini.full { opacity: 0.5; cursor: not-allowed; background: #f1f5f9; }
        .room-card-mini.selected { border-color: #4361ee; background: #eff6ff; box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1); }

        /* Fancy Header Styling */
        .fancy-header-card {
            background: linear-gradient(135deg, #4361ee 0%, #3a7bd5 100%);
            border-radius: 20px;
            padding: 30px;
            color: #fff;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
        }
        .fancy-header-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .header-icon-badge {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 20px;
        }

        /* GPS Map Styling */
        #admin-map { width: 100%; transition: all 0.3s ease; }
        .gps-active { border-color: #4361ee !important; box-shadow: 0 0 0 4px rgba(67,97,238,0.1) !important; }

        /* Premium GPS Button Styling */
        .btn-gps-trigger {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 18px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            text-align: left;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .btn-gps-trigger:hover {
            border-color: #4361ee;
            background: #f8faff;
            transform: translateY(-2px);
        }
        .gps-icon-box {
            width: 40px; height: 40px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-right: 12px; color: #4361ee; font-size: 1.1rem;
        }
        .btn-gps-trigger:hover .gps-icon-box {
            background: #4361ee; color: #ffffff;
        }
        .gps-text-box { flex-grow: 1; }
        .gps-label { display: block; font-size: 0.6rem; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; text-transform: uppercase; }
        .gps-sub { display: block; font-size: 0.8rem; font-weight: 700; color: #1e293b; }
    </style>
    <!-- Leaflet Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>
        <div class="main-content">
            <div class="content-wrapper">
                <div class="fancy-header-card d-flex align-items-center mb-5 animate__animated animate__fadeInDown">
                    <div class="header-icon-badge shadow-lg">
                        <i class="fas fa-user-plus text-white"></i>
                    </div>
                    <div>
                        <h2 class="fw-800 mb-1" style="letter-spacing: -0.5px;">Student Enrollment</h2>
                        <p class="mb-0 opacity-75 fw-500">Add a new student to the hostel system and allocate their living space.</p>
                    </div>
                </div>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-4 p-4 mb-4 shadow-sm border-0 animate__animated animate__shakeX">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-800 text-danger">Registration Error</h6>
                                <p class="mb-0 small fw-600"><?php echo $_SESSION['error']; ?></p>
                            </div>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['errors'])): ?>
                    <div class="alert alert-warning rounded-4 p-4 mb-4 shadow-sm border-0 animate__animated animate__fadeInRight">
                        <h6 class="mb-2 fw-800 text-warning text-uppercase small">Validation Warnings</h6>
                        <ul class="mb-0 small fw-600">
                            <?php foreach($_SESSION['errors'] as $e): ?>
                                <li><?php echo $e; ?></li>
                            <?php endforeach; unset($_SESSION['errors']); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" onsubmit="return validateForm()">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="reg-card p-4 h-100 shadow-sm animate__animated animate__fadeInLeft">
                                <h5 class="form-section-title"><i class="fas fa-user-circle"></i> Student Profile Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-800 text-muted">FIRST NAME</label>
                                        <input type="text" name="fname" class="form-control" placeholder="e.g. John" required value="<?php echo $_POST['fname'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-800 text-muted">MIDDLE NAME</label>
                                        <input type="text" name="mname" class="form-control" placeholder="Optional" value="<?php echo $_POST['mname'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-800 text-muted">LAST NAME</label>
                                        <input type="text" name="lname" class="form-control" placeholder="e.g. Doe" required value="<?php echo $_POST['lname'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-800 text-muted">GENDER</label>
                                        <select name="gender" class="form-select" required>
                                            <option value="" disabled selected>Select gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-800 text-muted">MOBILE NUMBER</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 fw-800 text-primary">+255</span>
                                            <input type="tel" name="contact" id="contact" class="form-control border-start-0 ps-0" placeholder="7XXXXXXXX" required value="<?php echo isset($_POST['contact']) ? (str_starts_with($_POST['contact'], '255') ? substr($_POST['contact'], 3) : htmlspecialchars($_POST['contact'])) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-800 text-muted">EMAIL ADDRESS (FOR LOGIN)</label>
                                        <input type="email" name="email" class="form-control" placeholder="student@example.com" required value="<?php echo $_POST['email'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-800 text-muted">LOGIN PASSWORD</label>
                                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-800 text-muted">REPEAT PASSWORD</label>
                                        <input type="password" name="cpassword" id="cpassword" class="form-control" placeholder="••••••••" required>
                                    </div>
                                    
                                    <!-- GPS Intelligence integration -->
                                    <div class="col-12 mt-4 pt-4 border-top">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 class="fw-800 text-primary mb-0"><i class="fas fa-satellite-dish me-2"></i>Resident Data GPS Intelligence</h6>
                                        </div>
                                        
                                        <div class="d-flex gap-2 mb-3">
                                            <button type="button" class="btn-gps-trigger" onclick="toggleGPSMap()">
                                                <div class="gps-icon-box">
                                                    <i class="fas fa-map-location-dot"></i>
                                                </div>
                                                <div class="gps-text-box">
                                                    <span class="gps-label">LOCATION INTEL</span>
                                                    <span class="gps-sub" id="gpsBtnText">Pin Residential Location</span>
                                                </div>
                                                <div class="px-2 text-muted opacity-50">
                                                    <i class="fas fa-chevron-right"></i>
                                                </div>
                                            </button>
                                        </div>
                                        
                                        <div id="gps-collapsible" style="display: none;">
                                            <div id="admin-map" style="height: 250px; border-radius: 16px; border: 2px solid #eef2ff;" class="mb-3 shadow-sm"></div>
                                            <div id="gps-preview" class="p-3 bg-light rounded-4 small fw-600 text-muted">
                                                Click map to pinpoint residential address.
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" id="latitude" name="latitude">
                                        <input type="hidden" id="longitude" name="longitude">
                                        <input type="hidden" id="city" name="city">
                                        <input type="hidden" id="state_gps" name="state_gps">
                                        <input type="hidden" id="country_gps" name="country_gps">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="reg-card p-4 h-100 shadow-sm animate__animated animate__fadeInRight">
                                <h5 class="form-section-title"><i class="fas fa-building"></i> Accommodation & Period</h5>
                                <input type="hidden" name="room" id="room" required>
                                <input type="hidden" name="seater" id="seater">
                                <input type="hidden" name="fpm" id="fpm">
                                
                                <div id="roomSelector" class="room-picker-btn mb-4 border-primary bg-primary-subtle" data-bs-toggle="modal" data-bs-target="#roomModal">
                                    <div id="noRoomView">
                                        <div class="bg-primary text-white d-inline-flex p-3 rounded-circle mb-3 shadow-sm">
                                            <i class="fas fa-search-plus fs-4"></i>
                                        </div>
                                        <div class="fw-800 fs-5 text-primary">SELECT STUDENT BED</div>
                                        <p class="small text-muted mb-0 fw-600">Click to browse available rooms</p>
                                    </div>
                                    <div id="activeRoomView" style="display:none;">
                                        <div class="badge bg-primary rounded-pill px-3 py-2 mb-2">ALLOCATED</div>
                                        <h3 id="displayRoomNo" class="fw-800 text-dark mb-1"></h3>
                                        <div id="displayRoomMeta" class="small text-muted fw-800 text-uppercase"></div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-5">
                                    <div class="col-6">
                                        <label class="form-label small fw-800 text-muted">EFFECTIVE FROM</label>
                                        <input type="date" name="stayf" class="form-control bg-white" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-800 text-muted">STAY DURATION</label>
                                        <select name="duration" class="form-select bg-white" required>
                                            <option value="5">One Semester (5 Months)</option>
                                            <option value="10">Academic Year (10 Months)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Hidden Food Option (Disabled as requested) -->
                                <input type="hidden" name="foodstatus" value="0">

                                <div class="pt-2">
                                    <button type="submit" name="submit" class="btn btn-primary w-100 rounded-4 py-4 fw-800 shadow-lg d-flex align-items-center justify-content-center">
                                        <i class="fas fa-check-circle me-3 fs-5"></i> COMPLETE ENROLLMENT
                                    </button>
                                    <div class="text-center mt-3">
                                        <p class="small text-muted fw-600 mb-0">System will autogenerate registration number upon success.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Room Selection Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 30px;">
                <div class="modal-header border-0 px-5 pt-5 pb-0">
                    <div>
                        <h4 class="fw-800 text-dark mb-1">Available Inventory</h4>
                        <p class="text-muted small mb-0 fw-600">Pick a bed for the student from the catalog below.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-5 pt-4">
                    <ul class="nav nav-pills mb-5 p-2 bg-light rounded-4" role="tablist">
                        <?php $bi=0; foreach ($rooms_by_block as $bn => $wings): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill px-4 py-2 fw-700 <?php echo $bi===0?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#m-pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$bn); ?>" type="button"><?php echo $bn; ?></button>
                            </li>
                        <?php $bi++; endforeach; ?>
                    </ul>
                    <div class="tab-content">
                        <?php $bi=0; foreach ($rooms_by_block as $bn => $wings): ?>
                            <div class="tab-pane fade <?php echo $bi===0?'show active':''; ?>" id="m-pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$bn); ?>">
                                <?php foreach ($wings as $sn => $s_rooms): ?>
                                    <div class="mb-5">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary-subtle p-2 rounded-circle me-3">
                                                <div style="width:10px; height:10px; background:var(--primary); border-radius:50%;"></div>
                                            </div>
                                            <h6 class="fw-800 text-dark small text-uppercase mb-0" style="letter-spacing: 1px;"><?php echo $sn; ?></h6>
                                        </div>
                                        <div class="room-grid">
                                            <?php foreach ($s_rooms as $rm): 
                                                $full = $rm->is_full;
                                                $remaining = $rm->seater - $rm->occupied;
                                            ?>
                                                <div class="room-card-mini <?php echo $full?'full':''; ?> border shadow-sm" style="border-radius: 20px;"
                                                     onclick="pickRoom(this, '<?php echo $rm->room_no; ?>', <?php echo $rm->seater; ?>, <?php echo $rm->fees; ?>, <?php echo $rm->occupied; ?>)">
                                                    <div class="fw-800 text-dark fs-5 mb-1"><?php echo $rm->room_no; ?></div>
                                                    <div class="small fw-700 text-muted mb-3" style="font-size: 0.7rem;">
                                                        <i class="fas fa-users me-1"></i> <?php echo $rm->occupied; ?> / <?php echo $rm->seater; ?> BEDS
                                                    </div>
                                                    <?php if($full): ?>
                                                        <span class="badge bg-danger text-white rounded-pill w-100 py-2" style="font-size: 0.6rem; letter-spacing: 0.5px;">UNAVAILABLE</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success rounded-pill w-100 py-2" style="font-size: 0.6rem; letter-spacing: 0.5px;"><?php echo $remaining; ?> SPOTS LEFT</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php $bi++; endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 p-5 pt-0">
                    <button type="button" class="btn btn-primary rounded-pill px-5 py-3 fw-800 shadow-sm" data-bs-dismiss="modal">Confirm Selection</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Overlay -->
    <div class="success-overlay" id="regSuccessOverlay">
        <div class="success-modal animate__animated animate__zoomIn">
            <div class="success-icon-container mx-auto mb-4">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="fw-800 text-dark mb-1">Success!</h2>
            <p class="text-muted fw-600 mb-4">The student has been successfully enrolled.</p>
            
            <?php if(isset($_SESSION['reg_success'])): $rs = $_SESSION['reg_success']; ?>
            <div class="bg-light rounded-4 p-4 text-start mt-4 border border-2">
                <div class="mb-3">
                    <span class="text-muted small fw-800 text-uppercase d-block mb-1">Student Name</span>
                    <span class="fw-700 text-dark fs-5"><?php echo htmlspecialchars($rs['name']); ?></span>
                </div>
                <div class="row">
                    <div class="col-6 border-end">
                        <span class="text-muted small fw-800 text-uppercase d-block mb-1">Reg Number</span>
                        <span class="fw-800 text-primary"><?php echo htmlspecialchars($rs['regno']); ?></span>
                    </div>
                    <div class="col-6 ps-3">
                        <span class="text-muted small fw-800 text-uppercase d-block mb-1">Status</span>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3">ACTIVE</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-5 pt-2 d-grid gap-3">
                <button class="btn btn-primary rounded-pill py-3 fw-800 shadow-sm" onclick="location.href='registration.php'">ENROLL ANOTHER STUDENT</button>
                <a href="manage-students.php" class="btn btn-link text-muted fw-700 text-decoration-none">VIEW DIRECTORY</a>
            </div>
        </div>
    </div>

    <style>
        .success-icon-container {
            width: 90px;
            height: 90px;
            background: #10b981;
            color: white;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            transform: rotate(-10deg);
        }
        .room-picker-btn {
            border: 2px dashed #cbd5e1 !important;
            transition: all 0.3s ease;
        }
        .room-picker-btn:hover {
            border-color: var(--primary) !important;
            background: #f0f7ff !important;
        }
        .room-card-mini {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .room-card-mini:hover:not(.full) {
            transform: translateY(-5px);
            border-color: var(--primary) !important;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
        }
        .room-card-mini.selected {
            background-color: #f0f7ff;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1) !important;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function pickRoom(el, rNo, seater, fees, occupied) { 
        if (el.classList.contains('full')) return; 
        document.querySelectorAll('.room-card-mini').forEach(c => c.classList.remove('selected')); 
        el.classList.add('selected'); 
        document.getElementById('room').value = rNo; 
        document.getElementById('seater').value = seater; 
        document.getElementById('fpm').value = fees; 
        document.getElementById('noRoomView').style.display = 'none'; 
        document.getElementById('activeRoomView').style.display = 'block'; 
        document.getElementById('displayRoomNo').innerText = 'Room ' + rNo; 
        document.getElementById('displayRoomMeta').innerText = occupied + '/' + seater + ' BEDS ALLOCATED • TSH ' + parseInt(fees).toLocaleString(); 
    }
    function validateForm() { 
        const pass = document.getElementById('password').value; 
        const cpass = document.getElementById('cpassword').value; 
        if (pass !== cpass) { alert("Passwords do not match!"); return false; } 
        if (!document.getElementById('room').value) { alert("Please allocate a room for the student!"); return false; } 
        return true; 
    }
    <?php if(isset($_GET['registered'])): ?>
    document.addEventListener('DOMContentLoaded', () => { 
        document.getElementById('regSuccessOverlay').classList.add('show'); 
        <?php unset($_SESSION['reg_success']); ?> 
    });
    <?php endif; ?>
    const contactInput = document.getElementById('contact');
    if (contactInput) {
        contactInput.addEventListener('input', function() { 
            this.value = this.value.replace(/[^0-9]/g, ''); 
            if (this.value.length > 9) this.value = this.value.substring(0, 9); 
        });
    }

    // GPS Map Intelligence for Admin
    let adminMap, adminMarker;
    let gpsVisible = false;

    function toggleGPSMap() {
        gpsVisible = !gpsVisible;
        const wrap = document.getElementById('gps-collapsible');
        const btnText = document.getElementById('gpsBtnText');
        wrap.style.display = gpsVisible ? 'block' : 'none';
        btnText.innerText = gpsVisible ? 'Close Map' : 'Pin Location';
        
        if (gpsVisible) {
            if (!adminMap) {
                adminMap = L.map('admin-map').setView([-6.7924, 39.2083], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(adminMap);
                adminMarker = L.marker([-6.7924, 39.2083], {draggable: true}).addTo(adminMap);
                
                adminMap.on('click', function(e) {
                    updateGPS(e.latlng.lat, e.latlng.lng);
                });
                adminMarker.on('dragend', function() {
                    const {lat, lng} = adminMarker.getLatLng();
                    updateGPS(lat, lng);
                });

                // Auto-detect current location if possible
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((pos) => {
                        const {latitude, longitude} = pos.coords;
                        adminMap.setView([latitude, longitude], 15);
                        updateGPS(latitude, longitude);
                    });
                }
            }
            setTimeout(() => adminMap.invalidateSize(), 200);
        }
    }

    function updateGPS(lat, lng) {
        adminMarker.setLatLng([lat, lng]);
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18`)
            .then(res => res.json())
            .then(data => {
                if (data.address) {
                    const city = data.address.city || data.address.town || data.address.village || "N/A";
                    const state = data.address.state || data.address.region || "N/A";
                    const country = data.address.country || "N/A";
                    
                    document.getElementById('city').value = city;
                    document.getElementById('state_gps').value = state;
                    document.getElementById('country_gps').value = country;
                    
                    document.getElementById('gps-preview').innerHTML = `
                        <div class='text-dark fw-800 mb-1'><i class='fas fa-map-pin text-primary me-2'></i>${city}, ${state}</div>
                        <div class='text-muted small'>${country} • Captured at ${new Date().toLocaleString()}</div>
                    `;
                }
            });
    }
    </script>
</body>
</html>
