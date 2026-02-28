<?php 
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Code for add courses
if(isset($_POST['submit'])) {
    $coursecode = htmlspecialchars(trim($_POST['cc']));
    $coursename = htmlspecialchars(trim($_POST['cn'])); // Single course name

    $query = "INSERT INTO courses (course_code, course_sn, course_fn) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sss', $coursecode, $coursename, $coursename);
    
    if($stmt->execute()) {
        $_SESSION['course_success'] = [
            'code' => $coursecode,
            'name' => $coursename
        ];
        header("Location: add-courses.php?created=1");
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Add Course | HostelMS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body {
            background: #f0f2f5;
            padding-top: 0;
        }

        .course-form-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(67,97,238,.08);
            overflow: hidden;
            max-width: 780px;
            margin: 30px auto;
        }

        .course-card-header {
            background: linear-gradient(135deg, #4361ee 0%, #7b2ff7 100%);
            padding: 32px 36px;
            position: relative;
            overflow: hidden;
        }

        .course-card-header h2 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .course-card-header p {
            color: rgba(255,255,255,.75);
            margin: 6px 0 0;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .header-icon-big {
            width: 60px; height: 60px;
            background: rgba(255,255,255,.2);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff;
            margin-bottom: 14px;
            position: relative; z-index: 1;
        }

        .course-card-body {
            padding: 36px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            color: #2d3748;
            transition: all 0.2s;
            background: #fafbff;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67,97,238,.12);
            background: #fff;
            outline: none;
        }

        .btn-create {
            background: linear-gradient(135deg, #4361ee, #7b2ff7);
            border: none;
            color: #fff;
            padding: 13px 32px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(67,97,238,.2);
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,97,238,.4);
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                
                <!-- Header -->
                <div class="content-header">
                    <div class="header-left">
                        <h1 class="page-title">
                            <i class="fas fa-book-open"></i> Add New Course
                        </h1>
                    </div>
                    <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                        <a href="manage-courses.php" class="btn btn-primary" style="background: linear-gradient(135deg, #4361ee, #7b2ff7); border: none; padding: 10px 20px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 15px rgba(67,97,238,0.2); color: white;">
                            <i class="fas fa-list"></i> Manage Courses
                        </a>
                        <div class="date-filter" style="background: white; padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 8px; color: #4a5568; font-weight: 500;">
                            <i class="fas fa-calendar-alt" style="color: #4361ee;"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="course-form-card">
                    <div class="course-card-header">
                        <div class="header-icon-big">
                            <i class="fas fa-book"></i>
                        </div>
                        <h2>Create Course</h2>
                        <p>Fill in the details below to add a new course to the system.</p>
                    </div>

                    <div class="course-card-body">
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger" style="border-radius:10px;">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="courseForm">
                            <input type="hidden" name="submit" value="1">
                            
                            <div class="mb-4">
                                <label for="cc" class="form-label">Course Code *</label>
                                <input type="text" class="form-control" name="cc" id="cc" required placeholder="e.g. CS101">
                            </div>

                            <div class="mb-4">
                                <label for="cn" class="form-label">Course Name *</label>
                                <input type="text" class="form-control" name="cn" id="cn" required placeholder="e.g. Bachelor of Technology">
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn-create" id="createBtn">
                                    <i class="fas fa-plus-circle"></i> Add Course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show success modal on page load if just created
        <?php if(isset($_GET['created']) && isset($_SESSION['course_success'])): 
            $c = $_SESSION['course_success'];
            unset($_SESSION['course_success']);
        ?>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Course Created Successfully!',
                html: 'Course Code: <b><?php echo htmlspecialchars($c['code']); ?></b><br>Course Name: <b><?php echo htmlspecialchars($c['name']); ?></b>',
                icon: 'success',
                confirmButtonColor: '#4361ee',
                confirmButtonText: 'OK'
            }).then((result) => {
                window.location.href = 'add-courses.php';
            });
        });
        <?php endif; ?>

        // Form loading state
        document.getElementById('courseForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('createBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;
        });
    </script>
</body>
</html>