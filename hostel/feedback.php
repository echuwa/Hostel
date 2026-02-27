<?php
session_start();
include('includes/config.php');
date_default_timezone_set('Asia/Kolkata');
include('includes/checklogin.php');
check_login();
$aid = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

if(isset($_POST['submit'])) {
    if(!$aid) {
        // Fallback: try to find user id by email if session aid is missing
        if(isset($_SESSION['login'])) {
            $uemail = $_SESSION['login'];
            $find_stmt = $mysqli->prepare("SELECT id FROM userregistration WHERE email = ?");
            $find_stmt->bind_param('s', $uemail);
            $find_stmt->execute();
            $find_stmt->bind_result($aid);
            $find_stmt->fetch();
            $find_stmt->close();
        }
    }
    // Sanitize inputs
    $acceswardent = htmlspecialchars(trim($_POST['acceswardent']));
    $accesmember = htmlspecialchars(trim($_POST['accesmember']));
    $redproblem = htmlspecialchars(trim($_POST['redproblem']));
    $Room = htmlspecialchars(trim($_POST['Room']));
    $Mess = htmlspecialchars(trim($_POST['Mess']));
    $hstelsor = htmlspecialchars(trim($_POST['hstelsor']));
    $overall = htmlspecialchars(trim($_POST['overall']));
    $feedback = htmlspecialchars(trim($_POST['feedback']));

    // Insert into database
    $query = "INSERT INTO feedback(AccessibilityWarden, AccessibilityMember, RedressalProblem, Room, Mess, HostelSurroundings, OverallRating, FeedbackMessage, userId) VALUES(?,?,?,?,?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssssssi', $acceswardent, $accesmember, $redproblem, $Room, $Mess, $hstelsor, $overall, $feedback, $aid);
    
    if($stmt->execute()) {
        $_SESSION['success'] = "Feedback submitted successfully!";
        header("Location: feedback.php");
        exit();
    } else {
        $_SESSION['error'] = "Error submitting feedback. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Hostel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-modern.css">
    <style>
        .rating-item {
            margin-bottom: 24px;
            padding: 24px;
            border-radius: 20px;
            background-color: var(--gray-light);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.2s;
        }
        .rating-item:hover {
            background-color: #fff;
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
        }
        .rating-title {
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 1rem;
        }
        .rating-options {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .rating-option input[type="radio"] {
            display: none;
        }
        .rating-option label {
            padding: 10px 20px;
            border-radius: 12px;
            background-color: #fff;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        .rating-option input[type="radio"]:checked + label {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        .feedback-history-card {
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-9">
                        <div class="mb-5 animate__animated animate__fadeInLeft">
                            <h2 class="section-title">Hostel Feedback</h2>
                            <p class="section-subtitle">Help us improve your living experience</p>
                        </div>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        $uid = $_SESSION['login'];
                        $stmt = $mysqli->prepare("SELECT emailid FROM registration WHERE emailid=? || regno=?");
                        $stmt->bind_param('ss', $uid, $uid);
                        $stmt->execute();
                        $stmt->store_result();
                        $rs = $stmt->num_rows > 0;
                        $stmt->close();
                        
                        if($rs) {
                            $stmt = $mysqli->prepare("SELECT id FROM feedback WHERE userId=?");
                            $stmt->bind_param('i', $aid);
                            $stmt->execute();
                            $stmt->bind_result($f_id);
                            $stmt->store_result();
                            $has_feedback = $stmt->num_rows > 0;
                            $stmt->close();
                            
                            if($has_feedback) {
                                $ret = "SELECT * FROM feedback WHERE userId=?";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->bind_param('i', $aid);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while($row = $res->fetch_object()):
                        ?>
                        
                        <div class="feedback-history-card animate__animated animate__fadeInUp">
                            <div class="p-4 d-flex justify-content-between align-items-center" style="background: var(--gradient-primary); color: white;">
                                <div>
                                    <h4 class="mb-1 fw-800">Your Feedback Submission</h4>
                                    <div class="small opacity-75"><i class="fas fa-calendar-alt me-1"></i> Submitted on <?php echo date('d M Y', strtotime($row->postinDate)); ?></div>
                                </div>
                                <div class="bg-white bg-opacity-20 p-3 rounded-4">
                                    <div class="small fw-700 opacity-75">OVERALL</div>
                                    <div class="h5 fw-800 mb-0"><?php echo strtoupper($row->OverallRating); ?></div>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="p-3 bg-light rounded-4">
                                            <div class="small fw-800 text-muted mb-1">WARDEN</div>
                                            <div class="text-primary fw-800"><?php echo $row->AccessibilityWarden; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-light rounded-4">
                                            <div class="small fw-800 text-muted mb-1">MESS</div>
                                            <div class="text-primary fw-800"><?php echo $row->Mess; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-light rounded-4">
                                            <div class="small fw-800 text-muted mb-1">ROOM</div>
                                            <div class="text-primary fw-800"><?php echo $row->Room; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-light rounded-4">
                                            <div class="small fw-800 text-muted mb-1">SURROUNDINGS</div>
                                            <div class="text-primary fw-800"><?php echo $row->HostelSurroundings; ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label-modern mb-2">My Comments</label>
                                    <div class="p-3 bg-light rounded-4 text-dark fst-italic">
                                        "<?php echo htmlspecialchars($row->FeedbackMessage ?: 'No specific comments provided.'); ?>"
                                    </div>
                                </div>

                                <div class="pt-4 border-top">
                                    <label class="form-label-modern mb-3">Management Response</label>
                                    <?php if($row->adminRemark): ?>
                                    <div class="p-4 rounded-4" style="background: #ecfdf5; border-left: 5px solid var(--success);">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px; height:32px;">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <span class="fw-800 text-success">Admin Response</span>
                                            <span class="ms-auto text-muted small fw-700"><?php echo date('d M, Y', strtotime($row->adminRemarkDate)); ?></span>
                                        </div>
                                        <div class="text-dark" style="line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($row->adminRemark)); ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="p-3 bg-light rounded-4 text-center text-muted small fw-600">
                                        <i class="fas fa-hourglass-half me-2"></i> Pending review by the hostel administration
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php endwhile; } else { ?>
                        
                        <div class="card-modern">
                            <div class="card-body-modern p-5">
                                <form method="post" action="" name="feedbackForm" class="needs-validation" novalidate>
                                    <div class="row">
                                        <!-- Question 1 -->
                                        <div class="col-md-6">
                                            <div class="rating-item">
                                                <div class="rating-title">1. Accessibility to Warden</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="w-<?=str_replace(' ','',$val)?>" name="acceswardent" value="<?=$val?>" required>
                                                        <label for="w-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Question 2 -->
                                        <div class="col-md-6">
                                            <div class="rating-item">
                                                <div class="rating-title">2. Accessibility to Committee Members</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="m-<?=str_replace(' ','',$val)?>" name="accesmember" value="<?=$val?>" required>
                                                        <label for="m-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Question 3 -->
                                        <div class="col-md-6">
                                            <div class="rating-item">
                                                <div class="rating-title">3. Redressal of Problems</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="p-<?=str_replace(' ','',$val)?>" name="redproblem" value="<?=$val?>" required>
                                                        <label for="p-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Question 4 -->
                                        <div class="col-md-6">
                                            <div class="rating-item">
                                                <div class="rating-title">4. Room Condition & Maintenance</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="r-<?=str_replace(' ','',$val)?>" name="Room" value="<?=$val?>" required>
                                                        <label for="r-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Question 5 -->
                                        <div class="col-md-6">
                                            <div class="rating-item">
                                                <div class="rating-title">5. Mess/Food Quality</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="ms-<?=str_replace(' ','',$val)?>" name="Mess" value="<?=$val?>" required>
                                                        <label for="ms-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Question 6 -->
                                        <div class="col-md-6">
                                            <div class="rating-item">
                                                <div class="rating-title">6. Hostel Surroundings</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="s-<?=str_replace(' ','',$val)?>" name="hstelsor" value="<?=$val?>" required>
                                                        <label for="s-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Overall -->
                                        <div class="col-12">
                                            <div class="rating-item" style="background: var(--primary-light);">
                                                <div class="rating-title text-primary"><i class="fas fa-star me-2"></i>7. Overall Rating</div>
                                                <div class="rating-options">
                                                    <?php foreach(['Excellent', 'Very Good', 'Good', 'Average', 'Below Average'] as $val): ?>
                                                    <div class="rating-option">
                                                        <input type="radio" id="o-<?=str_replace(' ','',$val)?>" name="overall" value="<?=$val?>" required>
                                                        <label for="o-<?=str_replace(' ','',$val)?>"><?=$val?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Comments -->
                                        <div class="col-12 mt-3">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern">Additional Comments (Optional)</label>
                                                <textarea name="feedback" class="form-control form-control-modern" style="height:120px; padding:15px;" placeholder="Is there anything else you'd like to tell us?"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12 text-center mt-5">
                                            <button type="submit" name="submit" class="btn-modern btn-modern-primary py-3 px-5 justify-content-center shadow-lg">
                                                <i class="fas fa-paper-plane"></i> SUBMIT FEEDBACK
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <?php } } else { ?>
                        
                        <div class="card-modern border-0">
                            <div class="p-5 text-center">
                                <div class="bg-gray-light p-4 rounded-circle d-inline-flex mb-4">
                                    <i class="fas fa-lock fa-3x text-gray"></i>
                                </div>
                                <h4 class="fw-800">Feedback Unavailable</h4>
                                <p class="text-muted mb-4">You need to have an active hostel booking to provide feedback.<br>Please secure a room first.</p>
                                <a href="book-hostel.php" class="btn-modern btn-modern-primary justify-content-center">
                                    <i class="fas fa-bed"></i> Book Hostel Now
                                </a>
                            </div>
                        </div>
                        
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.min.js"></script>
    <script>
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    var invalidFields = form.querySelectorAll(':invalid');
                    if(invalidFields.length > 0) {
                        invalidFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>