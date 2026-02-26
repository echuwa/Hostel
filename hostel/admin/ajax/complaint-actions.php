<?php
session_start();
include('../includes/config.php');
include('../includes/checklogin.php');
check_login();

if(isset($_POST['action'])) {
    $cid = intval($_POST['cid']);
    
    if($_POST['action'] == 'mark_read') {
        // If status is NULL (New), mark it as 'In Process'
        $check = $mysqli->query("SELECT complaintStatus FROM complaints WHERE id=$cid");
        $row = $check->fetch_assoc();
        
        if(empty($row['complaintStatus'])) {
            $new_status = 'In Process';
            $stmt = $mysqli->prepare("UPDATE complaints SET complaintStatus=? WHERE id=?");
            $stmt->bind_param('si', $new_status, $cid);
            $stmt->execute();
            
            // Log history
            $remark = "Admin viewed the complaint for the first time.";
            $hstmt = $mysqli->prepare("INSERT INTO complainthistory(complaintid, compalintStatus, complaintRemark) VALUES (?, ?, ?)");
            $hstmt->bind_param('iss', $cid, $new_status, $remark);
            $hstmt->execute();
            
            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
        } else {
            echo json_encode(['status' => 'no_change']);
        }
    }
    
    if($_POST['action'] == 'update_complaint') {
        $cstatus = $_POST['cstatus'];
        $remark = $_POST['remark'];
        
        $stmt = $mysqli->prepare("UPDATE complaints SET complaintStatus=? WHERE id=?");
        $stmt->bind_param('si', $cstatus, $cid);
        if($stmt->execute()) {
            $hstmt = $mysqli->prepare("INSERT INTO complainthistory(complaintid, compalintStatus, complaintRemark) VALUES (?, ?, ?)");
            $hstmt->bind_param('iss', $cid, $cstatus, $remark);
            $hstmt->execute();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $mysqli->error]);
        }
    }
}
?>
