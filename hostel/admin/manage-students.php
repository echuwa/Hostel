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
        echo "<script>alert('Student account activated successfully');</script>";
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
        echo "<script>alert('Fee status updated successfully');</script>";
        echo "<script>window.location.href='manage-students.php';</script>";
    }
    $stmt->close();	   
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="theme-color" content="#4361ee">
	<title>Students Directory | Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern Styling -->
	<link rel="stylesheet" href="css/modern.css">
    
    <style>
        .student-item-row { 
            background: #fff; 
            margin-bottom: 12px; 
            padding: 16px 24px; 
            border-radius: 16px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .student-item-row:hover {
            border-color: #4361ee;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }
        .student-avatar {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1rem; margin-right: 18px;
        }
        .avatar-m { background: #eff6ff; color: #3b82f6; }
        .avatar-f { background: #fff1f2; color: #e11d48; }

        .metric-label { font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .metric-value { font-size: 1.4rem; font-weight: 800; color: #1e293b; }
        
        /* Modal Customization */
        .modal-content { border-radius: 24px; border: none; }
        .info-card { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 10px; }
        .info-label { display: block; font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 3px; }
        .info-val { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                <!-- Mini Header -->
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">Students Directory</h2>
                        <p class="text-muted small mb-0">Manage all students from one place</p>
                    </div>
                </div>

                <?php 
                $total_stud = $mysqli->query("SELECT COUNT(*) FROM userregistration")->fetch_row()[0];
                $active_stud = $mysqli->query("SELECT COUNT(*) FROM userregistration WHERE status='Active'")->fetch_row()[0];
                $no_room = $mysqli->query("SELECT COUNT(*) FROM userregistration u LEFT JOIN registration r ON u.regNo = r.regno WHERE r.regno IS NULL")->fetch_row()[0];
                ?>

                <!-- Simple Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="bg-white p-3 rounded-4 border text-center">
                            <div class="metric-label">Registered</div>
                            <div class="metric-value"><?php echo $total_stud; ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white p-3 rounded-4 border text-center">
                            <div class="metric-label">Active</div>
                            <div class="metric-value text-success"><?php echo $active_stud; ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white p-3 rounded-4 border text-center">
                            <div class="metric-label">Pending Room</div>
                            <div class="metric-value text-warning"><?php echo $no_room; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-3 position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="studentSearch" class="form-control ps-5 py-3 border-0 shadow-sm" placeholder="Search by name or registration number..." style="border-radius: 16px;">
                </div>

                <!-- Simple List -->
                <div id="studentList">
                    <?php	
                    $ret="SELECT u.id, u.regNo, u.firstName, u.middleName, u.lastName, u.contactNo, u.status, u.gender, u.fee_status, u.payment_status, r.roomno, r.seater 
                          FROM userregistration u 
                          LEFT JOIN registration r ON u.regNo = r.regno 
                          ORDER BY u.id DESC";
                    $res=$mysqli->query($ret);
                    while($row=$res->fetch_object()) {
                        $fn = htmlspecialchars($row->firstName . ' ' . $row->lastName);
                    ?>
                    <div class="student-item-row" onclick='openStudentInfo(<?php echo json_encode($row); ?>)'>
                        <div class="d-flex align-items-center">
                            <div class="student-avatar <?php echo $row->gender == 'female' ? 'avatar-f' : 'avatar-m'; ?>">
                                <?php echo substr($row->firstName, 0, 1) . substr($row->lastName, 0, 1); ?>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo $fn; ?></h6>
                                <small class="text-muted"><?php echo $row->regNo; ?></small>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-light"></i>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SIMPLE MODAL FOR STUDENT INFO -->
    <div class="modal fade" id="infoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div id="mAvatar" class="mx-auto student-avatar mb-3" style="width:70px; height:70px; border-radius:20px; font-size:1.5rem;"></div>
                        <h4 id="mName" class="fw-bold mb-1"></h4>
                        <span id="mReg" class="badge bg-light text-muted px-3"></span>
                    </div>

                    <div class="info-card">
                        <label class="info-label">Room Allocation</label>
                        <div id="mRoom" class="info-val text-primary"></div>
                    </div>
                    <div class="info-card">
                        <label class="info-label">Payment Status</label>
                        <div id="mPayment" class="info-val"></div>
                    </div>
                    <div class="info-card">
                        <label class="info-label">Account Status</label>
                        <div id="mStatus" class="info-val"></div>
                    </div>

                    <div class="mt-4" id="mActions">
                        <!-- Actions -->
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
    function openStudentInfo(data) {
        const modal = new bootstrap.Modal(document.getElementById('infoModal'));
        
        // Setup Avatar
        const avatar = document.getElementById('mAvatar');
        avatar.className = `mx-auto student-avatar mb-3 avatar-${data.gender === 'female' ? 'f' : 'm'}`;
        avatar.innerText = data.firstName.charAt(0) + data.lastName.charAt(0);
        
        document.getElementById('mName').innerText = data.firstName + ' ' + data.lastName;
        document.getElementById('mReg').innerText = data.regNo;
        document.getElementById('mRoom').innerText = data.roomno ? `Room ${data.roomno} (${data.seater} Seater)` : 'Not Assigned';
        
        const payEl = document.getElementById('mPayment');
        payEl.innerText = data.fee_status == 1 ? 'Eligible' : 'Ineligible';
        payEl.className = `info-val text-${data.fee_status == 1 ? 'success' : 'danger'}`;
        
        const statEl = document.getElementById('mStatus');
        const isActive = data.status.toLowerCase() === 'active';
        statEl.innerText = isActive ? 'Active' : data.status;
        statEl.className = `info-val text-${isActive ? 'success' : 'warning'}`;
        
        const actionHtml = `
            <a href="student-details.php?id=${data.id}" class="btn btn-primary w-100 rounded-4 py-2 fw-bold mb-2 shadow-sm">View Full Profile</a>
            <div class="d-flex gap-2 mb-2">
                <a href="manage-students.php?toggle_fee=${data.regNo}" class="btn btn-light border w-50 rounded-4 py-2 small fw-bold">Toggle Fee</a>
                <a href="manage-students.php?del=${data.regNo}" onclick="return confirm('Delete Student?')" class="btn btn-outline-danger w-50 rounded-4 py-2 small fw-bold">Delete</a>
            </div>
            ${!isActive ? `<a href="manage-students.php?approve=${data.regNo}" class="btn btn-success w-100 rounded-4 py-2 fw-bold">Activate Account</a>` : ''}
        `;
        document.getElementById('mActions').innerHTML = actionHtml;
        
        modal.show();
    }

    // Live Search
    $("#studentSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".student-item-row").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    </script>
</body>
</html>
