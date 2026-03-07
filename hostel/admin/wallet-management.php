<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Restricted to Super Admin
if(!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

// 1. Process Approval
if(isset($_GET['approve'])) {
    $tid = intval($_GET['approve']);
    
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("UPDATE wallet_transactions SET status = 'Completed' WHERE id = ? AND transaction_type = 'Withdrawal' AND status = 'Pending'");
        $stmt->bind_param('i', $tid);
        $stmt->execute();
        
        if($stmt->affected_rows > 0) {
            $_SESSION['success_msg'] = "Withdrawal request approved and marked as completed.";
            $mysqli->commit();
        } else {
            throw new Exception("Transaction not found or already processed.");
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = $e->getMessage();
    }
    header("Location: wallet-management.php");
    exit();
}

// 2. Process Rejection (Refund)
if(isset($_GET['reject'])) {
    $tid = intval($_GET['reject']);
    
    $mysqli->begin_transaction();
    try {
        $q = $mysqli->prepare("SELECT user_id, amount FROM wallet_transactions WHERE id = ? AND transaction_type = 'Withdrawal' AND status = 'Pending'");
        $q->bind_param('i', $tid);
        $q->execute();
        $res = $q->get_result();
        
        if($row = $res->fetch_object()) {
            $user_id = $row->user_id;
            $amount = $row->amount;
            
            // Refund the user
            $upd = $mysqli->prepare("UPDATE userregistration SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $upd->bind_param('di', $amount, $user_id);
            $upd->execute();
            
            // Mark transaction as Cancelled
            $stmt = $mysqli->prepare("UPDATE wallet_transactions SET status = 'Cancelled', description = CONCAT(description, ' - Rejected by Admin') WHERE id = ?");
            $stmt->bind_param('i', $tid);
            $stmt->execute();
            
            $mysqli->commit();
            $_SESSION['success_msg'] = "Withdrawal rejected. Funds have been refunded to the student's wallet.";
        } else {
            throw new Exception("Transaction not found or already processed.");
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = $e->getMessage();
    }
    header("Location: wallet-management.php");
    exit();
}

// Fetch Global Stats
$total_balance = $mysqli->query("SELECT SUM(wallet_balance) FROM userregistration")->fetch_row()[0] ?? 0;
$total_deposits = $mysqli->query("SELECT SUM(amount) FROM wallet_transactions WHERE transaction_type='Deposit'")->fetch_row()[0] ?? 0;
$total_payouts = $mysqli->query("SELECT SUM(amount) FROM wallet_transactions WHERE transaction_type='Withdrawal' AND status='Completed'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Wallet Management | HostelMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-modern.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .finance-card {
            border-radius: 24px; border: none; overflow: hidden;
            transition: transform 0.3s;
        }
        .finance-card:hover { transform: translateY(-5px); }
        .table-modern thead th {
            background: #f8fafc; text-transform: uppercase; font-size: 0.7rem;
            letter-spacing: 1px; font-weight: 800; color: #64748b;
            padding: 20px; border: none;
        }
        .table-modern tbody td { padding: 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; }
        .bg-pending { background: #fef3c7; color: #92400e; }
        .bg-completed { background: #d1fae5; color: #065f46; }
        .bg-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include('includes/sidebar_modern.php'); ?>
        <div class="main-content">
            <?php include('includes/header.php'); ?>
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Financial Operations</h2>
                        <p class="text-muted fw-600 mb-0">Oversee wallet ecosystems, monitor flow, and authorize payouts.</p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card finance-card shadow-sm" style="background: linear-gradient(135deg, #4361ee, #4895ef); color: white;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                        <i class="fas fa-vault fa-2x"></i>
                                    </div>
                                    <div class="text-end">
                                        <div class="small fw-700 opacity-75">SYSTEM LIABILITY</div>
                                        <div class="h3 fw-800 mb-0">TSH <?php echo number_format($total_balance); ?></div>
                                    </div>
                                </div>
                                <p class="mb-0 small fw-600 opacity-75">Total funds currently held in student wallets.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card finance-card shadow-sm" style="background: linear-gradient(135deg, #10b981, #34d399); color: white;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                        <i class="fas fa-arrow-trend-up fa-2x"></i>
                                    </div>
                                    <div class="text-end">
                                        <div class="small fw-700 opacity-75">ALL-TIME DEPOSITS</div>
                                        <div class="h3 fw-800 mb-0">TSH <?php echo number_format($total_deposits); ?></div>
                                    </div>
                                </div>
                                <p class="mb-0 small fw-600 opacity-75">Total volume of funds loaded into the system.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card finance-card shadow-sm" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                        <i class="fas fa-hand-holding-dollar fa-2x"></i>
                                    </div>
                                    <div class="text-end">
                                        <div class="small fw-700 opacity-75">TOTAL PAYOUTS</div>
                                        <div class="h3 fw-800 mb-0">TSH <?php echo number_format($total_payouts); ?></div>
                                    </div>
                                </div>
                                <p class="mb-0 small fw-600 opacity-75">Processed withdrawals successfully sent to students.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payout Requests -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-800 text-dark mb-0"><i class="fas fa-clock text-warning me-2"></i> Pending Payout Requests</h5>
                        <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill fw-800 small">Requires Authority</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Requested On</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $p_q = $mysqli->query("SELECT wt.*, ur.firstName, ur.lastName, ur.regNo FROM wallet_transactions wt JOIN userregistration ur ON wt.user_id = ur.id WHERE wt.transaction_type='Withdrawal' AND wt.status='Pending' ORDER BY wt.created_at ASC");
                                if($p_q->num_rows > 0):
                                    while($row = $p_q->fetch_object()):
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-800 text-dark"><?php echo $row->firstName . ' ' . $row->lastName; ?></div>
                                        <div class="small text-muted fw-600"><?php echo $row->regNo; ?></div>
                                    </td>
                                    <td><code class="fw-800"><?php echo $row->reference_no; ?></code></td>
                                    <td class="fw-800 text-primary">TSH <?php echo number_format($row->amount); ?></td>
                                    <td>
                                        <div class="small fw-700 text-dark"><?php echo date('d M Y', strtotime($row->created_at)); ?></div>
                                        <div class="small text-muted"><?php echo date('h:i A', strtotime($row->created_at)); ?></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button onclick="confirmApproval(<?php echo $row->id; ?>, '<?php echo number_format($row->amount); ?>')" class="btn btn-success btn-sm rounded-pill px-3 fw-800">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                            <button onclick="confirmReject(<?php echo $row->id; ?>)" class="btn btn-danger btn-sm rounded-pill px-3 fw-800">
                                                <i class="fas fa-times me-1"></i> Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted fw-700">No pending withdrawal requests.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Comprehensive Log -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-800 text-dark mb-0"><i class="fas fa-list-check text-primary me-2"></i> System Wallet Audit Log</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $log_q = $mysqli->query("SELECT wt.*, ur.firstName, ur.lastName FROM wallet_transactions wt JOIN userregistration ur ON wt.user_id = ur.id ORDER BY wt.created_at DESC LIMIT 50");
                                while($log = $log_q->fetch_object()):
                                    $s_class = 'bg-' . strtolower($log->status);
                                    $t_color = ($log->transaction_type == 'Deposit') ? 'text-success' : (($log->transaction_type == 'Withdrawal') ? 'text-warning' : 'text-danger');
                                ?>
                                <tr>
                                    <td>
                                        <div class="small fw-700 text-dark"><?php echo date('d M Y', strtotime($log->created_at)); ?></div>
                                    </td>
                                    <td class="small fw-800"><?php echo $log->firstName . ' ' . $log->lastName; ?></td>
                                    <td><span class="badge rounded-pill bg-light text-dark px-3 py-1 fw-700"><?php echo $log->transaction_type; ?></span></td>
                                    <td class="fw-800 <?php echo $t_color; ?>">TSH <?php echo number_format($log->amount); ?></td>
                                    <td><code class="small fw-700"><?php echo $log->reference_no; ?></code></td>
                                    <td><span class="status-pill <?php echo $s_class; ?>"><?php echo $log->status; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function confirmApproval(id, amt) {
        Swal.fire({
            title: 'Authorize Payout?',
            text: `Confirm that you have sent TSH ${amt} to the student. This action will mark the request as completed.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, Authorize'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `wallet-management.php?approve=${id}`;
            }
        });
    }

    function confirmReject(id) {
        Swal.fire({
            title: 'Reject Request?',
            text: 'This will cancel the payout and REFUND the amount back to the student\'s wallet.',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Reject & Refund'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `wallet-management.php?reject=${id}`;
            }
        });
    }
    </script>

    <?php if(isset($_SESSION['success_msg'])): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Action Secured', text: '<?php echo $_SESSION['success_msg']; ?>', timer: 3000, showConfirmButton: false, position: 'top-end', toast: true });
    </script>
    <?php unset($_SESSION['success_msg']); endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
    <script>
        Swal.fire({ icon: 'error', title: 'Operation Failed', text: '<?php echo $_SESSION['error_msg']; ?>' });
    </script>
    <?php unset($_SESSION['error_msg']); endif; ?>
</body>
</html>
