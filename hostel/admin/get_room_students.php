<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['room_no'])) {
    $room_no = $_GET['room_no'];
    $query = "SELECT u.firstName, u.lastName, u.regNo, u.contactNo, r.stayfrom FROM userregistration u JOIN registration r ON u.regNo = r.regno WHERE r.roomno = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $room_no);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $students = [];
    while($row = $res->fetch_object()) {
        $students[] = $row;
    }
    
    echo json_encode($students);
}
?>
