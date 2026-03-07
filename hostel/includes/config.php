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

// Environment Detection logic (Local vs Online)
$is_local = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

if ($is_local) {
    // Local Machine Database configuration
    $dbhost = "localhost";
    $dbuser = "official_chuwa";
    $dbpass = "chuwa123";
    $dbname = "Hostel";
} else {
    // Live cPanel Database configuration (WEKA CREDENTIALS ZA ONLINE HAPA)
    $dbhost = "localhost"; // Kwenye cPanel mara nyingi inabaki 'localhost'
    $dbuser = "online_db_user"; // Badili iwe user wa cpanel
    $dbpass = "online_db_password"; // Badili iwe password ya cpanel
    $dbname = "online_db_name"; // Badili iwe jina la DB ya cpanel
}

// Create connection
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to UTF-8
$mysqli->set_charset("utf8mb4");
?>