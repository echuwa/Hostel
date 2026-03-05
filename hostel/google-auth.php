<?php
session_start();
include('includes/config.php');

if (isset($_POST['idtoken'])) {
    $id_token = $_POST['idtoken'];
    
    // Verify token with Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $payload = json_decode($response, true);

    if ($payload && isset($payload['sub'])) {
        $google_id = $payload['sub'];
        $email = $payload['email'];
        $fname = $payload['given_name'] ?? '';
        $lname = $payload['family_name'] ?? '';
        $picture = $payload['picture'] ?? '';

        // Check if user exists with this Google ID
        $stmt = $mysqli->prepare("SELECT id, firstName, lastName, status FROM userregistration WHERE google_id = ?");
        $stmt->bind_param("s", $google_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // User exists, log them in
            if ($user['status'] === 'Blocked') {
                echo json_encode(['status' => 'error', 'message' => 'Your account is blocked.']);
                exit;
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $email;
            $_SESSION['name'] = $user['firstName'] . ' ' . $user['lastName'];
            $_SESSION['user_role'] = 'student';
            
            // Log the access
            require_once('includes/checklogin.php');
            log_student_access($user['id'], $email);
            
            echo json_encode(['status' => 'success', 'redirect' => 'dashboard.php']);
        } else {
            // User doesn't exist, check if email exists
            $stmt = $mysqli->prepare("SELECT id FROM userregistration WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $email_result = $stmt->get_result();

            if ($email_user = $email_result->fetch_assoc()) {
                // Email exists, link Google ID to this account and log in
                $update = $mysqli->prepare("UPDATE userregistration SET google_id = ?, profile_pic = ? WHERE email = ?");
                $update->bind_param("sss", $google_id, $picture, $email);
                $update->execute();

                $_SESSION['user_id'] = $email_user['id'];
                $_SESSION['login'] = $email;
                $_SESSION['user_role'] = 'student';

                // Log the access
                require_once('includes/checklogin.php');
                log_student_access($email_user['id'], $email);

                echo json_encode(['status' => 'success', 'redirect' => 'dashboard.php']);
            } else {
                // New user, create account automatically
            
            // 1. Generate Reg No (Simplified for Google Users)
            $year = date('y');
            $quarter = ceil(date('n') / 3);
            $prefix = "G{$year}-0{$quarter}-";
            $stmt = $mysqli->prepare("SELECT MAX(regNo) FROM userregistration WHERE regNo LIKE ?");
            $param = $prefix . '%';
            $stmt->bind_param('s', $param);
            $stmt->execute();
            $stmt->bind_result($lastRegNo);
            $stmt->fetch();
            $stmt->close();
            
            $newNumber = $lastRegNo ? intval(substr($lastRegNo, strlen($prefix))) + 1 : 1;
            $regno = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

            // 2. Control Numbers functions (Helper)
            function generateGoogleCtrl() { return "99" . rand(10, 99) . date('md') . rand(100, 999) . rand(1000, 9999); }
            $fee_ctrl = generateGoogleCtrl();
            $acc_ctrl = generateGoogleCtrl();
            $reg_ctrl = generateGoogleCtrl();

            // 3. Insert into DB with Pending status
            // Note: password is set to a random string since they login via Google
            $temp_pass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
            $query = "INSERT INTO userregistration(regNo, firstName, lastName, email, password, status, google_id, profile_pic, fee_control_no, acc_control_no, reg_control_no) VALUES(?,?,?,?,?,'Pending',?,?,?,?,?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('sssssssssss', $regno, $fname, $lname, $email, $temp_pass, $google_id, $picture, $fee_ctrl, $acc_ctrl, $reg_ctrl);
            
            if($stmt->execute()) {
                echo json_encode(['status' => 'pending', 'message' => 'Your account has been created via Google! Please wait for administrative approval.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $mysqli->error]);
            }
            $stmt->close();
        }
    }
} else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID token.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No token provided.']);
}
?>
