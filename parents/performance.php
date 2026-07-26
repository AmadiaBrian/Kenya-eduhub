<?php
// Parent Performance Page
// Authentication is handled by index.php router
$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status to find active term
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);
$active_term = $calendar_status['current_term']['term_name'] ?? null;

// Get terms from database for current year
$terms = [];
try {
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $current_year]);
    $term_records = $stmt->fetchAll();
    foreach ($term_records as $term) {
        $terms[] = $term['term_name'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

if (empty($terms)) {
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

// Use active term if available, otherwise use first term
$current_term = $active_term ?? ($terms[0] ?? 'Term 1');

$selected_child_id = $_GET['child_id'] ?? null;
$view_mode = $_GET['view'] ?? 'children'; // 'children' or 'class'

// Get children of this parent (EXACT same query as fees.php)
try {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    error_log("Found " . count($children) . " children via student_parents table (same as dashboard)");
    foreach ($children as $child) {
        error_log("Child ID: {$child['id']}, Name: {$child['first_name']} {$child['last_name']}");
    }
    
    // Get performance data for all children (same logic as dashboard)
    $performance_data = [];
    $current_year = date('Y');
    
    foreach ($children as $child) {
        error_log("Checking performance for child ID: " . $child['id']);
        $stmt = $pdo->prepare("SELECT ap.*, et.exam_type_name, et.exam_type_code,
                                            (SELECT gs.points FROM grading_scales gs
                                            WHERE gs.school_id = ?
                                            AND ap.marks BETWEEN gs.min_score AND gs.max_score
                                            AND UPPER(ap.grade) = UPPER(gs.grade_name)
                                            LIMIT 1) as grade_points
                               FROM academic_performance ap
                               LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                               WHERE ap.student_id = ? AND ap.term = ? AND ap.year = ?
                               ORDER BY ap.created_at DESC");
        $stmt->execute([$school_id, $child['id'], $current_term, $current_year]);
        $child_performance = $stmt->fetchAll();
        
        error_log("Performance records for child {$child['id']}: " . count($child_performance));
        
        if (!empty($child_performance)) {
            $performance_data[$child['id']] = [
                'child' => $child,
                'performance' => $child_performance
            ];
        }
    }
    
    error_log("Total children with performance: " . count($performance_data));
    
    // Get class performance if requested
    $class_performance = [];
    if ($view_mode === 'class' && !empty($children)) {
        $class_id = $children[0]['class_id'];
        if ($class_id) {
            $stmt = $pdo->prepare("SELECT ap.*, s.first_name, s.last_name, et.exam_type_name, et.exam_type_code,
                                            (SELECT gs.points FROM grading_scales gs
                                            WHERE gs.school_id = ?
                                            AND ap.marks BETWEEN gs.min_score AND gs.max_score
                                            AND UPPER(ap.grade) = UPPER(gs.grade_name)
                                            LIMIT 1) as grade_points
                                   FROM academic_performance ap
                                   JOIN students s ON ap.student_id = s.id
                                   LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                                   WHERE ap.student_id IN (SELECT id FROM students WHERE class_id = ?)
                                   ORDER BY ap.created_at DESC");
            $stmt->execute([$school_id, $class_id]);
            $class_performance = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log("Failed to fetch performance data: " . $e->getMessage());
    $children = [];
    $performance_data = [];
    $class_performance = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance - <?php echo htmlspecialchars($parent_name); ?></title>
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
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
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
        
        /* Filters */
        .filters {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        /* Table - PDF Document Style */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table th,
        .table td {
            padding: 12px 16px;
            text-align: left;
            border: 1px solid #000;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }

        .table th {
            background: #f0f0f0;
            font-weight: 600;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 2px solid #000;
        }

        .table th:last-child {
            border-right: 2px solid #000;
        }

        .table td:last-child {
            border-right: 2px solid #000;
        }

        .table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }

        .table tbody tr:hover {
            background: #f9f9f9;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        /* PDF Document Container */
        .pdf-document {
            background: var(--bg-color);
            border: none;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: none;
            border-radius: 0;
        }
        
        .pdf-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .pdf-title {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: #1a1a1a;
            letter-spacing: 1px;
        }
        
        .pdf-subtitle {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 16px;
            color: #444;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .pdf-info {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 13px;
            color: #666;
            font-style: italic;
        }
        
        .pdf-footer {
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 25px;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11px;
            color: #888;
            font-style: italic;
        }
        
        .grade-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .grade-a { background: #e6f4ea; color: #137333; }
        .grade-b { background: #e8f0fe; color: #1967d2; }
        .grade-c { background: #fef7e0; color: #b06000; }
        .grade-d { background: #fce8e6; color: #c5221f; }
        .grade-e { background: #fce8e6; color: #c5221f. }
        
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
        
        .btn {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid;
            background: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: #e55a2b;
            border-color: #e55a2b;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background: #fff3e0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .card {
                padding: 10px;
                margin-bottom: 15px;
            }
            
            .table-responsive {
                overflow-x: auto;
                margin: 0 -10px;
                padding: 0 10px;
                width: 100%;
            }
            
            .table {
                min-width: 100vw;
                width: 100%;
            }
            
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
                <?php echo strtoupper(substr($parent_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="children">
                <i class="fas fa-child"></i> My Children
            </a>
            <a class="nav-link active" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-award"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="assignments">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a class="nav-link" href="fines">
                <i class="fas fa-book"></i> Library Fines
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link" href="profile">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Performance</h1>
        <p class="page-subtitle">
            View academic performance records
        </p>
        
        <div class="card">
            <h2 class="card-title">View Options</h2>
            <div style="display: flex; gap: 16px;">
                <a href="performance?view=children" class="btn <?php echo $view_mode === 'children' ? 'btn-primary' : 'btn-outline'; ?>">
                    <i class="fas fa-child"></i> My Children's Results
                </a>
                <a href="performance?view=class" class="btn <?php echo $view_mode === 'class' ? 'btn-primary' : 'btn-outline'; ?>">
                    <i class="fas fa-users"></i> Class Performance
                </a>
            </div>
        </div>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="dashboard" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="quick-access-label">Dashboard</div>
                </a>
                <a href="attendance" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="fees" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="quick-access-label">Fee Payments</div>
                </a>
                <a href="children" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="quick-access-label">My Children</div>
                </a>
                <a href="profile" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="quick-access-label">Profile</div>
                </a>
                <a href="#" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="quick-access-label">Messages</div>
                </a>
            </div>
        </div>
        
        <?php if ($view_mode === 'children'): ?>
            <?php if (empty($performance_data)): ?>
                <div class="card">
                    <p class="text-muted">No performance records found for your children.</p>
                </div>
            <?php else: ?>
                <?php foreach ($performance_data as $child_id => $data): ?>
                    <div class="pdf-document">
                        <div class="pdf-header">
                            <div class="pdf-title">Student Performance Report</div>
                            <div class="pdf-subtitle">
                                <?php echo htmlspecialchars($data['child']['first_name'] . ' ' . $data['child']['last_name']); ?>
                            </div>
                            <div class="pdf-info">
                                Class: <?php echo htmlspecialchars($data['child']['class_name'] ?? 'No Class'); ?> | 
                                Year: <?php echo $current_year; ?>
                            </div>
                        </div>
                        
                        <?php 
                        // Check if there are multiple terms
                        $terms = array_unique(array_column($data['performance'], 'term'));
                        $hasMultipleTerms = count($terms) > 1;
                        
                        if ($hasMultipleTerms) {
                            // Group by term
                            $groupedByTerm = [];
                            foreach ($data['performance'] as $record) {
                                $term = $record['term'] ?? 'No Term';
                                if (!isset($groupedByTerm[$term])) {
                                    $groupedByTerm[$term] = [];
                                }
                                $groupedByTerm[$term][] = $record;
                            }
                            ksort($groupedByTerm); // Sort terms alphabetically
                        }
                        ?>
                        
                        <?php if ($hasMultipleTerms): ?>
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                <?php foreach ($groupedByTerm as $term => $records): ?>
                                    <div class="card">
                                        <div class="card-header" style="background-color: #e8f0fe; font-weight: bold;">
                                            <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($term); ?> (<?php echo count($records); ?> records)
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive" style="overflow-x: auto;">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Subject</th>
                                                            <th>Exam Type</th>
                                                            <th>Marks</th>
                                                            <th>Grade</th>
                                                            <th>Points</th>
                                                            <th>Remarks</th>
                                                            <th>Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($records as $record): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                                                <td><?php echo htmlspecialchars($record['exam_type_name'] ?? '-'); ?></td>
                                                                <td><?php echo number_format($record['marks'], 2); ?></td>
                                                                <td>
                                                                    <span class="grade-badge <?php
                                                                        $grade = strtoupper($record['grade']);
                                                                        echo 'grade-' . strtolower($grade);
                                                                    ?>">
                                                                        <?php echo htmlspecialchars($grade); ?>
                                                                    </span>
                                                                </td>
                                                                <td><strong><?php echo $record['grade_points'] ?? '-'; ?></strong></td>
                                                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                                                <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive" style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Exam Type</th>
                                            <th>Marks</th>
                                            <th>Grade</th>
                                            <th>Points</th>
                                            <th>Remarks</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['performance'] as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($record['exam_type_name'] ?? '-'); ?></td>
                                                <td><?php echo number_format($record['marks'], 2); ?></td>
                                                <td>
                                                    <span class="grade-badge <?php
                                                        $grade = strtoupper($record['grade']);
                                                        echo 'grade-' . strtolower($grade);
                                                    ?>">
                                                        <?php echo htmlspecialchars($grade); ?>
                                                    </span>
                                                </td>
                                                <td><strong><?php echo $record['grade_points'] ?? '-'; ?></strong></td>
                                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="pdf-footer">
                            Generated on <?php echo date('F d, Y g:i A'); ?> | Kenya EduHub Performance System
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <?php if (empty($class_performance)): ?>
                <div class="card">
                    <p class="text-muted">No class performance records found.</p>
                </div>
            <?php else: ?>
                <div class="pdf-document">
                    <div class="pdf-header">
                        <div class="pdf-title">Class Performance Report</div>
                        <div class="pdf-subtitle">
                            <?php echo htmlspecialchars($children[0]['class_name'] ?? 'Class'); ?>
                        </div>
                        <div class="pdf-info">
                            Year: <?php echo $current_year; ?>
                        </div>
                    </div>
                    
                    <?php 
                    // Check if there are multiple terms
                    $terms = array_unique(array_column($class_performance, 'term'));
                    $hasMultipleTerms = count($terms) > 1;
                    
                    if ($hasMultipleTerms) {
                        // Group by term
                        $groupedByTerm = [];
                        foreach ($class_performance as $record) {
                            $term = $record['term'] ?? 'No Term';
                            if (!isset($groupedByTerm[$term])) {
                                $groupedByTerm[$term] = [];
                            }
                            $groupedByTerm[$term][] = $record;
                        }
                        ksort($groupedByTerm); // Sort terms alphabetically
                    }
                    ?>
                    
                    <?php if ($hasMultipleTerms): ?>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <?php foreach ($groupedByTerm as $term => $records): ?>
                                <div class="card">
                                    <div class="card-header" style="background-color: #e8f0fe; font-weight: bold;">
                                        <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($term); ?> (<?php echo count($records); ?> records)
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive" style="overflow-x: auto;">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Subject</th>
                                                        <th>Exam Type</th>
                                                        <th>Marks</th>
                                                        <th>Grade</th>
                                                        <th>Points</th>
                                                        <th>Remarks</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($records as $record): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                                            <td><?php echo htmlspecialchars($record['exam_type_name'] ?? '-'); ?></td>
                                                            <td><?php echo number_format($record['marks'], 2); ?></td>
                                                            <td>
                                                                <span class="grade-badge <?php
                                                                    $grade = strtoupper($record['grade']);
                                                                    echo 'grade-' . strtolower($grade);
                                                                ?>">
                                                                    <?php echo htmlspecialchars($grade); ?>
                                                                </span>
                                                            </td>
                                                            <td><strong><?php echo $record['grade_points'] ?? '-'; ?></strong></td>
                                                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Points</th>
                                        <th>Remarks</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($class_performance as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($record['exam_type_name'] ?? '-'); ?></td>
                                            <td><?php echo number_format($record['marks'], 2); ?></td>
                                            <td>
                                                <span class="grade-badge <?php
                                                    $grade = strtoupper($record['grade']);
                                                    echo 'grade-' . strtolower($grade);
                                                ?>">
                                                    <?php echo htmlspecialchars($grade); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo $record['grade_points'] ?? '-'; ?></strong></td>
                                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <div class="pdf-footer">
                        Generated on <?php echo date('F d, Y g:i A'); ?> | Kenya EduHub Performance System
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
    
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
