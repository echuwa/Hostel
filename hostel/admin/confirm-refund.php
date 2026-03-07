<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header("Location: dashboard.php"); exit();
}

$admin_id = $_SESSION['id'];
$refund_id = intval($_GET['id'] ?? 0);
$url_token  = trim($_GET['token'] ?? '');

// ─── VALIDATE TOKEN IN URL ─────────────────────────────────────────
$refund = null;
$student = null;

if($refund_id && $url_token) {
    $q = $mysqli->prepare("SELECT rr.*, ur.firstName, ur.lastName, ur.regNo, ur.wallet_balance 
                           FROM refund_requests rr
                           JOIN userregistration ur ON rr.user_id = ur.id
                           WHERE rr.id = ? AND rr.status = 'Pending' AND rr.token_expires > NOW()");
    $q->bind_param('i', $refund_id);
    $q->execute();
    $refund = $q->get_result()->fetch_object();
    if($refund && !hash_equals($refund->confirm_token, $url_token)) $refund = null;
}

// ─── PROCESS CONFIRMATION ─────────────────────────────────────────
if(isset($_POST['confirm_refund']) && $refund) {
    $entered = trim($_POST['confirm_token'] ?? '');
    if(!hash_equals($refund->confirm_token, $entered)) {
        $_SESSION['error_msg'] = "Invalid confirmation token. Please copy it exactly.";
        header("Location: confirm-refund.php?id={$refund_id}&token={$url_token}"); exit();
    }

    $mysqli->begin_transaction();
    try {
        $prev_bal = $refund->wallet_balance;
        $new_bal  = $prev_bal + $refund->amount;

        // Credit wallet
        $upd = $mysqli->prepare("UPDATE userregistration SET wallet_balance = ? WHERE id = ?");
        $upd->bind_param('di', $new_bal, $refund->user_id);
        $upd->execute();

        // Reverse fee payment if needed
        if($refund->reverse_fee && !empty($refund->fee_type)) {
            $col_map = ['Fees'=>'fees_paid','Accommodation'=>'accommodation_paid','Registration'=>'registration_paid'];
            $col = $col_map[$refund->fee_type] ?? null;
            if($col) {
                $rvs = $mysqli->prepare("UPDATE userregistration SET {$col} = GREATEST(0, {$col} - ?), payment_status='Partially Paid' WHERE id = ?");
                $rvs->bind_param('di', $refund->amount, $refund->user_id);
                $rvs->execute();
            }
        }

        // Log wallet transaction
        $ref  = "REF-" . time() . "-" . strtoupper(substr(md5($refund_id), 0, 6));
        $desc = "SuperAdmin Refund. Reason: " . substr($refund->reason, 0, 100);
        $log  = $mysqli->prepare("INSERT INTO wallet_transactions (user_id, transaction_type, amount, prev_balance, new_balance, description, reference_no, status) VALUES (?, 'Deposit', ?, ?, ?, ?, ?, 'Completed')");
        $log->bind_param('idddss', $refund->user_id, $refund->amount, $prev_bal, $new_bal, $desc, $ref);
        $log->execute();

        // Update refund status
        $upd_r = $mysqli->prepare("UPDATE refund_requests SET status='Completed', confirmed_at=NOW(), completed_at=NOW() WHERE id=?");
        $upd_r->bind_param('i', $refund_id);
        $upd_r->execute();

        $mysqli->commit();
        $_SESSION['success_msg'] = "✅ Refund of TSH " . number_format($refund->amount) . " completed! Wallet credited for {$refund->firstName} {$refund->lastName}.";
        header("Location: wallet-management.php"); exit();
    } catch(Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "❌ " . $e->getMessage();
        header("Location: confirm-refund.php?id={$refund_id}&token={$url_token}"); exit();
    }
}

// ─── CANCEL / REJECT REFUND ───────────────────────────────────────
if(isset($_GET['cancel']) && $refund_id) {
    $mysqli->prepare("UPDATE refund_requests SET status='Rejected' WHERE id=? AND status='Pending'")->execute() ;
    $_SESSION['success_msg'] = "Refund request cancelled.";
    header("Location: wallet-management.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm Refund | HostelMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .confirm-card { background: #fff; border-radius: 24px; box-shadow: 0 30px 80px rgba(0,0,0,0.4); max-width: 520px; width: 100%; overflow: hidden; }
        .card-top { background: linear-gradient(135deg, #4361ee, #7b2ff7); padding: 36px; text-align: center; color: white; }
        .token-display { background: #f8fafc; border: 2px dashed #c7d2fe; border-radius: 12px; padding: 16px; font-family: monospace; font-size: 0.85rem; font-weight: 800; color: #4361ee; letter-spacing: 1px; word-break: break-all; cursor: pointer; transition: 0.2s; }
        .token-display:hover { background: #eef2ff; }
        .amount-badge { font-size: 2.4rem; font-weight: 800; }
        .timer-bar { height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
        .timer-fill { height: 100%; background: linear-gradient(90deg, #10b981, #4ade80); border-radius: 2px; transition: width 1s linear; }
    </style>
</head>
<body>
<div class="confirm-card">
    <?php if($refund): ?>
    <!-- Header -->
    <div class="card-top">
        <div class="bg-white bg-opacity-20 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
            <i class="fas fa-shield-check fa-2x"></i>
        </div>
        <h4 class="fw-800 mb-1">🔐 Confirm Refund</h4>
        <p class="mb-0 opacity-75 small">Two-Step Authorization Required</p>
    </div>

    <!-- Timer Bar -->
    <div class="timer-bar"><div class="timer-fill" id="timerFill" style="width:100%;"></div></div>

    <div class="p-4">
        <!-- Info Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="bg-light rounded-3 p-3">
                    <div class="small fw-700 text-muted mb-1 text-uppercase">Student</div>
                    <div class="fw-800 text-dark"><?= htmlspecialchars($refund->firstName . ' ' . $refund->lastName) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($refund->regNo) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="bg-success bg-opacity-10 rounded-3 p-3 text-center">
                    <div class="small fw-700 text-muted mb-1 text-uppercase">Refund Amount</div>
                    <div class="fw-800 text-success" style="font-size:1.3rem;">TSH <?= number_format($refund->amount) ?></div>
                </div>
            </div>
        </div>

        <div class="bg-light rounded-3 p-3 mb-4">
            <div class="small fw-700 text-muted mb-1 text-uppercase">Reason for Refund</div>
            <div class="fw-700 text-dark"><?= htmlspecialchars($refund->reason) ?></div>
            <?php if($refund->payment_ref): ?>
            <div class="small text-muted mt-1">Original Ref: <code><?= htmlspecialchars($refund->payment_ref) ?></code></div>
            <?php endif; ?>
        </div>

        <!-- Token Display (copy-able) -->
        <div class="mb-4">
            <label class="form-label small fw-800 text-muted text-uppercase mb-2">
                <i class="fas fa-key me-1 text-warning"></i> Your Authorization Token (click to copy)
            </label>
            <div class="token-display" id="tokenDisplay" onclick="copyToken()" title="Click to copy">
                <?= htmlspecialchars($url_token) ?>
            </div>
            <div class="form-text text-center mt-1" id="copyMsg"></div>
        </div>

        <!-- Expiry countdown -->
        <div class="alert alert-warning rounded-3 small fw-700 mb-4 d-flex align-items-center gap-2">
            <i class="fas fa-clock"></i>
            Token expires: <strong id="expiryCountdown"></strong>
        </div>

        <!-- Confirmation Form -->
        <form method="post">
            <div class="mb-4">
                <label class="form-label fw-800">Enter Token to Authorize</label>
                <input type="text" name="confirm_token" class="form-control form-control-lg rounded-3 fw-700"
                       placeholder="Paste or type the token above" required autocomplete="off"
                       style="letter-spacing: 0.5px; font-family: monospace;">
                <div class="form-text">Copy the token above and paste it here to confirm.</div>
            </div>
            <input type="hidden" name="refund_id" value="<?= $refund_id ?>">
            <div class="d-grid gap-2">
                <button type="submit" name="confirm_refund" class="btn btn-success btn-lg rounded-pill fw-800 py-3">
                    <i class="fas fa-check-circle me-2"></i> AUTHORIZE REFUND
                </button>
                <a href="confirm-refund.php?cancel=1&id=<?= $refund_id ?>" class="btn btn-outline-danger rounded-pill fw-700"
                   onclick="return confirm('Cancel this refund request?')">
                    <i class="fas fa-ban me-2"></i> Cancel Refund
                </a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- Invalid/Expired state -->
    <div class="card-top" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
        <h4 class="fw-800">Invalid or Expired</h4>
        <p class="opacity-75 mb-0">This refund request has expired or been processed.</p>
    </div>
    <div class="p-4 text-center">
        <p class="text-muted fw-600">Tokens are valid for 30 minutes only. Please initiate a new refund if needed.</p>
        <a href="wallet-management.php" class="btn btn-primary rounded-pill px-5 fw-800">
            <i class="fas fa-arrow-left me-2"></i> Back to Wallet Management
        </a>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyToken() {
    const text = document.getElementById('tokenDisplay').innerText.trim();
    navigator.clipboard.writeText(text).then(() => {
        document.getElementById('copyMsg').innerHTML = '<span class="text-success fw-700">✔ Token copied!</span>';
        setTimeout(() => document.getElementById('copyMsg').innerHTML = '', 2000);
    });
}

// Expiry countdown (30 min from page load as proxy)
<?php if($refund): ?>
const expiry = new Date('<?= date('Y-m-d H:i:s', strtotime($refund->token_expires)) ?>').getTime();
function updateCountdown() {
    const now = Date.now();
    const left = Math.max(0, expiry - now);
    const m = Math.floor(left / 60000);
    const s = Math.floor((left % 60000) / 1000);
    document.getElementById('expiryCountdown').textContent = m + 'm ' + s + 's remaining';
    const pct = (left / (30 * 60 * 1000)) * 100;
    document.getElementById('timerFill').style.width = Math.max(0, pct) + '%';
    if(left <= 0) {
        document.getElementById('expiryCountdown').textContent = 'EXPIRED';
        document.getElementById('timerFill').style.background = '#ef4444';
    }
}
setInterval(updateCountdown, 1000);
updateCountdown();
<?php endif; ?>
</script>

<?php if(isset($_SESSION['error_msg'])): ?>
<script>
Swal.fire({icon:'error',title:'Token Error',text:'<?= addslashes($_SESSION['error_msg']) ?>'});
</script>
<?php unset($_SESSION['error_msg']); endif; ?>
</body>
</html>
