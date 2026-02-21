<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@example.com';
$_SESSION['is_superadmin'] = 1;

ob_start();
include('admin/dashboard.php');
$output = ob_get_clean();

if (strlen($output) > 100) {
    echo "Dashboard works, size: " . strlen($output);
} else {
    echo "Dashboard is returning very little: ". $output;
}
?>
