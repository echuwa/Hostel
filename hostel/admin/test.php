<?php
include('includes/config.php');
$stmt = $mysqli->prepare("SELECT complaintDetails FROM complaints LIMIT 1");
if(!$stmt) {
    die("Error: " . $mysqli->error);
}
echo "OK";
?>
