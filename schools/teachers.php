<?php
// Teachers Management Page
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['school_id'])) {
    header('Location: index.php');
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
    <title>Teachers - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 256px;
            --header-height: 64px;
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
        
        /* Header */
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-color);
            border-bottom: 1px solid #e8eaed;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            color: #5f6368;
            transition: background 0.2s;
        }
        
        .menu-btn:hover {
            background: #f1f3f4;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 400;
            color: #202124;
        }
        
        .logo i {
            color: var(--primary-color);
        }
        
        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .school-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
        }
        
        .sidebar.collapsed {
            transform: translateX(-256px);
        }
        
        .sidebar-section {
            padding: 12px 0;
        }
        
        .sidebar-title {
            padding: 8px 24px;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
        }
        
        .nav-link:hover {
            background: #f1f3f4;
        }
        
        .nav-link.active {
            background: #e8f0fe;
            color: var(--primary-color);
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: #FF6B35;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
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
        }
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
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
        
        .table {
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
            width: 100%;
            margin: 0;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        .table th {
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: 600;
            color: #000;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 12px;
            border: 1px solid #000;
            color: #000;
            font-size: 13px;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }
            
            .header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                padding: 0 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                transform: none !important;
            }
            
            .logo span {
                font-size: 18px;
            }
            
            .menu-btn {
                padding: 8px;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .menu-btn:hover {
                background: rgba(0,0,0,0.04);
            }
            
            .sidebar {
                position: fixed !important;
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
            }
            
            .table {
                min-width: 600px;
                width: 100%;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="students.php">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link active" href="teachers.php">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes.php">
                <i class="fas fa-chalkboard"></i> Classes
            </a>
            <a class="nav-link" href="streams.php">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link" href="subjects.php">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="parents.php">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="performance.php">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance.php">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="fees.php">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Teachers Management</h1>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="dashboard.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="quick-access-label">Dashboard</div>
                </a>
                <a href="students.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="quick-access-label">Students</div>
                </a>
                <a href="classes.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
                <a href="attendance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="performance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="fees.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="quick-access-label">Fees</div>
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Teachers</span>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus me-2"></i> Add Teacher
                </button>
            </div>
            <div class="card-body">
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
    </div>
    
    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addSubjectAssignment()">
                                    <i class="fas fa-plus me-2"></i> Add Subject Assignment
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addTeacher()">Add Teacher</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                                    <i class="fas fa-plus me-2"></i> Add Subject Assignment
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateTeacher()">Update Teacher</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        
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
                        <i class="fas fa-times"></i>
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
                        <i class="fas fa-times"></i>
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
                                <button class="btn btn-sm btn-primary" onclick="editTeacher(${teacher.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${teacher.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
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
                                    <i class="fas fa-times"></i>
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
                                            <i class="fas fa-times"></i>
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
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        // Initialize
        loadClassesDropdown();
        loadTeachers();
    </script>
    
    <!-- Footer -->
    <footer style="background: transparent; color: white; padding: 2rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
