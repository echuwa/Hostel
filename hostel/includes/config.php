<?php
// Initialize Security
require_once(__DIR__ . '/security.php');
set_security_headers();

// Session security
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    // ini_set('session.cookie_secure', 1); // Enable if HTTPS
    session_start();
}
secure_session();

// Database configuration
$dbhost = "localhost";  // or "127.0.0.1" if having connection issues
$dbuser = "official_chuwa";      // Your actual database username
$dbpass = "chuwa123";   // Your actual database password
$dbname = "Hostel";     // Your database name

// Create connection
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . 
        " (Error code: " . $mysqli->connect_errno . ")");
}

// Set charset to UTF-8
$mysqli->set_charset("utf8mb4");

// Optional: Verify connection works
// echo "Successfully connected to database: " . $dbname;
?>