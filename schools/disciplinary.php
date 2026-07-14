<?php
// School Disciplinary Management Page
// Authentication is handled by index.php router
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';
$admission_prefix = $_SESSION['admission_prefix'] ?? '';

// Get disciplinary statistics
try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action_type = 'expulsion' THEN 1 ELSE 0 END) as expulsions,
        SUM(CASE WHEN action_type = 'suspension' THEN 1 ELSE 0 END) as suspensions,
        SUM(CASE WHEN action_type = 'warning' THEN 1 ELSE 0 END) as warnings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN YEAR(incident_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_year
        FROM disciplinary_records WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total' => 0, 'expulsions' => 0, 'suspensions' => 0, 'warnings' => 0, 'pending' => 0, 'this_year' => 0];
}

// Get recent disciplinary records
try {
    $stmt = $pdo->prepare("SELECT dr.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
             s.class_id, s.stream_id, c.class_name, st.stream_name
             FROM disciplinary_records dr
             JOIN students s ON dr.student_id = s.id
             LEFT JOIN classes c ON s.class_id = c.id
             LEFT JOIN streams st ON s.stream_id = st.id
             WHERE dr.school_id = ?
             ORDER BY dr.incident_date DESC, dr.created_at DESC
             LIMIT 10");
    $stmt->execute([$school_id]);
    $recent_records = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_records = [];
}

// Get students for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, admission_number, CONCAT(first_name, ' ', last_name) as student_name, class_id 
             FROM students WHERE school_id = ? AND status = 'active' ORDER BY admission_number");
    $stmt->execute([$school_id]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
}

// Get classes for filter
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $classes = [];
}

// Get streams for filter (grouped by class for dynamic loading)
try {
    $stmt = $pdo->prepare("SELECT s.id, s.stream_name, s.class_id, c.class_name FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = ? ORDER BY c.class_name, s.stream_name");
    $stmt->execute([$school_id]);
    $streams = $stmt->fetchAll();
    
    // Group streams by class_id for easier filtering
    $streams_by_class = [];
    foreach ($streams as $stream) {
        if (!isset($streams_by_class[$stream['class_id']])) {
            $streams_by_class[$stream['class_id']] = [];
        }
        $streams_by_class[$stream['class_id']][] = $stream;
    }
    
    // Debug: Check if streams are being fetched
    if (empty($streams)) {
        error_log("No streams found for school_id: $school_id");
    }
} catch (PDOException $e) {
    error_log("Error fetching streams: " . $e->getMessage());
    $streams = [];
    $streams_by_class = [];
}

// Get disciplinary action types from database
try {
    $stmt = $pdo->prepare("SELECT * FROM disciplinary_action_types ORDER BY action_name");
    $stmt->execute();
    $action_types = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching action types: " . $e->getMessage());
    $action_types = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary Management - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
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
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
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
        
        .stat-card {
            background: var(--bg-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--secondary-color);
            margin-top: 8px;
        }
        
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .badge-expulsion { background: #dc3545; }
        .badge-suspension { background: #ffc107; color: #000; }
        .badge-warning { background: #17a2b8; }
        .badge-probation { background: #fd7e14; }
        .badge-pending { background: #6c757d; }
        .badge-active { background: #198754; }
        .badge-resolved { background: #0d6efd; }
        
        /* PDF Table Styling */
        .pdf-table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
        }
        
        .pdf-table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #000;
        }
        
        .pdf-table th {
            border: 1px solid #000;
            padding: 8px 12px;
            text-align: left;
            font-weight: bold;
            color: #000;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .pdf-table td {
            border: 1px solid #000;
            padding: 6px 12px;
            color: #000;
            vertical-align: middle;
        }
        
        .pdf-table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        
        .pdf-table tbody tr:hover {
            background: #f0f0f0;
        }
        
        /* Filter Section Styling */
        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .filter-section .card-title {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        
        .filter-group label {
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .filter-group .form-control {
            font-size: 14px;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            transition: border-color 0.2s;
        }
        
        .filter-group .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        /* Inline Form Styling */
        .inline-form-section {
            background: white;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .inline-form-section .card-title {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
            padding-bottom: 12px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-group label {
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .form-group .form-control {
            font-size: 14px;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            transition: border-color 0.2s;
        }
        
        .form-group .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .form-group textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding-top: 16px;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
        }
        
        .modal-header {
            padding: 20px 24px;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 16px 24px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }
        
        .modal .form-label {
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .modal .form-control,
        .modal .form-select {
            font-size: 14px;
            padding: 10px 14px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        
        .modal .form-control:focus,
        .modal .form-select:focus {
            border-color: #1a73e8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .modal .form-control-plaintext {
            color: #202124;
            font-size: 14px;
            padding: 10px 0;
        }
        
        .modal .modal-content {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        .modal .modal-header {
            padding: 20px 24px;
            background: #ffffff;
        }
        
        .modal .modal-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
        }
        
        .modal .modal-body {
            padding: 24px;
            background: #f8f9fa;
        }
        
        .modal .modal-footer {
            padding: 16px 24px;
            background: #ffffff;
        }
        
        .modal .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 4px;
            padding: 8px 24px;
            font-weight: 500;
        }
        
        .modal .btn-primary:hover {
            background: #e55a2b;
            border-color: #e55a2b;
        }
        
        /* Global button override for orange theme */
        .btn-primary {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .btn-primary:hover {
            background: #e55a2b !important;
            border-color: #e55a2b !important;
        }
        
        /* Card container styling */
        .card {
            background: white;
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
        
        .modal .btn-secondary {
            background: #ffffff;
            border-color: #dadce0;
            border-radius: 4px;
            padding: 8px 24px;
            font-weight: 500;
            color: #5f6368;
        }
        
        .modal .btn-secondary:hover {
            background: #f1f3f4;
            border-color: #dadce0;
        }
        
        .modal .btn-close {
            background: transparent;
            border: none;
            opacity: 0.6;
        }
        
        .modal .btn-close:hover {
            opacity: 1;
        }
        
        .modal .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 6px;
        }
        
        .modal .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .modal .table thead th {
            background: #f8f9fa;
            font-weight: 500;
            color: #5f6368;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 12px 16px;
        }
        
        .modal .table tbody td {
            padding: 12px 16px;
            color: #202124;
            font-size: 14px;
        }
        
        .modal .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .modal h6 {
            font-size: 14px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1a73e8;
        }
        
        /* Hide modal on large screens, show inline form */
        @media (min-width: 992px) {
            #addRecordModal {
                display: none !important;
            }
        }
        
        @media (max-width: 991px) {
            #inlineAddForm {
                display: none !important;
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
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
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
                Academic <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="students">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a class="nav-link" href="teachers">
                    <i class="fas fa-chalkboard-teacher"></i> Teachers
                </a>
                <a class="nav-link" href="classes">
                    <i class="fas fa-chalkboard"></i> Classes
                </a>
                <a class="nav-link" href="streams">
                    <i class="fas fa-layer-group"></i> Streams
                </a>
                <a class="nav-link" href="subjects">
                    <i class="fas fa-book"></i> Subjects
                </a>
                <a class="nav-link" href="grading">
                    <i class="fas fa-chart-bar"></i> Grading
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Academic Records <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="performance">
                    <i class="fas fa-chart-line"></i> Performance
                </a>
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Financial <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="fees">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>
                <a class="nav-link" href="finance-managers">
                    <i class="fas fa-user-tie"></i> Finance Managers
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Administrative <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="parents">
                    <i class="fas fa-users"></i> Parents
                </a>
                <a class="nav-link active" href="disciplinary">
                    <i class="fas fa-shield-alt"></i> Disciplinary
                </a>
                <a class="nav-link" href="librarians">
                    <i class="fas fa-book-reader"></i> Librarians
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Settings <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a class="nav-link" href="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Disciplinary Management</h1>
        
        <!-- Statistics -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 32px;">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Cases</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['expulsions']; ?></div>
                <div class="stat-label">Expulsions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ffc107;"><?php echo $stats['suspensions']; ?></div>
                <div class="stat-label">Suspensions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #17a2b8;"><?php echo $stats['warnings']; ?></div>
                <div class="stat-label">Warnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #6c757d;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #0d6efd;"><?php echo $stats['this_year']; ?></div>
                <div class="stat-label">This Year</div>
            </div>
        </div>

        <!-- Add New Record Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                <i class="fas fa-plus"></i> Add Disciplinary Record
            </button>
            <button class="btn btn-secondary" onclick="loadRecords()">
                <i class="fas fa-sync"></i> Refresh
            </button>
            <a href="disciplinary-action-types.php" class="btn btn-info">
                <i class="fas fa-cog"></i> Manage Action Types
            </a>
        </div>

        <!-- Inline Add Form (Large Screens Only) -->
        <div class="inline-form-section" id="inlineAddForm">
            <div class="card-title">Add Disciplinary Record</div>
            <form id="addRecordFormInline">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Student</label>
                        <input type="text" class="form-control" id="studentSearchInline" placeholder="Search by admission number or name..." onkeyup="filterStudentDropdown()" style="margin-bottom: 8px;">
                        <select class="form-control" id="recordStudentInline" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" data-admission="<?php echo strtolower(htmlspecialchars($student['admission_number'])); ?>" data-name="<?php echo strtolower(htmlspecialchars($student['student_name'])); ?>">
                                    <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['student_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Action Type</label>
                        <select class="form-control" id="recordActionTypeInline" required>
                            <option value="">Select Action</option>
                            <?php foreach ($action_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['action_type']); ?>"><?php echo htmlspecialchars($type['action_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Severity</label>
                        <select class="form-control" id="recordSeverityInline" required>
                            <option value="minor">Minor</option>
                            <option value="moderate">Moderate</option>
                            <option value="severe">Severe</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Incident Date</label>
                        <input type="date" class="form-control" id="recordIncidentDateInline" required>
                    </div>
                    <div class="form-group">
                        <label>Action Date</label>
                        <input type="date" class="form-control" id="recordActionDateInline" required>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" class="form-control" id="recordTitleInline" required>
                    </div>
                    <div class="form-group">
                        <label>Reported By</label>
                        <input type="text" class="form-control" id="recordReportedByInline" required>
                    </div>
                    <div class="form-group">
                        <label>Handled By</label>
                        <input type="text" class="form-control" id="recordHandledByInline" required>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Description</label>
                        <textarea class="form-control" id="recordDescriptionInline" rows="2"></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Notes</label>
                        <textarea class="form-control" id="recordNotesInline" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="addRecordInline()">
                        <i class="fas fa-plus"></i> Add Record
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addRecordFormInline').reset()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="card-title">Filter Records</div>
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Class</label>
                    <select class="form-control" id="filterClass" onchange="filterRecords()">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Stream</label>
                    <select class="form-control" id="filterStream" onchange="filterRecords()">
                        <option value="">All Streams</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search (Name/Admission)</label>
                    <input type="text" class="form-control" id="searchRecords" placeholder="Type to search..." onkeyup="filterRecords()">
                </div>
                <div class="filter-group">
                    <label>Action Type</label>
                    <select class="form-control" id="filterActionType" onchange="filterRecords()">
                        <option value="">All Types</option>
                        <?php foreach ($action_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['action_type']); ?>"><?php echo htmlspecialchars($type['action_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select class="form-control" id="filterStatus" onchange="filterRecords()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="resolved">Resolved</option>
                        <option value="appealed">Appealed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button class="btn btn-secondary" onclick="clearFilters()" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Records Table -->
        <div class="card">
            <div class="card-title">Recent Disciplinary Records</div>
            <div class="table-responsive">
                <table class="table pdf-table" id="recordsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Action Type</th>
                                <th>Severity</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_records as $record): ?>
                                <tr data-class-id="<?php echo $record['class_id'] ?? ''; ?>" 
                                    data-stream-id="<?php echo $record['stream_id'] ?? ''; ?>"
                                    data-admission="<?php echo strtolower(htmlspecialchars($record['admission_number'])); ?>"
                                    data-name="<?php echo strtolower(htmlspecialchars($record['student_name'])); ?>"
                                    data-action-type="<?php echo strtolower($record['action_type']); ?>"
                                    data-status="<?php echo strtolower($record['status']); ?>">
                                    <td><?php echo date('M d, Y', strtotime($record['incident_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['admission_number']); ?></strong><br>
                                        <?php echo htmlspecialchars($record['student_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['class_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($record['action_type']); ?>">
                                            <?php echo ucfirst($record['action_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $record['severity'] === 'critical' ? 'danger' : ($record['severity'] === 'severe' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($record['severity']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                    <td>
                                        <strong><?php echo ucfirst($record['status']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="disciplinary_view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editRecord(<?php echo $record['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (in_array($record['action_type'], ['suspension', 'expulsion', 'transfer'])): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="generatePDF(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSidebarSection(titleElement) {
            const linksContainer = titleElement.nextElementSibling;
            const isCollapsed = linksContainer.classList.contains('collapsed');
            
            titleElement.classList.toggle('collapsed');
            linksContainer.classList.toggle('collapsed');
        }
        
        // Only enable collapsible sections on large screens
        function handleResize() {
            const sidebarTitles = document.querySelectorAll('.sidebar-title');
            const sidebarLinks = document.querySelectorAll('.sidebar-links');
            
            if (window.innerWidth <= 768) {
                // On mobile, expand all sections
                sidebarTitles.forEach(title => title.classList.remove('collapsed'));
                sidebarLinks.forEach(links => links.classList.remove('collapsed'));
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        function filterRecords() {
            const search = document.getElementById('searchRecords').value.toLowerCase();
            const classId = document.getElementById('filterClass').value;
            const streamId = document.getElementById('filterStream').value;
            const actionType = document.getElementById('filterActionType').value;
            const status = document.getElementById('filterStatus').value;
            
            const rows = document.querySelectorAll('#recordsTable tbody tr');
            
            rows.forEach(row => {
                const rowClassId = row.getAttribute('data-class-id') || '';
                const rowStreamId = row.getAttribute('data-stream-id') || '';
                const rowAdmission = row.getAttribute('data-admission') || '';
                const rowName = row.getAttribute('data-name') || '';
                const rowActionType = row.getAttribute('data-action-type') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                
                let showRow = true;
                
                if (classId && rowClassId !== classId) {
                    showRow = false;
                }
                
                if (streamId && rowStreamId !== streamId) {
                    showRow = false;
                }
                
                if (search && !rowAdmission.includes(search) && !rowName.includes(search)) {
                    showRow = false;
                }
                
                if (actionType && rowActionType !== actionType) {
                    showRow = false;
                }
                
                if (status && rowStatus !== status) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
        
        function clearFilters() {
            document.getElementById('filterClass').value = '';
            document.getElementById('filterStream').value = '';
            document.getElementById('searchRecords').value = '';
            document.getElementById('filterActionType').value = '';
            document.getElementById('filterStatus').value = '';
            filterRecords();
        }
        
        // Dynamic stream loading based on class selection
        const streamsByClass = <?php echo json_encode($streams_by_class); ?>;
        
        function loadStreamsForClass(classId) {
            const streamSelect = document.getElementById('filterStream');
            streamSelect.innerHTML = '<option value="">All Streams</option>';
            
            if (classId && streamsByClass[classId]) {
                streamsByClass[classId].forEach(stream => {
                    const option = document.createElement('option');
                    option.value = stream.id;
                    option.textContent = stream.stream_name;
                    streamSelect.appendChild(option);
                });
            }
        }
        
        // Update stream dropdown when class changes
        document.getElementById('filterClass').addEventListener('change', function() {
            loadStreamsForClass(this.value);
            filterRecords();
        });
        
        // Load streams on page load if class is pre-selected
        window.addEventListener('load', function() {
            const classId = document.getElementById('filterClass').value;
            if (classId) {
                loadStreamsForClass(classId);
            }
        });
        
        function filterStudentDropdown() {
            const searchInput = document.getElementById('studentSearchInline').value.toLowerCase().trim();
            const select = document.getElementById('recordStudentInline');
            const options = Array.from(select.getElementsByTagName('option'));
            
            // Save current selection
            const currentValue = select.value;
            
            // Clear and rebuild options
            const firstOption = options[0].cloneNode(true);
            select.innerHTML = '';
            select.appendChild(firstOption);
            
            let exactMatch = null;
            let matchCount = 0;
            
            for (let i = 1; i < options.length; i++) {
                const option = options[i];
                const admission = (option.getAttribute('data-admission') || '').toLowerCase();
                const name = (option.getAttribute('data-name') || '').toLowerCase();
                
                if (searchInput === '' || admission.includes(searchInput) || name.includes(searchInput)) {
                    select.appendChild(option.cloneNode(true));
                    
                    // Check for exact admission number match
                    if (admission === searchInput) {
                        exactMatch = option.value;
                        matchCount++;
                    }
                }
            }
            
            // Auto-select if there's exactly one exact match
            if (exactMatch && matchCount === 1) {
                select.value = exactMatch;
            } else if (currentValue) {
                // Restore selection if still available
                select.value = currentValue;
            }
        }
        
        function addRecordInline() {
            const data = {
                student_id: document.getElementById('recordStudentInline').value,
                action_type: document.getElementById('recordActionTypeInline').value,
                severity: document.getElementById('recordSeverityInline').value,
                title: document.getElementById('recordTitleInline').value,
                description: document.getElementById('recordDescriptionInline').value,
                incident_date: document.getElementById('recordIncidentDateInline').value,
                action_date: document.getElementById('recordActionDateInline').value,
                end_date: '',
                reported_by: document.getElementById('recordReportedByInline').value,
                handled_by: document.getElementById('recordHandledByInline').value,
                notes: document.getElementById('recordNotesInline').value
            };
            
            if (!data.student_id || !data.action_type || !data.severity || !data.title || !data.incident_date || !data.action_date || !data.reported_by || !data.handled_by) {
                notificationSystem.warning('Validation Error', 'Please fill in all required fields');
                return;
            }
            
            fetch('api/disciplinary.php?type=record', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    notificationSystem.success('Success', 'Disciplinary record added successfully!');
                    document.getElementById('addRecordFormInline').reset();
                    location.reload();
                } else {
                    notificationSystem.error('Error', result.error || 'Failed to add record');
                }
            })
            .catch(error => notificationSystem.error('Error', 'An error occurred'));
        }
        
        function addRecord() {
            const data = {
                student_id: document.getElementById('recordStudent').value,
                action_type: document.getElementById('recordActionType').value,
                severity: document.getElementById('recordSeverity').value,
                title: document.getElementById('recordTitle').value,
                description: document.getElementById('recordDescription').value,
                incident_date: document.getElementById('recordIncidentDate').value,
                action_date: document.getElementById('recordActionDate').value,
                end_date: document.getElementById('recordEndDate').value,
                reported_by: document.getElementById('recordReportedBy').value,
                handled_by: document.getElementById('recordHandledBy').value,
                notes: document.getElementById('recordNotes').value
            };
            
            fetch('api/disciplinary.php?type=record', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    notificationSystem.success('Success', 'Disciplinary record added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addRecordModal')).hide();
                    document.getElementById('addRecordForm').reset();
                    location.reload();
                } else {
                    notificationSystem.error('Error', result.error || 'Failed to add record');
                }
            })
            .catch(error => notificationSystem.error('Error', 'An error occurred'));
        }
        
        function viewRecord(id) {
            notificationSystem.info('Info', 'View record: ' + id);
            // TODO: Implement view functionality
        }
        
        function editRecord(id) {
            notificationSystem.info('Info', 'Edit record: ' + id);
            // TODO: Implement edit functionality
        }
        
        function generatePDF(recordId) {
            window.open('disciplinary_document.php?record_id=' + recordId, '_blank');
        }
    </script>
    
    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Disciplinary Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRecordForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student</label>
                                <select class="form-select" id="recordStudent" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['student_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Action Type</label>
                                <select class="form-select" id="recordActionType" required>
                                    <option value="">Select Action</option>
                                    <?php foreach ($action_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['action_type']); ?>"><?php echo htmlspecialchars($type['action_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Severity</label>
                                <select class="form-select" id="recordSeverity" required>
                                    <option value="minor">Minor</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="severe">Severe</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Incident Date</label>
                                <input type="date" class="form-control" id="recordIncidentDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="recordTitle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="recordDescription" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Action Date</label>
                                <input type="date" class="form-control" id="recordActionDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date (for suspensions)</label>
                                <input type="date" class="form-control" id="recordEndDate">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reported By</label>
                                <input type="text" class="form-control" id="recordReportedBy" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Handled By</label>
                                <input type="text" class="form-control" id="recordHandledBy" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="recordNotes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addRecord()">Add Record</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Record Modal -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Disciplinary Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student Name</label>
                            <div id="viewStudent" class="form-control-plaintext"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Admission Number</label>
                            <div id="viewAdmission" class="form-control-plaintext"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class</label>
                            <div id="viewClass" class="form-control-plaintext"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stream</label>
                            <div id="viewStream" class="form-control-plaintext"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Action Type</label>
                            <div id="viewActionType" class="form-control-plaintext"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Severity</label>
                            <div id="viewSeverity" class="form-control-plaintext"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <div id="viewTitle" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <div id="viewDescription" class="form-control-plaintext"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Incident Date</label>
                            <div id="viewIncidentDate" class="form-control-plaintext"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Action Date</label>
                            <div id="viewActionDate" class="form-control-plaintext"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <div id="viewEndDate" class="form-control-plaintext"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div id="viewStatus" class="form-control-plaintext"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reported By</label>
                            <div id="viewReportedBy" class="form-control-plaintext"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Handled By</label>
                            <div id="viewHandledBy" class="form-control-plaintext"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <div id="viewNotes" class="form-control-plaintext"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Disciplinary Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editRecordForm">
                        <input type="hidden" id="editRecordId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student</label>
                                <select class="form-select" id="editStudent" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['student_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Action Type</label>
                                <select class="form-select" id="editActionType" required>
                                    <option value="">Select Action</option>
                                    <?php foreach ($action_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['action_type']); ?>"><?php echo htmlspecialchars($type['action_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Severity</label>
                                <select class="form-select" id="editSeverity" required>
                                    <option value="minor">Minor</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="severe">Severe</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="editStatus" required>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Incident Date</label>
                                <input type="date" class="form-control" id="editIncidentDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Action Date</label>
                                <input type="date" class="form-control" id="editActionDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTitle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date (for suspensions)</label>
                                <input type="date" class="form-control" id="editEndDate">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reported By</label>
                                <input type="text" class="form-control" id="editReportedBy" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Handled By</label>
                                <input type="text" class="form-control" id="editHandledBy" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="editNotes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateRecord()">Update Record</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addRecord() {
            const data = {
                student_id: document.getElementById('recordStudent').value,
                action_type: document.getElementById('recordActionType').value,
                severity: document.getElementById('recordSeverity').value,
                title: document.getElementById('recordTitle').value,
                description: document.getElementById('recordDescription').value,
                incident_date: document.getElementById('recordIncidentDate').value,
                action_date: document.getElementById('recordActionDate').value,
                end_date: document.getElementById('recordEndDate').value,
                reported_by: document.getElementById('recordReportedBy').value,
                handled_by: document.getElementById('recordHandledBy').value,
                notes: document.getElementById('recordNotes').value
            };
            
            fetch('api/disciplinary.php?type=record', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    notificationSystem.success('Success', 'Disciplinary record added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addRecordModal')).hide();
                    document.getElementById('addRecordForm').reset();
                    location.reload();
                } else {
                    notificationSystem.error('Error', result.error || 'Failed to add record');
                }
            })
            .catch(error => notificationSystem.error('Error', 'An error occurred'));
        }
        
        function viewRecord(id) {
            fetch(`api/disciplinary.php?type=view&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const record = result.data;
                        document.getElementById('viewStudent').textContent = record.student_name;
                        document.getElementById('viewAdmission').textContent = record.admission_number;
                        document.getElementById('viewClass').textContent = record.class_name || '-';
                        document.getElementById('viewStream').textContent = record.stream_name || '-';
                        document.getElementById('viewActionType').textContent = record.action_name || record.action_type;
                        document.getElementById('viewSeverity').textContent = record.severity;
                        document.getElementById('viewTitle').textContent = record.title;
                        document.getElementById('viewDescription').textContent = record.description;
                        document.getElementById('viewIncidentDate').textContent = record.incident_date;
                        document.getElementById('viewActionDate').textContent = record.action_date;
                        document.getElementById('viewEndDate').textContent = record.end_date || '-';
                        document.getElementById('viewReportedBy').textContent = record.reported_by;
                        document.getElementById('viewHandledBy').textContent = record.handled_by;
                        document.getElementById('viewStatus').textContent = record.status;
                        document.getElementById('viewNotes').textContent = record.notes || '-';
                        
                        new bootstrap.Modal(document.getElementById('viewRecordModal')).show();
                    } else {
                        notificationSystem.error('Error', result.error || 'Failed to load record');
                    }
                })
                .catch(error => notificationSystem.error('Error', 'An error occurred'));
        }
        
        function editRecord(id) {
            fetch(`api/disciplinary.php?type=view&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const record = result.data;
                        document.getElementById('editRecordId').value = record.id;
                        document.getElementById('editStudent').value = record.student_id;
                        document.getElementById('editActionType').value = record.action_type;
                        document.getElementById('editSeverity').value = record.severity;
                        document.getElementById('editTitle').value = record.title;
                        document.getElementById('editDescription').value = record.description;
                        document.getElementById('editIncidentDate').value = record.incident_date;
                        document.getElementById('editActionDate').value = record.action_date;
                        document.getElementById('editEndDate').value = record.end_date || '';
                        document.getElementById('editReportedBy').value = record.reported_by;
                        document.getElementById('editHandledBy').value = record.handled_by;
                        document.getElementById('editStatus').value = record.status;
                        document.getElementById('editNotes').value = record.notes || '';
                        
                        new bootstrap.Modal(document.getElementById('editRecordModal')).show();
                    } else {
                        notificationSystem.error('Error', result.error || 'Failed to load record');
                    }
                })
                .catch(error => notificationSystem.error('Error', 'An error occurred'));
        }
        
        function updateRecord() {
            const id = document.getElementById('editRecordId').value;
            const data = {
                id: id,
                student_id: document.getElementById('editStudent').value,
                action_type: document.getElementById('editActionType').value,
                severity: document.getElementById('editSeverity').value,
                title: document.getElementById('editTitle').value,
                description: document.getElementById('editDescription').value,
                incident_date: document.getElementById('editIncidentDate').value,
                action_date: document.getElementById('editActionDate').value,
                end_date: document.getElementById('editEndDate').value,
                reported_by: document.getElementById('editReportedBy').value,
                handled_by: document.getElementById('editHandledBy').value,
                status: document.getElementById('editStatus').value,
                notes: document.getElementById('editNotes').value
            };
            
            console.log('Updating record:', data);
            
            fetch('api/disciplinary.php?type=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                console.log('Update response:', result);
                if (result.success) {
                    notificationSystem.success('Success', 'Disciplinary record updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editRecordModal')).hide();
                    location.reload();
                } else {
                    notificationSystem.error('Error', result.error || 'Failed to update record');
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                notificationSystem.error('Error', error.message);
            });
        }
        
        function filterRecords() {
            const search = document.getElementById('searchRecords').value.toLowerCase();
            const rows = document.querySelectorAll('#recordsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
