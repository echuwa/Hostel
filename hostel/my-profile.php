<?php
session_start();
include('includes/config.php');
date_default_timezone_set('Asia/Kolkata');
include('includes/checklogin.php');
check_login();
$aid = $_SESSION['user_id'] ?? $_SESSION['id'];

if(isset($_POST['update'])) {
    // Core details
    $fname = htmlspecialchars($_POST['fname']);
    $mname = htmlspecialchars($_POST['mname']);
    $lname = htmlspecialchars($_POST['lname']);
    $gender = htmlspecialchars($_POST['gender']);
    $contactno = '255' . preg_replace('/[^0-9]/', '', $_POST['contact']);
    
    // Additional details
    $course = htmlspecialchars($_POST['course'] ?? '');
    $guardianName = htmlspecialchars($_POST['guardianName'] ?? '');
    $guardianRelation = htmlspecialchars($_POST['guardianRelation'] ?? '');
    $egycontactno = '255' . preg_replace('/[^0-9]/', '', $_POST['egycontactno']);
    $guardianContactno = '255' . preg_replace('/[^0-9]/', '', $_POST['guardianContactno']);
    
    // Address Details
    $pmntAddress = trim($_POST['pmntAddress'] ?? '');
    $pmntState = trim($_POST['pmntState'] ?? '');
    $pmntCountry = trim($_POST['pmntCountry'] ?? '');
    $corresAddress = trim($_POST['corresAddress'] ?? '');
    $corresState = trim($_POST['corresState'] ?? '');
    $corresCountry = trim($_POST['corresCountry'] ?? '');
    
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
    $city = !empty($_POST['city']) ? htmlspecialchars($_POST['city']) : null;
    $state_new = !empty($_POST['state']) ? htmlspecialchars($_POST['state']) : $corresState;
    $country_new = !empty($_POST['country']) ? htmlspecialchars($_POST['country']) : $corresCountry;
    $location_captured_at = (!empty($latitude) && !empty($longitude)) ? date('Y-m-d H:i:s') : null;
    $udate = date('d-m-Y h:i:s', time());
    
    $query = "UPDATE userregistration SET firstName=?, middleName=?, lastName=?, gender=?, contactNo=?, updationDate=?, latitude=?, longitude=?, city=?, state=?, country=?, location_captured_at=? WHERE id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssssddssssi', $fname, $mname, $lname, $gender, $contactno, $udate, $latitude, $longitude, $city, $state_new, $country_new, $location_captured_at, $aid);
    
    if($stmt->execute()) {
        $stmt->close();
        
        // Handle Profile Picture Upload
        if(isset($_FILES["profilePic"]) && $_FILES["profilePic"]["error"] == 0){
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
            $filename = $_FILES["profilePic"]["name"];
            $filetype = $_FILES["profilePic"]["type"];
            $filesize = $_FILES["profilePic"]["size"];
        
            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(!array_key_exists($ext, $allowed)) {
                $_SESSION['error'] = "Error: Please select a valid file format (JPG, JPEG, PNG).";
                header("Location: my-profile.php");
                exit();
            }
        
            // Verify file size - 5MB max
            $maxsize = 5 * 1024 * 1024;
            if($filesize > $maxsize) {
                $_SESSION['error'] = "Error: File size is larger than the allowed limit (5MB).";
                header("Location: my-profile.php");
                exit();
            }
        
            // Verify MYME type of the file
            if(in_array($filetype, $allowed)){
                // Check whether file exists before uploading it
                $new_filename = "student_" . $aid . "_" . time() . "." . $ext;
                $upload_path = "uploads/profiles/" . $new_filename;
                
                if(move_uploaded_file($_FILES["profilePic"]["tmp_name"], $upload_path)){
                    // Update database
                    $update_pic = $mysqli->prepare("UPDATE userregistration SET profile_pic = ? WHERE id = ?");
                    $update_pic->bind_param("si", $upload_path, $aid);
                    $update_pic->execute();
                    $update_pic->close();
                } else{
                    $_SESSION['error'] = "Error: There was a problem uploading your file. Please try again.";
                    header("Location: my-profile.php");
                    exit();
                } 
            } else{
                $_SESSION['error'] = "Error: There was a problem with your file type.";
                header("Location: my-profile.php");
                exit();
            }
        }

        // Fetch regNo to update the registration table as well
        $q2 = "SELECT regNo FROM userregistration WHERE id=?";
        $st2 = $mysqli->prepare($q2);
        $st2->bind_param('i', $aid);
        $st2->execute();
        $res2 = $st2->get_result();
        $userRow = $res2->fetch_object();
        $st2->close();
        
        if($userRow) {
            $regNo = $userRow->regNo;
            $updateReg = "UPDATE registration SET firstName=?, middleName=?, lastName=?, gender=?, contactno=?, course=?, guardianName=?, guardianRelation=?, egycontactno=?, guardianContactno=?, pmntAddress=?, pmntState=?, pmntCountry=?, corresAddress=?, corresState=?, corresCountry=? WHERE regno = ? ORDER BY id DESC LIMIT 1";
            $stmtReg = $mysqli->prepare($updateReg);
            if($stmtReg) {
                $stmtReg->bind_param('sssssssssssssssss', $fname, $mname, $lname, $gender, $contactno, $course, $guardianName, $guardianRelation, $egycontactno, $guardianContactno, $pmntAddress, $pmntState, $pmntCountry, $corresAddress, $corresState, $corresCountry, $regNo);
                $stmtReg->execute();
                $stmtReg->close();
            }
        }
        
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: my-profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Hostel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-modern.css">
    <style>
        .profile-hero {
            background: var(--gradient-primary);
            border-radius: 24px;
            padding: 50px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }
        .profile-hero::after {
            content: '';
            position: absolute;
            right: -50px;
            top: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        .avatar-lg {
            width: 120px;
            height: 120px;
            background: white;
            color: var(--primary);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: 4px solid rgba(255,255,255,0.3);
        }
        .status-pill {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Map Styles */
        .map_container {
            width: 100%;
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            transition: height 0.3s ease;
            margin-bottom: 0px;
            border: 1px solid #e2e8f0;
        }
        .map_container.active {
            height: 250px;
            margin-bottom: 15px;
        }
    </style>
    <!-- Leaflet Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <?php
                $aid = $_SESSION['user_id'] ?? $_SESSION['id'];
                $ret = "SELECT u.*, r.course, r.guardianName, r.guardianRelation, r.egycontactno, r.guardianContactno, r.pmntAddress, r.pmntState, r.pmntCountry, r.corresAddress, r.corresState, r.corresCountry FROM userregistration u LEFT JOIN registration r ON u.regNo = r.regno WHERE u.id=? ORDER BY r.id DESC LIMIT 1";
                $stmt = $mysqli->prepare($ret);
                $stmt->bind_param('i', $aid);
                $stmt->execute();
                $res = $stmt->get_result();
                
                // Fetch states and courses for dropdowns
                $states = $mysqli->query("SELECT State FROM states");
                $courses = $mysqli->query("SELECT * FROM courses");
                
                if($row = $res->fetch_object()):
                ?>
                
                <!-- Profile Hero -->
                <div class="profile-hero animate__animated animate__fadeInDown">
                    <div class="row align-items-center">
                        <div class="col-md-auto text-center text-md-start mb-4 mb-md-0">
                            <div class="avatar-container" style="width: 120px; height: 120px;">
                                <?php 
                                $pic_src = '';
                                if (!empty($row->profile_pic)) {
                                    if (substr($row->profile_pic, 0, 4) === 'http') {
                                        $pic_src = $row->profile_pic;
                                    } else {
                                        $pic_src = '/' . ltrim($row->profile_pic, '/');
                                    }
                                }
                                $initial = strtoupper(substr($row->firstName, 0, 1));
                                ?>
                                <div id="avatar_display_box" class="position-relative" style="width: 120px; height: 120px;">
                                    <?php if (!empty($pic_src)): ?>
                                        <img src="<?php echo htmlspecialchars($pic_src); ?>" alt="Avatar" class="rounded-circle shadow" style="width: 100%; height: 100%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3);">
                                    <?php else: ?>
                                        <div class="avatar-lg d-flex align-items-center justify-content-center h-100 rounded-circle" style="background: rgba(255,255,255,0.2); border: 4px solid rgba(255,255,255,0.3); color: white;">
                                            <span style="font-weight: 800;"><?php echo $initial; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Change Photo Overlay -->
                                    <label for="profilePic" class="position-absolute bottom-0 end-0 bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 38px; height: 38px; cursor: pointer; border: 3px solid var(--primary); transform: translate(5px, 5px); transition: 0.3s;">
                                        <i class="fas fa-camera" style="font-size: 0.9rem;"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md text-center text-md-start">
                            <div class="status-pill mb-3">
                                <i class="fas fa-check-circle"></i> VERIFIED STUDENT ACCOUNT
                            </div>
                            <h1 class="fw-800 mb-1" style="font-size: 2.5rem;"><?php echo $row->firstName . ' ' . $row->lastName; ?></h1>
                            <p class="opacity-75 fw-500 mb-0">
                                <i class="fas fa-id-badge me-2"></i>Registration ID: <?php echo $row->regNo; ?>
                                <span class="mx-3 opacity-25">|</span>
                                <i class="fas fa-envelope me-2"></i><?php echo $row->email; ?>
                            </p>
                        </div>
                        <div class="col-md-auto mt-4 mt-md-0 d-flex gap-2">
                             <div class="p-3 rounded-4 text-center text-white" style="background: rgba(255,255,255,0.2); min-width: 120px;">
                                <div class="small fw-700 opacity-75 text-white">STATUS</div>
                                <div class="h5 fw-800 mb-0 text-white">Active</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card-modern border-0 animate__animated animate__fadeInUp">
                            <div class="card-header-modern">
                                <h5 class="fw-800 mb-0">Update Personal Profile</h5>
                            </div>
                            <div class="card-body-modern p-5">
                                <?php if(isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
                                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="post" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    
                                    <!-- Profile Picture Upload Section -->
                                    <div class="row mb-5">
                                        <div class="col-12 text-center text-md-start d-flex flex-column flex-md-row align-items-center">
                                            <div class="position-relative me-md-4 mb-3 mb-md-0">
                                                <?php if(!empty($row->profile_pic)): ?>
                                                    <img src="<?php echo htmlspecialchars($row->profile_pic); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 120px; height: 120px; font-size: 3rem;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <label for="profilePic" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 cursor-pointer shadow" style="cursor: pointer;" title="Change Photo">
                                                    <i class="fas fa-camera"></i>
                                                </label>
                                            </div>
                                            <div>
                                                <h6 class="fw-800 mb-1">Profile Photo</h6>
                                                <p class="small text-muted mb-2">Allowed formats: JPG, JPEG, PNG. Max size: 5MB.</p>
                                                <input type="file" id="profilePic" name="profilePic" class="form-control form-control-modern d-none" accept=".jpg,.jpeg,.png">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('profilePic').click()">Choose Image</button>
                                                <span id="fileNameDisplay" class="small ms-2 text-muted fw-600"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">First Name</label>
                                                <input type="text" name="fname" class="form-control form-control-modern" value="<?php echo $row->firstName; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Middle Name</label>
                                                <input type="text" name="mname" class="form-control form-control-modern" value="<?php echo $row->middleName; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Last Name</label>
                                                <input type="text" name="lname" class="form-control form-control-modern" value="<?php echo $row->lastName; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Gender</label>
                                                <select name="gender" class="form-select form-control-modern" required>
                                                    <option value="male" <?php if($row->gender=='male') echo 'selected';?>>Male</option>
                                                    <option value="female" <?php if($row->gender=='female') echo 'selected';?>>Female</option>
                                                    <option value="others" <?php if($row->gender=='others') echo 'selected';?>>Others</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Contact Number</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0 fw-800 text-primary">+255</span>
                                                    <input type="tel" name="contact" class="form-control form-control-modern border-start-0 ps-0 tel-validate" value="<?php echo (str_starts_with($row->contactNo, '255') ? substr($row->contactNo, 3) : $row->contactNo); ?>" required maxlength="9">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Additional Information Section -->
                                        <div class="col-12 mt-4">
                                            <h6 class="fw-800 border-bottom pb-2">Academic & Guardian Details</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Course Applied</label>
                                                <select name="course" class="form-select form-control-modern">
                                                    <option value="">Select Course</option>
                                                    <?php $courses->data_seek(0); while($c = $courses->fetch_object()): ?>
                                                        <option value="<?php echo htmlspecialchars($c->course_fn); ?>" <?php if(($row->course ?? '') == $c->course_fn) echo 'selected'; ?>><?php echo htmlspecialchars($c->course_sn); ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Emergency Contact No.</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0 fw-800 text-primary">+255</span>
                                                    <input type="tel" name="egycontactno" class="form-control form-control-modern border-start-0 ps-0 tel-validate" value="<?php echo (str_starts_with($row->egycontactno ?? '', '255') ? substr($row->egycontactno, 3) : htmlspecialchars($row->egycontactno ?? '')); ?>" maxlength="9">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Guardian Full Name</label>
                                                <input type="text" name="guardianName" class="form-control form-control-modern" value="<?php echo htmlspecialchars($row->guardianName ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Guardian Contact No.</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0 fw-800 text-primary">+255</span>
                                                    <input type="tel" name="guardianContactno" class="form-control form-control-modern border-start-0 ps-0 tel-validate" value="<?php echo (str_starts_with($row->guardianContactno ?? '', '255') ? substr($row->guardianContactno, 3) : htmlspecialchars($row->guardianContactno ?? '')); ?>" maxlength="9">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Guardian Relation</label>
                                                <select name="guardianRelation" class="form-select form-control-modern">
                                                    <option value="">Select Relation</option>
                                                    <?php 
                                                    $relations = ['Father', 'Mother', 'Brother', 'Sister', 'Uncle', 'Aunt', 'Legal Guardian'];
                                                    foreach($relations as $rel) {
                                                        $selected = (($row->guardianRelation ?? '') == $rel) ? 'selected' : '';
                                                        echo "<option value=\"$rel\" $selected>$rel</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- GPS Data Intelligence -->
                                        <div class="col-12 mt-4">
                                            <h6 class="fw-800 border-bottom pb-2 text-primary"><i class="fas fa-satellite-dish me-2"></i>Resident Data Intelligence (GPS)</h6>
                                            <div class="p-4 rounded-4 bg-light border border-opacity-25 border-primary mb-3">
                                                <div class="d-flex gap-2 mb-3">
                                                    <button type="button" class="btn btn-modern btn-light border w-100 fw-800" onclick="toggleMap()" style="border-radius: 12px; font-size: 0.8rem; background: #fff;">
                                                        <i class="fas fa-map-marked-alt text-primary me-2"></i>
                                                        <span id="map-btn-text">Update Residential GPS Point</span>
                                                    </button>
                                                    <button type="button" class="btn btn-modern btn-primary px-4 shadow-sm" onclick="locateMe()" style="border-radius: 12px;" title="Auto-Detect GPS">
                                                        <i class="fas fa-crosshairs"></i>
                                                    </button>
                                                </div>
                                                
                                                <div id="map-collapsible" style="display: none;">
                                                    <div id="map" class="map_container"></div>
                                                    <div id="address-preview" class="small fw-700 text-muted p-2">
                                                        <?php if($row->latitude): ?>
                                                            <div class='mb-1 text-primary'><i class='fas fa-check-circle me-1'></i> GPS Point and Geodata Encoded</div>
                                                            <div class='mb-1'><i class='fas fa-map-location me-2 text-primary'></i><?php echo $row->state; ?> • <?php echo $row->city; ?></div>
                                                            <div class='small opacity-75'><i class='fas fa-clock me-1'></i>Last Sync: <?php echo $row->location_captured_at; ?></div>
                                                        <?php else: ?>
                                                            No GPS intelligence point encoded. Click button to pinpoint.
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <input type="hidden" id="latitude" name="latitude" value="<?php echo $row->latitude; ?>">
                                                <input type="hidden" id="longitude" name="longitude" value="<?php echo $row->longitude; ?>">
                                                <input type="hidden" id="city_field" name="city" value="<?php echo $row->city; ?>">
                                                <input type="hidden" id="state_field" name="state" value="<?php echo $row->state; ?>">
                                                <input type="hidden" id="country_field" name="country" value="<?php echo $row->country; ?>">
                                            </div>
                                        </div>

                                        <!-- Addresses Section -->
                                        <div class="col-12 mt-4">
                                            <h6 class="fw-800 border-bottom pb-2">Address Information</h6>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Correspondence Address</label>
                                                <textarea name="corresAddress" id="cor-addr" class="form-control form-control-modern" rows="2" placeholder="P.O Box / Street Name..."><?php echo htmlspecialchars($row->corresAddress ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Correspondence City/State</label>
                                                <select name="corresState" id="cor-state" class="form-select form-control-modern">
                                                    <option value="">Select Region</option>
                                                    <?php $states->data_seek(0); while($s = $states->fetch_object()): ?>
                                                        <option value="<?php echo htmlspecialchars($s->State); ?>" <?php if(($row->corresState ?? '') == $s->State) echo 'selected'; ?>><?php echo htmlspecialchars($s->State); ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Correspondence Country</label>
                                                <input type="text" name="corresCountry" id="cor-country" class="form-control form-control-modern" value="Tanzania" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 pt-3">
                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" id="copyAddr">
                                                <label class="form-check-label fw-800 text-primary" for="copyAddr">Permanent address is same as Correspondence Address</label>
                                            </div>
                                            <hr class="mb-4 opacity-25">
                                            
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Permanent Address</label>
                                                <textarea name="pmntAddress" id="perm-addr" class="form-control form-control-modern" rows="2"><?php echo htmlspecialchars($row->pmntAddress ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Permanent City/State</label>
                                                <select name="pmntState" id="perm-state" class="form-select form-control-modern">
                                                    <option value="">Select Region</option>
                                                    <?php $states->data_seek(0); while($s = $states->fetch_object()): ?>
                                                        <option value="<?php echo htmlspecialchars($s->State); ?>" <?php if(($row->pmntState ?? '') == $s->State) echo 'selected'; ?>><?php echo htmlspecialchars($s->State); ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Permanent Country</label>
                                                <input type="text" name="pmntCountry" id="perm-country" class="form-control form-control-modern" value="Tanzania" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 mt-5">
                                            <div class="p-4 rounded-4 bg-light mb-4">
                                                <div class="row align-items-center">
                                                    <div class="col">
                                                        <h6 class="fw-800 mb-1">Account Security</h6>
                                                        <p class="small text-muted mb-0">Keep your information up to date for better security.</p>
                                                    </div>
                                                    <div class="col-auto">
                                                        <a href="change-password.php" class="btn btn-outline-primary rounded-pill px-4">Change Password</a>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="update" class="btn-modern btn-modern-primary py-3 px-5 shadow-lg w-100 justify-content-center">
                                                <i class="fas fa-save me-2"></i> UPDATE PROFILE INFORMATION
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <!-- Sidebar Cards -->
                        <div class="card-modern border-0 mb-4 animate__animated animate__fadeInRight">
                            <div class="card-body-modern">
                                <h6 class="fw-800 mb-4">Account Metadata</h6>
                                <div class="d-flex flex-column gap-3">
                                    <div class="p-3 bg-light rounded-4 d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 text-primary">
                                            <i class="fas fa-calendar-plus"></i>
                                        </div>
                                        <div>
                                            <div class="small fw-800 text-muted">Join Date</div>
                                            <div class="fw-700"><?php echo date('d M, Y', strtotime($row->regDate)); ?></div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-light rounded-4 d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3 text-success">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <div>
                                            <div class="small fw-800 text-muted">Last Updated</div>
                                            <div class="fw-700 small"><?php echo $row->updationDate ?: 'Never updated'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-modern border-0 text-white animate__animated animate__fadeInRight" style="background: var(--dark); animation-delay: 0.1s">
                            <div class="card-body-modern p-4">
                                <h6 class="fw-800 mb-3"><i class="fas fa-info-circle text-info me-2"></i>Need Help?</h6>
                                <p class="small opacity-50 mb-4">If you need to change your registered Email or Registration Number, please contact the IT helpdesk.</p>
                                <button type="button" class="btn btn-primary w-100 rounded-pill py-2" data-bs-toggle="modal" data-bs-target="#supportModal">Contact Support</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Support Contact Modal -->
    <div class="modal fade" id="supportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-0 bg-primary text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-headset me-2"></i>Technical Support</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-4">
                         <div class="bg-primary bg-opacity-10 text-primary mx-auto rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-cog fa-2x"></i>
                         </div>
                         <h4 class="fw-800 mb-1">Emmanuel Chuwa</h4>
                         <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-700">Engineer</span>
                    </div>
                    
                    <div class="bg-light p-3 rounded-4 mb-3">
                        <div class="small fw-700 text-muted opacity-75 mb-2">WHATSAPP / CALL</div>
                        <div class="d-flex flex-column gap-3">
                            <a href="tel:+255788020014" class="text-decoration-none text-dark fw-800 h5 mb-0">
                                <i class="fas fa-phone-alt me-2 text-primary"></i>+255 788 020 014
                            </a>
                            <a href="tel:+255748230014" class="text-decoration-none text-dark fw-800 h5 mb-0">
                                <i class="fas fa-phone-alt me-2 text-primary"></i>+255 748 230 014
                            </a>
                        </div>
                    </div>
                    
                    <div class="alert alert-info border-0 rounded-4 small mb-0">
                        <i class="fas fa-info-circle me-1"></i> For any inquiries regarding system access or technical issues, please reach out via the numbers above.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 text-center">
                    <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-800 shadow-sm" data-bs-dismiss="modal">Understood</button>
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
    
    $(document).ready(function(){
        $('#copyAddr').click(function(){
            if(this.checked) {
                $('#perm-addr').val($('#cor-addr').val());
                $('#perm-state').val($('#cor-state').val());
                $('#perm-country').val($('#cor-country').val());
            } else {
                $('#perm-addr').val('');
                $('#perm-state').val('');
            }
        });
    });

    // Leaflet Map Logic
    let map, marker;
    function initMap() {
        const defaultLat = <?php echo $row->latitude ?: -6.7924; ?>;
        const defaultLng = <?php echo $row->longitude ?: 39.2083; ?>;
        
        map = L.map('map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);

        map.on('click', function(e) {
            updateLocation(e.latlng.lat, e.latlng.lng);
        });

        marker.on('dragend', function(e) {
            const { lat, lng } = marker.getLatLng();
            updateLocation(lat, lng);
        });
    }

    let mapVisible = false;
    function toggleMap() {
        const wrap = document.getElementById("map-collapsible");
        const btnText = document.getElementById("map-btn-text");
        mapVisible = !mapVisible;
        wrap.style.display = mapVisible ? "block" : "none";
        btnText.innerText = mapVisible ? "Close Intelligence Map" : "Pinpoint Residential Location";
        if (mapVisible) {
            if (!map) initMap();
            else map.invalidateSize();
            document.querySelector(".map_container").classList.add("active");
        }
    }

    // Auto-submit profile pic on change
    const profilePicInput = document.getElementById('profilePic');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                Swal.fire({
                    title: 'Processing Photo...',
                    text: 'Updating your profile picture',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                const updateBtn = document.querySelector('button[name="update"]');
                if (updateBtn) updateBtn.click();
            }
        });
    }


    function updateLocation(lat, lng) {
        marker.setLatLng([lat, lng]);
        document.getElementById("latitude").value = lat;
        document.getElementById("longitude").value = lng;
        
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
            .then(res => res.json())
            .then(data => {
                if (data.address) {
                    const city = data.address.city || data.address.town || data.address.village || data.address.suburb || "N/A";
                    const state = data.address.state || data.address.region || "N/A";
                    const country = data.address.country || "N/A";
                    
                    document.getElementById("city_field").value = city;
                    document.getElementById("state_field").value = state;
                    document.getElementById("country_field").value = country;
                    
                    // Pre-fill address fields if they are empty
                    if(!document.getElementById("cor-addr").value) {
                         document.getElementById("cor-addr").value = city + " - (GPS Intelligence)";
                         document.getElementById("cor-country").value = country;
                         document.getElementById("cor-state").value = state;
                    }
                    
                    const timeStr = new Date().toLocaleString();
                    document.getElementById("address-preview").innerHTML = `
                        <div class='mb-1'><i class='fas fa-earth-africa text-primary me-2'></i>${country}</div>
                        <div class='mb-1'><i class='fas fa-map-location text-primary me-2'></i>${state} • ${city}</div>
                        <div class='mt-2 small opacity-75'><i class='fas fa-clock me-1'></i>Captured: ${timeStr}</div>
                    `;
                }
            });
    }

    function locateMe() {
        if (!mapVisible) toggleMap();
        if (navigator.geolocation) {
            Swal.fire({ title: 'Locating...', text: 'Finding your GPS point', icon: 'info', showConfirmButton: false, timer: 1500 });
            navigator.geolocation.getCurrentPosition((position) => {
                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], 17);
                updateLocation(latitude, longitude);
            });
        }
    }
    document.querySelectorAll('.tel-validate').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 9) this.value = this.value.substring(0, 9);
            });
        });

        // Profile Picture File Name Display
        document.getElementById('profilePic').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : '';
            document.getElementById('fileNameDisplay').textContent = fileName;
        });
    </script>
</body>
</html>