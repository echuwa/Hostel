<?php 
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Code for add courses
if(isset($_POST['submit'])) {
    $coursecode = $_POST['cc'];
    $coursesn = $_POST['cns'];
    $coursefn = $_POST['cnf'];

    $query = "INSERT INTO courses (course_code, course_sn, course_fn) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sss', $coursecode, $coursesn, $coursefn);
    
    if($stmt->execute()) {
        $_SESSION['success'] = "Course has been added successfully";
        header("Location: manage-courses.php");
        exit();
    } else {
        $_SESSION['error'] = "Error adding course. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #3a7bd5;
            --secondary-color: #00d2ff;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #2c65b4, #00b7eb);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 123, 213, 0.25);
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
                        <div class="form-container">
                            <h2 class="form-header"><i class="fas fa-book me-2"></i> Add New Course</h2>
                            
                            <?php if(isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" class="row g-3">
                                <div class="col-md-12">
                                    <label for="cc" class="form-label">Course Code</label>
                                    <input type="text" class="form-control" name="cc" id="cc" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="cns" class="form-label">Course Name (Short)</label>
                                    <input type="text" class="form-control" name="cns" id="cns" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="cnf" class="form-label">Course Name (Full)</label>
                                    <input type="text" class="form-control" name="cnf" id="cnf">
                                </div>
                                
                                <div class="col-12 text-end mt-4">
                                    <button type="submit" name="submit" class="btn btn-submit text-white">
                                        <i class="fas fa-plus-circle me-1"></i> Add Course
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>