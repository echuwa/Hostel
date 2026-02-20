<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();
if(isset($_POST['submit']))
{
// Posted Values
$cid=$_GET['cid'];
$cstatus=$_POST['cstatus'];
$redproblem=$_POST['remark'];





// Query for insertion data into database
$query="insert into  complainthistory(complaintid,compalintStatus,complaintRemark) values(?,?,?)";
$stmt = $mysqli->prepare($query);
$rc=$stmt->bind_param('iss',$cid,$cstatus,$redproblem);
$stmt->execute();

$query1="update complaints set complaintStatus=? where id=?";
$stmt1 = $mysqli->prepare($query1);
$rc1=$stmt1->bind_param('si',$cstatus,$cid);
$stmt1->execute();
echo "<script>alert('Complaint Updated');</script>";
echo "<script type='text/javascript'> document.location = 'all-complaints.php'; </script>";
}



?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Complaint Details | HostelMS Admin</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="css/style.css">
    <style>
        .detail-card { border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.08); border:none; margin-bottom:20px; }
        .detail-card .card-header { border-radius:12px 12px 0 0; font-weight:700; font-size:0.95rem; }
        .detail-table th { background:#f8f9fc; font-weight:600; color:#495057; width:30%; }
        .detail-table td { color:#2d3748; }
        .history-table th { background:#e8f4f8; }
        .btn-action { background:linear-gradient(135deg,#4361ee,#7b2ff7); color:#fff; border:none; border-radius:8px; padding:10px 24px; font-weight:600; }
        .btn-action:hover { opacity:.9; color:#fff; }
    </style>
</head>

<body>

		<?php include('includes/header.php');?>

	<div class="ts-main-content">
		<?php include('includes/sidebar.php');?>
		<div class="content-wrapper">
			<div class="container-fluid" style="padding-top:20px;">

				<!-- Back button -->
				<div class="mb-3">
					<a href="all-complaints.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
						<i class="fas fa-arrow-left me-1"></i> Back to All Complaints
					</a>
				</div>

<?php	
$cid=$_GET['cid'];
$ret="select c.*, u.firstName, u.lastName, u.email, (SELECT roomno FROM registration WHERE emailid = u.email ORDER BY id DESC LIMIT 1) as roomno FROM complaints c JOIN userregistration u ON c.userId = u.id WHERE c.id=?";
$stmt= $mysqli->prepare($ret);
$stmt->bind_param('i',$cid);
$stmt->execute();
$res=$stmt->get_result();
$cnt=1;
if($row=$res->fetch_object()) {
    $cstatus = $row->complaintStatus;
?>
				<!-- Complaint Detail Card -->
				<div class="card detail-card" id="print">
					<div class="card-header" style="background:linear-gradient(135deg,#4361ee,#7b2ff7); color:#fff;">
						<i class="fas fa-file-alt me-2"></i>#<?php echo htmlspecialchars($row->ComplainNumber); ?> — Complaint Details
						<span onclick="CallPrint()" title="Print" style="float:right; cursor:pointer;">
							<i class="fas fa-print"></i>
						</span>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-6 mb-4">
								<h6 class="text-muted text-uppercase fw-bold mb-3" style="font-size:.75rem; letter-spacing:1px;">Complaint Info</h6>
								<table class="table detail-table">
									<tr><th>Complaint #</th><td><?php echo htmlspecialchars($row->ComplainNumber); ?></td></tr>
									<tr><th>Type</th><td><?php echo htmlspecialchars($row->complaintType); ?></td></tr>
									<tr><th>Date Filed</th><td><?php echo htmlspecialchars($row->registrationDate); ?></td></tr>
									<tr><th>Status</th><td>
										<?php
										if(empty($cstatus)):
										    echo '<span class="badge" style="background:#e74a3b;">New</span>';
										elseif(strtolower($cstatus)=='in process' || strtolower($cstatus)=='in progress'):
										    echo '<span class="badge" style="background:#f6c23e; color:#333;">In Process</span>';
										elseif(strtolower($cstatus)=='closed'):
										    echo '<span class="badge" style="background:#1cc88a;">Closed</span>';
										else:
										    echo '<span class="badge bg-secondary">'.htmlspecialchars($cstatus).'</span>';
										endif;
										?>
									</td></tr>
									<tr><th>Attachment</th><td>
										<?php echo (!empty($row->complaintDoc)) ? '<a href="../comnplaintdoc/'.$row->complaintDoc.'" target="_blank"><i class="fas fa-paperclip"></i> View File</a>' : 'None'; ?>
									</td></tr>
								</table>
							</div>
							<div class="col-md-6 mb-4">
								<h6 class="text-muted text-uppercase fw-bold mb-3" style="font-size:.75rem; letter-spacing:1px;">Student Info</h6>
								<table class="table detail-table">
									<tr><th>Student Name</th><td><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?></td></tr>
									<tr><th>Email</th><td><?php echo htmlspecialchars($row->email ?? '—'); ?></td></tr>
									<tr><th>Room No</th><td><?php echo htmlspecialchars($row->roomno ?? '—'); ?></td></tr>
								</table>
							</div>
						</div>
						<!-- Complaint Details text -->
						<h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size:.75rem; letter-spacing:1px;">Complaint Description</h6>
						<div style="background:#f8f9fc; border-radius:8px; padding:16px; color:#444; margin-bottom:16px;">
							<?php echo nl2br(htmlspecialchars($row->complaintDetails ?? 'No description provided.')); ?>
						</div>
<?php } ?>

						<!-- Complaint History -->
						<?php
						$query = "SELECT * FROM complainthistory WHERE complaintid=? ORDER BY postingDate DESC";
						$stmt1 = $mysqli->prepare($query);
						$stmt1->bind_param('i', $cid);
						$stmt1->execute();
						$res1 = $stmt1->get_result();
						?>
						<h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size:.75rem; letter-spacing:1px;">Action History</h6>
						<?php if($res1->num_rows > 0): ?>
						<table class="table history-table table-bordered table-sm">
							<thead><tr><th>Remark</th><th>Status Set</th><th>Date</th></tr></thead>
							<tbody>
							<?php while($row1 = $res1->fetch_object()): ?>
							<tr>
								<td><?php echo htmlspecialchars($row1->complaintRemark); ?></td>
								<td><?php echo htmlspecialchars($row1->compalintStatus); ?></td>
								<td><?php echo htmlspecialchars($row1->postingDate); ?></td>
							</tr>
							<?php endwhile; ?>
							</tbody>
						</table>
						<?php else: ?>
						<p class="text-muted"><i class="fas fa-info-circle me-1"></i>No actions taken yet.</p>
						<?php endif; ?>

						<!-- Take Action button (only if not closed) -->
						<?php if(empty($cstatus) || strtolower($cstatus) == 'in process' || strtolower($cstatus) == 'in progress'): ?>
						<button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#takeActionModal">
							<i class="fas fa-gavel me-1"></i> Take Action
						</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>



<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Take Action</h4>
      </div>
      <form method="post">
      <div class="modal-body">
        <p><select name="cstatus" class="form-control" required>
        	<option value="">Select Status</option>
        	<option value="In Process">In Process</option>
        	<option value="Closed">Closed</option>
        </select></p>
        <p><textarea name="remark" id="remark" placeholder="Remark or Messgae" rows="6" class="form-control"></textarea></p>
        <p><input type="submit" name="submit" Value="Submit" class="btn btn-primary"></p>
      </div>
  </form>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

	<!-- Loading Scripts -->
	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap-select.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery.dataTables.min.js"></script>
	<script src="js/dataTables.bootstrap.min.js"></script>
	<script src="js/Chart.min.js"></script>
	<script src="js/fileinput.js"></script>
	<script src="js/chartData.js"></script>
	<script src="js/main.js"></script>
 <script>
$(function () {
$("[data-toggle=tooltip]").tooltip();
    });
function CallPrint(strid) {
var prtContent = document.getElementById("print");
var WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
WinPrint.document.write(prtContent.innerHTML);
WinPrint.document.close();
WinPrint.focus();
WinPrint.print();
}
</script>
</body>

</html>
