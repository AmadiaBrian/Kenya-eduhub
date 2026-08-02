<?php
// Exam Types Management Page for Examiners
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['examiner_id']) || !isset($_SESSION['examiner_token'])) {
    header('Location: login');
    exit;
}

$examiner_id = $_SESSION['examiner_id'];
$examiner_name = $_SESSION['examiner_name'] ?? 'Examiner';
$school_id = $_SESSION['examiner_school_id'];

// Get school name
try {
    $stmt = $pdo->prepare("SELECT school_name FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $school_name = $school['school_name'] ?? 'School';
} catch (PDOException $e) {
    error_log("Error fetching school name: " . $e->getMessage());
    $school_name = 'School';
}

// Handle form submissions
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exam_type'])) {
        $exam_type_name = trim($_POST['exam_type_name'] ?? '');
        $exam_type_code = trim($_POST['exam_type_code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($exam_type_name)) {
            $errors[] = 'Exam type name is required';
        }
        if (empty($exam_type_code)) {
            $errors[] = 'Exam type code is required';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO exam_types (school_id, exam_type_name, exam_type_code, description, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $exam_type_name, $exam_type_code, $description, $examiner_id]);
                $success = 'Exam type added successfully!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = 'Exam type code already exists for this school';
                } else {
                    $errors[] = 'Failed to add exam type. Please try again.';
                }
            }
        }
    }
    
    if (isset($_POST['edit_exam_type'])) {
        $exam_type_id = intval($_POST['exam_type_id'] ?? 0);
        $exam_type_name = trim($_POST['exam_type_name'] ?? '');
        $exam_type_code = trim($_POST['exam_type_code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($exam_type_name)) {
            $errors[] = 'Exam type name is required';
        }
        if (empty($exam_type_code)) {
            $errors[] = 'Exam type code is required';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE exam_types SET exam_type_name = ?, exam_type_code = ?, description = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$exam_type_name, $exam_type_code, $description, $exam_type_id, $school_id]);
                $success = 'Exam type updated successfully!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = 'Exam type code already exists for this school';
                } else {
                    $errors[] = 'Failed to update exam type. Please try again.';
                }
            }
        }
    }
    
    if (isset($_POST['delete_exam_type'])) {
        $exam_type_id = intval($_POST['exam_type_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM exam_types WHERE id = ? AND school_id = ?");
            $stmt->execute([$exam_type_id, $school_id]);
            $success = 'Exam type deleted successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Failed to delete exam type. It may be in use.';
        }
    }
}

// Get exam types
$exam_types = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM exam_types WHERE school_id = ? ORDER BY created_at DESC");
    $stmt->execute([$school_id]);
    $exam_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Failed to load exam types.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Exam Types - <?php echo htmlspecialchars($examiner_name); ?></title>
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
            font-size: 18px;
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
            transition: transform 0.3s ease, margin-left 0.3s ease;
            z-index: 999;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
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
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
        }
        
        .sidebar-title .chevron {
            transition: transform 0.3s ease;
        }
        
        .sidebar-title.collapsed .chevron {
            transform: rotate(-90deg);
        }
        
        .sidebar-links {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-links.collapsed {
            max-height: 0;
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
            padding-bottom: 80px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding: 16px 20px;
            font-weight: 500;
            color: var(--secondary-color);
            border-radius: 12px;
        }
        
        .card-body {
            padding: 20px;
            border-radius: 12px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        /* Buttons */
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
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #3c4043;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
            cursor: pointer;
        }
        
        .btn-action:hover {
            background: #e9ecef;
        }
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
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
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .table td {
            padding: 12px;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        /* Badge */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-archived {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-success {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid rgba(19, 115, 51, 0.1);
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid rgba(197, 34, 31, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary-color);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #dadce0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
                z-index: 9999;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                padding: 16px;
                padding-bottom: 80px;
            }

            .main-content.expanded {
                margin-left: 0 !important;
            }

            .header {
                padding: 0 16px;
            }

            .menu-btn {
                padding: 8px;
            }

            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            /* Grid layouts to single column */
            [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }

            /* Table responsive */
            .table-responsive {
                overflow-x: auto;
            }

            /* Button sizing adjustments */
            .btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .btn-primary {
                width: 100%;
                margin-bottom: 8px;
            }
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
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="school-avatar">
                <?php echo strtoupper(substr($examiner_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Main <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Examination <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="subjects">
                    <i class="fas fa-book"></i> Subjects
                </a>
                <a class="nav-link active" href="exam-types">
                    <i class="fas fa-clipboard-list"></i> Exam Types
                </a>
                <a class="nav-link" href="performance">
                    <i class="fas fa-chart-line"></i> Performance
                </a>
                <a class="nav-link" href="grading">
                    <i class="fas fa-chart-bar"></i> Grading System
                </a>
                <a class="nav-link" href="results">
                    <i class="fas fa-award"></i> Results
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                School <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="students">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a class="nav-link" href="classes">
                    <i class="fas fa-chalkboard"></i> Classes
                </a>
                <a class="nav-link" href="streams">
                    <i class="fas fa-layer-group"></i> Streams
                </a>
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link" href="timetable">
                    <i class="fas fa-calendar-alt"></i> Timetable
                </a>
                <a class="nav-link" href="calendar">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <h1 class="page-title">Exam Types</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Exam Type Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>Add New Exam Type
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label">Exam Type Name *</label>
                            <input type="text" class="form-control" name="exam_type_name" placeholder="e.g., CAT, Mid Term, End Term" required>
                        </div>
                        <div>
                            <label class="form-label">Exam Type Code *</label>
                            <input type="text" class="form-control" name="exam_type_code" placeholder="e.g., CAT, MID_TERM, END_TERM" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Description of this exam type..."></textarea>
                    </div>
                    <button type="submit" name="add_exam_type" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Exam Type
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Exam Types List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Existing Exam Types
            </div>
            <div class="card-body">
                <?php if (empty($exam_types)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h5>No Exam Types Found</h5>
                        <p>Add your first exam type using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exam_types as $exam_type): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam_type['exam_type_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam_type['exam_type_code']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($exam_type['description'], 0, 50)) . (strlen($exam_type['description']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <?php if ($exam_type['is_active']): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-archived">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-action" onclick="editExamType(<?php echo $exam_type['id']; ?>, '<?php echo htmlspecialchars($exam_type['exam_type_name']); ?>', '<?php echo htmlspecialchars($exam_type['exam_type_code']); ?>', '<?php echo htmlspecialchars($exam_type['description']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-action" onclick="openDeleteModal(<?php echo $exam_type['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content">
                <div class="modal-body" style="padding: 32px; text-align: center;">
                    <div style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #FF6B35;"></i>
                    </div>
                    <h3 style="font-size: 22px; font-weight: 400; color: #202124; margin: 0 0 12px 0;">Delete Exam Type</h3>
                    <p style="font-size: 14px; color: #5f6368; margin: 0;">Are you sure you want to delete this exam type? This action cannot be undone.</p>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px; display: flex; justify-content: center; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Exam Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editForm">
                        <input type="hidden" name="exam_type_id" id="editExamTypeId">
                        <div class="mb-3">
                            <label class="form-label">Exam Type Name *</label>
                            <input type="text" class="form-control" name="exam_type_name" id="editExamTypeName" required placeholder="e.g., CAT, Mid Term, End Term">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Exam Type Code *</label>
                            <input type="text" class="form-control" name="exam_type_code" id="editExamTypeCode" required placeholder="e.g., CAT, MID_TERM, END_TERM">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3" placeholder="Description of this exam type..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editForm" name="edit_exam_type" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: #f8f9fa; border-top: 1px solid #e8eaed; padding: 12px 24px; text-align: center; font-size: 12px; color: #5f6368; z-index: 998;">
        &copy; <?php echo date('Y'); ?> Kenya EduHub - Examiners Portal
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth <= 768) {
                // Mobile: toggle show/hide
                sidebar.classList.toggle('show');
            } else {
                // Desktop: toggle collapse/expand
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }

        // Handle window resize to reset sidebar state
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth > 768) {
                // Desktop: ensure proper state
                sidebar.classList.remove('show');
            } else {
                // Mobile: ensure sidebar is hidden by default
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                sidebar.classList.remove('show');
            }
        });
        
        function toggleSidebarSection(titleElement) {
            const linksContainer = titleElement.nextElementSibling;
            const isCollapsed = linksContainer.classList.contains('collapsed');
            
            linksContainer.classList.toggle('collapsed');
            titleElement.classList.toggle('collapsed');
        }
        
        function editExamType(id, name, code, description) {
            document.getElementById('editExamTypeId').value = id;
            document.getElementById('editExamTypeName').value = name;
            document.getElementById('editExamTypeCode').value = code;
            document.getElementById('editDescription').value = description;
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
        
        function openDeleteModal(examTypeId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
            
            document.getElementById('confirmDeleteBtn').onclick = function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="exam_type_id" value="' + examTypeId + '">' +
                               '<input type="hidden" name="delete_exam_type" value="1">';
                document.body.appendChild(form);
                form.submit();
            };
        }
    </script>
</body>
</html>
