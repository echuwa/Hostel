<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];

// Define Constants for Fee Amounts
define('FEES_TOTAL', 1500000);
define('FEES_HALF', 750000);
define('ACC_TOTAL', 178500);
define('REG_TOTAL', 50000);

// Simulation Logic: When a student "pays"
if(isset($_POST['simulate_payment'])) {
    $type = $_POST['payment_type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $ctrl = $_POST['control_no'] ?? '';
    
    // Fetch current user data for amount validation
    $q_data = $mysqli->prepare("SELECT fees_paid, accommodation_paid, registration_paid, regNo FROM userregistration WHERE id = ?");
    $q_data->bind_param('i', $user_id);
    $q_data->execute();
    $curr = $q_data->get_result()->fetch_object();
    $q_data->close();

    $error = null;
    $valid_amounts = [];

    // ENFORCE STRICT PAYMENT RULES
    // Only fetch valid limits for validation logic.
    if ($type === 'Fees') {
        $remaining = FEES_TOTAL - $curr->fees_paid;
        if ($remaining <= 0) {
            $error = "Fees are already fully paid.";
        } elseif ($amount > $remaining) {
           $error = "Amount exceeds the remaining tuition fee balance.";
        } elseif ($amount != FEES_HALF && $amount != FEES_TOTAL && $amount != $remaining) {
            $error = "For School Fees, you must pay in chunks of exactly 50% (TSH 750,000) or complete your balance.";
        }
    } elseif ($type === 'Accommodation') {
        $remaining = ACC_TOTAL - $curr->accommodation_paid;
        if ($remaining <= 0) {
            $error = "Accommodation is already fully paid.";
        } elseif ($amount > $remaining) {
            $error = "Amount exceeds the remaining accommodation balance.";
        } elseif ($amount != $remaining && $amount != ACC_TOTAL) {
            $error = "Accommodation must be paid in full (TSH " . number_format(ACC_TOTAL) . ").";
        }
    } elseif ($type === 'Registration') {
        $remaining = REG_TOTAL - $curr->registration_paid;
        if ($remaining <= 0) {
            $error = "Registration is already fully paid.";
        } elseif ($amount > $remaining) {
            $error = "Amount exceeds the remaining registration balance.";
        } elseif ($amount != $remaining && $amount != REG_TOTAL) {
            $error = "Registration must be paid in full (TSH " . number_format(REG_TOTAL) . ").";
        }
    } else {
        $error = "Invalid payment type.";
    }

    if (!$error && $amount > 0) {
        // Map types to columns
        $col_map = [
            'Fees' => 'fees_paid',
            'Accommodation' => 'accommodation_paid',
            'Registration' => 'registration_paid'
        ];
        
        $col = $col_map[$type] ?? '';
        
        if ($col) {
            $mysqli->begin_transaction();
            try {
                // Update the paid amount
                $update_query = "UPDATE userregistration SET $col = $col + ? WHERE id = ?";
                $stmt = $mysqli->prepare($update_query);
                $stmt->bind_param('di', $amount, $user_id);
                $stmt->execute();
                $stmt->close();
                
                // Fetch updated amounts to re-evaluate fee_status
                $q = "SELECT regNo, fees_paid, accommodation_paid, registration_paid FROM userregistration WHERE id = ?";
                $st = $mysqli->prepare($q);
                $st->bind_param('i', $user_id);
                $st->execute();
                $res = $st->get_result()->fetch_object();
                $regNo = $res->regNo;
                $st->close();
                
                // 1 if met threshold (at least 50% fees + 100% acc + 100% reg), else 0
                $new_fee_status = ($res->fees_paid >= FEES_HALF && $res->accommodation_paid >= ACC_TOTAL && $res->registration_paid >= REG_TOTAL) ? 1 : 0;
                
                // Update payment_status text
                $payment_status = "Partially Paid";
                if ($res->fees_paid >= FEES_TOTAL && $res->accommodation_paid >= ACC_TOTAL && $res->registration_paid >= REG_TOTAL) {
                    $payment_status = "Fully Paid";
                }

                $upd = $mysqli->prepare("UPDATE userregistration SET fee_status = ?, payment_status = ? WHERE id = ?");
                $upd->bind_param('isi', $new_fee_status, $payment_status, $user_id);
                $upd->execute();
                $upd->close();
                
                // Log the payment with a verification hash for proof
                $log = $mysqli->prepare("INSERT INTO payment_logs (regNo, control_no, amount, payment_type, transaction_id, status) VALUES (?, ?, ?, ?, ?, 'Verified')");
                $trans_id = "TRANS-" . date('Ymd') . "-" . strtoupper(substr(md5($regNo . time() . $amount), 0, 8));
                $log->bind_param('ssdss', $regNo, $ctrl, $amount, $type, $trans_id);
                $log->execute();
                $log->close();
                
                $mysqli->commit();
                $_SESSION['success'] = "Payment verified! Receipt for TSH " . number_format($amount) . " issued for $type. Transaction Ref: $trans_id";
            } catch (Exception $e) {
                $mysqli->rollback();
                $_SESSION['error'] = "Critical error processing payment: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = $error ?? "Invalid payment amount or transaction cancelled.";
    }
    header("Location: pay-fees.php");
    exit();
}

// Fetch current user data
$query = "SELECT * FROM userregistration WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_object();
$stmt->close();

// Fallback for control numbers if they are NULL (old students)
if (empty($user->fee_control_no)) {
    function generateControlNumber() {
        return "99" . rand(10, 99) . date('md') . rand(100, 999) . rand(1000, 9999);
    }
    $f = generateControlNumber();
    $a = generateControlNumber();
    $r = generateControlNumber();
    $mysqli->query("UPDATE userregistration SET fee_control_no='$f', acc_control_no='$a', reg_control_no='$r' WHERE id=$user_id");
    // Refresh
    header("Location: pay-fees.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Hostel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-modern.css">
    <style>
        .ctrl-box {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ctrl-box:hover {
            border-color: var(--primary);
            background: #f0f7ff;
            transform: scale(1.02);
        }
        .ctrl-box code {
            font-size: 1.4rem;
            color: var(--primary);
            font-weight: 800;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 transition-all">
                    <div class="animate__animated animate__fadeInLeft">
                        <h2 class="section-title">Payments & Control Numbers</h2>
                        <p class="section-subtitle">Manage your dues and simulate GePG payments</p>
                    </div>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeInDown">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeInDown">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Requirements Banner -->
                <div class="card-modern border-0 mb-4 overflow-hidden" style="background: white;">
                    <div class="d-flex align-items-center p-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4">
                            <i class="fas fa-info-circle fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-800 mb-1">Room Allocation Requirements</h5>
                            <p class="mb-0 text-muted">A total of <strong>50% Fees (750k)</strong>, <strong>100% Accommodation (178.5k)</strong>, and <strong>100% Registration (50k)</strong> is required for room selection.</p>
                            <?php if ($user->fees_paid >= 750000 && $user->accommodation_paid >= 178500 && $user->registration_paid >= 50000): ?>
                                <div class="mt-3">
                                    <a href="book-hostel.php" class="btn btn-success rounded-pill px-4 fw-800 animate__animated animate__pulse animate__infinite">
                                        <i class="fas fa-door-open me-2"></i> PROCEED TO BOOK ROOM
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <!-- School Fees -->
                    <div class="col-lg-4">
                        <div class="card-modern h-100">
                            <div class="p-4" style="background: var(--gradient-primary); color: white;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-800 mb-0">School Fees</h5>
                                    <i class="fas fa-graduation-cap fs-4 opacity-50"></i>
                                </div>
                                <div class="h3 fw-800 mb-1">TSH 1,500,000</div>
                                <div class="small opacity-75">Full Academic Year</div>
                            </div>
                            <div class="card-body-modern">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small fw-700 text-muted">AMOUNT PAID</span>
                                        <span class="small fw-800 text-primary"><?php echo number_format(($user->fees_paid/1500000)*100, 1); ?>%</span>
                                    </div>
                                    <div class="progress rounded-pill mb-3" style="height: 10px;">
                                        <div class="progress-bar rounded-pill" style="width: <?php echo ($user->fees_paid/1500000)*100; ?>%; background: var(--gradient-primary);"></div>
                                    </div>
                                    <div class="d-flex justify-content-between h6 mb-0">
                                        <span class="text-muted">Paid: <?php echo number_format($user->fees_paid); ?>/=</span>
                                        <span class="text-danger fw-700">Due: <?php echo number_format(1500000 - $user->fees_paid); ?>/=</span>
                                    </div>
                                </div>

                                <?php if($user->fees_paid < 1500000): ?>
                                    <label class="form-label-modern mb-1">Control Number</label>
                                    <div class="ctrl-box" onclick="copyToClipboard('<?php echo $user->fee_control_no; ?>')">
                                        <code><?php echo $user->fee_control_no; ?></code>
                                    </div>
                                    <button class="btn btn-modern btn-modern-primary w-100 justify-content-center mt-2" data-bs-toggle="modal" data-bs-target="#payModal" 
                                            data-type="Fees" data-ctrl="<?php echo $user->fee_control_no; ?>" 
                                            data-balance="<?php echo 1500000 - $user->fees_paid; ?>">
                                        <i class="fas fa-money-check-alt"></i> Pay Balance
                                    </button>
                                <?php else: ?>
                                    <div class="text-center py-4 bg-success bg-opacity-10 rounded-4">
                                        <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                                        <div class="fw-800 text-success">FULLY PAID</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Accommodation -->
                    <div class="col-lg-4">
                        <div class="card-modern h-100">
                            <div class="p-4" style="background: var(--gradient-success); color: white;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-800 mb-0">Accommodation</h5>
                                    <i class="fas fa-bed fs-4 opacity-50"></i>
                                </div>
                                <div class="h3 fw-800 mb-1">TSH 178,500</div>
                                <div class="small opacity-75">Per Semester</div>
                            </div>
                            <div class="card-body-modern">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small fw-700 text-muted">AMOUNT PAID</span>
                                        <span class="small fw-800 text-success"><?php echo number_format(($user->accommodation_paid/178500)*100, 1); ?>%</span>
                                    </div>
                                    <div class="progress rounded-pill mb-3" style="height: 10px;">
                                        <div class="progress-bar rounded-pill" style="width: <?php echo ($user->accommodation_paid/178500)*100; ?>%; background: var(--gradient-success);"></div>
                                    </div>
                                    <div class="d-flex justify-content-between h6 mb-0">
                                        <span class="text-muted">Paid: <?php echo number_format($user->accommodation_paid); ?>/=</span>
                                        <span class="text-danger fw-700">Due: <?php echo number_format(178500 - $user->accommodation_paid); ?>/=</span>
                                    </div>
                                </div>

                                <?php if($user->accommodation_paid < 178500): ?>
                                    <label class="form-label-modern mb-1">Control Number</label>
                                    <div class="ctrl-box" onclick="copyToClipboard('<?php echo $user->acc_control_no; ?>')">
                                        <code><?php echo $user->acc_control_no; ?></code>
                                    </div>
                                    <button class="btn btn-modern btn-modern-primary w-100 justify-content-center mt-2" style="background: var(--gradient-success);" 
                                            data-bs-toggle="modal" data-bs-target="#payModal" 
                                            data-type="Accommodation" data-ctrl="<?php echo $user->acc_control_no; ?>" 
                                            data-balance="<?php echo 178500 - $user->accommodation_paid; ?>">
                                        <i class="fas fa-money-check-alt"></i> Pay Balance
                                    </button>
                                <?php else: ?>
                                    <div class="text-center py-4 bg-success bg-opacity-10 rounded-4">
                                        <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                                        <div class="fw-800 text-success">FULLY PAID</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Registration -->
                    <div class="col-lg-4">
                        <div class="card-modern h-100">
                             <div class="p-4" style="background: var(--gradient-purple); color: white;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-800 mb-0">Registration</h5>
                                    <i class="fas fa-id-card fs-4 opacity-50"></i>
                                </div>
                                <div class="h3 fw-800 mb-1">TSH 50,000</div>
                                <div class="small opacity-75">One-time Fee</div>
                            </div>
                            <div class="card-body-modern">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small fw-700 text-muted">AMOUNT PAID</span>
                                        <span class="small fw-800" style="color:#7209b7;"><?php echo number_format(($user->registration_paid/50000)*100, 1); ?>%</span>
                                    </div>
                                    <div class="progress rounded-pill mb-3" style="height: 10px;">
                                        <div class="progress-bar rounded-pill" style="width: <?php echo ($user->registration_paid/50000)*100; ?>%; background: var(--gradient-purple);"></div>
                                    </div>
                                    <div class="d-flex justify-content-between h6 mb-0">
                                        <span class="text-muted">Paid: <?php echo number_format($user->registration_paid); ?>/=</span>
                                        <span class="text-danger fw-700">Due: <?php echo number_format(50000 - $user->registration_paid); ?>/=</span>
                                    </div>
                                </div>

                                <?php if($user->registration_paid < 50000): ?>
                                    <label class="form-label-modern mb-1">Control Number</label>
                                    <div class="ctrl-box" onclick="copyToClipboard('<?php echo $user->reg_control_no; ?>')">
                                        <code><?php echo $user->reg_control_no; ?></code>
                                    </div>
                                    <button class="btn btn-modern btn-modern-primary w-100 justify-content-center mt-2" style="background: var(--gradient-purple);"
                                            data-bs-toggle="modal" data-bs-target="#payModal" 
                                            data-type="Registration" data-ctrl="<?php echo $user->reg_control_no; ?>" 
                                            data-balance="<?php echo 50000 - $user->registration_paid; ?>">
                                        <i class="fas fa-money-check-alt"></i> Pay Balance
                                    </button>
                                <?php else: ?>
                                    <div class="text-center py-4 bg-success bg-opacity-10 rounded-4">
                                        <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                                        <div class="fw-800 text-success">FULLY PAID</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Log -->
                <div class="card-modern">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-history text-primary"></i>
                            <h5 class="fw-800 mb-0">Official Payment History</h5>
                        </div>
                        <span class="badge bg-primary-light text-primary rounded-pill px-3 py-2 fw-700">Recent 10</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Date & Time</th>
                                    <th>Verification Ref</th>
                                    <th>Fee Type</th>
                                    <th>Control No</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $logs = $mysqli->query("SELECT * FROM payment_logs WHERE regNo = '$user->regNo' ORDER BY created_at DESC LIMIT 10");
                                if($logs->num_rows > 0):
                                    while($log = $logs->fetch_object()):
                                        $status_color = ($log->status == 'Verified' || $log->status == 'Success') ? 'success' : 'warning';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-700 text-dark"><?php echo date('d M Y', strtotime($log->created_at)); ?></div>
                                        <div class="small text-muted"><?php echo date('h:i A', strtotime($log->created_at)); ?></div>
                                    </td>
                                    <td>
                                        <code class="text-primary fw-800" style="font-size: 0.9rem;"><?php echo $log->transaction_id; ?></code>
                                    </td>
                                    <td>
                                        <span class="badge-modern" style="background: rgba(67, 97, 238, 0.08); color: var(--primary);"><?php echo $log->payment_type; ?></span>
                                    </td>
                                    <td class="fw-700 text-muted small"><?php echo $log->control_no; ?></td>
                                    <td class="fw-800 text-dark">TSH <?php echo number_format($log->amount); ?></td>
                                    <td>
                                        <span class="badge-modern badge-modern-<?php echo $status_color; ?>">
                                            <i class="fas fa-check-circle me-1"></i> <?php echo $log->status ?? 'Verified'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-5">
                                    <div class="opacity-50 mb-3"><i class="fas fa-receipt fa-3x"></i></div>
                                    <p class="text-muted fw-600">No official payments recorded yet.</p>
                                </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light p-3 text-center">
                        <small class="text-muted fw-600"><i class="fas fa-info-circle me-1"></i> These transaction logs serve as digital receipts for all GePG payments.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Simulation Modal -->
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
                <div class="modal-body p-5 text-center">
                    <div class="mb-4">
                        <img src="https://bot.co.tz/GePG/images/gepg_logo.png" alt="GePG" style="height: 60px;" onerror="this.src='https://via.placeholder.com/150x60?text=GePG+Portal'">
                    </div>
                    <h4 class="fw-800 mb-1">GePG Payment Portal</h4>
                    <p class="text-muted mb-4">Simulate payment for <span id="modal-type" class="fw-700 text-primary"></span></p>
                    
                    <div class="bg-light p-4 rounded-4 mb-4">
                        <div class="small fw-700 text-muted text-uppercase mb-1">Control Number</div>
                        <div class="h3 fw-800 text-dark" id="modal-ctrl"></div>
                    </div>

                    <form action="" method="post" id="payForm">
                        <input type="hidden" name="payment_type" id="form-type">
                        <input type="hidden" name="control_no" id="form-ctrl">
                        <input type="hidden" name="amount" id="form-amount-hidden">
                        
                        <div class="mb-4 text-start">
                            <label class="form-label-modern mb-2">Select Amount to Pay (TSH)</label>
                            
                            <!-- Amount Options Container -->
                            <div id="fees-options" style="display:none;">
                                <div class="form-check custom-radio-btn mb-2">
                                    <input class="form-check-input amnt-trigger" type="radio" name="amnt-choice" id="amt50" value="750000">
                                    <label class="form-check-label w-100 p-3 border rounded-3 fw-700" for="amt50">
                                        50% - TSH 750,000
                                    </label>
                                </div>
                                <div class="form-check custom-radio-btn mb-2">
                                    <input class="form-check-input amnt-trigger" type="radio" name="amnt-choice" id="amt100" value="1500000">
                                    <label class="form-check-label w-100 p-3 border rounded-3 fw-700" for="amt100">
                                        100% - TSH 1,500,000
                                    </label>
                                </div>
                                <div class="form-check custom-radio-btn mb-2">
                                    <input class="form-check-input amnt-trigger" type="radio" name="amnt-choice" id="amtRem" value="750000">
                                    <label class="form-check-label w-100 p-3 border rounded-3 fw-700" for="amtRem">
                                        Remaining 50% - TSH 750,000
                                    </label>
                                </div>
                            </div>

                            <div id="full-msg" class="mb-3" style="display:none;">
                                <div class="p-3 border rounded-3 bg-light text-center">
                                    <div class="small fw-700 text-muted mb-1">Required Amount</div>
                                    <div class="h4 fw-800 text-dark mb-0" id="fixed-amt-display"></div>
                                </div>
                            </div>
                            
                            <div class="form-text text-center mt-2 text-primary fw-600">
                                <i class="fas fa-shield-alt me-1"></i> Official payment amounts only
                            </div>
                        </div>

                        <button type="submit" name="simulate_payment" class="btn-modern btn-modern-primary w-100 py-3 justify-content-center shadow-lg">
                            <i class="fas fa-lock me-2"></i> CONFIRM SECURE PAYMENT
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-link link-secondary mt-3 text-decoration-none fw-700" data-bs-dismiss="modal">Cancel & Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            var payModal = document.getElementById('payModal')
            payModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget
                var type = button.getAttribute('data-type')
                var ctrl = button.getAttribute('data-ctrl')
                var balance = button.getAttribute('data-balance')
                var paid = <?php echo json_encode([
                    'Fees' => $user->fees_paid,
                    'Accommodation' => $user->accommodation_paid,
                    'Registration' => $user->registration_paid
                ]); ?>;

                $('#modal-type').text(type);
                $('#form-type').val(type);
                $('#modal-ctrl').text(ctrl);
                $('#form-ctrl').val(ctrl);

                // Reset UI
                $('#fees-options').hide();
                $('#full-msg').hide();
                $('#form-amount-hidden').val('');
                $('.amnt-trigger').prop('checked', false);

                if (type === 'Fees') {
                    $('#fees-options').show();
                    $('.amnt-trigger').prop('required', true);
                    
                    if (paid.Fees == 0) {
                        $('#amt50').parent().show();
                        $('#amt100').parent().show();
                        $('#amtRem').parent().hide();
                    } else if (paid.Fees >= 750000 && paid.Fees < 1500000) {
                        $('#amt50').parent().hide();
                        $('#amt100').parent().hide();
                        $('#amtRem').parent().show();
                    }
                } else {
                    $('#full-msg').show();
                    $('.amnt-trigger').prop('required', false);
                    $('#fixed-amt-display').text('TSH ' + parseInt(balance).toLocaleString());
                    $('#form-amount-hidden').val(balance);
                }
            });

            // Update hidden amount whenever radio changes
            $('.amnt-trigger').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#form-amount-hidden').val($(this).val());
                }
            });
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Control Number: ' + text,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }
    </script>
</body>
</html>
