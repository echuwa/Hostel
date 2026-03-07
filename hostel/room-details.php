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
            /* Hide ALL navigation and UI chrome */
            .brand, .ts-sidebar, #sidebar, nav, .sidebar-mobile-toggle,
            .header-modern, .btn-modern, .print-hidden, .ts-profile-nav,
            .col-xl-3, .d-flex.justify-content-between { 
                display: none !important; 
            }
            
            /* Full-page content */
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .ts-main-content, .content-wrapper {
                margin: 0 !important;
                padding: 10px !important;
                width: 100% !important;
                display: block !important;
            }
            
            /* Row becomes full width */
            .row.g-4#printContent { display: block !important; }
            .col-xl-9 { width: 100% !important; max-width: 100% !important; flex: none !important; padding: 0 !important; }
            
            /* Cards */
            .card-modern {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                break-inside: avoid;
            }
            
            /* Colors preserved */
            .bg-light {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Print header */
            .print-header-show {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 15px;
            }

            /* Fix extra page issue */
            html, body {
                height: auto !important;
                overflow: visible !important;
            }
            .ts-main-content {
                display: block !important;
            }
            
            /* Signature block */
            .print-signatures {
                margin-top: 50px !important;
                page-break-inside: avoid;
            }
        }
        .print-header-show { display: none; }
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
                $ret = "SELECT r.*, u.fee_status, u.profile_pic FROM registration r JOIN userregistration u ON r.regno = u.regNo WHERE u.id = ? ORDER BY r.id DESC LIMIT 1";
                $stmt = $mysqli->prepare($ret);
                $stmt->bind_param('i', $aid);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if($res->num_rows > 0):
                    while($row = $res->fetch_object()):
                        $totalFees = $row->feespm * $row->duration;
                        $fee_status = $row->fee_status;
                        
                        // Check if student is eligible to view their room (Must have paid fully)
                        if ($fee_status == 1):
                ?>
                
                <div class="row g-4" id="printContent">
                    <div class="col-xl-9">
                        
                        <!-- Official Print Header (Only visible on print) -->
                        <div class="print-header-show">
                            <h2 style="margin: 0; font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif;">Hostel Management System</h2>
                            <p style="margin: 5px 0; font-size: 14px;"><strong>Official Room Allocation & Tenancy Agreement</strong></p>
                            <p style="margin: 0; font-size: 12px; color: #666;">Issued on: <?php echo date('F j, Y'); ?></p>
                        </div>

                        <!-- Primary Allocation Details -->
                        <div class="card-modern border-0 mb-4 animate__animated animate__fadeInUp">
                            <div class="p-4 rounded-top-4" style="background: var(--gradient-primary); color: white;">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                                        <?php if (!empty($row->profile_pic)): ?>
                                            <img src="<?php echo htmlspecialchars($row->profile_pic); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail shadow-sm print-px" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3); background-color: white;">
                                        <?php else: ?>
                                            <div class="p-3 rounded-circle d-inline-flex mx-auto text-white shadow-sm print-px" style="background: rgba(255,255,255,0.2); width: 100px; height: 100px; align-items: center; justify-content: center;">
                                                <i class="fas fa-user-graduate" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 text-center text-md-start">
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                                            <div class="p-2 rounded-circle me-3 text-white" style="background: rgba(255,255,255,0.2);">
                                                <i class="fas fa-hotel fs-4"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-0 fw-800 text-white">Room <?php echo htmlspecialchars($row->roomno); ?></h4>
                                            </div>
                                        </div>
                                        <div class="small fw-700 opacity-75 text-white">
                                            <i class="fas fa-calendar-check me-1"></i> Allocation Confirmed on <?php echo ($row->postingDate && $row->postingDate != '0000-00-00 00:00:00') ? date('d M Y', strtotime($row->postingDate)) : 'Awaiting Confirmation'; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                                        <div class="d-inline-block p-3 rounded-4 text-white" style="background: rgba(255,255,255,0.2);">
                                            <div class="small fw-700 opacity-75 text-uppercase text-white">Total Fees</div>
                                            <div class="h4 mb-0 fw-800 text-white">TSH <?php echo number_format($totalFees); ?>/=</div>
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
                                        <div class="detail-value"><?php echo strtoupper($row->firstName . ' ' . $row->lastName); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Course Applied</div>
                                        <div class="detail-value">
                                            <?php 
                                            if (empty($row->course) || $row->course == '0') {
                                                echo '<span class="text-muted fst-italic fs-6">Not Provided</span>';
                                            } else {
                                                echo htmlspecialchars($row->course); 
                                            }
                                            ?>
                                        </div>
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
                                        <div class="detail-value"><?php echo $row->contactno ?: '<span class="text-muted fst-italic fs-6">Not Provided</span>'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Guardian Name</div>
                                        <div class="detail-value"><?php echo $row->guardianName ?: '<span class="text-muted fst-italic fs-6">Not Provided</span>'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-label">Emergency Number</div>
                                        <div class="detail-value text-danger fw-800"><?php echo $row->egycontactno ?: '<span class="text-muted fst-italic fs-6">Not Provided</span>'; ?></div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-4" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="detail-label">Permanent Address</div>
                                            <div class="fw-700 text-dark">
                                                <?php 
                                                if (isset($row->corresAddress) && strpos($row->corresAddress, 'GPS') !== false) {
                                                    echo htmlspecialchars($row->corresAddress);
                                                } else {
                                                    echo $row->pmntAddress ?: '<span class="text-muted fst-italic fs-6">Not Provided</span>'; 
                                                }
                                                ?>
                                            </div>
                                            <?php if($row->pmntState || $row->pmntCountry): ?>
                                            <div class="small text-muted mt-1"><?php echo htmlspecialchars($row->pmntState); ?> <?php echo $row->pmntCountry ? '('.$row->pmntCountry.')' : ''; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 border-start-md">
                                            <div class="detail-label">Correspondence Address</div>
                                            <div class="fw-700 text-dark"><?php echo $row->corresAddress ?: '<span class="text-muted fst-italic fs-6">Not Provided</span>'; ?></div>
                                            <?php if($row->corresState || $row->corresCountry): ?>
                                            <div class="small text-muted mt-1"><?php echo htmlspecialchars($row->corresState); ?> <?php echo $row->corresCountry ? '('.$row->corresCountry.')' : ''; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Section 4: Contract Agreement -->
                                <div class="info-section-title mt-5">
                                    <i class="fas fa-file-contract"></i> Hostel Tenancy Agreement
                                </div>
                                <div class="p-4 rounded-4 bg-light mb-4 text-dark" style="font-size: 0.85rem; line-height: 1.6; border-left: 4px solid var(--primary);">
                                    <h6 class="fw-800 text-uppercase mb-3">Terms and Conditions of Residency</h6>
                                    <ol class="ps-3 mb-4 text-muted">
                                        <li class="mb-2"><strong>Compliance with Rules:</strong> I, the undersigned student, agree to abide by all the rules and regulations of the Hostel Management as stipulated in the student handbook.</li>
                                        <li class="mb-2"><strong>Payment of Fees:</strong> I understand that my allocation is contingent upon the full payment of prescribed fees. Failure to pay may result in immediate eviction.</li>
                                        <li class="mb-2"><strong>Property Damage:</strong> I shall be held personally and financially responsible for any damages caused to hostel property, furniture, or fixtures allocated to me. The management reserves the right to impose fines for willful destruction.</li>
                                        <li class="mb-2"><strong>Disciplinary Action:</strong> Any involvement in illegal activities, substance abuse, or acts that disrupt the peace of the hostel will lead to immediate expulsion and disciplinary action by the University disciplinary committee.</li>
                                        <li class="mb-2"><strong>Right of Entry:</strong> Management reserves the right to enter and inspect the room at any time for maintenance, security, or disciplinary checks without prior notice.</li>
                                    </ol>
                                    
                                    <div class="row mt-5 pt-3 border-top border-dark border-opacity-10 print-signatures">
                                        <div class="col-6">
                                            <div class="mb-4">______________________________________</div>
                                            <div class="fw-800 text-uppercase"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?></div>
                                            <div class="small fw-700 text-muted">Student Signature</div>
                                            <div class="small text-muted mt-1">Date: ________________________</div>
                                        </div>
                                        <div class="col-6 text-end">
                                            <div class="mb-4 d-inline-block text-center">
                                                <div style="width: 200px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                                            </div>
                                            <div class="fw-800 text-uppercase">Warden / Management</div>
                                            <div class="small fw-700 text-muted">Authorized Signature & Stamp</div>
                                            <div class="small text-muted mt-1">Date: ________________________</div>
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
                
                <?php else: ?>
                <!-- PAYMENT REQUIRED LOCK SCREEN -->
                <div class="card-modern border-0 p-5 text-center bg-white shadow-sm animate__animated animate__zoomIn">
                    <div class="bg-danger bg-opacity-10 p-4 rounded-circle d-inline-flex mb-4 text-danger">
                        <i class="fas fa-lock fa-3x"></i>
                    </div>
                    <h4 class="fw-800 text-dark">Access Denied: Room Locked</h4>
                    <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                        Congratulations! You have been allocated a room by the administration. However, your detailed room slip is currently locked. To unlock and view your room details, please complete your fee payments (Registration, Tuition, and Accommodation).
                    </p>
                    <a href="pay-fees.php" class="btn-modern btn-modern-success d-inline-flex mx-auto fw-800 px-4 py-3">
                        <i class="fas fa-wallet me-2 fs-5"></i>Proceed to Payments
                    </a>
                </div>
                <!-- END LOCK SCREEN -->
                <?php 
                        endif; 
                    endwhile; 
                else: 
                ?>
                
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
                
                <?php endif; // closes if($res->num_rows > 0) ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>