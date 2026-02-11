<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['id']) && !empty($_SESSION['id']);
}

/**
 * Force user to be logged in, redirect to login page if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: superadmin-login.php');
        exit();
    }
}

/**
 * Check if current user is admin (all users are admins in this system)
 * @return bool Always returns true if logged in
 */
function isAdmin() {
    return isLoggedIn(); // In this system, all logged in users are admins
}

/**
 * Check if user is super admin
 * @return bool True if user is super admin
 */
function isSuperAdmin() {
    return isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return bool True if password is strong enough
 */
function validatePasswordStrength($password) {
    return strlen($password) >= 8;
    // For stronger passwords, you could add:
    // && preg_match('/[A-Z]/', $password) // At least one uppercase
    // && preg_match('/[a-z]/', $password) // At least one lowercase
    // && preg_match('/[0-9]/', $password) // At least one number
    // && preg_match('/[\W]/', $password)  // At least one special char
}

/**
 * Verify user credentials
 * @param mysqli $mysqli Database connection
 * @param string $username Username or email
 * @param string $password Plain text password
 * @return array|false User data if valid, false otherwise
 */
function verifyCredentials($mysqli, $username, $password) {
    $stmt = $mysqli->prepare("SELECT id, username, email, password, reg_date FROM admin WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $stmt->bind_result($id, $db_username, $db_email, $db_password, $reg_date);
    $stmt->fetch();
    $stmt->close();

    if ($id && $db_password && password_verify($password, $db_password)) {
        return [
            'id' => $id,
            'username' => $db_username,
            'email' => $db_email,
            'reg_date' => $reg_date
        ];
    }
    return false;
}

/**
 * Log login attempt
 * @param mysqli $mysqli Database connection
 * @param int $adminId Admin ID
 * @param string $ip IP address
 * @return bool True if logged successfully
 */
function logLoginAttempt($mysqli, $adminId, $ip) {
    $stmt = $mysqli->prepare("INSERT INTO adminlogs (adminid, ip, login_time) VALUES (?, ?, NOW())");
    $stmt->bind_param('is', $adminId, $ip);
    return $stmt->execute();
}

/**
 * Log admin actions
 * @param mysqli $mysqli Database connection
 * @param string $action Action description
 * @param string $details Additional details
 */
function logSimpleAdminAction($mysqli, $action, $details = '') {
    if (isset($_SESSION['id'])) {
        $admin_id = $_SESSION['id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $stmt = $mysqli->prepare("INSERT INTO adminlogs (admin_id, action, details, ip_address, action_time) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $admin_id, $action, $details, $ip_address);
        $stmt->execute();
    }
}

/**
 * Check if user should be redirected after login
 */
function handleLoginRedirect() {
    if (isset($_SESSION['redirect_url'])) {
        $url = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: superadmin-register.php");
        exit();
    }
    // Default redirect
    header('Location: superadmin-dashboard.php');
    exit();
}

/**
 * Generate a secure random token for password reset
 * @return string Generated token
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate reset token
 * @param mysqli $mysqli Database connection
 * @param string $token Reset token
 * @return array|false User data if valid, false otherwise
 */
function validateResetToken($mysqli, $token) {
    $stmt = $mysqli->prepare("SELECT id, username, email FROM admin WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($id, $username, $email);
    $stmt->fetch();
    $stmt->close();

    if ($id) {
        return [
            'id' => $id,
            'username' => $username,
            'email' => $email
        ];
    }
    return false;
}

/**
 * Set password reset token for user
 * @param mysqli $mysqli Database connection
 * @param string $email User email
 * @param string $token Reset token
 * @param string $expires Expiration datetime
 * @return bool True if successful
 */
function setResetToken($mysqli, $email, $token, $expires) {
    $stmt = $mysqli->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE email = ?");
    $stmt->bind_param('sss', $token, $expires, $email);
    return $stmt->execute();
}

/**
 * Clear reset token after use
 * @param mysqli $mysqli Database connection
 * @param int $userId User ID
 * @return bool True if successful
 */
function clearResetToken($mysqli, $userId) {
    $stmt = $mysqli->prepare("UPDATE admin SET reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->bind_param('i', $userId);
    return $stmt->execute();
}

/**
 * Update user password
 * @param mysqli $mysqli Database connection
 * @param int $userId User ID
 * @param string $password New password (plain text)
 * @return bool True if successful
 */
function updatePassword($mysqli, $userId, $password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE admin SET password = ?, updation_date = NOW() WHERE id = ?");
    $stmt->bind_param('si', $hashed_password, $userId);
    return $stmt->execute();
}

/**
 * Check if username or email already exists
 * @param mysqli $mysqli Database connection
 * @param string $username Username to check
 * @param string $email Email to check
 * @return array ['username' => bool, 'email' => bool]
 */
function checkUserExists($mysqli, $username, $email) {
    $result = ['username' => false, 'email' => false];

    $stmt = $mysqli->prepare("SELECT username, email FROM admin WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->bind_result($db_username, $db_email);

    while ($stmt->fetch()) {
        if ($db_username === $username) $result['username'] = true;
        if ($db_email === $email) $result['email'] = true;
    }

    $stmt->close();
    return $result;


/**
 * Logs administrative actions to the audit trail
 * 
 * @param mysqli $mysqli Database connection
 * @param int $userId Admin ID performing the action
 * @param string $actionType Type of action
 * @param string $description Action description
 * @param int|null $recordId Affected record ID
 * @param string|null $tableName Affected table name
 * @param array|null $additionalData Extra context data
 * @param string $status 'success' or 'failed'
 * @return bool True on success, false on failure
 */
function logAdminAction($mysqli, $userId, $actionType, $description, $recordId = null, $tableName = null, $additionalData = null, $status = 'success') {
    // Sanitize inputs
    $userId = (int)$userId;
    $actionType = trim($actionType);
    $description = trim($description);
    
    // Get client information
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Prepare additional data
    $additionalDataJson = null;
    if ($additionalData) {
        // Remove sensitive information before logging
        if (isset($additionalData['password'])) {
            unset($additionalData['password']);
        }
        if (isset($additionalData['token'])) {
            unset($additionalData['token']);
        }
        $additionalDataJson = json_encode($additionalData);
    }
    
    // Prepare and execute query
    $stmt = $mysqli->prepare("INSERT INTO audit_logs 
        (user_id, action_type, description, affected_record_id, affected_table, 
         ip_address, user_agent, additional_data, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        "ississsss", 
        $userId, 
        $actionType, 
        $description, 
        $recordId, 
        $tableName, 
        $ipAddress, 
        $userAgent, 
        $additionalDataJson,
        $status
    );
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// add something here

function log_activity($action_type, $description, $additional_data = null, $affected_table = null, $affected_record_id = null) {
    global $mysqli;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Convert array data to JSON if needed
    if (is_array($additional_data)) {
        $additional_data = json_encode($additional_data, JSON_PRETTY_PRINT);
    }
    
    $stmt = $mysqli->prepare("INSERT INTO audit_logs 
        (user_id, action_type, description, additional_data, ip_address, user_agent, affected_table, affected_record_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        "isssssss",
        $user_id,
        $action_type,
        $description,
        $additional_data,
        $ip_address,
        $user_agent,
        $affected_table,
        $affected_record_id
    );
    
    return $stmt->execute();
}













}