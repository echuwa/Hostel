<?php
// Set session configuration - must be before any session_start() happens
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Enable only if using HTTPS
    ini_set('session.use_strict_mode', 1);
}

// Database connection
$dbhost = "localhost";  // Host/IP
$dbuser = "official_chuwa";      // Username
$dbpass = "chuwa123";   // Password
$dbname = "Hostel";     // Database name

// Create connection with correct parameter order
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Enable/disable admin registration
define('ALLOW_ADMIN_REGISTRATION', true);
?>
