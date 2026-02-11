<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['is_superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once('../includes/config.php');

try {
    $query = "SELECT al.id, al.user_id, al.action_type as action, al.description as details, 
                     al.ip_address, al.created_at, a.username 
              FROM audit_logs al 
              LEFT JOIN admins a ON al.user_id = a.id 
              ORDER BY al.created_at DESC LIMIT 50";
    
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id' => $row['id'],
            'admin_id' => $row['user_id'],
            'username' => $row['username'] ?? 'System',
            'action' => $row['action'],
            'details' => $row['details'],
            'ip_address' => $row['ip_address'],
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'logs' => $logs]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching audit logs']);
}
?>