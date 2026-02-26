<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];

// Simulation Logic: When a student "pays"
if(isset($_POST['simulate_payment'])) {
    $type = $_POST['payment_type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $ctrl = $_POST['control_no'] ?? '';
    
    if ($amount > 0 && !empty($type)) {
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
                $q = "SELECT regNo, fees_paid, accommodation_paid FROM userregistration WHERE id = ?";
                $st = $mysqli->prepare($q);
                $st->bind_param('i', $user_id);
                $st->execute();
                $res = $st->get_result()->fetch_object();
                $regNo = $res->regNo;
                $st->close();
                
                // 1 if met threshold (50% fees + 100% acc), else 0
                $new_fee_status = ($res->fees_paid >= 750000 && $res->accommodation_paid >= 178500) ? 1 : 0;
                
                // Update payment_status text
                $payment_status = "Partially Paid";
                if ($res->fees_paid >= 1500000 && $res->accommodation_paid >= 178500) {
                    $payment_status = "Fully Paid";
                }

                $upd = $mysqli->prepare("UPDATE userregistration SET fee_status = ?, payment_status = ? WHERE id = ?");
                $upd->bind_param('isi', $new_fee_status, $payment_status, $user_id);
                $upd->execute();
                $upd->close();
                
                // Log the payment
                $log = $mysqli->prepare("INSERT INTO payment_logs (regNo, control_no, amount, payment_type, transaction_id) VALUES (?, ?, ?, ?, ?)");
                $trans_id = "SIM-" . strtoupper(substr(md5(time()), 0, 8));
                $log->bind_param('ssdss', $regNo, $ctrl, $amount, $type, $trans_id);
                $log->execute();
                $log->close();
                
                $mysqli->commit();
                $_SESSION['success'] = "Payment of TSH " . number_format($amount) . " for $type was successful! Control Number: $ctrl";
            } catch (Exception $e) {
                $mysqli->rollback();
                $_SESSION['error'] = "Payment simulation failed: " . $e->getMessage();
            }
        }
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
    <title>Make Payment | Hostel Management</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #06d6a0;
            --danger: #ef233c;
            --warning: #ffb703;
            --info: #4cc9f0;
            --gray: #94a3b8;
            --dark: #1e293b;
            --light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
        }

        .payment-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            overflow: hidden;
            background: #fff;
            height: 100%;
        }

        .payment-card:hover {
            transform: translateY(-5px);
        }

        .card-header-custom {
            padding: 24px;
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            border: none;
        }

        .ctrl-box {
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.2s;
        }

        .ctrl-box:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        .ctrl-box code {
            font-size: 1.25rem;
            color: var(--primary);
            font-weight: 800;
            letter-spacing: 1px;
        }

        .btn-simulate {
            background: var(--dark);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-simulate:hover {
            background: #000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .badge-payment {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .progress {
            height: 10px;
            border-radius: 10px;
            background-color: #e2e8f0;
        }

        /* Modal Simulation Styling */
        .gepg-logo {
            width: 150px;
            margin-bottom: 20px;
        }

        .modal-content {
            border-radius: 24px;
            border: none;
            overflow: hidden;
        }

        .amount-display {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="container-fluid py-5 mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Fee Payment & Control Numbers</h2>
                        <p class="text-muted">Simulate your payments using GePG Control Numbers</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i> Dashboard
                    </a>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4 border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2 text-success"></i> <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4 border-0 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-circle me-2 text-danger"></i> <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Rules Alert -->
                <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4" style="background: white; border-left: 5px solid var(--info) !important;">
                    <div class="d-flex align-items-center p-2">
                        <div class="me-3"><i class="fas fa-info-circle fa-2x text-info"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Room Allocation Requirements</h6>
                            <p class="mb-0 small text-muted">You must pay at least <strong>50% of School Fees (TSH 750,000)</strong> and <strong>100% of Accommodation (TSH 178,500)</strong> to be eligible for room selection.</p>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- School Fees Card -->
                    <div class="col-md-4">
                        <div class="payment-card">
                            <div class="card-header-custom">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-graduation-cap me-2"></i>School Fees</h5>
                                <small class="opacity-75">Full Amount: TSH 1,500,000</small>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Amount Paid</span>
                                        <span class="fw-bold">TSH <?php echo number_format($user->fees_paid); ?></span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-primary" style="width: <?php echo ($user->fees_paid/1500000)*100; ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-muted">Remaining Balance:</span>
                                        <span class="small fw-bold text-danger">TSH <?php echo number_format(max(0, 1500000 - $user->fees_paid)); ?></span>
                                    </div>
                                </div>

                                <?php if(1500000 - $user->fees_paid > 0): ?>
                                    <label class="small text-muted fw-bold">GePG Control Number:</label>
                                    <div class="ctrl-box" onclick="copyToClipboard('<?php echo $user->fee_control_no; ?>')">
                                        <code><?php echo $user->fee_control_no; ?></code>
                                    </div>

                                    <button class="btn-simulate" data-bs-toggle="modal" data-bs-target="#payModal" 
                                            data-type="Fees" data-ctrl="<?php echo $user->fee_control_no; ?>" 
                                            data-balance="<?php echo 1500000 - $user->fees_paid; ?>">
                                        <i class="fas fa-money-check-alt me-2"></i> Pay Remaining Balance
                                    </button>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <div class="badge bg-success-subtle text-success p-3 rounded-pill w-100">
                                            <i class="fas fa-check-circle me-2"></i> FEE FULLY PAID
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Accommodation Card -->
                    <div class="col-md-4">
                        <div class="payment-card">
                            <div class="card-header-custom" style="background: linear-gradient(135deg, #06d6a0 0%, #05c08e 100%);">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-bed me-2"></i>Accommodation</h5>
                                <small class="opacity-75">Full Amount: TSH 178,500</small>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Amount Paid</span>
                                        <span class="fw-bold">TSH <?php echo number_format($user->accommodation_paid); ?></span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" style="width: <?php echo ($user->accommodation_paid/178500)*100; ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-muted">Remaining Balance:</span>
                                        <span class="small fw-bold text-danger">TSH <?php echo number_format(max(0, 178500 - $user->accommodation_paid)); ?></span>
                                    </div>
                                </div>

                                <?php if(178500 - $user->accommodation_paid > 0): ?>
                                    <label class="small text-muted fw-bold">GePG Control Number:</label>
                                    <div class="ctrl-box" onclick="copyToClipboard('<?php echo $user->acc_control_no; ?>')">
                                        <code><?php echo $user->acc_control_no; ?></code>
                                    </div>

                                    <button class="btn-simulate" data-bs-toggle="modal" data-bs-target="#payModal" 
                                            data-type="Accommodation" data-ctrl="<?php echo $user->acc_control_no; ?>" 
                                            data-balance="<?php echo 178500 - $user->accommodation_paid; ?>">
                                        <i class="fas fa-money-check-alt me-2"></i> Pay Remaining Balance
                                    </button>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <div class="badge bg-success-subtle text-success p-3 rounded-pill w-100">
                                            <i class="fas fa-check-circle me-2"></i> FULLY PAID
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Fee Card -->
                    <div class="col-md-4">
                        <div class="payment-card">
                            <div class="card-header-custom" style="background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-id-card me-2"></i>Registration Fee <span class="badge bg-light text-dark small ms-2" style="font-size:0.6rem;">(Optional)</span></h5>
                                <small class="opacity-75">Full Amount: TSH 50,000</small>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Amount Paid</span>
                                        <span class="fw-bold">TSH <?php echo number_format($user->registration_paid); ?></span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar" style="width: <?php echo ($user->registration_paid/50000)*100; ?>%; background-color:#7209b7;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-muted">Remaining Balance:</span>
                                        <span class="small fw-bold text-danger">TSH <?php echo number_format(max(0, 50000 - $user->registration_paid)); ?></span>
                                    </div>
                                </div>

                                <?php if(50000 - $user->registration_paid > 0): ?>
                                    <label class="small text-muted fw-bold">GePG Control Number:</label>
                                    <div class="ctrl-box" onclick="copyToClipboard('<?php echo $user->reg_control_no; ?>')">
                                        <code><?php echo $user->reg_control_no; ?></code>
                                    </div>

                                    <button class="btn-simulate" data-bs-toggle="modal" data-bs-target="#payModal" 
                                            data-type="Registration" data-ctrl="<?php echo $user->reg_control_no; ?>" 
                                            data-balance="<?php echo 50000 - $user->registration_paid; ?>">
                                        <i class="fas fa-money-check-alt me-2"></i> Pay Remaining Balance
                                    </button>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <div class="badge bg-success-subtle text-success p-3 rounded-pill w-100">
                                            <i class="fas fa-check-circle me-2"></i> FULLY PAID
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card border-0 shadow-sm rounded-4 mt-5">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fas fa-history me-2"></i>Recent Transactions (Simulation)</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction ID</th>
                                        <th>Type</th>
                                        <th>Control No</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $logs = $mysqli->query("SELECT * FROM payment_logs WHERE regNo = '$user->regNo' ORDER BY created_at DESC LIMIT 5");
                                    if($logs->num_rows > 0):
                                        while($log = $logs->fetch_object()):
                                    ?>
                                    <tr>
                                        <td class="small"><?php echo date('d M Y, H:i', strtotime($log->created_at)); ?></td>
                                        <td class="fw-bold"><?php echo $log->transaction_id; ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $log->payment_type; ?></span></td>
                                        <td class="text-primary fw-bold"><?php echo $log->control_no; ?></td>
                                        <td class="fw-bold">TSH <?php echo number_format($log->amount); ?></td>
                                        <td><span class="badge bg-success-subtle text-success px-3">SUCCESS</span></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No recent transactions found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Simulation Modal -->
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <img src="https://bot.co.tz/GePG/images/gepg_logo.png" alt="GePG" class="gepg_logo mb-3" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/GePG_Logo.png/220px-GePG_Logo.png'; this.style.width='150px'">
                    <h4 class="fw-bold">GePG Payment Simulation</h4>
                    <p class="text-muted">You are paying for <strong id="modal-type"></strong></p>
                    
                    <div class="ctrl-display mb-3">
                        <small class="text-muted d-block uppercase fw-bold">Control Number</small>
                        <span class="h4 fw-bold text-primary" id="modal-ctrl"></span>
                    </div>

                    <form action="" method="post">
                        <input type="hidden" name="payment_type" id="form-type">
                        <input type="hidden" name="control_no" id="form-ctrl">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Enter Amount to Pay (Tsh)</label>
                            <input type="number" name="amount" id="form-amount" class="form-control form-control-lg text-center fw-bold" required>
                            <div class="form-text mt-2">Balance: <span id="modal-balance"></span></div>
                        </div>

                        <button type="submit" name="simulate_payment" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow">
                            SIMULATE PAYMENT SUCCESS
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-link link-secondary mt-3 text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
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

                $('#modal-type').text(type);
                $('#form-type').val(type);
                $('#modal-ctrl').text(ctrl);
                $('#form-ctrl').val(ctrl);
                $('#form-amount').val(balance > 0 ? balance : 0);
                $('#modal-balance').text('TSH ' + parseInt(balance).toLocaleString());
            });
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Control Number Copied',
                    text: text,
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }
    </script>
</body>
</html>
