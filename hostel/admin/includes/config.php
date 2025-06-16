<?php
// Set session configuration - must be before any session_start() happens
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Enable only if using HTTPS
    ini_set('session.use_strict_mode', 1);
}

// Database connection
$dbuser = "chuwa";
$dbpass = "chuwa123";
$host = "localhost";
$db = "Hostel";
$mysqli = new mysqli($host, $dbuser, $dbpass, $db);

// Enable/disable admin registration
define('ALLOW_ADMIN_REGISTRATION', true);
?>
