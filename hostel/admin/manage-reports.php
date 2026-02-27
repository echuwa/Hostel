<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Security Check - Only Super Admin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';

if (isset($_POST['send_reply'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token mismatch.";
    } else {
        $report_id = $_POST['report_id'];
        $reply_content = trim($_POST['reply_content']);

        if (empty($reply_content)) {
            $error = "Reply content cannot be empty.";
        } else {
            $stmt = $mysqli->prepare("UPDATE debtor_reports SET admin_reply = ?, status = 'responded', replied_at = NOW(), debtor_read = 0 WHERE id = ?");
            $stmt->bind_param("si", $reply_content, $report_id);
            if ($stmt->execute()) {
                $success = "Reply transmitted successfully to the debtor!";
            } else {
                $error = "Failed to send reply.";
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
        // SOFT DELETE: Only hide from admin view
        $stmt = $mysqli->prepare("UPDATE debtor_reports SET deleted_by_admin = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Report removed from your management view.";
        } else {
            $error = "Failed to remove report.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Block Reports | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .report-row {
            background: #fff; border-radius: 20px; border: 1px solid #f1f5f9;
            padding: 30px; margin-bottom: 25px; transition: all 0.3s ease;
            position: relative;
        }
        .report-row:hover { border-color: var(--primary); box-shadow: 0 15px 35px rgba(67, 97, 238, 0.08); }
        .sender-avatar {
            width: 50px; height: 50px; border-radius: 14px;
            background: var(--primary-light); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.3rem; box-shadow: var(--shadow-sm);
        }
        .block-tag { background: #f1f5f9; color: #475569; padding: 5px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; }
        .reply-box { background: #f8fafc; border-radius: 16px; padding: 25px; margin-top: 25px; display: none; border: 1px solid #e2e8f0; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.5px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-responded { background: #d1fae5; color: #065f46; }
        .purge-btn {
            position: absolute; top: 30px; right: 30px; opacity: 0; transition: 0.3s;
            width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
            background: #fff1f2; color: #e11d48; border: 1px solid #fecaca;
        }
        .report-row:hover .purge-btn { opacity: 1; }
        .purge-btn:hover { background: #e11d48; color: #fff; transform: rotate(90deg); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Debtor Communication Center</h2>
                        <p class="text-muted fw-600 mb-0">Review reports from block debtors and provide specific directives.</p>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 rounded-4 shadow-sm p-4 text-center">
                            <h2 class="fw-800 text-primary mb-0">
                                <?php echo $mysqli->query("SELECT COUNT(*) FROM debtor_reports WHERE deleted_by_admin = 0")->fetch_row()[0]; ?>
                            </h2>
                            <p class="text-muted small fw-700 mb-0">TOTAL REPORTS</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 rounded-4 shadow-sm p-4 text-center">
                            <h2 class="fw-800 text-warning mb-0">
                                <?php echo $mysqli->query("SELECT COUNT(*) FROM debtor_reports WHERE status='pending' AND deleted_by_admin = 0")->fetch_row()[0]; ?>
                            </h2>
                            <p class="text-muted small fw-700 mb-0">PENDING ACTION</p>
                        </div>
                    </div>
                </div>

                <div class="reports-list">
                    <?php 
                    $query = "SELECT r.*, a.username as debtor_name, a.email as debtor_email 
                             FROM debtor_reports r 
                             JOIN admins a ON r.debtor_id = a.id 
                             WHERE r.deleted_by_admin = 0
                             ORDER BY r.created_at DESC";
                    $res = $mysqli->query($query);
                    if ($res->num_rows > 0):
                        while($row = $res->fetch_assoc()):
                    ?>
                        <div class="report-row shadow-sm">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="sender-avatar">
                                        <?php echo strtoupper(substr($row['debtor_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-800 text-dark mb-0"><?php echo htmlspecialchars($row['debtor_name']); ?></h6>
                                        <span class="block-tag"><i class="fas fa-building me-1"></i> Block <?php echo htmlspecialchars($row['block_name']); ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge <?php echo $row['status'] == 'pending' ? 'status-pending' : 'status-responded'; ?> mb-2 d-inline-block">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>
                                    <div class="small text-muted fw-600"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></div>
                                </div>
                                <button class="purge-btn shadow-sm" onclick="confirmPurge(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>

                            <div class="report-content ps-5 ms-3">
                                <h5 class="fw-800 text-dark mb-3"><?php echo htmlspecialchars($row['title']); ?></h5>
                                <p class="text-secondary fw-500 mb-4"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                                
                                <?php if ($row['admin_reply']): ?>
                                    <div class="bg-success bg-opacity-10 rounded-3 p-4 border-start border-success border-4 mb-4">
                                        <label class="small fw-800 text-success d-block mb-1">MY RESPONSE</label>
                                        <p class="text-dark fw-600 mb-0"><?php echo nl2br(htmlspecialchars($row['admin_reply'])); ?></p>
                                        <p class="text-muted small mt-2 mb-0">Sent on <?php echo date('M d, Y H:i', strtotime($row['replied_at'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button onclick="toggleReply('reply-<?php echo $row['id']; ?>')" class="btn btn-primary btn-sm rounded-pill px-4 fw-800">
                                        <i class="fas fa-reply me-1"></i> <?php echo $row['admin_reply'] ? 'Update Reply' : 'Draft Response'; ?>
                                    </button>
                                </div>

                                <div id="reply-<?php echo $row['id']; ?>" class="reply-box mt-3 border shadow-sm">
                                    <form method="POST">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label fw-800 small text-muted">YOUR DIRECTIVE TO <?php echo strtoupper($row['debtor_name']); ?></label>
                                            <textarea name="reply_content" class="form-control rounded-3" rows="4" placeholder="Type your response or instructions here..."><?php echo $row['admin_reply']; ?></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" name="send_reply" class="btn btn-success fw-800 px-5 rounded-pill">
                                                TRANSMIT DIRECTIVE <i class="fas fa-paper-plane ms-1"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="card p-5 text-center border-0 shadow-sm rounded-4">
                            <i class="fas fa-inbox fa-3x mb-3 text-muted opacity-25"></i>
                            <h4 class="fw-800 text-dark">Inbox is Empty</h4>
                            <p class="text-muted fw-600">No reports have been submitted by debtors yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function toggleReply(id) {
            $(`#${id}`).slideToggle();
        }

        function confirmPurge(id, title) {
            Swal.fire({
                title: 'Hide Report?',
                html: `Are you sure you want to remove this report from your view?<br><br><small class='text-muted'>Note: The debtor will still see this report in their history unless they delete it too.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Hide it!',
                background: '#fff',
                borderRadius: '24px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `manage-reports.php?del=${id}&token=<?php echo generate_csrf_token(); ?>`;
                }
            });
        }
    </script>

    <?php if($success): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Transmitted', text: '<?php echo $success; ?>' });
    </script>
    <?php endif; ?>
</body>
</html>
