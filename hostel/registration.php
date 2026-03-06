<?php
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
    $stmt = $mysqli->prepare("SELECT MAX(regNo) FROM userregistration WHERE regNo LIKE ?");
    $param = $prefix . '%';
    $stmt->bind_param('s', $param);
    $stmt->execute();
    $stmt->bind_result($lastRegNo);
    $stmt->fetch();
    $stmt->close();
    
    if ($lastRegNo) {
        $lastNumber = intval(substr($lastRegNo, strlen($prefix)));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $regno = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    
    // Sanitize inputs
    $fname = htmlspecialchars($_POST['fname']);
    $mname = htmlspecialchars($_POST['mname']);
    $lname = htmlspecialchars($_POST['lname']);
    $gender = htmlspecialchars($_POST['gender']);
    // Handle contact number: append 255 if missing, but be smart about it
    $raw_contact = preg_replace('/[^0-9]/', '', $_POST['contact']);
    if (str_starts_with($raw_contact, '255')) {
        $contactno = $raw_contact;
    } else {
        $contactno = '255' . $raw_contact;
    }
    $emailid = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
    $city = !empty($_POST['city']) ? htmlspecialchars($_POST['city']) : null;
    $state = !empty($_POST['state']) ? htmlspecialchars($_POST['state']) : null;
    $country = !empty($_POST['country']) ? htmlspecialchars($_POST['country']) : null;
    $location_captured_at = (!empty($latitude) && !empty($longitude)) ? date('Y-m-d H:i:s') : null;
    $google_id = $_POST['google_id'] ?? null;
    $profile_pic = $_POST['profile_pic'] ?? null;
    
    // Validate inputs
    $errors = [];
    if(empty($fname)) $errors[] = "First name is required";
    if(empty($lname)) $errors[] = "Last name is required";
    if(empty($gender)) $errors[] = "Gender is required";
    if(!preg_match('/^255\d{9}$/', $contactno)) $errors[] = "Contact number must be 12 digits starting with 255 (255XXXXXXXXX)";
    if(!filter_var($emailid, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if(strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if($password !== $cpassword) $errors[] = "Passwords do not match";

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
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate Control Numbers for the new student
            $fee_ctrl = generateControlNumber();
            $acc_ctrl = generateControlNumber();
            $reg_ctrl = generateControlNumber();

            // Insert with Pending status and generated control numbers
            $query = "INSERT INTO userregistration(regNo,firstName,middleName,lastName,gender,contactNo,email,password,status,fee_control_no,acc_control_no,reg_control_no,google_id,profile_pic,latitude,longitude,city,state,country,location_captured_at) VALUES(?,?,?,?,?,?,?,?,'Pending',?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('sssssssssssssddssss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $hashed_password, $fee_ctrl, $acc_ctrl, $reg_ctrl, $google_id, $profile_pic, $latitude, $longitude, $city, $state, $country, $location_captured_at);
            
            if($stmt->execute()) {
                $registration_success = true;
            } else {
                $_SESSION['error'] = "Registration failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | HostelMS</title>
    
    <!-- Favicon (Data URI to prevent 404) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏨</text></svg>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Auth Modern CSS -->
    <link rel="stylesheet" href="css/auth-modern.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Leaflet Maps (Free Alternative to Google Maps) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* Premium GPS Button Styling */
        .btn-gps-trigger {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 15px 20px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            text-align: left;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .btn-gps-trigger:hover {
            border-color: #4361ee;
            background: #f8faff;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.1);
        }
        .gps-icon-box {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(123, 47, 247, 0.1) 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-right: 15px; color: #4361ee; font-size: 1.2rem;
            transition: all 0.3s;
        }
        .btn-gps-trigger:hover .gps-icon-box {
            background: #4361ee; color: #ffffff;
        }
        .gps-text-box { flex-grow: 1; }
        .gps-label { display: block; font-size: 0.65rem; font-weight: 800; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 2px; }
        .gps-sub { display: block; font-size: 0.85rem; font-weight: 700; color: #1e293b; }
        .gps-arrow { color: #cbd5e1; font-size: 0.9rem; transition: transform 0.3s; }
        .btn-gps-trigger:hover .gps-arrow { transform: translateX(5px); color: #4361ee; }

        /* Professional Header Styling */
        .auth_header { text-align: center; margin-bottom: 25px; position: relative; }
        .header_icon_circle {
            width: 55px; height: 55px; background: var(--gradient-primary); border-radius: 16px;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;
            color: white; font-size: 1.5rem; box-shadow: 0 8px 20px rgba(67, 97, 238, 0.2);
            transform: rotate(-10deg); transition: all 0.3s ease;
        }
        .header_icon_circle:hover { transform: rotate(0deg) scale(1.05); }
        .header_badge {
            display: inline-block; background: var(--primary-light); color: var(--primary);
            padding: 4px 10px; border-radius: 8px; font-size: 0.65rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;
        }

        /* Modal specific scroll and display fixes - PROFESSIONAL ISOLATION */
        body.is-modal { 
            background: #ffffff !important; 
            overflow-y: auto !important; 
            display: block !important;
            height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        body.is-modal .auth_wrapper {
            display: block !important;
            min-height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        body.is-modal .auth_card {
            display: block !important;
            min-height: auto !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            background: transparent !important;
        }
        body.is-modal .bg-blob, 
        body.is-modal .auth_hero { display: none !important; }
        
        body.is-modal .auth_content { 
            padding: 40px 30px 60px 30px !important; /* Added more top and bottom padding */
            width: 100% !important;
            display: block !important;
        }
        body.is-modal .auth_header { margin-bottom: 20px !important; }
        body.is-modal .header_icon_circle { width: 45px !important; height: 45px !important; margin-bottom: 15px !important; }
        
        body.is-modal .input_container { margin-bottom: 12px !important; }
        body.is-modal .input-group-modern { margin-bottom: 12px !important; }
        body.is-modal .auth_actions { margin-top: 20px !important; padding-bottom: 20px !important; }
        body.is-modal .auth_footer { margin-top: 15px !important; margin-bottom: 40px !important; }
        
        @media (max-width: 576px) {
            body.is-modal .auth_content { padding: 30px 15px 80px 15px !important; }
        }
        /* Fix for bottom visibility on small phones */
        @media (max-width: 480px) {
            body.is-modal .auth_content { padding-bottom: 80px !important; }
            body.is-modal .auth_footer { margin-bottom: 20px !important; }
        }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="auth_wrapper">
        <div class="auth_card" data-aos="fade-up" data-aos-duration="1000">
            <!-- Left Panel - Hero Section -->
            <div class="auth_hero">
                <div class="auth_hero_content" data-aos="fade-right" data-aos-delay="200">
                    <h2>Join Our Community</h2>
                    <p>Experience a new way of living. Secure your room and start your academic journey with us today.</p>
                    <img src="assets/img/registration_hero.png" alt="Registration Hero">
                </div>
            </div>

            <div class="auth_content">
                <div class="auth_header" data-aos="fade-down" data-aos-delay="300">
                    <span class="header_badge">New Enrollment</span>
                    <div class="header_icon_circle">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1 class="auth_title">Student Registration</h1>
                    <p class="auth_subtitle">Create your digital profile to get started.</p>
                </div>

                <!-- Display Errors -->
                <?php if(isset($_SESSION['errors'])): ?>
                    <div class="alert-modern alert-danger-modern" data-aos="fade-in">
                        <ul class="mb-0 p-0" style="list-style: none;">
                            <?php foreach($_SESSION['errors'] as $error): ?>
                                <li><i class="fas fa-circle-exclamation me-2"></i> <?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert-modern alert-danger-modern" data-aos="fade-in">
                        <i class="fas fa-circle-exclamation me-2"></i> <?php echo $_SESSION['error']; ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="post" action="" name="registration" id="registrationForm">
                    <div class="regno-note" data-aos="fade-up" data-aos-delay="400">
                        <i class="fas fa-sparkles"></i>
                        <span>Your registration ID will be automatically generated upon submission.</span>
                    </div>

                    <div class="auth_row">
                        <!-- First Name -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="450">
                            <label class="form-label">First Name</label>
                            <div class="input-group-modern">
                                <i class="fas fa-id-card-clip"></i>
                                <input type="text" name="fname" placeholder="First Name" required
                                       value="<?php echo isset($_POST['google_fname']) ? htmlspecialchars($_POST['google_fname']) : (isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''); ?>">
                            </div>
                        </div>

                        <!-- Middle Name -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="475">
                            <label class="form-label">Middle Name</label>
                            <div class="input-group-modern">
                                <i class="fas fa-id-card-clip"></i>
                                <input type="text" name="mname" placeholder="Middle Name"
                                       value="<?php echo isset($_POST['mname']) ? htmlspecialchars($_POST['mname']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="auth_row">
                        <!-- Last Name -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="500">
                            <label class="form-label">Last Name</label>
                            <div class="input-group-modern">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="lname" placeholder="Last Name" required
                                       value="<?php echo isset($_POST['google_lname']) ? htmlspecialchars($_POST['google_lname']) : (isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''); ?>">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="550">
                            <label class="form-label">Gender</label>
                            <div class="input-group-modern">
                                <i class="fas fa-venus-mars"></i>
                                <select name="gender" required>
                                    <option value="" disabled selected>Select Gender</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="others" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'others') ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="auth_row">
                        <!-- Contact -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="600">
                            <label class="form-label">Contact Number</label>
                            <div class="input-group-modern">
                                <i class="fas fa-phone-volume"></i>
                                <span class="tel-prefix">+255</span>
                                <input type="tel" id="contact" name="contact" maxlength="9" placeholder="7XXXXXXXX" required class="tel-input"
                                       value="<?php echo isset($_POST['contact']) ? (str_starts_with($_POST['contact'], '255') ? substr($_POST['contact'], 3) : htmlspecialchars($_POST['contact'])) : ''; ?>">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="650">
                            <label class="form-label">Email Address</label>
                            <div class="input-group-modern">
                                <i class="fas fa-envelope-open-text"></i>
                                <input type="email" id="email" name="email" placeholder="example@domain.com" required
                                       value="<?php echo isset($_POST['google_email']) ? htmlspecialchars($_POST['google_email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Google Fields -->
                    <input type="hidden" name="google_id" value="<?php echo htmlspecialchars($_POST['google_google_id'] ?? ''); ?>">
                    <input type="hidden" name="profile_pic" value="<?php echo htmlspecialchars($_POST['google_pic'] ?? ''); ?>">

                    <div class="map_wrapper mb-4" data-aos="fade-up" data-aos-delay="680">
                        <label class="form-label text-primary fw-800 mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">RESIDENT DATA INTELLIGENCE (GPS)</label>
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn-gps-trigger" onclick="toggleMap()">
                                <div class="gps-icon-box">
                                    <i class="fas fa-location-dot"></i>
                                </div>
                                <div class="gps-text-box">
                                    <span class="gps-label">PINPOINT RESIDENCE</span>
                                    <span class="gps-sub" id="map-btn-text">Select Residential Address</span>
                                </div>
                                <div class="gps-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </button>
                            <button type="button" class="btn btn-modern btn-primary px-4" onclick="locateMe()" style="border-radius: 16px; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);" title="Auto-Detect GPS">
                                <i class="fas fa-crosshairs-alt"></i>
                            </button>
                        </div>
                        
                        <div id="map-collapsible" style="display: none;">
                            <div class="location-badge">
                                <i class="fas fa-satellite"></i>
                                <span id="location-text">Click map to set point</span>
                            </div>
                            <div id="map" class="map_container" style="height: 250px;"></div>
                            <div class="mt-2 p-3 rounded-4 bg-light border border-opacity-25 border-primary">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-primary text-white p-2 rounded-circle shadow-sm" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;"><i class="fas fa-brain"></i></div>
                                    <h6 class="mb-0 fw-800" style="font-size: 0.8rem;">Geocoded Address Insight</h6>
                                </div>
                                <div id="address-preview" class="small fw-700 text-muted">
                                    No address specified. Click on map to pinpoint.
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                        <input type="hidden" id="city_field" name="city">
                        <input type="hidden" id="state_field" name="state">
                        <input type="hidden" id="country_field" name="country">
                    </div>

                    <div class="auth_row">
                        <!-- Password -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="700">
                            <label class="form-label">Create Password</label>
                            <div class="input-group-modern">
                                <i class="fas fa-fingerprint"></i>
                                <input type="password" id="password" name="password" placeholder="••••••••" required
                                       onkeyup="checkPasswordStrength()">
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="input_container" data-aos="fade-up" data-aos-delay="750">
                            <label class="form-label">Verify Password</label>
                            <div class="input-group-modern">
                                <i class="fas fa-shield-halved"></i>
                                <input type="password" id="cpassword" name="cpassword" placeholder="••••••••" required
                                       onkeyup="checkPasswordMatch()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="password-strength" data-aos="fade-up" data-aos-delay="700">
                        <div id="password-strength-bar" class="strength-bar"></div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="auth_actions mt-4" data-aos="fade-up" data-aos-delay="800">
                        <button type="submit" name="submit" class="btn-primary-modern">
                            <span>Register Account</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <div class="auth_footer" data-aos="fade-up" data-aos-delay="850">
                        Already have an account? <a href="index.php">Log in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
    AOS.init({ duration: 800, once: true });

    <?php if(isset($registration_success) && $registration_success): ?>
    Swal.fire({
        title: 'Registration Protocol Successful!',
        text: 'Your account has been created. Access is currently pending administrative verification.',
        icon: 'success',
        confirmButtonColor: '#4361ee',
        confirmButtonText: 'Enter Terminal'
    }).then((result) => {
        window.top.location.href = 'index.php';
    });
    <?php endif; ?>

    // Check password strength
    function checkPasswordStrength() {
        var password = $("#password").val();
        var strength = 0;
        
        if (password.length >= 6) strength += 1;
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
        if (password.match(/([0-9])/)) strength += 1;
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
        
        var width = (strength / 4) * 100;
        var bar = $("#password-strength-bar");
        bar.css("width", width + "%");
        
        if (strength < 2) bar.css("background", "#ef4444");
        else if (strength == 2) bar.css("background", "#f59e0b");
        else bar.css("background", "#10b981");
    }
    
    // Check password match
    function checkPasswordMatch() {
        var password = $("#password").val();
        var confirmPassword = $("#cpassword").val();
        var group = $("#cpassword").closest(".input-group-modern");
        
        if (password != confirmPassword && confirmPassword != "") {
            group.find("input").css("border-color", "#ef4444");
            group.find("i").css("color", "#ef4444");
        } else if (password == confirmPassword && confirmPassword != "") {
            group.find("input").css("border-color", "#10b981");
            group.find("i").css("color", "#10b981");
        } else {
            group.find("input").css("border-color", "transparent");
            group.find("i").css("color", "#64748b");
        }
    }
    
    // Contact number validation
    document.getElementById('contact').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 9) {
            this.value = this.value.substring(0, 9);
        }
    });

    // Leaflet Map Initialization (FREE)
    let map, marker;
    function initMap() {
        const defaultLat = -6.7924;
        const defaultLng = 39.2083; // Dar es Salaam
        
        map = L.map('map').setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);

        map.on('click', function(e) {
            const { lat, lng } = e.latlng;
            updateLocation(lat, lng);
        });

        marker.on('dragend', function(e) {
            const { lat, lng } = marker.getLatLng();
            updateLocation(lat, lng);
        });

        // Try to get current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                map.setView([lat, lng], 15);
                updateLocation(lat, lng);
            });
        }
    }

    // Initialize map immediately
    let mapVisible = false;
    function toggleMap() {
        const wrap = document.getElementById("map-collapsible");
        const btnText = document.getElementById("map-btn-text");
        mapVisible = !mapVisible;
        wrap.style.display = mapVisible ? "block" : "none";
        btnText.innerText = mapVisible ? "Close Intelligence Map" : "Pinpoint Residential Location";
        if (mapVisible) {
            map.invalidateSize();
        }
    }

    function updateLocation(lat, lng) {
        marker.setLatLng([lat, lng]);
        document.getElementById("latitude").value = lat;
        document.getElementById("longitude").value = lng;
        document.getElementById("location-text").innerText = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
        document.querySelector(".map_container").classList.add("active");
        
        // Reverse Geocoding using Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
            .then(res => res.json())
            .then(data => {
                if (data.address) {
                    const city = data.address.city || data.address.town || data.address.village || data.address.suburb || "N/A";
                    const state = data.address.state || data.address.region || "N/A";
                    const country = data.address.country || "N/A";
                    const road = data.address.road || data.address.neighbourhood || "";
                    
                    document.getElementById("city_field").value = city;
                    document.getElementById("state_field").value = state;
                    document.getElementById("country_field").value = country;
                    
                    const timeStr = new Date().toLocaleString();
                    document.getElementById("address-preview").innerHTML = `
                        <div class='mb-1'><i class='fas fa-earth-africa text-primary me-2'></i>${country}</div>
                        <div class='mb-1'><i class='fas fa-map-location text-primary me-2'></i>${state} • ${city}</div>
                        <div class='mb-1'><i class='fas fa-road text-primary me-2'></i>${road}</div>
                        <div class='mt-2 small opacity-75'><i class='fas fa-clock me-1'></i>Captured: ${timeStr}</div>
                    `;
                }
            })
            .catch(err => console.error("Geocoding failed", err));
    }
    function locateMe() {
        if (!mapVisible) toggleMap(); // Show map if finding location
        if (navigator.geolocation) {
            Swal.fire({
                title: 'Detecting Location...',
                text: 'Finding your exact GPS coordinates.',
                icon: 'question',
                showConfirmButton: false,
                timer: 2000
            });
            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                map.setView([lat, lng], 17);
                updateLocation(lat, lng);
            }, (error) => {
                Swal.fire('GPS Error', 'Could not get location. Please pin manually.', 'error');
            });
        }
    }

    window.onload = initMap;
    
    AOS.init({ duration: 800, once: true });

    // Handle modal mode
    if (window.self !== window.top) {
        document.body.classList.add('is-modal');
    }
    </script>
</body>
</html>
