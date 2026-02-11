<?php
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