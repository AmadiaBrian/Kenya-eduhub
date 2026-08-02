<?php
// Examiners Dashboard
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
    header('Location: index.php?route=login');
    exit;
}

$examiner_id = $_SESSION['examiner_id'];
$examiner_name = $_SESSION['examiner_name'] ?? 'Examiner';
$school_id = $_SESSION['examiner_school_id'];

// Get examiner details
try {
    $stmt = $pdo->prepare("SELECT * FROM examination_department_heads WHERE id = ?");
    $stmt->execute([$examiner_id]);
    $examiner = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching examiner details: " . $e->getMessage());
    $examiner = [];
}

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

// Get statistics for the dashboard
$stats = [
    'pending_exams' => 0,
    'completed_exams' => 0,
    'total_students' => 0,
    'total_classes' => 0,
    'total_streams' => 0,
    'total_subjects' => 0,
    'total_exam_types' => 0,
    'total_attendance_records' => 0
];

try {
    // Get pending exams (exams that haven't been completed yet)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM exams WHERE school_id = ? AND status = 'pending'");
    $stmt->execute([$school_id]);
    $stats['pending_exams'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching pending exams: " . $e->getMessage());
}

try {
    // Get completed exams
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM exams WHERE school_id = ? AND status = 'completed'");
    $stmt->execute([$school_id]);
    $stats['completed_exams'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching completed exams: " . $e->getMessage());
}

try {
    // Get total students for this school
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_students'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching students count: " . $e->getMessage());
}

try {
    // Get total classes for this school
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_classes'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching classes count: " . $e->getMessage());
}

try {
    // Get total streams for this school
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id) as count FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_streams'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching streams count: " . $e->getMessage());
}

try {
    // Get total subjects for this school
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subjects WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$school_id]);
    $stats['total_subjects'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching subjects count: " . $e->getMessage());
}

try {
    // Get total exam types for this school
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM exam_types WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_exam_types'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching exam types count: " . $e->getMessage());
}

try {
    // Get total attendance records for this school
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_attendance_records'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching attendance records count: " . $e->getMessage());
}

// Get recent exams for the dashboard
try {
    $stmt = $pdo->prepare("SELECT id, exam_name, exam_date, status FROM exams WHERE school_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$school_id]);
    $recent_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent exams: " . $e->getMessage());
    $recent_exams = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Dashboard - <?php echo htmlspecialchars($examiner_name); ?></title>
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
        
        .stat-card {
            text-align: center;
            padding: 24px;
        }
        
        .stat-card .icon {
            font-size: 32px;
            color: #FF6B35;
            margin-bottom: 12px;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #5f6368;
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
        
        .badge-draft {
            background: #f1f3f4;
            color: #5f6368;
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
        
        .badge-warning {
            background: #fef7e0;
            color: #b06000;
        }
            margin-bottom: 0;
            background: white;
            border: 1px solid #000;
            border-radius: 0;
            overflow: hidden;
        }
        
        .table th {
            font-weight: 600;
            color: #000;
            border-bottom: 2px solid #000;
            border-right: 1px solid #000;
            background: #f8f9fa;
        }
        
        .table th:last-child {
            border-right: none;
        }
        
        .table td {
            vertical-align: middle;
            background: white;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }
        
        .table td:last-child {
            border-right: none;
        }
        
        .table tr:last-child td {
            border-bottom: none;
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
                <a class="nav-link active" href="dashboard">
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
                <a class="nav-link" href="exam-types">
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
            <h1 class="page-title">Dashboard</h1>
            
            <!-- Stats Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div style="background: #e8f0fe; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #1967d2; margin-bottom: 12px;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #1967d2;"><?php echo $stats['pending_exams']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Pending Exams</div>
                </div>
                <div style="background: #e6f4ea; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #137333; margin-bottom: 12px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #137333;"><?php echo $stats['completed_exams']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Completed Exams</div>
                </div>
                <div style="background: #fef7e0; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #b06000; margin-bottom: 12px;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #b06000;"><?php echo $stats['total_students']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Students</div>
                </div>
                <div style="background: #fce8e6; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #c5221f; margin-bottom: 12px;">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #c5221f;"><?php echo $stats['total_classes']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Classes</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div style="background: #e8f0fe; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #1967d2; margin-bottom: 12px;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #1967d2;"><?php echo $stats['total_streams']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Streams</div>
                </div>
                <div style="background: #e6f4ea; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #137333; margin-bottom: 12px;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #137333;"><?php echo $stats['total_subjects']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Subjects</div>
                </div>
                <div style="background: #fef7e0; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #b06000; margin-bottom: 12px;">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #b06000;"><?php echo $stats['total_exam_types']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Exam Types</div>
                </div>
                <div style="background: #fce8e6; padding: 24px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; color: #c5221f; margin-bottom: 12px;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div style="font-size: 36px; font-weight: 600; color: #c5221f;"><?php echo $stats['total_attendance_records']; ?></div>
                    <div style="font-size: 14px; color: #5f6368;">Attendance Records</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="subjects" class="btn btn-primary">
                            <i class="fas fa-book me-2"></i>Manage Subjects
                        </a>
                        <a href="exam-types" class="btn btn-primary">
                            <i class="fas fa-clipboard-list me-2"></i>Manage Exam Types
                        </a>
                        <a href="performance" class="btn btn-primary">
                            <i class="fas fa-chart-line me-2"></i>View Performance
                        </a>
                        <a href="grading" class="btn btn-primary">
                            <i class="fas fa-chart-bar me-2"></i>Manage Grading
                        </a>
                        <a href="results" class="btn btn-primary">
                            <i class="fas fa-award me-2"></i>View Results
                        </a>
                        <a href="students" class="btn btn-primary">
                            <i class="fas fa-user-graduate me-2"></i>View Students
                        </a>
                        <a href="classes" class="btn btn-primary">
                            <i class="fas fa-chalkboard me-2"></i>View Classes
                        </a>
                        <a href="streams" class="btn btn-primary">
                            <i class="fas fa-layer-group me-2"></i>View Streams
                        </a>
                        <a href="attendance" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>View Attendance
                        </a>
                        <a href="timetable" class="btn btn-primary">
                            <i class="fas fa-calendar-alt me-2"></i>View Timetable
                        </a>
                        <a href="calendar" class="btn btn-primary">
                            <i class="fas fa-calendar me-2"></i>View Calendar
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Exams -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-2"></i>Recent Exams
                </div>
                <div class="card-body">
                    <?php if (empty($recent_exams)): ?>
                        <p style="color: #5f6368;">No exams found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Exam Name</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_exams as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                            <td><?php echo $exam['exam_date'] ? date('M d, Y', strtotime($exam['exam_date'])) : 'Not set'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $exam['status'] === 'completed' ? 'active' : 'draft'; ?>">
                                                    <?php echo ucfirst($exam['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
    </script>
</body>
</html>
