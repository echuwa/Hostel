<?php
session_start();
include('includes/config.php');
date_default_timezone_set('Asia/Kolkata');
include('includes/checklogin.php');
check_login();
$aid = $_SESSION['id'];

if(isset($_POST['submit'])) {
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
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .feedback-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .feedback-header {
            text-align: center;
            margin-bottom: 30px;
            color: #3a7bd5;
        }
        .rating-item {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .rating-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #495057;
        }
        .rating-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .rating-option {
            display: flex;
            align-items: center;
        }
        .rating-option input[type="radio"] {
            display: none;
        }
        .rating-option label {
            padding: 8px 15px;
            border-radius: 20px;
            background-color: #e9ecef;
            cursor: pointer;
            transition: all 0.3s;
        }
        .rating-option input[type="radio"]:checked + label {
            background-color: #3a7bd5;
            color: white;
        }
        .btn-submit {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #2c65b4, #00b7eb);
        }
        .feedback-view {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .feedback-item {
            margin-bottom: 15px;
        }
        .feedback-label {
            font-weight: 600;
            color: #495057;
        }
        .not-eligible {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="ts-main-content">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="feedback-container">
                            <div class="feedback-header">
                                <h2><i class="fas fa-comment-alt me-2"></i> Hostel Feedback</h2>
                                <p class="text-muted">Share your experience with us</p>
                            </div>
                            
                            <!-- Display Success/Error Messages -->
                            <?php if(isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            $uid = $_SESSION['login'];
                            $stmt = $mysqli->prepare("SELECT emailid FROM registration WHERE emailid=? || regno=?");
                            $stmt->bind_param('ss', $uid, $uid);
                            $stmt->execute();
                            $stmt->bind_result($email);
                            $rs = $stmt->fetch();
                            $stmt->close();
                            
                            if($rs) {
                                $ret = $mysqli->prepare("SELECT id FROM feedback WHERE userId=?");
                                $ret->bind_param('i', $aid);
                                $ret->execute();
                                $ret->bind_result($count);
                                $ret->fetch();
                                
                                if($count > 0) {
                                    $ret = "SELECT * FROM feedback WHERE userId=?";
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->bind_param('i', $aid);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    while($row = $res->fetch_object()):
                            ?>
                            
                            <div class="feedback-view">
                                <h4 class="text-center mb-4"><i class="fas fa-check-circle me-2"></i>Your Feedback Details</h4>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Feedback Date:</div>
                                    <div><?php echo date('d M Y, h:i A', strtotime($row->postinDate)); ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Accessibility to Warden:</div>
                                    <div class="rating-badge"><?php echo $row->AccessibilityWarden; ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Accessibility to Hostel Committee members:</div>
                                    <div class="rating-badge"><?php echo $row->AccessibilityMember; ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Redressal of Problem:</div>
                                    <div class="rating-badge"><?php echo $row->RedressalProblem; ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Room:</div>
                                    <div class="rating-badge"><?php echo $row->Room; ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Mess:</div>
                                    <div class="rating-badge"><?php echo $row->Mess; ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Hostel Surroundings:</div>
                                    <div class="rating-badge"><?php echo $row->HostelSurroundings; ?></div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-label">Overall Rating:</div>
                                    <div class="rating-badge"><?php echo $row->OverallRating; ?></div>
                                </div>
                                
                                <?php if(!empty($row->FeedbackMessage)): ?>
                                <div class="feedback-item">
                                    <div class="feedback-label">Your Feedback Message:</div>
                                    <div class="feedback-message"><?php echo $row->FeedbackMessage; ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php
                                    endwhile;
                                } else {
                            ?>
                            
                            <form method="post" action="" name="feedbackForm" class="needs-validation" novalidate>
                                <!-- Accessibility to Warden -->
                                <div class="rating-item">
                                    <div class="rating-title">1. Accessibility to Warden</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="warden-excellent" name="acceswardent" value="Excellent" required>
                                            <label for="warden-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="warden-vgood" name="acceswardent" value="Very Good">
                                            <label for="warden-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="warden-good" name="acceswardent" value="Good">
                                            <label for="warden-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="warden-avg" name="acceswardent" value="Average">
                                            <label for="warden-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="warden-bavg" name="acceswardent" value="Below Average">
                                            <label for="warden-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Accessibility to Hostel Committee members -->
                                <div class="rating-item">
                                    <div class="rating-title">2. Accessibility to Hostel Committee members</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="member-excellent" name="accesmember" value="Excellent" required>
                                            <label for="member-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="member-vgood" name="accesmember" value="Very Good">
                                            <label for="member-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="member-good" name="accesmember" value="Good">
                                            <label for="member-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="member-avg" name="accesmember" value="Average">
                                            <label for="member-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="member-bavg" name="accesmember" value="Below Average">
                                            <label for="member-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Redressal of Problems -->
                                <div class="rating-item">
                                    <div class="rating-title">3. Redressal of Problems</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="problem-excellent" name="redproblem" value="Excellent" required>
                                            <label for="problem-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="problem-vgood" name="redproblem" value="Very Good">
                                            <label for="problem-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="problem-good" name="redproblem" value="Good">
                                            <label for="problem-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="problem-avg" name="redproblem" value="Average">
                                            <label for="problem-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="problem-bavg" name="redproblem" value="Below Average">
                                            <label for="problem-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Room -->
                                <div class="rating-item">
                                    <div class="rating-title">4. Room</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="room-excellent" name="Room" value="Excellent" required>
                                            <label for="room-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="room-vgood" name="Room" value="Very Good">
                                            <label for="room-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="room-good" name="Room" value="Good">
                                            <label for="room-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="room-avg" name="Room" value="Average">
                                            <label for="room-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="room-bavg" name="Room" value="Below Average">
                                            <label for="room-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mess -->
                                <div class="rating-item">
                                    <div class="rating-title">5. Mess</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="mess-excellent" name="Mess" value="Excellent" required>
                                            <label for="mess-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="mess-vgood" name="Mess" value="Very Good">
                                            <label for="mess-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="mess-good" name="Mess" value="Good">
                                            <label for="mess-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="mess-avg" name="Mess" value="Average">
                                            <label for="mess-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="mess-bavg" name="Mess" value="Below Average">
                                            <label for="mess-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hostel Surroundings -->
                                <div class="rating-item">
                                    <div class="rating-title">6. Hostel Surroundings (e.g. Lawn etc.)</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="surround-excellent" name="hstelsor" value="Excellent" required>
                                            <label for="surround-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="surround-vgood" name="hstelsor" value="Very Good">
                                            <label for="surround-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="surround-good" name="hstelsor" value="Good">
                                            <label for="surround-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="surround-avg" name="hstelsor" value="Average">
                                            <label for="surround-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="surround-bavg" name="hstelsor" value="Below Average">
                                            <label for="surround-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Overall Rating -->
                                <div class="rating-item">
                                    <div class="rating-title">7. Overall Rating</div>
                                    <div class="rating-options">
                                        <div class="rating-option">
                                            <input type="radio" id="overall-excellent" name="overall" value="Excellent" required>
                                            <label for="overall-excellent">Excellent</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="overall-vgood" name="overall" value="Very Good">
                                            <label for="overall-vgood">Very Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="overall-good" name="overall" value="Good">
                                            <label for="overall-good">Good</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="overall-avg" name="overall" value="Average">
                                            <label for="overall-avg">Average</label>
                                        </div>
                                        <div class="rating-option">
                                            <input type="radio" id="overall-bavg" name="overall" value="Below Average">
                                            <label for="overall-bavg">Below Average</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Feedback Message -->
                                <div class="rating-item">
                                    <div class="rating-title">8. Additional Feedback (Optional)</div>
                                    <textarea name="feedback" id="feedback" class="form-control" rows="4" placeholder="Please share any additional comments or suggestions..."></textarea>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="text-center mt-4">
                                    <button type="submit" name="submit" class="btn btn-submit text-white">
                                        <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                                    </button>
                                </div>
                            </form>
                            
                            <?php
                                }
                            } else {
                            ?>
                            
                            <div class="not-eligible">
                                <i class="fas fa-info-circle fa-3x mb-3" style="color: #6c757d;"></i>
                                <h4>You are not eligible to submit feedback yet</h4>
                                <p>Once you book a hostel room, you will be able to share your feedback with us.</p>
                                <a href="book-hostel.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-bed me-2"></i> Book Hostel
                                </a>
                            </div>
                            
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Scroll to first invalid field
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