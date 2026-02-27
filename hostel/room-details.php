<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details | Hostel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-modern.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
        }
        .detail-item {
            padding: 24px;
            background: #f8fafc;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s;
        }
        .detail-item:hover {
            background: #fff;
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
        }
        .detail-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray);
            font-weight: 800;
            margin-bottom: 8px;
        }
        .detail-value {
            font-weight: 800;
            color: var(--dark);
            font-size: 1.05rem;
        }
        .info-section-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        .info-section-title i {
            width: 32px;
            height: 32px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 0.9rem;
        }
        @media print {
            .ts-sidebar, .header-modern, .btn-modern, .print-hidden {
                display: none !important;
            }
            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card-modern {
                box-shadow: none !important;
                border: 1px solid #eee !important;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeInLeft">
                    <div>
                        <h2 class="section-title">My Room Information</h2>
                        <p class="section-subtitle">Comprehensive breakdown of your stay and allocation</p>
                    </div>
                    <button onclick="window.print()" class="btn-modern btn-modern-primary px-4 shadow-lg print-hidden">
                        <i class="fas fa-print me-2"></i> Print Slip
                    </button>
                </div>

                <?php
                $aid = $_SESSION['user_id'] ?? $_SESSION['id'];
                $ret = "SELECT r.* FROM registration r JOIN userregistration u ON r.regno = u.regNo WHERE u.id = ? ORDER BY r.id DESC LIMIT 1";
                $stmt = $mysqli->prepare($ret);
                $stmt->bind_param('i', $aid);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if($res->num_rows > 0):
                    while($row = $res->fetch_object()):
                        $totalFees = $row->feespm * $row->duration;
                ?>
                
                <div class="row g-4" id="printContent">
                    <div class="col-xl-9">
                        <!-- Primary Allocation Details -->
                        <div class="card-modern border-0 mb-4 animate__animated animate__fadeInUp">
                            <div class="p-4 rounded-top-4" style="background: var(--gradient-primary); color: white;">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-white bg-opacity-20 p-3 rounded-circle me-4">
                                                <i class="fas fa-hotel fs-3"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-1 fw-800">Room <?php echo $row->roomno; ?></h4>
                                                <div class="small fw-700 opacity-75">
                                                    <i class="fas fa-calendar-check me-1"></i> Allocation Confirmed on <?php echo date('d M Y', strtotime($row->postingDate)); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <div class="bg-white bg-opacity-20 d-inline-block p-3 rounded-4">
                                            <div class="small fw-700 opacity-75 text-uppercase">Total Fees</div>
                                            <div class="h4 mb-0 fw-800">TSH <?php echo number_format($totalFees); ?>/=</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body-modern p-5">
                                <!-- Section 1: Official Info -->
                                <div class="info-section-title">
                                    <i class="fas fa-id-card"></i> Official Documents
                                </div>
                                <div class="row g-4 mb-5">
                                    <div class="col-md-4">
                                        <div class="detail-label">Reg Number</div>
                                        <div class="detail-value"><?php echo $row->regno; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Full Name</div>
                                        <div class="detail-value"><?php echo strtoupper($row->firstName . ' ' . $row->middleName . ' ' . $row->lastName); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Course Applied</div>
                                        <div class="detail-value"><?php echo $row->course; ?></div>
                                    </div>
                                </div>

                                <!-- Section 2: Room specifics -->
                                <div class="info-section-title">
                                    <i class="fas fa-bed"></i> Room & Stay Information
                                </div>
                                <div class="row g-4 mb-5">
                                    <div class="col-md-4">
                                        <div class="detail-label">Configuration</div>
                                        <div class="detail-value"><?php echo $row->seater; ?> Seater Room</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Monthly Rate</div>
                                        <div class="detail-value text-primary">TSH <?php echo number_format($row->feespm); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Food Opt-in</div>
                                        <div class="detail-value">
                                            <?php if($row->foodstatus == 1): ?>
                                                <span class="text-success"><i class="fas fa-utensils me-1"></i> Included</span>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-times-circle me-1"></i> Excluded</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Stay Starts</div>
                                        <div class="detail-value"><?php echo date('d M, Y', strtotime($row->stayfrom)); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Duration</div>
                                        <div class="detail-value"><?php echo $row->duration; ?> Months</div>
                                    </div>
                                </div>

                                <!-- Section 3: Contact & Emergency -->
                                <div class="info-section-title">
                                    <i class="fas fa-phone-alt"></i> Contact & Guardians
                                </div>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4">
                                        <div class="detail-label">Student Contact</div>
                                        <div class="detail-value"><?php echo $row->contactno; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Guardian Name</div>
                                        <div class="detail-value"><?php echo $row->guardianName; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Emergency Number</div>
                                        <div class="detail-value text-danger fw-800"><?php echo $row->egycontactno; ?></div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-4" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="detail-label">Permanent Address</div>
                                            <div class="fw-700 text-dark"><?php echo $row->pmntAddress; ?></div>
                                            <div class="small text-muted mt-1"><?php echo $row->pmntCity; ?>, <?php echo $row->pmnatetState; ?> - <?php echo $row->pmntPincode; ?></div>
                                        </div>
                                        <div class="col-md-6 border-start-md">
                                            <div class="detail-label">Correspondence Address</div>
                                            <div class="fw-700 text-dark"><?php echo $row->corresAddress; ?></div>
                                            <div class="small text-muted mt-1"><?php echo $row->corresCIty; ?>, <?php echo $row->corresState; ?> - <?php echo $row->corresPincode; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 print-hidden">
                        <div class="card-modern border-0 mb-4 animate__animated animate__fadeInRight">
                            <div class="card-body-modern p-4">
                                <h6 class="fw-800 mb-4">Quick Actions</h6>
                                <div class="d-grid gap-3">
                                    <a href="pay-fees.php" class="btn-modern btn-modern-primary py-3 justify-content-center">
                                        <i class="fas fa-wallet"></i> VIEW PAYMENTS
                                    </a>
                                    <a href="register-complaint.php" class="btn-modern btn-modern-outline py-3 justify-content-center">
                                        <i class="fas fa-tools"></i> LODGE COMPLAINT
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-modern border-0 bg-light animate__animated animate__fadeInRight" style="animation-delay: 0.1s">
                            <div class="card-body-modern p-4">
                                <h6 class="fw-800 text-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Security Policy
                                </h6>
                                <p class="small text-muted mb-0">
                                    All personal details must match your legal documents. Any discrepancy should be reported to the Warden's office within 48 hours of allocation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endwhile; else: ?>
                
                <div class="card-modern border-0 p-5 text-center bg-white shadow-sm animate__animated animate__zoomIn">
                    <div class="bg-gray-light p-4 rounded-circle d-inline-flex mb-4">
                        <i class="fas fa-user-slash fa-3x text-gray"></i>
                    </div>
                    <h4 class="fw-800">No Active Allocation</h4>
                    <p class="text-muted mx-auto mb-4" style="max-width: 400px;">
                        Our records show you don't have an active room allocation. If you just booked, please wait for admin approval.
                    </p>
                    <a href="book-hostel.php" class="btn-modern btn-modern-primary d-inline-flex mx-auto">
                        <i class="fas fa-plus-circle me-2"></i>Apply for a Room
                    </a>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>