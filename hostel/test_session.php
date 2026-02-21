<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['login'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['is_superadmin'] = 1;
echo session_id();
?>
