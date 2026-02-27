<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
	$id=$_GET['del'];
	$adn="delete from userregistration where regNo=?";
		$stmt= $mysqli->prepare($adn);
		$stmt->bind_param('s',$id);
        $stmt->execute();
        $stmt->close();	   
}

if(isset($_GET['approve']))
{
	$id=$_GET['approve'];
	$adn="UPDATE userregistration SET status='Active' WHERE regNo=?";
	$stmt= $mysqli->prepare($adn);
	$stmt->bind_param('s',$id);
    if($stmt->execute()) {
        $_SESSION['success_msg'] = "Student account activated successfully";
    }
    $stmt->close();	   
}

if(isset($_GET['toggle_fee']))
{
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
                    $query = "SELECT u.id, u.firstName, u.lastName, u.regNo, u.gender, u.email, u.status, u.regDate, r.roomno, r.seater 
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
                            
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <div class="badge rounded-pill bg-light text-dark px-3 py-2 fw-700 small">
                                    <i class="fas fa-door-closed me-1"></i> <?php echo $row->roomno ? 'Room ' . $row->roomno : 'No Room'; ?>
                                </div>
                                <div class="badge rounded-pill bg-light text-dark px-3 py-2 fw-700 small">
                                    <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M Y', strtotime($row->regDate)); ?>
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
                window.location.href = `manage-students.php?approve=${currentStudent.regNo}`;
            }
        });
    }

    function toggleFee() {
        if(!currentStudent) return;
        window.location.href = `manage-students.php?toggle_fee=${currentStudent.regNo}`;
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
                window.location.href = `manage-students.php?del=${currentStudent.regNo}`;
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
</body>
</html>

