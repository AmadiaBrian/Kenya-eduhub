<?php
// Teachers Management Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Teachers - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #F0F4F9;
            --card-bg: #F0F4F9;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
        }

        .material-symbols-outlined {
            font-size: 24px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .quick-access-icon .material-symbols-outlined {
            color: #5f6368;
        }

        .main-content {
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 40px;
            text-align: center;
        }
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 25px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            background: var(--bg-color);
        }

        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
        }
        
        .btn-danger {
            background: #d93025;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b92b20;
        }
        
        .btn-info {
            background: #0288d1;
            color: white;
        }
        
        .btn-info:hover {
            background: #01579b;
        }

        .btn-action {
            background: transparent;
            color: #FF6B35;
            border: none;
            cursor: pointer;
            margin-right: 4px;
            padding: 0;
        }

        .btn-action:hover {
            color: #e55a2b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 4px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table thead th {
            background: #f0f0f0;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: bold;
            color: #000;
        }

        .table td {
            padding: 12px;
            border: 2px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 24px;
            }

            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            .card[style*="padding: 0"] {
                padding: 0 !important;
            }

            .btn {
                width: 100%;
                padding: 12px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
            }

            .card-header .btn {
                width: 100%;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
                margin: 0 -16px;
                padding: 0 16px;
            }

            .table {
                min-width: 600px;
                width: 100%;
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
                font-size: 11px;
            }
            
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .card-header .btn {
                width: 100%;
            }
            
            .card {
                text-align: center;
            }
            
            .card-header {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 12px;
            }
            
            /* Quick Access Mobile */
            .quick-access-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .quick-access-item {
                padding: 12px;
            }
            
            .quick-access-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
                color: #FF6B35;
                margin-bottom: 8px;
            }
            
            .quick-access-label {
                font-size: 11px;
            }
        }
        
        /* Quick Access */
        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
        }
        
        .quick-access-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .quick-access-item:hover {
            background: #fff3e0;
            transform: translateY(-2px);
        }
        
        .quick-access-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: transparent;
            color: #FF6B35;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .quick-access-label {
            font-size: 13px;
            font-weight: 500;
            color: #202124;
            text-align: center;
        }

        /* Icon colors */
        .quick-access-item:nth-child(1) .quick-access-icon { background: #e8f0fe; color: #4285F4; }
        .quick-access-item:nth-child(2) .quick-access-icon { background: #fce8e6; color: #EA4335; }
        .quick-access-item:nth-child(3) .quick-access-icon { background: #e6f4ea; color: #00AC47; }
        .quick-access-item:nth-child(4) .quick-access-icon { background: #fef7e0; color: #FFBA00; }
        .quick-access-item:nth-child(5) .quick-access-icon { background: #e8f0fe; color: #0066DA; }
        .quick-access-item:nth-child(6) .quick-access-icon { background: #fce8e6; color: #4285F4; }
        .fa-plus { color: #00AC47; }
        .fa-download { color: #4285F4; }
        .fa-times { color: #EA4335; }
        .fa-edit { color: #0066DA; }
        .fa-trash { color: #EA4335; }

        /* Filter Section */
        .filter-section {
            background: var(--bg-color);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e8eaed;
        }

        .filter-section .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="dashboard-page">
</br>
</br>
</br>
</br>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'teachers'; require_once 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Teachers Management</h1>
        
        <!-- Quick Access -->
        <h2 class="card-title">Quick Access</h2>
        <div class="quick-access-grid">
            <a href="dashboard" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-symbols-outlined">dashboard</span>
                </div>
                <div class="quick-access-label">Dashboard</div>
            </a>
            <a href="students" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-symbols-outlined">school</span>
                </div>
                <div class="quick-access-label">Students</div>
            </a>
            <a href="classes" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-symbols-outlined">class</span>
                </div>
                <div class="quick-access-label">Classes</div>
            </a>
            <a href="attendance" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-symbols-outlined">event_available</span>
                </div>
                <div class="quick-access-label">Attendance</div>
            </a>
            <a href="performance" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-symbols-outlined">trending_up</span>
                </div>
                <div class="quick-access-label">Performance</div>
            </a>
            <a href="fees" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="quick-access-label">Fees</div>
            </a>
        </div>
        
        <!-- Teacher Statistics -->
        <div class="card">
            <h2 class="card-title">Teacher Statistics</h2>
            <div id="teacherStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                <?php
                $totalTeachers = 0;
                $classTeachers = 0;
                $subjectTeachers = 0;
                $activeTeachers = 0;

                try {
                    $stmt = $pdo->prepare("SELECT teacher_type, status FROM teachers WHERE school_id = ?");
                    $stmt->execute([$school_id]);
                    $teachers = $stmt->fetchAll();

                    foreach ($teachers as $teacher) {
                        $totalTeachers++;
                        if ($teacher['teacher_type'] === 'class_teacher') $classTeachers++;
                        if ($teacher['teacher_type'] === 'subject_teacher') $subjectTeachers++;
                        if ($teacher['status'] === 'active') $activeTeachers++;
                    }
                } catch (PDOException $e) {
                    error_log("Failed to fetch teacher stats: " . $e->getMessage());
                }
                ?>
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalTeachers; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Teachers</div>
                </div>
                <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $classTeachers; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Class Teachers</div>
                </div>
                <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $subjectTeachers; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Subject Teachers</div>
                </div>
                <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $activeTeachers; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Active</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-column gap-2 mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal" style="border-radius: 25px;">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add</span> Add Teacher
            </button>
            <button class="btn btn-success" onclick="exportTeachers()" style="border-radius: 25px;">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">download</span> Export
            </button>
        </div>

        <!-- Search and Filter Section -->
        <div class="card">
            <h2 class="card-title">Search & Filter</h2>
            <div class="row mb-3" style="gap: 12px;">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchTeacher" placeholder="Search by name, email, or phone..." onkeyup="filterTeachers()">
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterTeacherType" onchange="filterTeachers()">
                        <option value="">All Types</option>
                        <option value="class_teacher">Class Teacher</option>
                        <option value="subject_teacher">Subject Teacher</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterTeacherStatus" onchange="filterTeachers()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterTeacherClass" onchange="filterTeachers()">
                        <option value="">All Classes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterTeacherStream" onchange="filterTeachers()">
                        <option value="">All Streams</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 0;">
            <h2 class="card-title" style="padding: 24px 24px 16px 24px;">All Teachers</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>ID Number</th>
                            <th>Type</th>
                            <th>Assignment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teachersTable">
                        <tr><td colspan="8" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Teacher Modal - Google Material Design Style -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addTeacherForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="teacherFirstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="teacherLastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="teacherEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" id="teacherPhone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="teacherIdNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teacher Type</label>
                                <select class="form-control" id="teacherType" onchange="toggleTeacherTypeFields()">
                                    <option value="subject_teacher">Subject Teacher</option>
                                    <option value="class_teacher">Class Teacher</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" id="classTeacherFields" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Class</label>
                                <select class="form-control" id="teacherClassId">
                                    <option value="">Select Class</option>
                                </select>
                                <small class="text-muted">Required for class teachers</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Stream</label>
                                <select class="form-control" id="teacherStreamId">
                                    <option value="">Select Stream</option>
                                </select>
                                <small class="text-muted">Optional</small>
                            </div>
                        </div>
                        <div class="row" id="subjectTeacherFields">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Subject Assignments <span class="text-muted">(Optional for class teachers)</span></label>
                                <div id="subjectAssignments">
                                    <div class="row mb-2 subject-assignment">
                                        <div class="col-md-4">
                                            <select class="form-control subject-class">
                                                <option value="">Select Class</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-control subject-id">
                                                <option value="">Select Subject</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeSubjectAssignment(this)">
                                                <span class="material-symbols-outlined">close</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addSubjectAssignment()">
                                    <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add</span> Add Subject Assignment
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Login Password</label>
                                <input type="password" class="form-control" id="teacherPassword" required>
                                <small class="text-muted">Required for teacher portal access</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" id="teacherAddress" rows="1"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addTeacher()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Add Teacher</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Teacher Modal - Google Material Design Style -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="editTeacherForm">
                        <input type="hidden" id="editTeacherId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editTeacherFirstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editTeacherLastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="editTeacherEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" id="editTeacherPhone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="editTeacherIdNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teacher Type</label>
                                <select class="form-control" id="editTeacherType" onchange="toggleEditTeacherTypeFields()">
                                    <option value="subject_teacher">Subject Teacher</option>
                                    <option value="class_teacher">Class Teacher</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" id="editClassTeacherFields" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Class</label>
                                <select class="form-control" id="editTeacherClassId">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Stream</label>
                                <select class="form-control" id="editTeacherStreamId">
                                    <option value="">Select Stream</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" id="editSubjectTeacherFields">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Subject Assignments</label>
                                <div id="editSubjectAssignments">
                                    <!-- Subject assignments will be loaded here -->
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addEditSubjectAssignment()">
                                    <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add</span> Add Subject Assignment
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" id="editTeacherStatus">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" id="editTeacherAddress" rows="1"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateTeacher()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Update Teacher</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle teacher type fields
        function toggleTeacherTypeFields() {
            const type = document.getElementById('teacherType').value;
            document.getElementById('classTeacherFields').style.display = type === 'class_teacher' ? 'block' : 'none';
            document.getElementById('subjectTeacherFields').style.display = 'block'; // Always show subject assignments
        }
        
        // Toggle edit teacher type fields
        function toggleEditTeacherTypeFields() {
            const type = document.getElementById('editTeacherType').value;
            document.getElementById('editClassTeacherFields').style.display = type === 'class_teacher' ? 'block' : 'none';
            document.getElementById('editSubjectTeacherFields').style.display = 'block'; // Always show subject assignments
        }
        
        // Add subject assignment row
        function addSubjectAssignment() {
            const container = document.getElementById('subjectAssignments');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 subject-assignment';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <select class="form-control subject-class">
                        <option value="">Select Class</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-control subject-id">
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSubjectAssignment(this)">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Load classes and subjects for the new dropdowns
            loadClassesForSubjectDropdown(newRow.querySelector('.subject-class'));
            loadSubjectsForDropdown(newRow.querySelector('.subject-id'));
        }
        
        // Add edit subject assignment row
        function addEditSubjectAssignment() {
            const container = document.getElementById('editSubjectAssignments');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 edit-subject-assignment';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <select class="form-control edit-subject-class">
                        <option value="">Select Class</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-control edit-subject-id">
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditSubjectAssignment(this)">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Load classes and subjects for the new dropdowns
            loadClassesForSubjectDropdown(newRow.querySelector('.edit-subject-class'));
            loadSubjectsForDropdown(newRow.querySelector('.edit-subject-id'));
        }
        
        // Remove subject assignment row
        function removeSubjectAssignment(btn) {
            const container = document.getElementById('subjectAssignments');
            if (container.children.length > 1) {
                btn.closest('.subject-assignment').remove();
            }
        }
        
        // Remove edit subject assignment row
        function removeEditSubjectAssignment(btn) {
            const container = document.getElementById('editSubjectAssignments');
            if (container.children.length > 1) {
                btn.closest('.edit-subject-assignment').remove();
            }
        }
        
        // Load classes dropdown
        async function loadClassesDropdown() {
            try {
                const response = await fetch('api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const options = data.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                    document.getElementById('teacherClassId').innerHTML = '<option value="">Select Class</option>' + options;
                    document.getElementById('editTeacherClassId').innerHTML = '<option value="">Select Class</option>' + options;
                    
                    // Load classes for subject assignment dropdowns
                    document.querySelectorAll('.subject-class').forEach(select => {
                        select.innerHTML = '<option value="">Select Class</option>' + options;
                    });
                    document.querySelectorAll('.edit-subject-class').forEach(select => {
                        select.innerHTML = '<option value="">Select Class</option>' + options;
                    });
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
            
            // Load subjects for all subject dropdowns
            loadSubjectsForAllDropdowns();
        }
        
        function loadSubjectsForAllDropdowns() {
            fetch('api/subjects.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.subject_name} ${s.subject_code ? '(' + s.subject_code + ')' : ''}</option>`).join('');
                        document.querySelectorAll('.subject-id').forEach(select => {
                            select.innerHTML = '<option value="">Select Subject</option>' + options;
                        });
                        document.querySelectorAll('.edit-subject-id').forEach(select => {
                            select.innerHTML = '<option value="">Select Subject</option>' + options;
                        });
                    }
                })
                .catch(error => console.error('Error loading subjects:', error));
        }
        
        function loadClassesForSubjectDropdown(selectElement) {
            fetch('api/classes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const options = data.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                        selectElement.innerHTML = '<option value="">Select Class</option>' + options;
                    }
                })
                .catch(error => console.error('Error loading classes:', error));
        }
        
        // Load classes for filter dropdowns
        function loadClassesForFilters() {
            fetch('api/classes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const options = data.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                        document.getElementById('filterTeacherClass').innerHTML = '<option value="">All Classes</option>' + options;
                    }
                })
                .catch(error => console.error('Error loading classes:', error));
        }
        
        function loadSubjectsForDropdown(selectElement) {
            fetch('api/subjects.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.subject_name} ${s.subject_code ? '(' + s.subject_code + ')' : ''}</option>`).join('');
                        selectElement.innerHTML = '<option value="">Select Subject</option>' + options;
                    }
                })
                .catch(error => console.error('Error loading subjects:', error));
        }
        
        // Load streams based on class
        document.getElementById('teacherClassId').addEventListener('change', async function() {
            const classId = this.value;
            if (classId) {
                try {
                    const response = await fetch(`api/streams.php?class_id=${classId}`);
                    const data = await response.json();
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                        document.getElementById('teacherStreamId').innerHTML = '<option value="">Select Stream</option>' + options;
                    }
                } catch (error) {
                    console.error('Error loading streams:', error);
                }
            }
        });
        
        document.getElementById('editTeacherClassId').addEventListener('change', async function() {
            const classId = this.value;
            if (classId) {
                try {
                    const response = await fetch(`api/streams.php?class_id=${classId}`);
                    const data = await response.json();
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                        document.getElementById('editTeacherStreamId').innerHTML = '<option value="">Select Stream</option>' + options;
                    }
                } catch (error) {
                    console.error('Error loading streams:', error);
                }
            }
        });
        
        // Load streams for filter based on class
        document.getElementById('filterTeacherClass').addEventListener('change', async function() {
            const classId = this.value;
            if (classId) {
                try {
                    const response = await fetch(`api/streams.php?class_id=${classId}`);
                    const data = await response.json();
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                        document.getElementById('filterTeacherStream').innerHTML = '<option value="">All Streams</option>' + options;
                    }
                } catch (error) {
                    console.error('Error loading streams:', error);
                }
            } else {
                document.getElementById('filterTeacherStream').innerHTML = '<option value="">All Streams</option>';
            }
        });
        
        // Filter teachers
        function filterTeachers() {
            const search = document.getElementById('searchTeacher').value.toLowerCase();
            const type = document.getElementById('filterTeacherType').value;
            const status = document.getElementById('filterTeacherStatus').value;
            const classId = document.getElementById('filterTeacherClass').value;
            const streamId = document.getElementById('filterTeacherStream').value;
            
            const rows = document.querySelectorAll('#teachersTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const name = cells[0].textContent.toLowerCase();
                const email = cells[1].textContent.toLowerCase();
                const phone = cells[2].textContent.toLowerCase();
                const rowType = cells[4].textContent.toLowerCase();
                const assignment = cells[5].textContent.toLowerCase();
                const rowStatus = cells[6].textContent.toLowerCase();
                
                const matchesSearch = name.includes(search) || email.includes(search) || phone.includes(search);
                const matchesType = !type || rowType.includes(type.replace('_', ' '));
                const matchesStatus = !status || rowStatus.includes(status);
                const matchesClass = !classId || assignment.includes(classId);
                const matchesStream = !streamId || assignment.includes(streamId);
                
                row.style.display = (matchesSearch && matchesType && matchesStatus && matchesClass && matchesStream) ? '' : 'none';
            });
        }
        
        // Export teachers to CSV
        function exportTeachers() {
            const rows = document.querySelectorAll('#teachersTable tr');
            let csvContent = 'Name,Email,Phone,ID Number,Type,Assignment,Status\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const rowData = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent,
                    cells[3].textContent,
                    cells[4].textContent,
                    cells[5].textContent,
                    cells[6].textContent
                ].map(field => {
                    let text = String(field).trim();
                    text = text.replace(/"/g, '""');
                    if (text.includes(',') || text.includes('"')) {
                        text = `"${text}"`;
                    }
                    return text;
                });
                
                csvContent += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            const timestamp = new Date().toISOString().split('T')[0];
            link.setAttribute('href', url);
            link.setAttribute('download', `teachers_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Load teachers
        async function loadTeachers() {
            try {
                const response = await fetch('api/teachers.php');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('teachersTable');
                    tbody.innerHTML = data.data.map(teacher => {
                        let assignment = '';
                        if (teacher.teacher_type === 'class_teacher') {
                            assignment = `${teacher.class_name || 'Not assigned'} ${teacher.stream_name ? '- ' + teacher.stream_name : ''}`;
                            // Show subject assignments if any
                            if (teacher.subject_assignments && teacher.subject_assignments.length > 0) {
                                assignment += ' | Subjects: ' + teacher.subject_assignments.map(sa => sa.subject_name || sa.subject).join(', ');
                            }
                        } else {
                            // Subject teacher - show subject assignments
                            if (teacher.subject_assignments && teacher.subject_assignments.length > 0) {
                                assignment = teacher.subject_assignments.map(sa => `${sa.class_name} (${sa.subject_name || sa.subject})`).join(', ');
                            } else {
                                assignment = 'No assignments';
                            }
                        }
                        
                        return `
                        <tr>
                            <td>${teacher.first_name} ${teacher.last_name}</td>
                            <td>${teacher.email}</td>
                            <td>${teacher.phone}</td>
                            <td>${teacher.id_number}</td>
                            <td>
                                <span class="badge ${teacher.teacher_type === 'class_teacher' ? 'bg-primary' : 'bg-info'}">
                                    ${teacher.teacher_type === 'class_teacher' ? 'Class Teacher' : 'Subject Teacher'}
                                </span>
                            </td>
                            <td>${assignment}</td>
                            <td>
                                <span class="badge ${teacher.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                    ${teacher.status}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons-cell">
                                    <button class="btn btn-sm btn-action" onclick="editTeacher(${teacher.id})">
                                        <span class="material-symbols-outlined" style="color: #5f6368;">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-action" onclick="deleteTeacher(${teacher.id})">
                                        <span class="material-symbols-outlined" style="color: #d93025;">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `}).join('');
                }
            } catch (error) {
                console.error('Error loading teachers:', error);
            }
        }
        
        // Add teacher
        async function addTeacher() {
            const teacherType = document.getElementById('teacherType').value;
            const teacherData = {
                first_name: document.getElementById('teacherFirstName').value,
                last_name: document.getElementById('teacherLastName').value,
                email: document.getElementById('teacherEmail').value,
                phone: document.getElementById('teacherPhone').value,
                id_number: document.getElementById('teacherIdNumber').value,
                teacher_type: teacherType,
                password: document.getElementById('teacherPassword').value,
                address: document.getElementById('teacherAddress').value
            };
            
            if (teacherType === 'class_teacher') {
                teacherData.class_id = document.getElementById('teacherClassId').value || null;
                teacherData.stream_id = document.getElementById('teacherStreamId').value || null;
                if (!teacherData.class_id) {
                    alert('Class teachers must be assigned to a class');
                    return;
                }
            }
            
            // Collect subject assignments (for both teacher types)
            const subjectAssignments = [];
            document.querySelectorAll('.subject-assignment').forEach(row => {
                const classId = row.querySelector('.subject-class').value;
                const subjectId = row.querySelector('.subject-id').value;
                if (classId && subjectId) {
                    subjectAssignments.push({ class_id: classId, subject_id: subjectId });
                }
            });
            
            // Subject teachers must have at least one subject assignment
            if (teacherType === 'subject_teacher' && subjectAssignments.length === 0) {
                alert('Subject teachers must have at least one subject assignment');
                return;
            }
            
            if (subjectAssignments.length > 0) {
                teacherData.subject_assignments = subjectAssignments;
            }
            
            try {
                const response = await fetch('api/teachers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(teacherData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Teacher added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addTeacherModal')).hide();
                    document.getElementById('addTeacherForm').reset();
                    // Reset subject assignments
                    document.getElementById('subjectAssignments').innerHTML = `
                        <div class="row mb-2 subject-assignment">
                            <div class="col-md-5">
                                <select class="form-control subject-class">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control subject-name" placeholder="Subject name">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeSubjectAssignment(this)">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                        </div>
                    `;
                    loadClassesDropdown();
                    loadTeachers();
                } else {
                    alert(data.error || 'Failed to add teacher');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Edit teacher
        async function editTeacher(id) {
            try {
                const response = await fetch('api/teachers.php');
                const data = await response.json();
                if (data.success) {
                    const teacher = data.data.find(t => t.id === id);
                    if (teacher) {
                        document.getElementById('editTeacherId').value = teacher.id;
                        document.getElementById('editTeacherFirstName').value = teacher.first_name;
                        document.getElementById('editTeacherLastName').value = teacher.last_name;
                        document.getElementById('editTeacherEmail').value = teacher.email;
                        document.getElementById('editTeacherPhone').value = teacher.phone;
                        document.getElementById('editTeacherIdNumber').value = teacher.id_number;
                        document.getElementById('editTeacherType').value = teacher.teacher_type || 'subject_teacher';
                        document.getElementById('editTeacherStatus').value = teacher.status;
                        document.getElementById('editTeacherAddress').value = teacher.address || '';
                        
                        // Toggle fields based on teacher type
                        toggleEditTeacherTypeFields();
                        
                        if (teacher.teacher_type === 'class_teacher') {
                            document.getElementById('editTeacherClassId').value = teacher.class_id || '';
                            document.getElementById('editTeacherStreamId').value = teacher.stream_id || '';
                            
                            // Load streams for the selected class
                            if (teacher.class_id) {
                                const streamResponse = await fetch(`api/streams.php?class_id=${teacher.class_id}`);
                                const streamData = await streamResponse.json();
                                if (streamData.success) {
                                    const options = streamData.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                                    document.getElementById('editTeacherStreamId').innerHTML = '<option value="">Select Stream</option>' + options;
                                }
                            }
                        }
                        
                        // Load subject assignments (for both teacher types)
                        const container = document.getElementById('editSubjectAssignments');
                        container.innerHTML = '';
                        
                        if (teacher.subject_assignments && teacher.subject_assignments.length > 0) {
                            teacher.subject_assignments.forEach(sa => {
                                const row = document.createElement('div');
                                row.className = 'row mb-2 edit-subject-assignment';
                                row.innerHTML = `
                                    <div class="col-md-4">
                                        <select class="form-control edit-subject-class">
                                            <option value="">Select Class</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control edit-subject-id">
                                            <option value="">Select Subject</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeEditSubjectAssignment(this)">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                `;
                                container.appendChild(row);
                                
                                // Load classes and subjects, then select the assigned values
                                loadClassesForSubjectDropdown(row.querySelector('.edit-subject-class'));
                                loadSubjectsForDropdown(row.querySelector('.edit-subject-id'));
                                row.querySelector('.edit-subject-class').value = sa.class_id;
                                row.querySelector('.edit-subject-id').value = sa.subject_id;
                            });
                        } else {
                            // Add one empty row
                            addEditSubjectAssignment();
                        }
                        
                        new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading teacher:', error);
            }
        }
        
        // Update teacher
        async function updateTeacher() {
            const teacherType = document.getElementById('editTeacherType').value;
            const teacherData = {
                teacher_id: document.getElementById('editTeacherId').value,
                first_name: document.getElementById('editTeacherFirstName').value,
                last_name: document.getElementById('editTeacherLastName').value,
                email: document.getElementById('editTeacherEmail').value,
                phone: document.getElementById('editTeacherPhone').value,
                id_number: document.getElementById('editTeacherIdNumber').value,
                teacher_type: teacherType,
                status: document.getElementById('editTeacherStatus').value,
                address: document.getElementById('editTeacherAddress').value
            };
            
            if (teacherType === 'class_teacher') {
                teacherData.class_id = document.getElementById('editTeacherClassId').value || null;
                teacherData.stream_id = document.getElementById('editTeacherStreamId').value || null;
                if (!teacherData.class_id) {
                    alert('Class teachers must be assigned to a class');
                    return;
                }
            }
            
            // Collect subject assignments (for both teacher types)
            const subjectAssignments = [];
            document.querySelectorAll('.edit-subject-assignment').forEach(row => {
                const classId = row.querySelector('.edit-subject-class').value;
                const subjectId = row.querySelector('.edit-subject-id').value;
                if (classId && subjectId) {
                    subjectAssignments.push({ class_id: classId, subject_id: subjectId });
                }
            });
            
            // Subject teachers must have at least one subject assignment
            if (teacherType === 'subject_teacher' && subjectAssignments.length === 0) {
                alert('Subject teachers must have at least one subject assignment');
                return;
            }
            
            if (subjectAssignments.length > 0) {
                teacherData.subject_assignments = subjectAssignments;
            }
            
            try {
                const response = await fetch('api/teachers.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(teacherData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Teacher updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editTeacherModal')).hide();
                    loadTeachers();
                } else {
                    alert(data.error || 'Failed to update teacher');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Delete teacher
        async function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                try {
                    const response = await fetch(`api/teachers.php?id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Teacher deleted successfully!');
                        loadTeachers();
                    } else {
                        alert(data.error || 'Failed to delete teacher');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        // Initialize
        loadClassesDropdown();
        loadClassesForFilters();
        loadTeachers();
    </script>
    <script src="../assets/js/notifications.js"></script>

    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
