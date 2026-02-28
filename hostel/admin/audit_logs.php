<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('includes/config.php');
require_once('includes/auth.php');

// Function to clean input data
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to build query string for pagination
function buildQueryString($newParams = []) {
    $params = $_GET;
    foreach ($newParams as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}

// Only superadmin can access audit logs
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$search = $actionType = $userId = $dateFrom = $dateTo = '';
$where = $params = [];
$types = '';

// Process filters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = cleanInput($_GET['search'] ?? '');
    $actionType = cleanInput($_GET['action_type'] ?? '');
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $dateFrom = cleanInput($_GET['date_from'] ?? '');
    $dateTo = cleanInput($_GET['date_to'] ?? '');
    
    // Build WHERE conditions
    if (!empty($search)) {
        $where[] = "(description LIKE ? OR additional_data LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ss';
    }
    
    if (!empty($actionType)) {
        $where[] = "action_type = ?";
        $params[] = $actionType;
        $types .= 's';
    }
    
    if (!empty($userId)) {
        $where[] = "user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    if (!empty($dateFrom)) {
        $where[] = "created_at >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $where[] = "created_at <= ?";
        $params[] = $dateTo . ' 23:59:59';
        $types .= 's';
    }
}

// Pagination
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Base query
$query = "SELECT l.*, a.username 
          FROM audit_logs l 
          LEFT JOIN admins a ON l.user_id = a.id";

// Add WHERE conditions if any
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM audit_logs l" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
$countStmt = $mysqli->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);
$countStmt->close();

// Add sorting and pagination
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $perPage;
$params[] = $offset;

// Get logs
$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get distinct action types for filter dropdown
$actionTypes = $mysqli->query("SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type");

// Get admin users for filter dropdown
$adminUsers = $mysqli->query("SELECT id, username FROM admins ORDER BY username");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | HostelMS</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-modern.css">
    <style>
        .filter-section {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid #f1f5f9;
        }
        .table-modern-audit {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>
        
        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-800 mb-1">Audit Logs & System Tracking</h2>
                        <p class="text-muted fw-600 mb-0">Monitor high-level administrative actions and user access sessions.</p>
                    </div>
                    <div class="text-end">
                        <span class="badge rounded-pill bg-primary px-3 py-2 fw-800">Total Entries: <?= number_format($totalRows) ?></span>
                    </div>
                </div>

                <div class="card-modern p-4">
                    <!-- Search Filters -->
                    <div class="filter-section">
                        <form method="get" class="mb-0">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-800 text-muted">SEARCH DESCRIPTION</label>
                                    <input type="text" name="search" class="form-control rounded-pill border-light bg-light" placeholder="Keyword..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-800 text-muted">ACTION TYPE</label>
                                    <select name="action_type" class="form-select rounded-pill border-light bg-light">
                                        <option value="">All Actions</option>
                                        <?php while ($action = $actionTypes->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($action['action_type']) ?>" <?= $actionType === $action['action_type'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $action['action_type']))) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-800 text-muted">PERFORMED BY</label>
                                    <select name="user_id" class="form-select rounded-pill border-light bg-light">
                                        <option value="">All Users</option>
                                        <?php while ($user = $adminUsers->fetch_assoc()): ?>
                                            <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-800 text-muted">FROM DATE</label>
                                            <input type="date" name="date_from" class="form-control rounded-pill border-light bg-light" value="<?= htmlspecialchars($dateFrom) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-800 text-muted">TO DATE</label>
                                            <input type="date" name="date_to" class="form-control rounded-pill border-light bg-light" value="<?= htmlspecialchars($dateTo) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Logs Table -->
                    <div class="table-responsive table-modern-audit">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 small fw-800 text-muted py-3 ps-4">TIMESTAMP</th>
                                    <th class="border-0 small fw-800 text-muted py-3">ADMIN USER</th>
                                    <th class="border-0 small fw-800 text-muted py-3">ACTION EVENT</th>
                                    <th class="border-0 small fw-800 text-muted py-3">DESCRIPTION</th>
                                    <th class="border-0 small fw-800 text-muted py-3">CLIENT IP</th>
                                    <th class="border-0 small fw-800 text-muted py-3 pe-4 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($log = $result->fetch_assoc()): ?>
                                        <tr class="align-middle">
                                            <td class="ps-4">
                                                <div class="fw-800 text-dark small mb-0"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;"><?= date('H:i:s A', strtotime($log['created_at'])) ?></div>
                                            </td>
                                            <td class="fw-700 text-dark small"><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                            <td>
                                                <span class="badge rounded-pill bg-primary-light text-primary small fw-800" style="font-size: 0.65rem;">
                                                    <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $log['action_type']))) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small fw-600"><?= htmlspecialchars($log['description']) ?></td>
                                            <td><span class="badge rounded-pill bg-light text-muted fw-700"><?= htmlspecialchars($log['ip_address']) ?></span></td>
                                            <td class="pe-4 text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <button class="btn btn-sm btn-light rounded-pill px-3 fw-800 border-0 view-details" 
                                                            data-bs-toggle="modal" data-bs-target="#logDetailsModal"
                                                            data-log='<?= htmlspecialchars(json_encode($log)) ?>' style="font-size:0.75rem;">
                                                        DETAILS
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger rounded-circle border-0 text-danger" 
                                                            onclick="deleteAuditLog(<?= $log['id'] ?>)" title="Delete Log">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-search-plus fa-3x text-light mb-3"></i>
                                            <h5 class="text-muted fw-800">No logs found for these criteria</h5>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-4">
                            <ul class="pagination pagination-modern justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>"><i class="fas fa-chevron-left"></i></a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>"><i class="fas fa-chevron-right"></i></a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

            </div> <!-- End content-wrapper -->
        </div> <!-- End main-content -->
    </div> <!-- End app-container -->

    <!-- Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white p-4 border-0">
                    <h5 class="modal-title fw-800"><i class="fas fa-info-circle me-2"></i> Transaction Breakdown</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-4">
                                <label class="small fw-800 text-muted d-block mb-1">DATA ORIGIN</label>
                                <div id="detailIp" class="fw-800 text-dark"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-4">
                                <label class="small fw-800 text-muted d-block mb-1">EVENT TYPE</label>
                                <div id="detailAction" class="fw-800 text-primary"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="my-4">
                        <label class="small fw-800 text-muted d-block mb-2">DESCRIPTION</label>
                        <div id="detailDescription" class="p-3 border rounded-4 bg-white fw-600"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="small fw-800 text-muted d-block mb-2">RAW PAYLOAD</label>
                        <pre id="detailAdditional" class="p-4 bg-dark text-success rounded-4" style="font-size: 0.8rem;"></pre>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-light rounded-pill px-5 fw-800" data-bs-dismiss="modal">DISMISS</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        $('#logDetailsModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const log = JSON.parse(button.data('log'));
            
            let additionalData = 'NO PAYLOAD RECORDED';
            if (log.additional_data) {
                try {
                    additionalData = JSON.stringify(JSON.parse(log.additional_data), null, 4);
                } catch (e) {
                    additionalData = log.additional_data;
                }
            }
            
            $('#detailAction').text(log.action_type.toUpperCase().replace(/_/g, ' '));
            $('#detailIp').text(log.ip_address);
            $('#detailDescription').text(log.description || 'No description provided');
            $('#detailAdditional').text(additionalData);
        });
    });

    function deleteAuditLog(logId) {
        Swal.fire({
            title: 'Are you absolutely sure?',
            text: "You won't be able to revert this! This action deletes system tracking history.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef233c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt me-2"></i>Yes, delete it',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'rounded-pill px-4 fw-800',
                cancelButton: 'rounded-pill px-4 fw-800'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete-audit-log-ajax.php',
                    type: 'POST',
                    data: { id: logId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                customClass: { confirmButton: 'btn btn-primary rounded-pill px-4 fw-800' }
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: response.message,
                                customClass: { confirmButton: 'btn btn-primary rounded-pill px-4 fw-800' }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'System Error',
                            text: 'Could not communicate with the server.',
                            customClass: { confirmButton: 'btn btn-primary rounded-pill px-4 fw-800' }
                        });
                    }
                });
            }
        });
    }
    </script>
</body>
</html>