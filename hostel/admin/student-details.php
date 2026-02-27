<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Fetch student ID from URL
$student_id = $_GET['id'] ?? null;
$regno = $_GET['regno'] ?? null;

if (!$student_id && !$regno) {
    header("Location: manage-students.php");
    exit();
}

// Fetch student data - support both id and legacy regno
if ($student_id) {
    $query = "SELECT u.*, r.*, u.id as studentId FROM userregistration u LEFT JOIN registration r ON u.regNo = r.regno WHERE u.id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $student_id);
} else {
    $query = "SELECT u.*, r.*, u.id as studentId FROM userregistration u LEFT JOIN registration r ON u.regNo = r.regno WHERE u.regNo = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $regno);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_object();
$stmt->close();

if (!$data) {
    header("Location: manage-students.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>Intelligence Brief | <?php echo $data->firstName; ?> | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Unified Admin CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 30px;
            padding: 60px 40px;
            color: white;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .profile-hero::after {
            content: ''; position: absolute; bottom: -50px; right: -50px;
            width: 300px; height: 300px; background: radial-gradient(circle, rgba(67, 97, 238, 0.15) 0%, transparent 70%);
        }
        .data-card {
            background: #fff; border-radius: 24px; padding: 30px;
            border: 1px solid #f1f5f9; height: 100%; transition: 0.3s;
        }
        .data-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
        
        .metric-item { margin-bottom: 20px; }
        .metric-label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .metric-value { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
        
        .avatar-brief {
            width: 120px; height: 120px; border-radius: 35px;
            background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: 800; color: #fff;
        }
        
        .section-tag {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 16px; border-radius: 50px; font-size: 0.75rem; font-weight: 800;
            background: #eff6ff; color: #3b82f6; margin-bottom: 25px;
        }
        
        .control-pill {
            background: #f8fafc; border-radius: 18px; padding: 20px;
            border: 1.5px dashed #e2e8f0; margin-bottom: 15px;
        }

        @media print {
            .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .profile-hero { background: #fff !important; color: #000 !important; border: 2px solid #000 !important; }
            .avatar-brief { color: #000 !important; border: 2px solid #000 !important; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <div class="no-print">
            <?php include('includes/sidebar_modern.php'); ?>
        </div>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="no-print d-flex justify-content-between align-items-center mb-5">
                    <a href="manage-students.php" class="btn btn-modern btn-light text-dark fw-800">
                        <i class="fas fa-chevron-left me-2"></i> Registry
                    </a>
                    <div class="d-flex gap-2">
                        <button onclick="window.print()" class="btn btn-modern btn-light fw-800">
                            <i class="fas fa-print me-2"></i> Print Archive
                        </button>
                        <button onclick="terminateStudent('<?php echo $data->regNo; ?>', '<?php echo addslashes($data->firstName . ' ' . $data->lastName); ?>')" class="btn btn-modern btn-danger fw-800">
                            <i class="fas fa-trash-alt me-2"></i> Terminate
                        </button>
                    </div>
                </div>

                <!-- HERO BRIEF -->
                <div class="profile-hero">
                    <div class="row align-items-center g-5">
                        <div class="col-md-auto">
                            <div class="avatar-brief">
                                <?php echo strtoupper(substr($data->firstName, 0, 1) . substr($data->lastName, 0, 1)); ?>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="badge bg-primary px-3 py-2 rounded-pill fw-800">SYSTEM RESIDENT ID: <?php echo $data->studentId; ?></span>
                                <span class="badge bg-<?php echo strtolower($data->status) == 'active' ? 'success' : 'warning'; ?> px-3 py-2 rounded-pill fw-800"><?php echo strtoupper($data->status); ?></span>
                            </div>
                            <h1 class="fw-800 mb-2" style="font-size: 3rem;"><?php echo htmlspecialchars($data->firstName . ' ' . $data->lastName); ?></h1>
                            <p class="opacity-75 h5 fw-500 mb-0">
                                Registration: <span class="fw-800 text-white"><?php echo $data->regNo; ?></span> 
                                <span class="mx-3">|</span> 
                                Course: <span class="fw-800 text-white"><?php echo $data->course ?: 'Not Assigned'; ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Column 1: Core Identity -->
                    <div class="col-lg-4">
                        <div class="data-card">
                            <div class="section-tag"><i class="fas fa-id-card"></i> BIOMETRIC & IDENTITY</div>
                            <div class="metric-item">
                                <div class="metric-label">Full Legal Name</div>
                                <div class="metric-value"><?php echo $data->firstName . ' ' . $data->middleName . ' ' . $data->lastName; ?></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Email Signature</div>
                                <div class="metric-value font-monospace"><?php echo $data->email; ?></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Direct Communication</div>
                                <div class="metric-value"><?php echo $data->contactNo; ?></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Biological Gender</div>
                                <div class="metric-value"><?php echo ucfirst($data->gender); ?></div>
                            </div>
                            <hr class="my-4 opacity-50">
                            <div class="metric-item">
                                <div class="metric-label">Primary Guardian</div>
                                <div class="metric-value"><?php echo $data->guardianName ?: 'N/A'; ?></div>
                                <div class="small text-muted fw-700 mt-1"><?php echo $data->guardianRelation; ?> • <?php echo $data->guardianContactno; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Logistics & Operations -->
                    <div class="col-lg-4">
                        <div class="data-card">
                            <div class="section-tag" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-shield-halved"></i> LOGISTICS DEPLOYMENT</div>
                            <?php if($data->roomno): ?>
                                <div class="metric-item">
                                    <div class="metric-label">Assigned Sector</div>
                                    <div class="metric-value">Room <?php echo $data->roomno; ?></div>
                                    <div class="small text-muted fw-700 mt-1"><?php echo $data->seater; ?>-Seater Configuration</div>
                                </div>
                                <div class="metric-item">
                                    <div class="metric-label">Financial commitment (PM)</div>
                                    <div class="metric-value text-success">TSH <?php echo number_format($data->feespm); ?></div>
                                </div>
                                <div class="metric-item">
                                    <div class="metric-label">Deployment Term</div>
                                    <div class="metric-value"><?php echo $data->duration; ?> Academic Months</div>
                                    <div class="small text-muted fw-700 mt-1">Commenced: <?php echo date('d M, Y', strtotime($data->stayfrom)); ?></div>
                                </div>
                                <div class="metric-item">
                                    <div class="metric-label">Nutrition Service</div>
                                    <div class="metric-value"><?php echo $data->foodstatus == 1 ? 'ACTIVE ENROLLMENT' : 'NULL (External Sourcing)'; ?></div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-satellite-dish fa-3x text-light mb-3"></i>
                                    <p class="h6 fw-800 text-muted">Awaiting Logistics Allocation</p>
                                </div>
                            <?php endif; ?>
                            <hr class="my-4 opacity-50">
                            <div class="metric-item">
                                <div class="metric-label">Last Known Location</div>
                                <div class="metric-value small lh-base"><?php echo $data->corresAddress; ?>, <?php echo $data->corresState; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Financial Oversight -->
                    <div class="col-lg-4">
                        <div class="data-card">
                            <div class="section-tag" style="background: #faf5ff; color: #a855f7;"><i class="fas fa-vault"></i> REVENUE OVERSIGHT</div>
                            <div class="control-pill">
                                <div class="metric-label">Academic Tuition Vault</div>
                                <div class="d-flex justify-content-between align-items-end mt-2">
                                    <div class="metric-value text-primary font-monospace"><?php echo $data->fee_control_no ?: 'PENDING_GEN'; ?></div>
                                    <div class="small fw-800 text-muted">TSH <?php echo number_format($data->fees_paid); ?> PAID</div>
                                </div>
                            </div>
                            <div class="control-pill">
                                <div class="metric-label">Accommodation Ledger</div>
                                <div class="d-flex justify-content-between align-items-end mt-2">
                                    <div class="metric-value text-primary font-monospace"><?php echo $data->acc_control_no ?: 'PENDING_GEN'; ?></div>
                                    <div class="small fw-800 text-muted">TSH <?php echo number_format($data->accommodation_paid); ?> PAID</div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-4 rounded-4 bg-light">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-white p-2 rounded-circle"><i class="fas fa-history text-muted"></i></div>
                                    <h6 class="fw-800 mb-0">Audit Timeline</h6>
                                </div>
                                <div class="small fw-700 text-muted border-start border-2 ps-3 py-1 mb-2">
                                    Registry Entry Created: <?php echo date('d M, Y', strtotime($data->regDate)); ?>
                                </div>
                                <div class="small fw-700 text-muted border-start border-2 ps-3 py-1">
                                    Last Profile Sync: <?php echo $data->updationDate ?: 'Initial Setup'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        function terminateStudent(regNo, name) {
            Swal.fire({
                title: 'Confirm Termination?',
                html: `You are about to permanently purge <b>${name}</b> from the system registry. This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Terminate',
                cancelButtonText: 'Cancel',
                background: '#fff',
                borderRadius: '24px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `manage-students.php?del=${regNo}&token=<?php echo generate_csrf_token(); ?>`;
                }
            });
        }
    </script>
</body>
</html>
