<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Redirect super admins to manage reports instead
if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
    header("Location: manage-reports.php");
    exit();
}

$success = '';
$error = '';

if (isset($_POST['submit_report'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token mismatch.";
    } else {
        $debtor_id = $_SESSION['id'];
        $block_name = $_SESSION['assigned_block'] ?? 'All';
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO debtor_reports (debtor_id, block_name, title, content) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $debtor_id, $block_name, $title, $content);
            if ($stmt->execute()) {
                $success = "Report submitted successfully to the Super Admin!";
            } else {
                $error = "Failed to submit report. Please try again.";
            }
            $stmt->close();
        }
    }
}

if (isset($_POST['edit_report'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token mismatch.";
    } else {
        $report_id = $_POST['report_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $debtor_id = $_SESSION['id'];

        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            // Debtors can only edit their own reports
            $stmt = $mysqli->prepare("UPDATE debtor_reports SET title = ?, content = ? WHERE id = ? AND debtor_id = ?");
            $stmt->bind_param("ssii", $title, $content, $report_id, $debtor_id);
            if ($stmt->execute()) {
                $success = "Report updated successfully.";
            } else {
                $error = "Failed to update report.";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['del'])) {
    if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $error = "Security token mismatch.";
    } else {
        $id = $_GET['del'];
        $debtor_id = $_SESSION['id'];
        
        // SOFT DELETE: Only hide from debtor, don't affect admin
        $stmt = $mysqli->prepare("UPDATE debtor_reports SET deleted_by_debtor = 1 WHERE id = ? AND debtor_id = ?");
        $stmt->bind_param("ii", $id, $debtor_id);
        if ($stmt->execute()) {
            $success = "Report removed from your history.";
        } else {
            $error = "Failed to remove report.";
        }
        $stmt->close();
    }
}

// Fetch unread count before marking as read
$unread_stmt = $mysqli->prepare("SELECT COUNT(*) FROM debtor_reports WHERE debtor_id = ? AND debtor_read = 0 AND admin_reply IS NOT NULL AND deleted_by_debtor = 0");
$unread_stmt->bind_param("i", $_SESSION['id']);
$unread_stmt->execute();
$unread_stmt->bind_result($unread_count);
$unread_stmt->fetch();
$unread_stmt->close();

// Mark as read when viewing this page
$mark_read = $mysqli->prepare("UPDATE debtor_reports SET debtor_read = 1 WHERE debtor_id = ? AND debtor_read = 0 AND admin_reply IS NOT NULL");
$mark_read->bind_param("i", $_SESSION['id']);
$mark_read->execute();
$mark_read->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Prepare Block Report | HostelMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .report-card {
            background: #fff; border-radius: 20px; border: 1px solid #f1f5f9;
            padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .form-label { font-weight: 700; color: #475569; margin-bottom: 10px; }
        .form-control { border-radius: 12px; border: 1px solid #e2e8f0; padding: 12px 15px; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }
        .history-card { 
            border-radius: 20px; background: #fff; border: 1px solid #f1f5f9; 
            margin-bottom: 25px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden;
        }
        .history-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.06); border-color: var(--primary); }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.5px; }
        .delete-btn { 
            position: absolute; top: 20px; right: 20px; opacity: 0; transition: 0.3s;
            width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            background: #fef2f2; color: #ef4444; border: none;
        }
        .edit-btn { 
            position: absolute; top: 20px; right: 65px; opacity: 0; transition: 0.3s;
            width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            background: #f0f9ff; color: #0ea5e9; border: none;
        }
        .history-card:hover .delete-btn, .history-card:hover .edit-btn { opacity: 1; }
        .delete-btn:hover { background: #ef4444; color: #fff; }
        .edit-btn:hover { background: #0ea5e9; color: #fff; }
        
        .notification-banner {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            color: #fff; border-radius: 16px; padding: 20px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.2);
            margin-bottom: 30px; animation: slideIn 0.5s ease;
        }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .new-reply-badge {
            background: #ef4444; color: #fff; font-size: 0.65rem; font-weight: 900;
            padding: 4px 10px; border-radius: 8px; margin-left: 10px;
            animation: pulse 2s infinite; vertical-align: middle; padding-bottom: 4px; border:2px solid #fee2e2;
        }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Submit Block Report</h2>
                        <p class="text-muted fw-600 mb-0">Prepare and send technical or financial reports for <strong>Block <?php echo $_SESSION['assigned_block'] ?? 'All'; ?></strong>.</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="report-card">
                            <form method="POST">
                                <?php csrf_field(); ?>
                                <div class="mb-4">
                                    <label class="form-label">Report Title</label>
                                    <input type="text" name="title" class="form-control" placeholder="e.g. Weekly Maintenance Summary" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Report Details</label>
                                    <textarea name="content" class="form-control" rows="10" placeholder="Describe the current status, issues, or updates regarding your assigned block..." required></textarea>
                                </div>
                                <button type="submit" name="submit_report" class="btn btn-primary w-100 py-3 fw-800 rounded-3">
                                    <i class="fas fa-paper-plane me-2"></i> TRANSMIT REPORT TO SUPER ADMIN
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <h5 class="fw-800 mb-4">Recent Reports History</h5>
                        <div class="history-container" style="max-height: 600px; overflow-y: auto;">
                            <?php 
                            $query = "SELECT * FROM debtor_reports WHERE debtor_id = ? AND deleted_by_debtor = 0 ORDER BY created_at DESC";
                            $stmt = $mysqli->prepare($query);
                            $stmt->bind_param("i", $_SESSION['id']);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if ($res->num_rows > 0):
                                while($row = $res->fetch_assoc()):
                            ?>
                                <div class="history-card p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2 pe-5">
                                        <h6 class="fw-800 text-dark mb-0">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                            <?php if ($row['status'] == 'responded' && $row['debtor_read'] == 0): ?>
                                                <span class="new-reply-badge">NEW RESPONSE</span>
                                            <?php endif; ?>
                                        </h6>
                                        <span class="status-badge <?php echo $row['status'] == 'pending' ? 'status-pending' : 'status-responded'; ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </div>
                                    <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <p class="small text-muted fw-600 mb-3"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></p>
                                    <p class="text-secondary small mb-3"><?php echo nl2br(htmlspecialchars(substr($row['content'], 0, 150))) . (strlen($row['content']) > 150 ? '...' : ''); ?></p>
                                    
                                    <?php if ($row['admin_reply']): ?>
                                        <div class="bg-white rounded-3 p-3 border-start border-success border-4 mt-3">
                                            <label class="small fw-800 text-success d-block mb-1">SUPER ADMIN RESPONSE</label>
                                            <p class="small text-dark mb-1"><?php echo nl2br(htmlspecialchars($row['admin_reply'])); ?></p>
                                            <p class="text-muted" style="font-size: 0.65rem;">Replied: <?php echo date('M d, Y H:i', strtotime($row['replied_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                                    <p class="fw-700">No reports submitted yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Edit Report Modal -->
    <div class="modal fade" id="editReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-edit me-2"></i>Edit Report Content</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="report_id" id="edit_report_id">
                        <div class="mb-3">
                            <label class="form-label">Report Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Report Details</label>
                            <textarea name="content" id="edit_content" class="form-control" rows="8" required></textarea>
                        </div>
                        <div class="alert alert-info border-0 rounded-3 small py-2 mb-4">
                            <i class="fas fa-info-circle me-1"></i> Note: You are only editing your side of the communication.
                        </div>
                        <button type="submit" name="edit_report" class="btn btn-primary w-100 py-3 fw-800 rounded-3 shadow-sm">
                            <i class="fas fa-save me-2"></i> UPDATE REPORT
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(data) {
            document.getElementById('edit_report_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_content').value = data.content;
            new bootstrap.Modal(document.getElementById('editReportModal')).show();
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hide Report?',
                text: "This will remove the report from your view, but it will remain in the Super Admin's records.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Hide it!',
                background: '#fff',
                borderRadius: '20px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `prepare-report.php?del=${id}&token=<?php echo generate_csrf_token(); ?>`;
                }
            });
        }
    </script>
<?php if($success): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Success', text: '<?php echo $success; ?>' });
    </script>
    <?php endif; ?>

    <?php if($error): ?>
    <script>
        Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo $error; ?>' });
    </script>
    <?php endif; ?>
</body>
</html>
