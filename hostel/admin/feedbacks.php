<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
	$id=intval($_GET['del']);
	$adn="delete from registration where regNo=?";
		$stmt= $mysqli->prepare($adn);
		$stmt->bind_param('i',$id);
        $stmt->execute();
        $stmt->close();	   


}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#f5f6fa">
    <title>Student Feedback | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/modern.css">
    
    <style>
        .feedback-item-row { 
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
        .feedback-item-row:hover {
            border-color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.1);
        }
        .feedback-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: #eef2ff; color: #6366f1; 
            font-size: 1.2rem; margin-right: 18px;
        }
        .info-card { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 10px; }
        .info-label { display: block; font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 3px; }
        .info-val { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
        .modal-content { border-radius: 24px; border: none; }
        .rating-chip {
            padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">Student Feedbacks</h2>
                        <p class="text-muted small mb-0">Listen to what students say about the hostel</p>
                    </div>
                </div>

                <div class="mb-4 position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="feedbackSearch" class="form-control ps-5 py-3 border-0 shadow-sm" placeholder="Search by student or rating..." style="border-radius: 16px;">
                </div>

                <div id="feedbackList">
                    <?php  
                    $ret="SELECT f.*, u.firstName, u.lastName, r.roomno, r.seater 
                          FROM feedback f 
                          JOIN userregistration u ON f.userId = u.id 
                          LEFT JOIN registration r ON u.regNo = r.regno 
                          ORDER BY f.postinDate DESC";
                    $res=$mysqli->query($ret);
                    if($res->num_rows > 0):
                        while($row=$res->fetch_object()):
                    ?>
                    <div class="feedback-item-row" onclick='openFeedbackInfo(<?php echo json_encode($row); ?>)'>
                        <div class="d-flex align-items-center">
                            <div class="feedback-icon">
                                <i class="fas fa-star<?php echo $row->OverallRating == 'Excellent' ? '' : '-half-alt'; ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName); ?></h6>
                                <small class="text-muted">Room <?php echo $row->roomno ?: 'N/A'; ?> • Overall: <b><?php echo $row->OverallRating; ?></b></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <?php if($row->adminRemark): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">REPLIED</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle mb-1">PENDING</span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted" style="font-size:0.7rem;"><?php echo date('d M Y', strtotime($row->postinDate)); ?></small>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <div class="bg-white rounded-4 p-5 text-center shadow-sm">
                        <i class="fas fa-comments text-muted fs-1 mb-3"></i>
                        <h4 class="fw-bold">No Feedback Yet</h4>
                        <p class="text-muted">New student feedback will appear here.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">Feedback Details</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="info-card h-100">
                                <label class="info-label">Student</label>
                                <div id="mStudent" class="info-val"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-card h-100">
                                <label class="info-label">Mess Rating</label>
                                <div id="mMess" class="info-val"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-card h-100">
                                <label class="info-label">Room Rating</label>
                                <div id="mRoom" class="info-val"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-card h-100">
                                <label class="info-label">Overall</label>
                                <div id="mOverall" class="info-val text-primary"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card mb-4">
                        <label class="info-label">Student Message</label>
                        <div id="mMessage" class="info-val" style="font-weight: 500; font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap;"></div>
                    </div>

                    <div id="replySection" class="border-top pt-4">
                        <form id="actionForm">
                            <input type="hidden" id="mFid" name="fid">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Admin Response</label>
                                <textarea name="remark" id="mRemark" class="form-control rounded-4 border-0 bg-light p-3" rows="4" placeholder="Type your response to the student..."></textarea>
                            </div>
                            <button type="button" onclick="submitReply()" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> Send Response
                            </button>
                        </form>
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
    function openFeedbackInfo(data) {
        document.getElementById('mFid').value = data.id;
        document.getElementById('mStudent').innerText = data.firstName + ' ' + data.lastName;
        document.getElementById('mMess').innerText = data.Mess;
        document.getElementById('mRoom').innerText = data.Room;
        document.getElementById('mOverall').innerText = data.OverallRating;
        document.getElementById('mMessage').innerText = data.FeedbackMessage || "No additional message provided.";
        document.getElementById('mRemark').value = data.adminRemark || "";
        
        const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
        modal.show();
    }

    function submitReply() {
        const formData = {
            action: 'reply_feedback',
            fid: $('#mFid').val(),
            remark: $('#mRemark').val()
        };

        if(!formData.remark.trim()) {
            Swal.fire('Error', 'Please enter a response message', 'error');
            return;
        }

        $.post('ajax/feedback-actions.php', formData, function(res) {
            const data = JSON.parse(res);
            if(data.status === 'success') {
                Swal.fire('Sent', 'Your response has been sent to the student', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', 'Failed: ' + data.msg, 'error');
            }
        });
    }

    $("#feedbackSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".feedback-item-row").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    </script>
</body>
</html>
