<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
	$id=intval($_GET['del']);
	$adn="delete from feedback where id=?";
		$stmt= $mysqli->prepare($adn);
		$stmt->bind_param('i',$id);
        $stmt->execute();
        $stmt->close();	   


}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Feedback Hub | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Unified Admin CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .feedback-card {
            background: #fff; border-radius: 20px; padding: 25px;
            margin-bottom: 20px; border: 1px solid #f1f5f9;
            transition: all 0.3s; cursor: pointer; position: relative;
        }
        .feedback-card:hover { transform: translateY(-3px); border-color: var(--primary); box-shadow: 0 10px 30px rgba(67, 97, 238, 0.08); }
        
        .status-pill {
            position: absolute; top: 25px; right: 25px;
            padding: 4px 12px; border-radius: 50px; font-size: 0.65rem; font-weight: 800;
        }
        .status-replied { background: #dcfce7; color: #16a34a; }
        .status-pending { background: #fffbeb; color: #d97706; }
        
        .rating-stars { color: #fbbf24; font-size: 0.85rem; }
        .student-meta { font-size: 0.75rem; font-weight: 700; color: #64748b; margin-top: 5px; }
        
        .message-preview {
            font-size: 0.9rem; color: #334155; margin-top: 15px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <div class="main-content">
            <div class="content-wrapper">
                
                <div class="header-section mb-5 animate__animated animate__fadeIn">
                    <h2 class="fw-800 mb-1">Feedback Intelligence</h2>
                    <p class="text-muted fw-600 mb-0">Aggregate student sentiment, service ratings, and direct suggestions.</p>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-light">
                            <div class="small fw-800 text-muted mb-1">TOTAL ENTRIES</div>
                            <div class="h3 fw-800 text-dark"><?php echo $mysqli->query("SELECT count(*) FROM feedback")->fetch_row()[0]; ?></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-light">
                            <div class="small fw-800 text-muted mb-1">AVG SATISFACTION</div>
                            <div class="h3 fw-800 text-primary">84%</div>
                        </div>
                    </div>
                </div>

                <div class="row" id="feedbackContainer">
                    <?php  
                    // USING LEFT JOIN TO ENSURE NO FEEDBACK IS HIDDEN (even if user is deleted or NULL)
                    $ret="SELECT f.*, u.id as studentId, u.firstName, u.lastName, u.regNo, r.roomno 
                          FROM feedback f 
                          LEFT JOIN userregistration u ON f.userId = u.id 
                          LEFT JOIN registration r ON u.regNo = r.regno 
                          ORDER BY f.postinDate DESC";
                    $res=$mysqli->query($ret);
                    if($res->num_rows > 0):
                        while($row=$res->fetch_object()):
                    ?>
                    <div class="col-md-6 feedback-item" data-search="<?php echo strtolower($row->firstName.' '.$row->lastName.' '.$row->FeedbackMessage); ?>">
                        <div class="feedback-card" onclick='openFeedbackInfo(<?php echo json_encode($row); ?>)'>
                            <span class="status-pill <?php echo $row->adminRemark ? 'status-replied' : 'status-pending'; ?>">
                                <?php echo $row->adminRemark ? 'RESPONSE SENT' : 'REVIEW PENDING'; ?>
                            </span>

                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-800 me-3" style="width:48px; height:48px;">
                                    <?php echo $row->firstName ? substr($row->firstName,0,1) : '?'; ?>
                                </div>
                                <div>
                                    <div class="fw-800 text-dark mb-0"><?php echo htmlspecialchars($row->firstName . ' ' . $row->lastName ?: 'Anonymous Resident'); ?></div>
                                    <div class="student-meta"><?php echo $row->regNo ?: 'Orphaned ID'; ?> • Room <?php echo $row->roomno ?: 'N/A'; ?></div>
                                </div>
                            </div>

                            <div class="rating-stars mb-2">
                                <?php 
                                $stars = $row->OverallRating == 'Excellent' ? 5 : ($row->OverallRating == 'Very Good' ? 4 : ($row->OverallRating == 'Good' ? 3 : 2));
                                for($s=0; $s<5; $s++) echo '<i class="fa'.($s<$stars?'s':'r').' fa-star pe-1"></i>';
                                ?>
                                <span class="ms-2 fw-800 text-muted small"><?php echo $row->OverallRating; ?></span>
                            </div>

                            <div class="message-preview">
                                <?php echo htmlspecialchars($row->FeedbackMessage ?: 'No written comment provided.'); ?>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                                <span class="small fw-700 text-muted"><?php echo date('D, d M Y', strtotime($row->postinDate)); ?></span>
                                <span class="text-primary fw-800 small">Read Full Thread <i class="fas fa-arrow-right ms-1"></i></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-comment-slash fa-4x text-light mb-4"></i>
                        <h4 class="fw-800 text-muted">No Signal Detected</h4>
                        <p class="text-muted">Student feedback frequencies are currently offline.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Intelligence Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white p-4 border-0">
                    <h5 class="modal-title fw-800"><i class="fas fa-brain me-2"></i> Feedback Analysis & Command</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-4">
                                <label class="info-label small fw-800 text-muted d-block mb-1">Warden Access</label>
                                <div id="m-warden" class="fw-800 text-dark"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-4">
                                <label class="info-label small fw-800 text-muted d-block mb-1">Mess Quality</label>
                                <div id="m-mess" class="fw-800 text-dark"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-4">
                                <label class="info-label small fw-800 text-muted d-block mb-1">Surroundings</label>
                                <div id="m-env" class="fw-800 text-dark"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <h6 class="fw-800 text-muted small mb-3">CONVERSATION LOG</h6>
                        <div class="p-4 bg-light rounded-4 border-start border-4 border-primary">
                            <div id="m-msg" class="text-dark fst-italic" style="line-height: 1.6;"></div>
                        </div>
                    </div>

                    <form id="replyForm" action="" method="POST">
                        <input type="hidden" name="fid" id="m-fid">
                        <div class="mb-4">
                            <label class="form-label fw-800 small text-muted mb-2">MANAGEMENT REMARK</label>
                            <textarea name="remark" id="m-remark" class="form-control rounded-4 border-2 p-3" rows="4" placeholder="Draft your response..."></textarea>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-modern btn-modern-primary flex-grow-1 py-3 fw-800" onclick="submitReply()">
                                <i class="fas fa-paper-plane me-2"></i> DEPLOY RESPONSE
                            </button>
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-800 text-danger" onclick="deleteFeedback()">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </form>
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
        document.getElementById('m-fid').value = data.id;
        document.getElementById('m-warden').innerText = data.AccessibilityWarden;
        document.getElementById('m-mess').innerText = data.Mess;
        document.getElementById('m-env').innerText = data.HostelSurroundings;
        document.getElementById('m-msg').innerText = data.FeedbackMessage || "No additional commentary provided.";
        document.getElementById('m-remark').value = data.adminRemark || "";
        
        const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
        modal.show();
    }

    function submitReply() {
        const fid = $('#m-fid').val();
        const remark = $('#m-remark').val();
        
        if(!remark.trim()) {
            Swal.fire('Error', 'Please provide a response remark.', 'error');
            return;
        }

        Swal.fire({
            title: 'Submitting Response',
            text: 'Relaying message to resident...',
            didOpen: () => Swal.showLoading()
        });

        // For now using traditional post to self or AJAX
        $.post('ajax/feedback-actions.php', { action: 'reply_feedback', fid, remark }, function(res) {
            Swal.fire('Success!', 'Response telah diproses.', 'success').then(() => location.reload());
        });
    }

    function deleteFeedback() {
        const fid = $('#m-fid').val();
        Swal.fire({
            title: 'Terminate Entry?',
            text: 'This data will be permanently purged from the audit log.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'feedbacks.php?del=' + fid;
            }
        });
    }
    </script>
</body>
</html>

