<?php
session_start();
include('../includes/config.php');
include('../includes/checklogin.php');
check_login();

if(isset($_POST['action'])) {
    if($_POST['action'] == 'reply_feedback') {
        $fid = intval($_POST['fid']);
        $remark = $_POST['remark'];
        
        $stmt = $mysqli->prepare("UPDATE feedback SET adminRemark=?, adminRemarkDate=NOW() WHERE id=?");
        $stmt->bind_param('si', $remark, $fid);
        if($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $mysqli->error]);
        }
    }
}
?>
