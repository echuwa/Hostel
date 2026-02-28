<?php
session_start();
include('config.php');
include('checklogin.php');
check_login();

header('Content-Type: application/json');

if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$q = "%" . $_GET['q'] . "%";
$results = [];

// 1. Search Students
$stmt = $mysqli->prepare("SELECT id, firstName, lastName, regNo, email FROM userregistration WHERE firstName LIKE ? OR lastName LIKE ? OR regNo LIKE ? OR email LIKE ? LIMIT 5");
$stmt->bind_param("ssss", $q, $q, $q, $q);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $results[] = [
        'type' => 'Student',
        'title' => $row['firstName'] . ' ' . $row['lastName'],
        'subtitle' => $row['regNo'],
        'url' => 'student-details.php?id=' . $row['id'],
        'icon' => 'fa-user-graduate'
    ];
}
$stmt->close();

// 2. Search Rooms
$stmt = $mysqli->prepare("SELECT id, room_no, seater FROM rooms WHERE room_no LIKE ? LIMIT 5");
$stmt->bind_param("s", $q);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $results[] = [
        'type' => 'Room',
        'title' => 'Room ' . $row['room_no'],
        'subtitle' => $row['seater'] . ' Seater',
        'url' => 'edit-room.php?id=' . $row['id'],
        'icon' => 'fa-door-open'
    ];
}
$stmt->close();

// 3. Search Complaints
$stmt = $mysqli->prepare("SELECT id, complaintNo, complaintType FROM complaints WHERE complaintNo LIKE ? OR complaintType LIKE ? LIMIT 5");
$stmt->bind_param("ss", $q, $q);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $results[] = [
        'type' => 'Complaint',
        'title' => '#' . $row['complaintNo'],
        'subtitle' => $row['complaintType'],
        'url' => 'complaint-details.php?id=' . $row['id'],
        'icon' => 'fa-exclamation-circle'
    ];
}
$stmt->close();

echo json_encode($results);
?>
