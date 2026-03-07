<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// SuperAdmin only
if(!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header("Location: dashboard.php"); exit();
}

$admin_id = $_SESSION['id'];

// ─── APPROVE DEPOSIT ───────────────────────────────────────────────
if(isset($_GET['approve'])) {
    $req_id = intval($_GET['approve']);
    $mysqli->begin_transaction();
    try {
        $q = $mysqli->prepare("SELECT * FROM deposit_requests WHERE id = ? AND status = 'Pending'");
        $q->bind_param('i', $req_id);
        $q->execute();
        $req = $q->get_result()->fetch_object();
        if(!$req) throw new Exception("Request not found or already processed.");

        $qb = $mysqli->prepare("SELECT wallet_balance FROM userregistration WHERE id = ?");
        $qb->bind_param('i', $req->user_id);
        $qb->execute();
        $prev_bal = $qb->get_result()->fetch_object()->wallet_balance;
        $new_bal  = $prev_bal + $req->amount;

        $upd = $mysqli->prepare("UPDATE userregistration SET wallet_balance = ? WHERE id = ?");
        $upd->bind_param('di', $new_bal, $req->user_id);
        $upd->execute();

        $ref  = "DEP-APPR-" . time() . "-" . strtoupper(substr(md5($req_id), 0, 5));
        $desc = "Deposit Approved: {$req->payment_method} | Ref: {$req->reference_no}";
        $log  = $mysqli->prepare("INSERT INTO wallet_transactions (user_id, transaction_type, amount, prev_balance, new_balance, description, reference_no, status) VALUES (?, 'Deposit', ?, ?, ?, ?, ?, 'Completed')");
        $log->bind_param('idddss', $req->user_id, $req->amount, $prev_bal, $new_bal, $desc, $ref);
        $log->execute();

        $upd_req = $mysqli->prepare("UPDATE deposit_requests SET status='Approved', admin_id=?, reviewed_at=NOW() WHERE id=?");
        $upd_req->bind_param('ii', $admin_id, $req_id);
        $upd_req->execute();

        $mysqli->commit();
        $_SESSION['success_msg'] = "✅ Deposit of TSH " . number_format($req->amount) . " approved & credited to student's wallet.";
    } catch(Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "❌ " . $e->getMessage();
    }
    header("Location: deposit-requests.php"); exit();
}

// ─── REJECT DEPOSIT ────────────────────────────────────────────────
if(isset($_GET['reject'])) {
    $req_id  = intval($_GET['reject']);
    $note    = trim($_GET['note'] ?? 'Proof could not be verified.');
    $upd_req = $mysqli->prepare("UPDATE deposit_requests SET status='Rejected', admin_id=?, admin_note=?, reviewed_at=NOW() WHERE id=? AND status='Pending'");
    $upd_req->bind_param('isi', $admin_id, $note, $req_id);
    $upd_req->execute();
    if($upd_req->affected_rows > 0) {
        $_SESSION['success_msg'] = "Request rejected. Student will need to resubmit.";
    } else {
        $_SESSION['error_msg'] = "Request not found or already processed.";
    }
    header("Location: deposit-requests.php"); exit();
}

// ─── FETCH DATA ────────────────────────────────────────────────────
$pending_q = $mysqli->query("
    SELECT dr.*, ur.firstName, ur.lastName, ur.regNo, ur.contactNo
    FROM deposit_requests dr
    JOIN userregistration ur ON dr.user_id = ur.id
    WHERE dr.status = 'Pending'
    ORDER BY dr.requested_at ASC
");

$history_q = $mysqli->query("
    SELECT dr.*, ur.firstName, ur.lastName, ur.regNo, a.username AS reviewed_by
    FROM deposit_requests dr
    JOIN userregistration ur ON dr.user_id = ur.id
    LEFT JOIN admins a ON dr.admin_id = a.id
    WHERE dr.status != 'Pending'
    ORDER BY dr.reviewed_at DESC
    LIMIT 30
");

$stats_pending = $mysqli->query("SELECT COUNT(*) FROM deposit_requests WHERE status='Pending'")->fetch_row()[0] ?? 0;
$stats_approved_total = $mysqli->query("SELECT COALESCE(SUM(amount),0) FROM deposit_requests WHERE status='Approved'")->fetch_row()[0] ?? 0;
$stats_rejected = $mysqli->query("SELECT COUNT(*) FROM deposit_requests WHERE status='Rejected'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deposit Requests | HostelMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-modern.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .proof-thumb {
            width: 60px; height: 60px; object-fit: cover;
            border-radius: 10px; border: 2px solid #e2e8f0;
            cursor: pointer; transition: transform 0.2s;
        }
        .proof-thumb:hover { transform: scale(1.1); }
        .proof-pdf { width: 60px; height: 60px; background: #fee2e2; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #dc2626; font-size: 1.4rem; cursor: pointer; border: 2px solid #fecaca; }
        .table-modern thead th {
            background: #f8fafc; text-transform: uppercase; font-size: 0.68rem;
            letter-spacing: 1px; font-weight: 800; color: #64748b; padding: 18px; border: none;
        }
        .table-modern tbody td { padding: 18px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .pill-pending  { background: #fef3c7; color: #92400e; }
        .pill-approved { background: #d1fae5; color: #065f46; }
        .pill-rejected { background: #fee2e2; color: #991b1b; }
        .stat-card { border-radius: 20px; border: none; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-4px); }
        .empty-state { padding: 60px 20px; text-align: center; color: #94a3b8; }
    </style>
</head>
<body>
<div class="app-container">
    <?php include('includes/sidebar_modern.php'); ?>
    <div class="main-content">
        <?php include('includes/header.php'); ?>
        <div class="content-wrapper">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-800 mb-1">Deposit Verification Center</h2>
                    <p class="text-muted fw-600 mb-0">Review student payment proofs and credit wallets upon confirmation.</p>
                </div>
                <a href="wallet-management.php" class="btn btn-outline-secondary rounded-pill fw-800 px-4">
                    <i class="fas fa-arrow-left me-2"></i> Wallet Overview
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="background: linear-gradient(135deg,#f59e0b,#fbbf24); color:white;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-700 opacity-75 text-uppercase mb-1">Awaiting Review</div>
                                <div class="h2 fw-800 mb-0"><?= $stats_pending ?></div>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="background: linear-gradient(135deg,#10b981,#34d399); color:white;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-700 opacity-75 text-uppercase mb-1">Total Verified (TSH)</div>
                                <div class="h2 fw-800 mb-0"><?= number_format($stats_approved_total) ?></div>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                <i class="fas fa-check-double fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="background: linear-gradient(135deg,#ef4444,#f87171); color:white;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-700 opacity-75 text-uppercase mb-1">Rejected</div>
                                <div class="h2 fw-800 mb-0"><?= $stats_rejected ?></div>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Section -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-800 mb-0">
                        <i class="fas fa-inbox text-warning me-2"></i> Pending Verification
                    </h5>
                    <?php if($stats_pending > 0): ?>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-800">
                        <?= $stats_pending ?> Require Action
                    </span>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Proof</th>
                                <th>Requested</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($pending_q->num_rows > 0): while($row = $pending_q->fetch_object()): ?>
                        <tr>
                            <td>
                                <div class="fw-800 text-dark"><?= htmlspecialchars($row->firstName . ' ' . $row->lastName) ?></div>
                                <div class="small text-muted fw-600"><?= htmlspecialchars($row->regNo) ?></div>
                            </td>
                            <td class="fw-800 text-success" style="font-size: 1.1rem;">
                                TSH <?= number_format($row->amount) ?>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-700">
                                    <i class="fas fa-mobile-alt me-1"></i><?= htmlspecialchars($row->payment_method) ?>
                                </span>
                            </td>
                            <td><code class="fw-800"><?= htmlspecialchars($row->reference_no) ?></code></td>
                            <td>
                                <?php
                                $proof_path = '/tupo_kazin/Hostel-Management-Syste-Updated-Code/hostel/uploads/deposit_proofs/' . $row->proof_file;
                                $is_pdf = strtolower(pathinfo($row->proof_file, PATHINFO_EXTENSION)) === 'pdf';
                                ?>
                                <?php if($is_pdf): ?>
                                <div class="proof-pdf" onclick="window.open('<?= $proof_path ?>', '_blank')" title="Open PDF Proof">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <?php else: ?>
                                <img src="<?= $proof_path ?>" class="proof-thumb"
                                     onclick="viewProof('<?= $proof_path ?>')"
                                     title="Click to enlarge" alt="Payment Proof"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect width=\'60\' height=\'60\' fill=\'%23f1f5f9\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-size=\'22\' text-anchor=\'middle\' dominant-baseline=\'middle\' fill=\'%2394a3b8\'%3E🖼%3C/text%3E%3C/svg%3E'">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-700"><?= date('d M Y', strtotime($row->requested_at)) ?></div>
                                <div class="small text-muted"><?= date('h:i A', strtotime($row->requested_at)) ?></div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button onclick="confirmApprove(<?= $row->id ?>, '<?= number_format($row->amount) ?>', '<?= htmlspecialchars($row->firstName.' '.$row->lastName) ?>')"
                                        class="btn btn-success btn-sm rounded-pill px-3 fw-800">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                    <button onclick="confirmReject(<?= $row->id ?>)"
                                        class="btn btn-danger btn-sm rounded-pill px-3 fw-800">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                                    <p class="fw-700 mb-0">All clear! No pending deposit requests.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- History Section -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 p-4">
                    <h5 class="fw-800 mb-0">
                        <i class="fas fa-history text-primary me-2"></i> Review History (Last 30)
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Reviewed By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($history_q->num_rows > 0): while($h = $history_q->fetch_object()):
                            $pill = ($h->status == 'Approved') ? 'pill-approved' : 'pill-rejected';
                        ?>
                        <tr>
                            <td>
                                <div class="small fw-700"><?= date('d M Y', strtotime($h->reviewed_at)) ?></div>
                                <div class="small text-muted"><?= date('h:i A', strtotime($h->reviewed_at)) ?></div>
                            </td>
                            <td>
                                <div class="fw-800"><?= htmlspecialchars($h->firstName . ' ' . $h->lastName) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($h->regNo) ?></div>
                            </td>
                            <td class="fw-800">TSH <?= number_format($h->amount) ?></td>
                            <td><span class="badge rounded-pill bg-light text-dark fw-700"><?= htmlspecialchars($h->payment_method) ?></span></td>
                            <td><code><?= htmlspecialchars($h->reference_no) ?></code></td>
                            <td class="small fw-700 text-muted"><?= htmlspecialchars($h->reviewed_by ?? '—') ?></td>
                            <td><span class="status-pill <?= $pill ?>"><?= $h->status ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No history yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /content-wrapper -->
    </div><!-- /main-content -->
</div>

<!-- Proof Lightbox -->
<div id="proofLightbox" onclick="this.style.display='none'"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; cursor:zoom-out;
            display:none; align-items:center; justify-content:center;">
    <img id="proofImg" src="" style="max-width:90%; max-height:90%; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewProof(src) {
    const lb = document.getElementById('proofLightbox');
    document.getElementById('proofImg').src = src;
    lb.style.display = 'flex';
}

function confirmApprove(id, amount, name) {
    Swal.fire({
        title: 'Verify & Credit Wallet?',
        html: `Confirm that you have <strong>verified the proof</strong> and approve crediting <strong>TSH ${amount}</strong> to <strong>${name}</strong>'s wallet.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="fas fa-check me-1"></i> Yes, Approve & Credit',
        cancelButtonText: 'Cancel',
        customClass: { confirmButton: 'fw-800', cancelButton: 'fw-800' }
    }).then(r => { if(r.isConfirmed) window.location.href = `deposit-requests.php?approve=${id}`; });
}

function confirmReject(id) {
    Swal.fire({
        title: 'Reject This Request?',
        html: '<p class="text-muted small">Please provide a reason for rejection so the student can resubmit correctly.</p><textarea id="reject-note" class="form-control mt-2 rounded-3" rows="2" placeholder="e.g. Proof image is unclear, reference number mismatch..."></textarea>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Reject Request',
        preConfirm: () => document.getElementById('reject-note').value || 'Proof could not be verified.'
    }).then(r => {
        if(r.isConfirmed) {
            window.location.href = `deposit-requests.php?reject=${id}&note=${encodeURIComponent(r.value)}`;
        }
    });
}
</script>

<?php if(isset($_SESSION['success_msg'])): ?>
<script>Swal.fire({icon:'success',title:'Done!',text:'<?= addslashes($_SESSION['success_msg']) ?>',timer:3000,showConfirmButton:false,toast:true,position:'top-end'});</script>
<?php unset($_SESSION['success_msg']); endif; ?>

<?php if(isset($_SESSION['error_msg'])): ?>
<script>Swal.fire({icon:'error',title:'Error',text:'<?= addslashes($_SESSION['error_msg']) ?>'});</script>
<?php unset($_SESSION['error_msg']); endif; ?>

</body>
</html>
