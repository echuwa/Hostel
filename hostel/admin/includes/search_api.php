<?php
// ==================== INITIALIZATION ====================
// config.php handles session startup (same as all admin pages)
require_once(__DIR__ . '/config.php');

header('Content-Type: application/json');

// Must be logged in as admin
if (empty($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// ==================== PERMISSIONS CHECK ====================
$adminId       = (int) $_SESSION['id'];
$isSuperAdmin  = !empty($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1;
$assignedBlock = $_SESSION['assigned_block'] ?? null; // e.g. "1", "3", null

// Build block filter for rooms/students: e.g. "1%" matches "1A-G01", "100", etc.
$blockPattern  = $assignedBlock ? $assignedBlock . '%' : null;

$results = [];
$search  = "%" . $mysqli->real_escape_string($q) . "%";

// ==================== 1. SEARCH STUDENTS ====================
// Debtor sees only students assigned to rooms in their block
// Super admin sees all students
if ($isSuperAdmin || !$assignedBlock) {
    // Full access
    $sql = "SELECT u.id, u.firstName, u.lastName, u.regNo, u.email, u.status,
                   r.roomno
            FROM userregistration u
            LEFT JOIN registration r ON u.regNo = r.regno
            WHERE (u.firstName LIKE ? OR u.lastName LIKE ? OR u.regNo LIKE ? OR u.email LIKE ?)
            LIMIT 8";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
} else {
    // Restricted: only students whose assigned room starts with the block number
    $bp = $blockPattern;
    $sql = "SELECT u.id, u.firstName, u.lastName, u.regNo, u.email, u.status,
                   r.roomno
            FROM userregistration u
            INNER JOIN registration r ON u.regNo = r.regno
            WHERE r.roomno LIKE ?
              AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.regNo LIKE ? OR u.email LIKE ?)
            LIMIT 8";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssss", $bp, $search, $search, $search, $search);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $fullName = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
    $results[] = [
        'type'     => 'Student',
        'title'    => $fullName ?: '(No Name)',
        'subtitle' => 'Reg: ' . ($row['regNo'] ?: 'N/A') . '  |  Room: ' . ($row['roomno'] ?: 'Unassigned'),
        'url'      => 'student-details.php?id=' . $row['id'],
        'icon'     => 'fa-user-graduate',
        'badge'    => $row['status'] ?? 'Pending',
    ];
}
$stmt->close();

// ==================== 2. SEARCH ROOMS ====================
if ($isSuperAdmin || !$assignedBlock) {
    $sql  = "SELECT id, room_no, seater, fees FROM rooms WHERE room_no LIKE ? LIMIT 5";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $search);
} else {
    // Only rooms in their block
    $bp   = $blockPattern;
    $sql  = "SELECT id, room_no, seater, fees FROM rooms WHERE room_no LIKE ? AND room_no LIKE ? LIMIT 5";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $bp, $search);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'type'     => 'Room',
        'title'    => 'Room ' . $row['room_no'],
        'subtitle' => $row['seater'] . ' Seater  |  TSH ' . number_format((int)($row['fees'] ?? 0)) . '/=',
        'url'      => 'manage-rooms.php?open_room=' . urlencode($row['room_no']),
        'icon'     => 'fa-door-open',
        'badge'    => null,
    ];
}
$stmt->close();

// ==================== 3. SEARCH COMPLAINTS ====================
// Debtor sees only complaints linked to students in their block
if ($isSuperAdmin || !$assignedBlock) {
    $sql = "SELECT c.id, c.ComplainNumber, c.complaintType, c.complaintStatus,
                   u.firstName, u.lastName
            FROM complaints c
            LEFT JOIN userregistration u ON c.userId = u.id
            WHERE c.complaintType LIKE ? OR CAST(c.ComplainNumber AS CHAR) LIKE ?
               OR u.firstName LIKE ? OR u.lastName LIKE ?
            LIMIT 6";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
} else {
    $bp   = $blockPattern;
    $sql = "SELECT c.id, c.ComplainNumber, c.complaintType, c.complaintStatus,
                   u.firstName, u.lastName
            FROM complaints c
            INNER JOIN userregistration u ON c.userId = u.id
            INNER JOIN registration r ON u.regNo = r.regno
            WHERE r.roomno LIKE ?
              AND (c.complaintType LIKE ? OR CAST(c.ComplainNumber AS CHAR) LIKE ?
                   OR u.firstName LIKE ? OR u.lastName LIKE ?)
            LIMIT 6";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssss", $bp, $search, $search, $search, $search);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $studentName = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
    $results[] = [
        'type'     => 'Complaint',
        'title'    => '#' . $row['ComplainNumber'] . ' — ' . ucwords($row['complaintType'] ?? ''),
        'subtitle' => 'By: ' . ($studentName ?: 'Unknown') . '  |  Status: ' . ($row['complaintStatus'] ?? 'Open'),
        'url'      => 'complaint-details.php?cid=' . $row['id'],
        'icon'     => 'fa-exclamation-circle',
        'badge'    => $row['complaintStatus'] ?? null,
    ];
}
$stmt->close();

// ==================== 4. SEARCH ADMIN USERS (Super Admin Only) ====================
if ($isSuperAdmin) {
    $sql  = "SELECT id, username, email, is_superadmin FROM admins WHERE username LIKE ? OR email LIKE ? LIMIT 4";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $adminType = $row['is_superadmin'] ? 'Super Admin' : 'Debtor / Admin';
        $results[] = [
            'type'     => 'Admin User',
            'title'    => ucwords($row['username'] ?? ''),
            'subtitle' => $row['email'] . '  |  ' . $adminType,
            'url'      => 'superadmin-dashboard.php',
            'icon'     => 'fa-user-shield',
            'badge'    => $adminType,
        ];
    }
    $stmt->close();
}

echo json_encode(array_values($results));
?>
