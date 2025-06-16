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
    <title>Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            overflow: hidden;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
        .view-details {
            transition: all 0.2s;
        }
        .view-details:hover {
            transform: translateY(-2px);
        }
        .modal-body pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-bottom: 0;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Audit Logs</h2>
                    <span class="badge bg-light text-primary fs-6">Total: <?= number_format($totalRows) ?></span>
                </div>
            </div>

            <div class="card-body">
                <!-- Search Filters -->
                <div class="filter-section">
                    <form method="get" class="mb-0">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Search Text</label>
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Action Type</label>
                                <select name="action_type" class="form-select">
                                    <option value="">All Actions</option>
                                    <?php while ($action = $actionTypes->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($action['action_type']) ?>" <?= $actionType === $action['action_type'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $action['action_type']))) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">User</label>
                                <select name="user_id" class="form-select">
                                    <option value="">All Users</option>
                                    <?php while ($user = $adminUsers->fetch_assoc()): ?>
                                        <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['username']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Logs Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($log = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action_type']))) ?></span></td>
                                        <td><?= htmlspecialchars($log['description']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['ip_address']) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary view-details" 
                                                    data-bs-toggle="modal" data-bs-target="#logDetailsModal"
                                                    data-log='<?= htmlspecialchars(json_encode($log)) ?>'>
                                                <i class="fas fa-eye me-1"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-clipboard-question fs-1 text-muted mb-3"></i>
                                            <h5 class="text-muted">No audit logs found</h5>
                                            <?php if (!empty($where)): ?>
                                                <a href="audit_logs.php" class="btn btn-sm btn-outline-primary mt-2">
                                                    Clear filters
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => 1]) ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php 
                            // Show first page and ellipsis if needed
                            if ($page > 3): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => 1]) ?>">1</a>
                                </li>
                                <?php if ($page > 4): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php 
                            // Show last page and ellipsis if needed
                            if ($page < $totalPages - 2): ?>
                                <?php if ($page < $totalPages - 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => $totalPages]) ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildQueryString(['page' => $totalPages]) ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="logDetailsModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Audit Log Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="d-flex mb-2">
                                <div class="me-3 text-muted" style="width: 120px;">
                                    <i class="fas fa-clock me-1"></i> Timestamp:
                                </div>
                                <div id="detailTimestamp" class="fw-bold"></div>
                            </div>
                            <div class="d-flex mb-2">
                                <div class="me-3 text-muted" style="width: 120px;">
                                    <i class="fas fa-user me-1"></i> User:
                                </div>
                                <div id="detailUser" class="fw-bold"></div>
                            </div>
                            <div class="d-flex mb-2">
                                <div class="me-3 text-muted" style="width: 120px;">
                                    <i class="fas fa-bolt me-1"></i> Action:
                                </div>
                                <div id="detailAction" class="fw-bold"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-2">
                                <div class="me-3 text-muted" style="width: 120px;">
                                    <i class="fas fa-network-wired me-1"></i> IP Address:
                                </div>
                                <div id="detailIp" class="fw-bold"></div>
                            </div>
                            <div class="d-flex mb-2">
                                <div class="me-3 text-muted" style="width: 120px;">
                                    <i class="fas fa-flag me-1"></i> Status:
                                </div>
                                <div id="detailStatus" class="fw-bold"></div>
                            </div>
                            <div class="d-flex mb-2">
                                <div class="me-3 text-muted" style="width: 120px;">
                                    <i class="fas fa-database me-1"></i> Affected:
                                </div>
                                <div id="detailAffected" class="fw-bold"></div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h6 class="mb-3 text-primary">
                            <i class="fas fa-align-left me-1"></i> Description
                        </h6>
                        <div class="p-3 bg-light rounded">
                            <p id="detailDescription" class="mb-0"></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="mb-3 text-primary">
                            <i class="fas fa-code me-1"></i> Additional Data
                        </h6>
                        <pre id="detailAdditional" class="p-3 bg-light rounded"></pre>
                    </div>
                    
                    <div>
                        <h6 class="mb-3 text-primary">
                            <i class="fas fa-desktop me-1"></i> User Agent
                        </h6>
                        <div class="p-3 bg-light rounded">
                            <p id="detailUserAgent" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Modal handling
        $('#logDetailsModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const log = JSON.parse(button.data('log'));
            
            // Format affected record info
            let affectedInfo = 'N/A';
            if (log.affected_table && log.affected_record_id) {
                affectedInfo = `${log.affected_table} #${log.affected_record_id}`;
            }
            
            // Format additional data
            let additionalData = 'N/A';
            if (log.additional_data) {
                try {
                    additionalData = JSON.stringify(JSON.parse(log.additional_data), null, 2);
                } catch (e) {
                    additionalData = log.additional_data;
                }
            }
            
            // Format timestamp
            const timestamp = new Date(log.created_at);
            const formattedTimestamp = timestamp.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            // Populate modal
            $('#detailTimestamp').text(formattedTimestamp);
            $('#detailUser').text(log.username || 'System');
            $('#detailAction').text(log.action_type.replace(/_/g, ' '));
            $('#detailIp').text(log.ip_address);
            $('#detailStatus').text(log.status || 'N/A');
            $('#detailAffected').text(affectedInfo);
            $('#detailDescription').text(log.description || 'N/A');
            $('#detailAdditional').text(additionalData);
            $('#detailUserAgent').text(log.user_agent || 'N/A');
        });
        
        // Ensure modal is properly closed when clicking close button
        $('#logDetailsModal .btn-close, #logDetailsModal .btn-secondary').on('click', function() {
            $('#logDetailsModal').modal('hide');
        });
        
        // Reset modal content when closed to prevent flickering
        $('#logDetailsModal').on('hidden.bs.modal', function() {
            $('#detailTimestamp, #detailUser, #detailAction, #detailIp, #detailStatus, ' +
              '#detailAffected, #detailDescription, #detailAdditional, #detailUserAgent').text('');
        });
    });
    </script>
    <div class="d-flex justify-content-start mb-4">
    <a href="superadmin-dashboard.php" class="btn btn-outline-primary">
        ← Back to Dashboard
    </a>
</div>
</body>
</html>