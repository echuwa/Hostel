<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

if(isset($_GET['del']))
{
    // CSRF PROTECTION
    if(!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
        $_SESSION['error'] = "Security token mismatch. Action aborted.";
        header("Location: manage-courses.php");
        exit();
    }
    
    $id=intval($_GET['del']);
    $adn="delete from courses where id=?";
    $stmt= $mysqli->prepare($adn);
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $stmt->close();	   
    $_SESSION['success'] = "Course deleted successfully";
    header("Location: manage-courses.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>Academic Directory | HostelMS</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Unified Admin CSS -->
    <link rel="stylesheet" href="css/admin-modern.css">
    
    <style>
        .course-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .table-modern thead th {
            background: #f8fafc;
            border: none;
            color: #64748b;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 20px;
        }

        .table-modern tbody td {
            padding: 20px;
            vertical-align: middle;
            color: #1e293b;
            font-weight: 600;
            border-bottom: 1px solid #f1f5f9;
        }

        .code-pill {
            background: #eef2ff;
            color: #4361ee;
            padding: 8px 16px;
            border-radius: 12px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-weight: 800;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(67, 97, 238, 0.1);
        }

        .action-button {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
        }

        .btn-edit { background: #f0f7ff; color: #4361ee; }
        .btn-edit:hover { background: #4361ee; color: #fff; transform: translateY(-3px); box-shadow: 0 8px 15px rgba(67,97,238,0.2); }
        
        .btn-delete { background: #fff1f2; color: #ef4444; }
        .btn-delete:hover { background: #ef4444; color: #fff; transform: translateY(-3px); box-shadow: 0 8px 15px rgba(239,68,68,0.2); }

        .search-box {
            position: relative;
        }
        .search-box input {
            padding-left: 45px !important;
            height: 48px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-weight: 600;
        }
        .search-box i {
            position: absolute;
            left: 18px;
            top: 16px;
            color: #94a3b8;
        }

        .dataTables_wrapper .dataTables_paginate .page-link {
            border-radius: 10px;
            margin: 0 3px;
            font-weight: 700;
            border: none;
            color: #64748b;
        }
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background: var(--gradient-primary);
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <?php include('includes/sidebar_modern.php'); ?>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="content-wrapper">
                
                <!-- Page Top Bar -->
                <div class="d-flex justify-content-between align-items-end mb-5" data-aos="fade-down">
                    <div>
                        <nav aria-label="breadcrumb" class="mb-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Management</a></li>
                                <li class="breadcrumb-item active fw-800 text-primary">Courses</li>
                            </ol>
                        </nav>
                        <h2 class="fw-800 mb-1">Academic Portfolio</h2>
                        <p class="text-muted fw-600 mb-0">A registry of all authorized academic disciplines in the institution.</p>
                    </div>
                    <div>
                        <a href="add-courses.php" class="btn btn-modern btn-modern-primary px-4 py-3">
                            <i class="fas fa-plus-circle"></i> ADD NEW DISCIPLINE
                        </a>
                    </div>
                </div>

                <!-- Table Panel -->
                <div class="course-card" data-aos="fade-up">
                    <div class="p-4 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-800 text-dark"><i class="fas fa-list-ul me-2 text-primary"></i> ALL REGISTERED COURSES</h6>
                        <div class="small fw-700 text-muted">
                            <i class="fas fa-clock me-1"></i> Sync: Just now
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="table-responsive">
                            <table id="courses-table" class="table table-modern w-100">
                                <thead>
                                    <tr>
                                        <th class="ps-4">No.</th>
                                        <th>Discipline Identifier</th>
                                        <th>Course Formal Name</th>
                                        <th>Registry Date</th>
                                        <th class="text-center">Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ret = "SELECT * FROM courses ORDER BY posting_date DESC";
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    $cnt = 1;
                                    
                                    while($row = $res->fetch_object()):
                                    ?>
                                    <tr>
                                        <td class="ps-4"><?php echo $cnt++; ?></td>
                                        <td>
                                            <div class="code-pill">
                                                <i class="fas fa-hashtag"></i>
                                                <?php echo htmlspecialchars($row->course_code); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-800 text-dark"><?php echo htmlspecialchars($row->course_fn); ?></div>
                                            <div class="small text-muted fw-600">Full Academic Program</div>
                                        </td>
                                        <td>
                                            <div class="fw-700">
                                                <i class="far fa-calendar-check me-1 text-primary"></i>
                                                <?php echo date('d M, Y', strtotime($row->posting_date)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a href="edit-course.php?id=<?php echo $row->id; ?>" class="action-button btn-edit" title="Edit Records">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <button onclick="confirmDeletion(<?php echo $row->id; ?>, '<?php echo addslashes($row->course_fn); ?>')" class="action-button btn-delete" title="Purge Record">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            AOS.init({ duration: 800, once: true });

            $('#courses-table').DataTable({
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search by identifier or name...",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                },
                "pageLength": 10,
                "dom": '<"d-flex flex-column flex-md-row justify-content-between align-items-center mb-4"lf>rt<"d-flex flex-column flex-md-row justify-content-between align-items-center mt-4"ip>'
            });

            // Styling search box
            $('.dataTables_filter').html(`
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" class="form-control" placeholder="Search across registry...">
                </div>
            `);
            
            // Map the new search input to DataTables
            $('.search-box input').on('keyup', function() {
                $('#courses-table').DataTable().search($(this).val()).draw();
            });
        });

        function confirmDeletion(id, name) {
            Swal.fire({
                title: 'Purge Record?',
                html: `You are about to permanently delete <b>${name}</b> from the academic registry.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Purge Data',
                cancelButtonText: 'Abort',
                background: '#fff',
                borderRadius: '24px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `manage-courses.php?del=${id}&token=<?php echo generate_csrf_token(); ?>`;
                }
            });
        }
    </script>
    
    <?php if(isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Registry Updated',
            text: '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    </script>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Security Alert',
            text: '<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>',
            timer: 4000,
            showConfirmButton: true
        });
    </script>
    <?php endif; ?>
</body>
</html>

