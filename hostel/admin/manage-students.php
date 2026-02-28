<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
    // CSRF PROTECTION
    if(!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['error_msg'] = "Security token mismatch. Action aborted.";
    } else {
        $id=$_GET['del'];
        
        // 1. Delete room allocation to free up bed
        $del_reg = "DELETE FROM registration WHERE regno=?";
        $stmt1 = $mysqli->prepare($del_reg);
        $stmt1->bind_param('s', $id);
        $stmt1->execute();
        $stmt1->close();

        // 2. Delete main account
        $adn="delete from userregistration where regNo=?";
        $stmt= $mysqli->prepare($adn);
        $stmt->bind_param('s',$id);
        $stmt->execute();
        $stmt->close();	   
        
        $_SESSION['success_msg'] = "Student and associated records purged successfully";
    }
}

if(isset($_GET['approve']))
{
    // CSRF PROTECTION
    if(!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['error_msg'] = "Security token mismatch. Action aborted.";
    } else {
        $id=$_GET['approve'];
        $adn="UPDATE userregistration SET status='Active' WHERE regNo=?";
        $stmt= $mysqli->prepare($adn);
        $stmt->bind_param('s',$id);
        if($stmt->execute()) {
            $_SESSION['success_msg'] = "Student account activated successfully";
        }
        $stmt->close();	   
    }
}

if(isset($_GET['toggle_fee']))
{
    // CSRF PROTECTION
    if(!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['error_msg'] = "Security token mismatch. Action aborted.";
    } else {
        $id=$_GET['toggle_fee'];
        $adn="UPDATE userregistration SET fee_status = NOT fee_status WHERE regNo=?";
        $stmt= $mysqli->prepare($adn);
        $stmt->bind_param('s',$id);
        if($stmt->execute()) {
            $_SESSION['success_msg'] = "Fee status updated successfully";
            echo "<script>window.location.href='manage-students.php';</script>";
        }
        $stmt->close();	   
    }
}

// ==========================================
// NEW LOGIC: PROCESS PAYMENT FORM
// ==========================================
if(isset($_POST['submit_payment']))
{
    if(!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Security token mismatch. Action aborted.";
    } else {
        $regNo = $_POST['pay_regNo'];
        $payment_type = $_POST['payment_type'] ?? 'Other';
        $amount = $_POST['amount'] ?? 0;
        $transaction_id = $_POST['transaction_id'] ?? '-';
        $mark_eligible = isset($_POST['mark_eligible']) ? 1 : 0;
        
        $sql = "INSERT INTO payment_logs (regNo, amount, payment_type, transaction_id, status) VALUES (?, ?, ?, ?, 'Success')";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param('sdss', $regNo, $amount, $payment_type, $transaction_id);
            if($stmt->execute()) {
                
                // BEST PRODUCTION LOGIC: Accumulate the paid amount into the specific category
                $col = "";
                if ($payment_type === 'Accommodation') $col = "accommodation_paid";
                elseif ($payment_type === 'Registration') $col = "registration_paid";
                elseif ($payment_type === 'Tuition') $col = "fees_paid";
                
                if (!empty($col)) {
                    $acc_upd = $mysqli->prepare("UPDATE userregistration SET $col = $col + ? WHERE regNo = ?");
                    if($acc_upd) {
                        $acc_upd->bind_param('ds', $amount, $regNo);
                        $acc_upd->execute();
                        $acc_upd->close();
                    }
                }

                // NEW AUTO-CALCULATION LOGIC: System decides if ELIGIBLE based on thresholds
                $chk_query = "SELECT fees_paid, accommodation_paid, registration_paid FROM userregistration WHERE regNo = ?";
                $chk_stmt = $mysqli->prepare($chk_query);
                if($chk_stmt) {
                    $chk_stmt->bind_param('s', $regNo);
                    $chk_stmt->execute();
                    $chk_stmt->bind_result($fees_paid, $accommodation_paid, $registration_paid);
                    if($chk_stmt->fetch()) {
                        $chk_stmt->close();
                        
                        // Define Target Business Rules (Thresholds)
                        $target_registration = 50000;
                        $target_accommodation = 178500;
                        $target_tuition = 750000; // 50% of 1,500,000
                        
                        // Check if all thresholds have been met or exceeded
                        if($registration_paid >= $target_registration && 
                           $accommodation_paid >= $target_accommodation && 
                           $fees_paid >= $target_tuition) {
                            
                            $upd = $mysqli->prepare("UPDATE userregistration SET fee_status = 1 WHERE regNo = ?");
                            if($upd) {
                                $upd->bind_param('s', $regNo);
                                $upd->execute();
                                $upd->close();
                            }
                        } else {
                            // Enforce INELIGIBLE if thresholds are not met
                            $upd = $mysqli->prepare("UPDATE userregistration SET fee_status = 0 WHERE regNo = ?");
                            if($upd) {
                                $upd->bind_param('s', $regNo);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                    } else {
                        $chk_stmt->close();
                    }
                }
                
                $_SESSION['success_msg'] = "Payment verified and recorded successfully.";
            } else {
                $_SESSION['error_msg'] = "Error recording payment. Please try again.";
            }
            $stmt->close();
        }
        echo "<script>window.location.href='manage-students.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Directory | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Unified Admin CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .student-card {
            border-radius: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fff; border: 1px solid #f1f5f9; position: relative;
            overflow: hidden;
        }
        .student-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); border-color: var(--primary); }
        
        .avatar-circle {
            width: 80px; height: 80px; border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 800; margin-bottom: 20px;
            background: var(--primary-light); color: var(--primary);
        }
        
        .status-indicator {
            position: absolute; top: 20px; right: 20px;
            padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .filter-btn {
            background: #fff; border: 1px solid #e2e8f0; padding: 10px 20px;
            border-radius: 12px; font-weight: 700; color: var(--gray);
            transition: 0.2s;
        }
        .filter-btn:hover, .filter-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        
        .search-container { position: relative; }
        .search-container i { position: absolute; left: 18px; top: 18px; color: var(--gray); font-size: 1.1rem; }
        .search-input { padding-left: 50px !important; height: 56px; border-radius: 16px; border: none; box-shadow: var(--shadow-sm); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Student Records</h2>
                        <p class="text-muted fw-600 mb-0">Manage registrations, profiles, and account statuses from a single pane.</p>
                    </div>
                    <div>
                        <a href="registration.php" class="btn btn-modern btn-modern-primary">
                            <i class="fas fa-plus"></i> New Enrollment
                        </a>
                    </div>
                </div>

                <!-- FILTERS & SEARCH -->
                <div class="row g-4 mb-5 align-items-center">
                    <div class="col-lg-7">
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="filter-btn active" data-filter="all">All Records</button>
                            <button class="filter-btn" data-filter="active">Verified</button>
                            <button class="filter-btn" data-filter="pending">Pending</button>
                            <button class="filter-btn" data-filter="male">Male</button>
                            <button class="filter-btn" data-filter="female">Female</button>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="search-container">
                            <i class="fas fa-search"></i>
                            <input type="text" id="studentSearch" class="form-control search-input" placeholder="Search by name, registration, or email...">
                        </div>
                    </div>
                </div>

                <!-- STUDENT GRID -->
                <div id="studentGrid" class="row g-4">
                    <?php 
                    $query = "SELECT u.id, u.firstName, u.lastName, u.regNo, u.gender, u.email, u.status, u.regDate, 
                             u.fee_status, u.fees_paid, u.accommodation_paid, u.registration_paid, 
                             r.roomno, r.seater 
                             FROM userregistration u 
                             LEFT JOIN registration r ON u.regNo = r.regno";
                    
                    // BLOCK RESTRICTION FOR DEBTORS
                    if(isset($_SESSION['assigned_block']) && !empty($_SESSION['assigned_block'])) {
                        $block = $_SESSION['assigned_block'];
                        $query .= " WHERE r.roomno LIKE '$block%'";
                    }
                    
                    $query .= " ORDER BY u.regDate DESC";
                    $res = $mysqli->query($query);
                    while($row = $res->fetch_object()):
                        $gender_class = $row->gender == 'female' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary';
                        $status_class = 'status-' . strtolower($row->status);
                        
                        // Payment Calculation
                        $total_paid = $row->fees_paid + $row->accommodation_paid + $row->registration_paid;
                        $total_expected = 1500000 + 178500 + 50000;
                        $pay_perc = ($total_paid / $total_expected) * 100;
                    ?>
                    <div class="col-xl-4 col-md-6 student-item" 
                         data-gender="<?php echo strtolower($row->gender); ?>" 
                         data-status="<?php echo strtolower($row->status); ?>"
                         data-search="<?php echo strtolower($row->firstName . ' ' . $row->lastName . ' ' . $row->regNo . ' ' . $row->email); ?>">
                        
                        <div class="student-card p-4">
                            <span class="status-indicator <?php echo $status_class; ?>">
                                <?php echo strtoupper($row->status); ?>
                            </span>
                            
                            <div class="avatar-circle <?php echo $gender_class; ?>">
                                <?php echo strtoupper(substr($row->firstName, 0, 1) . substr($row->lastName, 0, 1)); ?>
                            </div>
                            
                            <h5 class="fw-800 text-dark mb-1"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?></h5>
                            <p class="text-muted small fw-700 mb-3"><i class="fas fa-id-card me-1"></i> <?php echo $row->regNo; ?></p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-700 text-muted">PAYMENT STATUS</span>
                                    <span class="small fw-800 text-primary"><?php echo number_format($pay_perc, 0); ?>%</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 6px;">
                                    <div class="progress-bar rounded-pill" style="width: <?php echo $pay_perc; ?>%; background: var(--gradient-primary);"></div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <div class="badge rounded-pill bg-light text-dark px-3 py-2 fw-700 small">
                                    <i class="fas fa-door-closed me-1"></i> <?php echo $row->roomno ? 'Room ' . $row->roomno : 'No Room'; ?>
                                </div>
                                <div class="badge rounded-pill bg-light text-dark px-3 py-2 fw-700 small">
                                    <i class="fas fa-coins me-1"></i> <?php echo number_format($total_paid/1000); ?>k
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 border-top pt-3">
                                <a href="student-details.php?id=<?php echo $row->id; ?>" class="btn btn-modern btn-modern-primary w-100 py-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-expand-alt"></i> Full Profile
                                </a>
                                <button onclick='quickAction(<?php echo json_encode($row); ?>)' class="btn btn-modern btn-light py-2 text-dark" style="width: 50px;">
                                     <i class="fas fa-ellipsis-h"></i>
                                 </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Student Quick View Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-id-card-clip me-2"></i> Student Brief</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div id="m-avatar" class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;"></div>
                    <h4 id="m-name" class="fw-800 text-dark mb-1"></h4>
                    <p id="m-reg" class="text-muted fw-700 mb-4"></p>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-4">
                                <label class="small fw-800 text-muted d-block mb-1">FEE STATUS</label>
                                <div id="m-fee" class="fw-800"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-4">
                                <label class="small fw-800 text-muted d-block mb-1">ACCOUNT</label>
                                <div id="m-status" class="fw-800"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <!-- Verify Button (Conditional) -->
                        <button id="m-verify-btn" onclick="verifyStudent()" class="btn btn-modern btn-modern-success py-3 fw-800 mb-2">
                            <i class="fas fa-check-circle me-2"></i> VERIFY ACCOUNT
                        </button>

                        <a id="m-detail-link" href="#" class="btn btn-modern btn-modern-primary py-3 fw-800">
                            <i class="fas fa-user-gear me-2"></i> COMMAND PROFILE
                        </a>
                        <div class="row g-2">
                            <div class="col-6">
                                <button onclick="toggleFee()" class="btn btn-light w-100 py-3 fw-800 text-success">
                                    <i class="fas fa-money-bill-transfer me-1"></i> PAY FEE
                                </button>
                            </div>
                            <div class="col-6">
                                <button onclick="deleteStudent()" class="btn btn-light w-100 py-3 fw-800 text-danger">
                                    <i class="fas fa-trash-alt me-1"></i> PURGE
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <!-- NEW: Comprehensive Payment Record Modal -->
    <div class="modal fade" id="payFeeModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-success text-white p-4">
                    <h5 class="modal-title fw-800"><i class="fas fa-money-bill-transfer me-2"></i> Register Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- The Form targets the self page -->
                <form method="POST" action="manage-students.php">
                    <div class="modal-body p-4 text-start">
                        <!-- CSRF Security -->
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="pay_regNo" id="pay_regNo">
                        
                        <div class="mb-4 text-center bg-light p-3 rounded-4">
                            <div class="small fw-800 text-muted mb-1" style="letter-spacing: 1px;">RECORDING PAYMENT FOR</div>
                            <h4 id="pay_student_name" class="fw-800 text-dark mb-0"></h4>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-800 text-muted small">PAYMENT CATEGORY</label>
                            <select name="payment_type" class="form-select form-control fw-600" required style="border-radius: 12px; padding: 12px; border-color: #e2e8f0;">
                                <option value="">Select Category...</option>
                                <option value="Accommodation">Accommodation Fee (Kodi ya Chumba)</option>
                                <option value="Registration">Registration Fee</option>
                                <option value="Tuition">Tuition / Academic Fee</option>
                                <option value="Other">Other / Miscellaneous</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-800 text-muted small">AMOUNT PAID (TSH)</label>
                            <div class="input-group">
                                <span class="input-group-text fw-800 text-muted bg-light border-end-0" style="border-radius: 12px 0 0 12px; border-color: #e2e8f0;">Tsh</span>
                                <input type="number" name="amount" class="form-control fw-700 border-start-0" required placeholder="e.g. 150000" style="border-radius: 0 12px 12px 0; padding: 12px; border-color: #e2e8f0;">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-800 text-muted small">RECEIPT / TRANSACTION ID</label>
                            <input type="text" name="transaction_id" class="form-control fw-700" required placeholder="e.g. REC-2023-XYZ" style="border-radius: 12px; padding: 12px; border-color: #e2e8f0;">
                            <small class="text-muted d-block mt-2" style="font-size: 0.75rem;"><i class="fas fa-info-circle text-primary me-1"></i> Enter the bank or system receipt number for auditing purposes.</small>
                        </div>
                        
                        <div class="alert alert-info border-0 rounded-4 small mb-0 d-flex align-items-center gap-3">
                            <i class="fas fa-robot fa-2x opacity-50"></i>
                            <div class="text-start">
                                <strong>Smart Approval:</strong> The system will automatically mark the student as ELIGIBLE once they complete the required thresholds: Registration (50K), Accommodation (178.5K), and Tuition (750K).
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-800 text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_payment" class="btn btn-success rounded-pill px-4 py-2 fw-800 shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> Confirm Setup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    let currentStudent = null;

    function quickAction(data) {
        currentStudent = data;
        const modal = new bootstrap.Modal(document.getElementById('studentModal'));
        
        const avatar = document.getElementById('m-avatar');
        avatar.innerText = (data.firstName[0] + data.lastName[0]).toUpperCase();
        avatar.className = `avatar-circle mx-auto mb-3 ${data.gender === 'female' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'}`;
        
        document.getElementById('m-name').innerText = data.firstName + ' ' + data.lastName;
        document.getElementById('m-reg').innerText = data.regNo;
        
        const feeEl = document.getElementById('m-fee');
        feeEl.innerText = data.fee_status == 1 ? 'ELIGIBLE' : 'INELIGIBLE';
        feeEl.className = `fw-800 ${data.fee_status == 1 ? 'text-success' : 'text-danger'}`;
        
        const statEl = document.getElementById('m-status');
        statEl.innerText = data.status.toUpperCase();
        statEl.className = `fw-800 ${data.status.toLowerCase() === 'active' ? 'text-success' : 'text-warning'}`;
        
        // Handle Verify Button Visibility
        const verifyBtn = document.getElementById('m-verify-btn');
        if (data.status.toLowerCase() === 'pending') {
            verifyBtn.style.display = 'block';
        } else {
            verifyBtn.style.display = 'none';
        }

        document.getElementById('m-detail-link').href = `student-details.php?id=${data.id}`;
        
        modal.show();
    }

    function verifyStudent() {
        if(!currentStudent) return;
        Swal.fire({
            title: 'Verify Student?',
            text: `Are you sure you want to activate the account for ${currentStudent.firstName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#06d6a0',
            confirmButtonText: 'Yes, Verify Student'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `manage-students.php?approve=${currentStudent.regNo}&token=<?php echo generate_csrf_token(); ?>`;
            }
        });
    }

    function toggleFee() {
        if(!currentStudent) return;
        
        // Hide Student Brief Modal temporarily
        const bModal = bootstrap.Modal.getInstance(document.getElementById('studentModal'));
        if(bModal) bModal.hide();
        
        // Populate the Payment form with student's specific info
        document.getElementById('pay_regNo').value = currentStudent.regNo;
        document.getElementById('pay_student_name').innerText = currentStudent.firstName + ' ' + currentStudent.lastName;
        
        // Reveal Payment Modal
        const payModal = new bootstrap.Modal(document.getElementById('payFeeModal'));
        payModal.show();
    }

    function deleteStudent() {
        if(!currentStudent) return;
        Swal.fire({
            title: 'Delete Resident?',
            text: 'This account will be permanently terminated from the registry.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Purge Data'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `manage-students.php?del=${currentStudent.regNo}&token=<?php echo generate_csrf_token(); ?>`;
            }
        });
    }

    // Filter Logic
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        const filter = $(this).data('filter');
        
        $('.student-item').each(function() {
            const gender = $(this).data('gender');
            const status = $(this).data('status');
            let show = false;
            
            if(filter === 'all') show = true;
            else if(filter === 'active' || filter === 'pending') show = (status === filter);
            else show = (gender === filter);
            
            $(this).toggle(show);
        });
    });

    // Search Logic
    $("#studentSearch").on("keyup", function() {
        const val = $(this).val().toLowerCase();
        $(".student-item").each(function() {
            $(this).toggle($(this).data('search').includes(val));
        });
    });
    </script>

    <?php if(isset($_SESSION['success_msg'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $_SESSION['success_msg']; ?>',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    </script>
    <?php unset($_SESSION['success_msg']); endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Security Alert',
            text: '<?php echo $_SESSION['error_msg']; ?>',
            timer: 4000,
            showConfirmButton: true
        });
    </script>
    <?php unset($_SESSION['error_msg']); endif; ?>
</body>
</html>

