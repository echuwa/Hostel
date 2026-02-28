<?php
/**
 * Analytics API — Dashboard Filter Engine
 * Provides filtered data for:
 *  - Occupancy Donut  (action=occupancy)
 *  - Monthly/Weekly Trend (action=trend)
 * Access: Super Admin + Full-access Admins (no assigned_block) only
 */
require_once(__DIR__ . '/../includes/config.php');
include(__DIR__ . '/../includes/checklogin.php');
check_login();

header('Content-Type: application/json');

// Only full-access users can use this endpoint
$is_super    = !empty($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1;
$is_full_adm = empty($_SESSION['assigned_block']);
if (!$is_super && !$is_full_adm) {
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? '';
$block  = $_GET['block']  ?? 'all';   // 'all' | '1A' | '1B' | '2A' | '3A' | '4B' | '6A'
$range  = $_GET['range']  ?? '6m';    // '7d' | '30d' | '3m' | '6m'

// Build block WHERE clause for rooms table
function blockRoomCond($block) {
    if ($block === 'all' || empty($block)) return '';
    $b = $GLOBALS['mysqli']->real_escape_string($block);
    return " AND room_no LIKE '{$b}-%'";
}
function blockRegCond($block) {
    if ($block === 'all' || empty($block)) return '';
    $b = $GLOBALS['mysqli']->real_escape_string($block);
    return " AND roomno LIKE '{$b}-%'";
}

// ============================================================
// ACTION: OCCUPANCY (for Donut chart)
// ============================================================
if ($action === 'occupancy') {
    $room_cond = blockRoomCond($block);
    $reg_cond  = blockRegCond($block);

    $total_cap_q = $mysqli->query("SELECT COALESCE(SUM(seater),0) FROM rooms WHERE room_no LIKE '%-%' $room_cond");
    $total_cap   = (int)$total_cap_q->fetch_row()[0];

    $occupied_q  = $mysqli->query("SELECT COUNT(*) FROM registration WHERE roomno LIKE '%-%' $reg_cond");
    $occupied    = (int)$occupied_q->fetch_row()[0];

    $vacant     = max(0, $total_cap - $occupied);
    $pct        = $total_cap > 0 ? round(($occupied / $total_cap) * 100) : 0;

    // Rooms breakdown per block (always return all blocks for legend)
    $blocks_data = [];
    $bq = $mysqli->query("
        SELECT 
            SUBSTRING_INDEX(room_no,'-',1) as blk,
            COUNT(*) as total_rooms,
            SUM(seater) as capacity,
            (SELECT COUNT(*) FROM registration rg WHERE rg.roomno LIKE CONCAT(SUBSTRING_INDEX(r.room_no,'-',1),'-%')) as occupied
        FROM rooms r
        WHERE room_no LIKE '%-%'
        " . ($block !== 'all' ? "AND room_no LIKE '{$block}-%' " : '') . "
        GROUP BY blk
        ORDER BY blk
    ");
    while ($row = $bq->fetch_assoc()) {
        $blocks_data[] = [
            'block'    => $row['blk'],
            'capacity' => (int)$row['capacity'],
            'occupied' => (int)$row['occupied'],
            'vacant'   => max(0, (int)$row['capacity'] - (int)$row['occupied']),
            'pct'      => $row['capacity'] > 0 ? round(($row['occupied'] / $row['capacity']) * 100) : 0
        ];
    }

    echo json_encode([
        'total_capacity' => $total_cap,
        'occupied'       => $occupied,
        'vacant'         => $vacant,
        'pct'            => $pct,
        'blocks'         => $blocks_data,
    ]);
    exit;
}

// ============================================================
// ACTION: TREND (for Bar+Line chart)
// ============================================================
if ($action === 'trend') {
    $reg_cond  = blockRegCond($block);
    $reg_cond2 = blockRegCond($block);  // for revenue join

    // Date range config
    $labels    = [];
    $points    = [];  // [ ['label'=>, 'date_from'=>, 'date_to'=>] ]

    $now = new DateTime('now');

    if ($range === '7d') {
        // Last 7 days — daily
        for ($i = 6; $i >= 0; $i--) {
            $d = (clone $now)->modify("-{$i} days");
            $points[] = [
                'label'     => $d->format('D d'),
                'date_from' => $d->format('Y-m-d') . ' 00:00:00',
                'date_to'   => $d->format('Y-m-d') . ' 23:59:59',
                'type'      => 'day'
            ];
        }
    } elseif ($range === '30d') {
        // Last 30 days — weekly buckets
        for ($i = 3; $i >= 0; $i--) {
            $week_end   = (clone $now)->modify("-" . ($i * 7) . " days");
            $week_start = (clone $week_end)->modify("-6 days");
            $points[] = [
                'label'     => 'Wk ' . $week_start->format('d/m'),
                'date_from' => $week_start->format('Y-m-d') . ' 00:00:00',
                'date_to'   => $week_end->format('Y-m-d')   . ' 23:59:59',
                'type'      => 'week'
            ];
        }
    } elseif ($range === '3m') {
        // Last 3 months — monthly
        for ($i = 2; $i >= 0; $i--) {
            $d = (clone $now)->modify("-{$i} months");
            $points[] = [
                'label'     => $d->format('M Y'),
                'date_from' => $d->format('Y-m-01') . ' 00:00:00',
                'date_to'   => $d->format('Y-m-t')  . ' 23:59:59',
                'type'      => 'month'
            ];
        }
    } else {
        // '6m' — default: last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $d = (clone $now)->modify("-{$i} months");
            $points[] = [
                'label'     => $d->format('M Y'),
                'date_from' => $d->format('Y-m-01') . ' 00:00:00',
                'date_to'   => $d->format('Y-m-t')  . ' 23:59:59',
                'type'      => 'month'
            ];
        }
    }

    $admissions_arr = [];
    $revenue_arr    = [];

    foreach ($points as $pt) {
        $df = $mysqli->real_escape_string($pt['date_from']);
        $dt = $mysqli->real_escape_string($pt['date_to']);

        // New registrations in this period
        $regQ = $mysqli->query("SELECT COUNT(*) FROM registration WHERE reg_date BETWEEN '{$df}' AND '{$dt}' $reg_cond");
        $admissions_arr[] = (int)$regQ->fetch_row()[0];

        // Revenue in this period (from userregistration via LEFT JOIN registration)
        $revQ = $mysqli->query("
            SELECT COALESCE(SUM(u.fees_paid + u.accommodation_paid + u.registration_paid), 0)
            FROM userregistration u
            LEFT JOIN registration r ON u.regNo = r.regno
            WHERE u.updationDate BETWEEN '{$df}' AND '{$dt}'
            $reg_cond2
        ");
        $revenue_arr[] = round((float)$revQ->fetch_row()[0] / 1000, 1); // in K TSH
    }

    echo json_encode([
        'labels'     => array_column($points, 'label'),
        'admissions' => $admissions_arr,
        'revenue'    => $revenue_arr,
        'range'      => $range,
        'block'      => $block,
    ]);
    exit;
}

// ============================================================
// ACTION: TREND_DETAIL — KPIs + Student Lists (for slide panel)
// ============================================================
if ($action === 'trend_detail') {
    $b_esc = $mysqli->real_escape_string($block);
    $room_filter = ($block !== 'all') ? "AND r.roomno LIKE '{$b_esc}-%'" : '';

    // Total residents in block (assigned a room)
    $totalQ = $mysqli->query("
        SELECT COUNT(*) FROM userregistration u
        JOIN registration r ON u.regNo = r.regno
        WHERE u.status = 'Active' $room_filter
    ");
    $total_residents = (int)$totalQ->fetch_row()[0];

    // Fully Paid
    $paidQ = $mysqli->query("
        SELECT COUNT(*),
               COALESCE(SUM(u.fees_paid + u.accommodation_paid + u.registration_paid), 0)
        FROM userregistration u
        JOIN registration r ON u.regNo = r.regno
        WHERE u.payment_status = 'Fully Paid' $room_filter
    ");
    $paidRow = $paidQ->fetch_row();
    $fully_paid_count  = (int)$paidRow[0];
    $fully_paid_amount = (float)$paidRow[1];

    // Partially Paid (debtors)
    $partQ = $mysqli->query("
        SELECT COUNT(*),
               COALESCE(SUM(u.fees_paid + u.accommodation_paid + u.registration_paid), 0),
               COALESCE(SUM((r.feespm * r.duration) - (u.fees_paid + u.accommodation_paid + u.registration_paid)), 0) as balance
        FROM userregistration u
        JOIN registration r ON u.regNo = r.regno
        WHERE u.payment_status = 'Partially Paid' $room_filter
    ");
    $partRow = $partQ->fetch_row();
    $partial_count  = (int)$partRow[0];
    $partial_amount = (float)$partRow[1];
    $total_balance  = (float)$partRow[2];

    // Pending payment (registered but no payment)
    $pendQ = $mysqli->query("
        SELECT COUNT(*) FROM userregistration u
        JOIN registration r ON u.regNo = r.regno
        WHERE (u.payment_status = 'Pending' OR u.payment_status IS NULL) $room_filter
    ");
    $pending_pay_count = (int)$pendQ->fetch_row()[0];

    // Pending verification (no room assigned yet)
    $pendVerQ = $mysqli->query("
        SELECT COUNT(*) FROM userregistration u
        WHERE u.status = 'Pending'
        " . ($block !== 'all' ? "AND u.regNo IN (SELECT regno FROM registration r WHERE r.roomno LIKE '{$b_esc}-%')" : '') . "
    ");
    $pending_verify_count = (int)$pendVerQ->fetch_row()[0];

    // Student list — Fully Paid
    $fp_students = [];
    $fpListQ = $mysqli->query("
        SELECT u.id, u.firstName, u.lastName, u.regNo, u.email,
               (u.fees_paid + u.accommodation_paid + u.registration_paid) as total_paid,
               r.roomno
        FROM userregistration u
        JOIN registration r ON u.regNo = r.regno
        WHERE u.payment_status = 'Fully Paid' $room_filter
        ORDER BY u.firstName LIMIT 20
    ");
    while ($row = $fpListQ->fetch_assoc()) {
        $fp_students[] = [
            'id'        => $row['id'],
            'name'      => trim($row['firstName'] . ' ' . $row['lastName']),
            'regNo'     => $row['regNo'],
            'room'      => $row['roomno'],
            'paid'      => number_format((float)$row['total_paid']),
        ];
    }

    // Student list — Partially Paid / Debtors
    $debt_students = [];
    $dListQ = $mysqli->query("
        SELECT u.id, u.firstName, u.lastName, u.regNo, u.email,
               (u.fees_paid + u.accommodation_paid + u.registration_paid) as total_paid,
               ((r.feespm * r.duration) - (u.fees_paid + u.accommodation_paid + u.registration_paid)) as balance,
               r.roomno
        FROM userregistration u
        JOIN registration r ON u.regNo = r.regno
        WHERE u.payment_status = 'Partially Paid' $room_filter
        ORDER BY balance DESC LIMIT 20
    ");
    while ($row = $dListQ->fetch_assoc()) {
        $debt_students[] = [
            'id'      => $row['id'],
            'name'    => trim($row['firstName'] . ' ' . $row['lastName']),
            'regNo'   => $row['regNo'],
            'room'    => $row['roomno'],
            'paid'    => number_format((float)$row['total_paid']),
            'balance' => number_format(max(0, (float)$row['balance'])),
        ];
    }

    // Student list — Pending verification
    $pend_students = [];
    $pListQ = $mysqli->query("
        SELECT u.id, u.firstName, u.lastName, u.regNo, u.email, u.regDate
        FROM userregistration u
        WHERE u.status = 'Pending'
        ORDER BY u.regDate DESC LIMIT 20
    ");
    while ($row = $pListQ->fetch_assoc()) {
        $pend_students[] = [
            'id'      => $row['id'],
            'name'    => trim($row['firstName'] . ' ' . $row['lastName']),
            'regNo'   => $row['regNo'],
            'since'   => date('d M Y', strtotime($row['regDate'])),
        ];
    }

    echo json_encode([
        'block'               => $block,
        'total_residents'     => $total_residents,
        'fully_paid_count'    => $fully_paid_count,
        'fully_paid_amount'   => number_format($fully_paid_amount),
        'partial_count'       => $partial_count,
        'partial_amount'      => number_format($partial_amount),
        'total_balance'       => number_format($total_balance),
        'pending_pay_count'   => $pending_pay_count,
        'pending_verify_count'=> $pending_verify_count,
        'fp_students'         => $fp_students,
        'debt_students'       => $debt_students,
        'pend_students'       => $pend_students,
    ]);
    exit;
}

// ============================================================
// ACTION: COMPLAINTS_STATS — for Issue Radar with block filter
// ============================================================
if ($action === 'complaints_stats') {
    $b_esc = $mysqli->real_escape_string($block);

    // Build block JOIN for complaints
    if ($block !== 'all') {
        $c_join  = "JOIN userregistration u ON c.userId = u.id JOIN registration r ON u.regNo = r.regno";
        $c_cond  = "AND r.roomno LIKE '{$b_esc}-%'";
    } else {
        $c_join = '';
        $c_cond = '';
    }

    // Status totals
    $statusQ = $mysqli->query("
        SELECT
            SUM(c.complaintStatus = 'New' OR c.complaintStatus IS NULL OR c.complaintStatus = '') as new_open,
            SUM(c.complaintStatus IN ('In Process','In Progress')) as in_process,
            SUM(c.complaintStatus IN ('Closed','Resolved')) as resolved
        FROM complaints c $c_join
        WHERE 1=1 $c_cond
    ");
    $sr = $statusQ->fetch_assoc();

    // By type
    $types = [];
    $typeQ = $mysqli->query("
        SELECT c.complaintType,
               COUNT(*) as total,
               SUM(c.complaintStatus IN ('Closed','Resolved')) as resolved,
               SUM(c.complaintStatus IN ('In Process','In Progress')) as in_process,
               SUM(c.complaintStatus = 'New' OR c.complaintStatus IS NULL OR c.complaintStatus = '') as new_open
        FROM complaints c $c_join
        WHERE 1=1 $c_cond
        GROUP BY c.complaintType
        ORDER BY total DESC
        LIMIT 6
    ");
    while ($row = $typeQ->fetch_assoc()) {
        $types[] = [
            'type'       => $row['complaintType'],
            'total'      => (int)$row['total'],
            'resolved'   => (int)$row['resolved'],
            'in_process' => (int)$row['in_process'],
            'new_open'   => (int)$row['new_open'],
        ];
    }

    // Block comparison (all blocks side by side)
    $block_comp = [];
    $bcQ = $mysqli->query("
        SELECT SUBSTRING_INDEX(r.roomno,'-',1) as blk,
               COUNT(c.id) as total,
               SUM(c.complaintStatus IN ('Closed','Resolved')) as resolved,
               SUM(c.complaintStatus IN ('In Process','In Progress')) as in_process,
               SUM(c.complaintStatus = 'New' OR c.complaintStatus IS NULL OR c.complaintStatus = '') as new_open
        FROM complaints c
        JOIN userregistration u ON c.userId = u.id
        JOIN registration r ON u.regNo = r.regno
        WHERE r.roomno LIKE '%-%'
        GROUP BY blk
        ORDER BY total DESC
    ");
    while ($row = $bcQ->fetch_assoc()) {
        $block_comp[] = [
            'block'      => $row['blk'],
            'total'      => (int)$row['total'],
            'resolved'   => (int)$row['resolved'],
            'in_process' => (int)$row['in_process'],
            'new_open'   => (int)$row['new_open'],
        ];
    }

    echo json_encode([
        'block'       => $block,
        'new_open'    => (int)($sr['new_open'] ?? 0),
        'in_process'  => (int)($sr['in_process'] ?? 0),
        'resolved'    => (int)($sr['resolved'] ?? 0),
        'types'       => $types,
        'block_comp'  => $block_comp,
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>
