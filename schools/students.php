<?php
// Students Management Page
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

// Get school admission prefix
try {
    $stmt = $pdo->prepare("SELECT admission_prefix FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $admission_prefix = $school['admission_prefix'] ?? '';
} catch (PDOException $e) {
    error_log("Failed to fetch school prefix: " . $e->getMessage());
    $admission_prefix = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Students - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
            --sidebar-width: 256px;
            --header-height: 64px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
        }

        .material-icons {
            vertical-align: middle;
            font-size: 20px;
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
            border: 1px solid #e8eaed;
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
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
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

        .btn-full-width {
            width: 100%;
        }

        .card-header .btn {
            flex: 1;
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

            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #dadce0;
            }

            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            }

            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e8eaed;
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
            
            .table th,
            .table td {
                padding: 8px;
                font-size: 12px;
            }
            
            .table-responsive {
                margin: 0 -16px;
                padding: 0 16px;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 12px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
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
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 6px 4px;
                font-size: 11px;
            }
            
            .table th:nth-child(n+4),
            .table td:nth-child(n+4) {
                display: none;
            }
            
            .table th:nth-child(1),
            .table td:nth-child(1),
            .table th:nth-child(2),
            .table td:nth-child(2),
            .table th:nth-child(3),
            .table td:nth-child(3) {
                display: table-cell;
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
        .fa-edit { color: #0066DA; }
        .fa-trash { color: #EA4335; }
    </style>
</head>
<body class="dashboard-page">
</br>
</br>
</br>
</br>
</br>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'students'; require_once 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Students Management</h1>
        
        <!-- Quick Access -->
        <h2 class="card-title">Quick Access</h2>
        <div class="quick-access-grid">
            <a href="dashboard" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-icons" style="color: #5f6368;">dashboard</span>
                </div>
                <div class="quick-access-label">Dashboard</div>
            </a>
            <a href="teachers" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-icons" style="color: #5f6368;">person_apron</span>
                </div>
                <div class="quick-access-label">Teachers</div>
            </a>
            <a href="classes" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-icons" style="color: #5f6368;">class</span>
                </div>
                <div class="quick-access-label">Classes</div>
            </a>
            <a href="attendance" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-icons" style="color: #5f6368;">event_available</span>
                </div>
                <div class="quick-access-label">Attendance</div>
            </a>
            <a href="performance" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-icons" style="color: #5f6368;">show_chart</span>
                </div>
                <div class="quick-access-label">Performance</div>
            </a>
            <a href="fees" class="quick-access-item">
                <div class="quick-access-icon">
                    <span class="material-icons" style="color: #5f6368;">payments</span>
                </div>
                <div class="quick-access-label">Fees</div>
            </a>
        </div>
        
        <!-- Student Statistics -->
        <div class="card">
            <h2 class="card-title">Student Statistics</h2>
            <div id="studentStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                <?php
                $totalStudents = 0;
                $maleStudents = 0;
                $femaleStudents = 0;
                $activeStudents = 0;
                $inactiveStudents = 0;

                try {
                    $stmt = $pdo->prepare("SELECT gender, status FROM students WHERE school_id = ?");
                    $stmt->execute([$school_id]);
                    $students = $stmt->fetchAll();

                    foreach ($students as $student) {
                        $totalStudents++;
                        if ($student['gender'] === 'Male') $maleStudents++;
                        if ($student['gender'] === 'Female') $femaleStudents++;
                        if ($student['status'] === 'active') $activeStudents++;
                        if ($student['status'] === 'inactive') $inactiveStudents++;
                    }
                } catch (PDOException $e) {
                    error_log("Failed to fetch student stats: " . $e->getMessage());
                }
                ?>
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalStudents; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Students</div>
                </div>
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $maleStudents; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Male</div>
                </div>
                <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $femaleStudents; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Female</div>
                </div>
                <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $activeStudents; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Active</div>
                </div>
                <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #5f6368;"><?php echo $inactiveStudents; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Inactive</div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="card">
            <h2 class="card-title">Search & Filter</h2>
            <div class="row mb-3" style="gap: 12px;">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchStudent" placeholder="Search by name or admission number..." onkeyup="filterStudents()">
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterGender" onchange="filterStudents()">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterStatus" onchange="filterStudents()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="transferred">Transferred</option>
                        <option value="graduated">Graduated</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterClass" onchange="filterStudents()">
                        <option value="">All Classes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterStream" onchange="filterStudents()">
                        <option value="">All Streams</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-column gap-2 mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal" style="border-radius: 25px;">
                <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">add</span> Add Student
            </button>
            <button class="btn btn-success" onclick="exportStudents()" style="border-radius: 25px;">
                <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">download</span> Export
            </button>
        </div>

        <!-- Students Table Card -->
        <div class="card" style="padding: 0;">
            <h2 class="card-title" style="padding: 24px 24px 16px 24px;">All Students</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Admission No</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th>Fee Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTable">
                        <tr><td colspan="9" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal - Google Material Design Style -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addStudentForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Names</label>
                                <input type="text" class="form-control" id="firstName" placeholder="Enter both first names" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-control" id="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dateOfBirth" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admission Date</label>
                                <input type="date" class="form-control" id="admissionDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admission Number</label>
                                <div class="input-group">
                                    <?php if (!empty($admission_prefix)): ?>
                                        <span class="input-group-text"><?php echo htmlspecialchars($admission_prefix); ?>/</span>
                                    <?php endif; ?>
                                    <input type="text" class="form-control" id="admissionNumberDisplay" placeholder="Auto-generated" readonly>
                                    <small class="form-text text-muted">Auto-generated</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-control" id="classId">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stream</label>
                                <select class="form-control" id="streamId">
                                    <option value="">Select Stream</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addStudent()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Add Student</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal - Google Material Design Style -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="editStudentForm">
                        <input type="hidden" id="editStudentId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Names</label>
                                <input type="text" class="form-control" id="editFirstName" placeholder="Enter both first names" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-control" id="editGender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admission Number</label>
                                <input type="text" class="form-control" id="editAdmissionNumber" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" id="editStatus">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="transferred">Transferred</option>
                                    <option value="graduated">Graduated</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-control" id="editClassId">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stream</label>
                                <select class="form-control" id="editStreamId">
                                    <option value="">Select Stream</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateStudent()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Update Student</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load students
        async function loadStudents() {
            try {
                const response = await fetch('api/students.php');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('studentsTable');
                    tbody.innerHTML = data.data.map(student => {
                        const balance = student.fee_balance || 0;
                        const balanceClass = balance > 0 ? 'text-danger' : (balance < 0 ? 'text-success' : 'text-muted');
                        const balanceText = balance > 0 ? `KES ${balance.toFixed(2)} (Due)` : (balance < 0 ? `KES ${Math.abs(balance).toFixed(2)} (Overpaid)` : 'KES 0.00 (Paid)');
                        
                        return `
                        <tr>
                            <td>${student.admission_number}</td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${student.gender}</td>
                            <td>${student.class_name || '-'}</td>
                            <td>${student.stream_name || '-'}</td>
                            <td>${student.parent_name || '-'}</td>
                            <td><span class="badge badge-${student.status}">${student.status}</span></td>
                            <td class="${balanceClass}">
                                <strong>${balanceText}</strong>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <button class="btn btn-sm btn-action" onclick="editStudent(${student.id})">
                                        <span class="material-icons" style="color: #5f6368;">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-action" onclick="deleteStudent(${student.id})">
                                        <span class="material-icons" style="color: #d93025;">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `}).join('');
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }
        
        // Load dropdowns
        async function loadDropdowns() {
            try {
                const classesRes = await fetch('api/classes.php');
                const classesData = await classesRes.json();
                if (classesData.success) {
                    const options = classesData.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                    document.getElementById('classId').innerHTML = '<option value="">Select Class</option>' + options;
                    document.getElementById('editClassId').innerHTML = '<option value="">Select Class</option>' + options;
                    document.getElementById('filterClass').innerHTML = '<option value="">All Classes</option>' + options;
                }
            } catch (error) {
                console.error('Error loading dropdowns:', error);
            }
        }
        
        // Load streams based on class
        document.getElementById('classId').addEventListener('change', async function() {
            const classId = this.value;
            if (classId) {
                try {
                    const response = await fetch(`api/streams.php?class_id=${classId}`);
                    const data = await response.json();
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                        document.getElementById('streamId').innerHTML = '<option value="">Select Stream</option>' + options;
                    }
                } catch (error) {
                    console.error('Error loading streams:', error);
                }
            }
        });
        
        // Load streams for filter based on class
        document.getElementById('filterClass').addEventListener('change', async function() {
            const classId = this.value;
            if (classId) {
                try {
                    const response = await fetch(`api/streams.php?class_id=${classId}`);
                    const data = await response.json();
                    if (data.success) {
                        const options = data.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                        document.getElementById('filterStream').innerHTML = '<option value="">All Streams</option>' + options;
                    }
                } catch (error) {
                    console.error('Error loading streams:', error);
                }
            } else {
                document.getElementById('filterStream').innerHTML = '<option value="">All Streams</option>';
            }
        });
        
        // Filter students
        function filterStudents() {
            const search = document.getElementById('searchStudent').value.toLowerCase();
            const gender = document.getElementById('filterGender').value;
            const status = document.getElementById('filterStatus').value;
            const classId = document.getElementById('filterClass').value;
            const streamId = document.getElementById('filterStream').value;
            
            const rows = document.querySelectorAll('#studentsTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const admission = cells[0].textContent.toLowerCase();
                const name = cells[1].textContent.toLowerCase();
                const rowGender = cells[2].textContent;
                const rowClass = cells[3].textContent;
                const rowStream = cells[4].textContent;
                const rowStatus = cells[6].textContent.toLowerCase();
                
                const matchesSearch = admission.includes(search) || name.includes(search);
                const matchesGender = !gender || rowGender === gender;
                const matchesStatus = !status || rowStatus.includes(status.toLowerCase());
                const matchesClass = !classId || rowClass.includes(classId);
                const matchesStream = !streamId || rowStream.includes(streamId);
                
                row.style.display = (matchesSearch && matchesGender && matchesStatus && matchesClass && matchesStream) ? '' : 'none';
            });
        }
        
        // Export students to CSV
        function exportStudents() {
            const rows = document.querySelectorAll('#studentsTable tr');
            let csvContent = 'Admission Number,Name,Gender,Class,Stream,Parent,Status,Fee Balance\n';
            
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
                    cells[6].textContent,
                    cells[7].textContent
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
            link.setAttribute('download', `students_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Add student
        async function addStudent() {
            const studentData = {
                first_name: document.getElementById('firstName').value,
                last_name: document.getElementById('lastName').value,
                gender: document.getElementById('gender').value,
                date_of_birth: document.getElementById('dateOfBirth').value,
                admission_date: document.getElementById('admissionDate').value,
                class_id: document.getElementById('classId').value || null,
                stream_id: document.getElementById('streamId').value || null
            };
            
            try {
                const response = await fetch('api/students.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(studentData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Student added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
                    document.getElementById('addStudentForm').reset();
                    loadStudents();
                } else {
                    alert(data.error || 'Failed to add student');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Edit student
        async function editStudent(id) {
            try {
                const response = await fetch('api/students.php');
                const data = await response.json();
                if (data.success) {
                    const student = data.data.find(s => s.id === id);
                    if (student) {
                        document.getElementById('editStudentId').value = student.id;
                        document.getElementById('editFirstName').value = student.first_name;
                        document.getElementById('editLastName').value = student.last_name;
                        document.getElementById('editGender').value = student.gender;
                        document.getElementById('editAdmissionNumber').value = student.admission_number;
                        document.getElementById('editStatus').value = student.status;
                        document.getElementById('editClassId').value = student.class_id || '';
                        new bootstrap.Modal(document.getElementById('editStudentModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading student:', error);
            }
        }
        
        // Update student
        async function updateStudent() {
            const studentData = {
                student_id: document.getElementById('editStudentId').value,
                first_name: document.getElementById('editFirstName').value,
                last_name: document.getElementById('editLastName').value,
                gender: document.getElementById('editGender').value,
                status: document.getElementById('editStatus').value,
                class_id: document.getElementById('editClassId').value || null
            };
            
            try {
                const response = await fetch('api/students.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(studentData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Student updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
                    loadStudents();
                } else {
                    alert(data.error || 'Failed to update student');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Delete student
        async function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                try {
                    const response = await fetch(`api/students.php?id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Student deleted successfully!');
                        loadStudents();
                    } else {
                        alert(data.error || 'Failed to delete student');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        loadStudents();
        loadDropdowns();
    </script>
    <script src="../assets/js/notifications.js"></script>
    
    <!-- Footer -->
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
