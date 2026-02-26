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
    $contactno = isset($_POST['contact']) ? preg_replace('/[^0-9]/', '', $_POST['contact']) : '';
    $emailid = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    
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

            $query = "INSERT INTO userregistration(regNo,firstName,middleName,lastName,gender,contactNo,email,password,fees_paid,accommodation_paid,registration_paid,payment_status,fee_status,fee_control_no,acc_control_no,reg_control_no) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $mysqli->prepare($query);
            if(!$stmt) {
                $_SESSION['error'] = "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
            } else {
                $stmt->bind_param('ssssssssdddsisss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $password, $fees_paid, $accommodation_paid, $registration_paid, $payment_status, $fee_status, $fee_ctrl, $acc_ctrl, $reg_ctrl);
                
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
                                $empty, // corresCountry
                                $empty, // corresState
                                $empty, // pmntAddress
                                $empty, // pmntCountry
                                $empty  // pmntState
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
	<link rel="stylesheet" href="css/modern.css">
    
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
    </style>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>
        <div class="main-content">
            <div class="content-wrapper">
                <div class="fancy-header-card d-flex align-items-center mb-4">
                    <div class="header-icon-badge">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-0">Student Registration</h2>
                        <p class="mb-0 opacity-75">Register a new student and assign a room in the system.</p>
                    </div>
                </div>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-4 p-3 mb-4 shadow-sm border-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger rounded-4 p-3 mb-4 shadow-sm border-0">
                        <ul class="mb-0 small">
                            <?php foreach($_SESSION['errors'] as $e): ?>
                                <li><?php echo $e; ?></li>
                            <?php endforeach; unset($_SESSION['errors']); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" onsubmit="return validateForm()">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="reg-card p-4 h-100 shadow-sm">
                                <h5 class="form-section-title"><i class="fas fa-user"></i> Personal Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label small fw-bold">First Name</label><input type="text" name="fname" class="form-control" required value="<?php echo $_POST['fname'] ?? ''; ?>"></div>
                                    <div class="col-md-6"><label class="form-label small fw-bold">Last Name</label><input type="text" name="lname" class="form-control" required value="<?php echo $_POST['lname'] ?? ''; ?>"></div>
                                    <div class="col-md-6"><label class="form-label small fw-bold">Gender</label><select name="gender" class="form-select" required><option value="">Select</option><option value="male">Male</option><option value="female">Female</option></select></div>
                                    <div class="col-md-6"><label class="form-label small fw-bold">Contact No</label><input type="tel" name="contact" id="contact" class="form-control" required value="<?php echo $_POST['contact'] ?? ''; ?>"></div>
                                    <div class="col-12"><label class="form-label small fw-bold">Email</label><input type="email" name="email" class="form-control" required value="<?php echo $_POST['email'] ?? ''; ?>"></div>
                                    <div class="col-md-6"><label class="form-label small fw-bold">Password</label><input type="password" name="password" id="password" class="form-control" required></div>
                                    <div class="col-md-6"><label class="form-label small fw-bold">Confirm</label><input type="password" name="cpassword" id="cpassword" class="form-control" required></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="reg-card p-4 h-100 shadow-sm">
                                <h5 class="form-section-title"><i class="fas fa-bed"></i> Room & Stay</h5>
                                <input type="hidden" name="room" id="room" required>
                                <input type="hidden" name="seater" id="seater"><input type="hidden" name="fpm" id="fpm">
                                <div id="roomSelector" class="room-picker-btn mb-4" data-bs-toggle="modal" data-bs-target="#roomModal">
                                    <div id="noRoomView"><i class="fas fa-plus-circle fs-3 mb-2"></i><div class="fw-bold fs-5">Pick a Room</div></div>
                                    <div id="activeRoomView" style="display:none;"><h3 id="displayRoomNo" class="fw-bold text-dark mb-0"></h3><div id="displayRoomMeta" class="small text-muted fw-bold"></div></div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6"><label class="form-label small fw-bold">Stay From</label><input type="date" name="stayf" class="form-control bg-white" value="<?php echo date('Y-m-d'); ?>" required></div>
                                    <div class="col-6"><label class="form-label small fw-bold">Duration</label><select name="duration" class="form-select bg-white" required><option value="5">1 Semester</option><option value="10">Full Year</option></select></div>
                                </div>
                                <div class="p-3 rounded-4 bg-light border mb-4 d-none">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="foodstatus" id="food0" value="0" checked><label class="form-check-label small fw-bold" for="food0">Without Food</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="foodstatus" id="food1" value="1"><label class="form-check-label small fw-bold" for="food1">With Food</label></div>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">Register Student</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg" style="border-radius: 28px;">
                <div class="modal-header border-0 px-4 pt-4"><h5 class="fw-bold mb-0 fs-4">Select a Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4 pt-2">
                    <ul class="nav nav-pills mb-4 p-2 bg-light rounded-4" role="tablist">
                        <?php $bi=0; foreach ($rooms_by_block as $bn => $wings): ?>
                            <li class="nav-item"><button class="nav-link rounded-pill px-4 <?php echo $bi===0?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#m-pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$bn); ?>" type="button"><?php echo $bn; ?></button></li>
                        <?php $bi++; endforeach; ?>
                    </ul>
                    <div class="tab-content mt-4">
                        <?php $bi=0; foreach ($rooms_by_block as $bn => $wings): ?>
                            <div class="tab-pane fade <?php echo $bi===0?'show active':''; ?>" id="m-pane-<?php echo preg_replace('/[^a-zA-Z0-9]/','',$bn); ?>">
                                <?php foreach ($wings as $sn => $s_rooms): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div style="width:12px; height:12px; background:#4361ee; border-radius:3px; margin-right:10px;"></div>
                                            <h6 class="fw-bold text-dark small text-uppercase mb-0"><?php echo $sn; ?></h6>
                                        </div>
                                        <div class="room-grid">
                                            <?php foreach ($s_rooms as $rm): 
                                                $full = $rm->is_full;
                                                $remaining = $rm->seater - $rm->occupied;
                                            ?>
                                                <div class="room-card-mini <?php echo $full?'full':''; ?>" 
                                                     onclick="pickRoom(this, '<?php echo $rm->room_no; ?>', <?php echo $rm->seater; ?>, <?php echo $rm->fees; ?>, <?php echo $rm->occupied; ?>)">
                                                    <div class="fw-bold text-dark fs-5 mb-1"><?php echo $rm->room_no; ?></div>
                                                    <div class="small fw-semibold text-muted mb-2" style="font-size: 0.75rem;">
                                                        <i class="fas fa-users me-1"></i> <?php echo $rm->occupied; ?> / <?php echo $rm->seater; ?> Full
                                                    </div>
                                                    <?php if($full): ?>
                                                        <span class="badge bg-danger-subtle text-danger rounded-pill w-100" style="font-size: 0.65rem;">ROOM FULL</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success rounded-pill w-100" style="font-size: 0.65rem;"><?php echo $remaining; ?> SPOTS LEFT</span>
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
                <div class="modal-footer border-0 p-4 pt-0">
                    <div class="d-flex gap-3 me-auto small">
                        <div class="d-flex align-items-center"><span class="d-inline-block bg-success-subtle rounded-circle me-1" style="width:10px; height:10px;"></span> Available</div>
                        <div class="d-flex align-items-center"><span class="d-inline-block bg-danger-subtle rounded-circle me-1" style="width:10px; height:10px;"></span> Full</div>
                    </div>
                    <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold" data-bs-dismiss="modal">Confirm Selection</button>
                </div>
            </div>
        </div>
    </div>

    <div class="success-overlay" id="regSuccessOverlay">
        <div class="success-modal">
            <div class="bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px; height:80px; border-radius:30px; font-size:2.5rem;"><i class="fas fa-check"></i></div>
            <h2 class="fw-bold text-dark mb-2">Usajili Umekamilika!</h2>
            <?php if(isset($_SESSION['reg_success'])): $rs = $_SESSION['reg_success']; ?>
            <div class="bg-light rounded-4 p-4 text-start mt-4 border">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted small fw-bold">NAME</span><span class="fw-bold"><?php echo htmlspecialchars($rs['name']); ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted small fw-bold">REG #</span><span class="fw-bold text-primary"><?php echo htmlspecialchars($rs['regno']); ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted small fw-bold">EMAIL</span><span class="fw-bold small"><?php echo htmlspecialchars($rs['email']); ?></span></div>
            </div>
            <?php endif; ?>
            <div class="mt-4 pt-3 d-grid gap-2"><button class="btn btn-primary rounded-pill py-3 fw-bold" onclick="location.href='registration.php'">Sajili Mwingine</button><a href="manage-students.php" class="btn btn-outline-secondary rounded-pill py-2 border-0 fw-bold">Student List</a></div>
        </div>
    </div>

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
        document.getElementById('displayRoomMeta').innerText = occupied + '/' + seater + ' Occupied • TSH ' + fees.toLocaleString(); 
    }
    function validateForm() { const pass = document.getElementById('password').value; const cpass = document.getElementById('cpassword').value; if (pass !== cpass) { alert("Passwords do not match!"); return false; } if (!document.getElementById('room').value) { alert("Please pick a room!"); return false; } return true; }
    <?php if(isset($_GET['registered'])): ?>
    document.addEventListener('DOMContentLoaded', () => { document.getElementById('regSuccessOverlay').classList.add('show'); <?php unset($_SESSION['reg_success']); ?> });
    <?php endif; ?>
    document.getElementById('contact').addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); if (!this.value.startsWith('255') && this.value.length > 0) this.value = '255' + this.value; if (this.value.length > 12) this.value = this.value.substring(0, 12); });
    </script>
</body>
</html>
