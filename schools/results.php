<?php
// School Results Management - View and Send Results via SMS
// Authentication is handled by index.php router
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get active term from calendar status
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

// Get school details
try {
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch school details: " . $e->getMessage());
    $school = null;
}

// Get school subject limits
$school_min_subjects = 7;
$school_max_subjects = 8;
if ($school) {
    try {
        $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school_limits = $stmt->fetch();
        $school_min_subjects = $school_limits['min_subjects'] ?? 7;
        $school_max_subjects = $school_limits['max_subjects'] ?? 8;
    } catch (PDOException $e) {
        error_log("Failed to fetch school subject limits: " . $e->getMessage());
    }
}

// Get all grading scales for this school
$grading_scales = [];
if ($school) {
    try {
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id, s.school_id as subject_school_id
                              FROM grading_scales gs
                              LEFT JOIN subjects s ON gs.subject_id = s.id
                              WHERE gs.school_id = ?
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch grading scales: " . $e->getMessage());
    }
}

// Get aggregate points distribution
$aggregate_distribution = [];
if ($school) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$school_id]);
        $aggregate_distribution = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch aggregate distribution: " . $e->getMessage());
    }
}

// Get student subject assignments for filtering total points calculation
$student_subject_assignments = [];
if ($school) {
    try {
        $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN students st ON ss.student_id = st.id
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE ss.school_id = ?");
        $stmt->execute([$school_id]);
        $assignments = $stmt->fetchAll();

        // Group by admission_number for easier matching
        foreach ($assignments as $assignment) {
            $admission_number = $assignment['admission_number'];
            $subject_name = $assignment['subject_name'];
            $is_compulsory = $assignment['is_compulsory'] ?? 0;
            if (!isset($student_subject_assignments[$admission_number])) {
                $student_subject_assignments[$admission_number] = [];
            }
            $student_subject_assignments[$admission_number][] = [
                'subject_name' => $subject_name,
                'is_compulsory' => $is_compulsory
            ];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch student subject assignments: " . $e->getMessage());
    }
}

// Get classes for this school
$classes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get exam types for this school
$exam_types = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT exam_type FROM exam_results WHERE school_id = ? ORDER BY exam_type");
    $stmt->execute([$school_id]);
    $exam_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Failed to fetch exam types: " . $e->getMessage());
}

// Get streams for this school
$streams = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM streams WHERE school_id = ? ORDER BY stream_name");
    $stmt->execute([$school_id]);
    $streams = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch streams: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - <?php echo htmlspecialchars($school_name); ?></title>
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
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .sidebar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
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
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
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
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
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
                padding: 16px;
            }
            
            .menu-btn {
                display: block !important;
            }
            
            .card {
                padding: 16px;
            }
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }
            
            .table {
                min-width: 100%;
                font-size: 12px;
            }
            
            .table th, .table td {
                padding: 8px 4px;
            }
            
            .form-control, .form-select {
                font-size: 14px;
            }
            
            .btn {
                font-size: 14px;
                padding: 8px 12px;
            }
            
            .col-md-3, .col-md-4, .col-md-2 {
                margin-bottom: 12px;
            }
            
            .page-title {
                font-size: 18px;
            }
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            display: none;
        }
        
        .notification.success {
            background: #28a745;
        }
        
        .notification.error {
            background: #dc3545;
        }
        
        .notification.warning {
            background: #ffc107;
            color: #333;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading i {
            font-size: 24px;
            color: #007bff;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
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
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
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
            <a class="nav-link" href="exam-types">
                <i class="fas fa-clipboard-list"></i> Exam Types
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="disciplinary">
                <i class="fas fa-shield-alt"></i> Disciplinary
            </a>
            <a class="nav-link" href="disciplinary-action-types">
                <i class="fas fa-list-alt"></i> Disciplinary Types
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link active" href="results">
                <i class="fas fa-award"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="index.php?route=logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Student Results</h1>
        
        <!-- Calendar Status Alert -->
        <?php if ($calendar_status['is_holiday']): ?>
        <div class="card" style="background: #fff3cd; border: 1px solid #ffc107;">
            <p style="margin: 0; color: #856404;">
                <i class="fas fa-info-circle"></i> 
                <strong>Holiday Period:</strong> <?php echo htmlspecialchars($calendar_status['holiday_name'] ?? 'Current holiday'); ?> 
                (<?php echo htmlspecialchars($calendar_status['holiday_start'] ?? ''); ?> to <?php echo htmlspecialchars($calendar_status['holiday_end'] ?? ''); ?>)
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title">Filter Results</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select class="form-control" id="classFilter">
                        <option value="">All Classes</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Stream</label>
                    <select class="form-control" id="streamFilter">
                        <option value="">All Streams</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select class="form-control" id="termFilter">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo $term; ?>"><?php echo $term; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Exam Type</label>
                    <select class="form-control" id="examTypeFilter">
                        <option value="">All Exam Types</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <select class="form-control" id="yearFilter">
                        <option value="">All Years</option>
                        <?php 
                        $current_year = date('Y');
                        for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="loadResults()">
                        <i class="fas fa-search me-1"></i> Load Results
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-info w-100" onclick="sendResultsViaSMS()">
                        <i class="fas fa-sms me-1"></i> Send via SMS
                    </button>
                </div>
            </div>
        </div>
        
        <div class="loading" id="loading">
            <i class="fas fa-spinner"></i> Loading results...
        </div>
        
        <div id="resultsContainer">
            <div class="text-center py-5">
                <i class="fas fa-award fa-3x text-muted mb-3"></i>
                <p class="text-muted">Select filters and click "Load Results" to view student results</p>
            </div>
        </div>
        
        <div class="card" id="noResultsCard" style="display: none;">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h3>No Results Found</h3>
                <p class="text-muted">Select filters to view student results</p>
            </div>
        </div>
    </main>
    
    <div class="notification" id="notification"></div>
    
    <script src="../assets/js/notifications.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const schoolMinSubjects = <?php echo json_encode($school_min_subjects); ?>;
        const schoolMaxSubjects = <?php echo json_encode($school_max_subjects); ?>;
        const aggregateDistribution = <?php echo json_encode($aggregate_distribution); ?>;
        const studentSubjectAssignments = <?php echo json_encode($student_subject_assignments); ?>;
        const gradingScales = <?php echo json_encode($grading_scales); ?>;
        const smsEnabled = <?php echo json_encode($school['sms_enabled'] ?? 0); ?>;
        const schoolId = <?php echo json_encode($school_id); ?>;
        const schoolName = <?php echo json_encode($school['school_name'] ?? ''); ?>;
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 5000);
        }

        // Function to abbreviate subject names
        function abbreviateSubject(subject) {
            const abbreviations = {
                'MATHEMATICS': 'MATH',
                'ENGLISH': 'ENG',
                'KISWAHILI': 'KISW',
                'PHYSICS': 'PHY',
                'CHEMISTRY': 'CHEM',
                'BIOLOGY': 'BIO',
                'GEOGRAPHY': 'GEOG',
                'HISTORY AND GOVERNMENT': 'HIST',
                'CHRISTIAN RELIGIOUS EDUCATION': 'CRE',
                'AGRICULTURE': 'AGR',
                'BUSINESS STUDIES': 'B/S',
                'COMPUTER STUDIES': 'COMP'
            };
            return abbreviations[subject] || subject.substring(0, 4);
        }

        // Function to get points from marks using grading scales
        function getPointsFromMarks(marks, subject) {
            subject = subject.toUpperCase();
            
            // Filter grading scales for the specific subject
            const subjectScales = gradingScales.filter(scale => {
                if (!scale.subject_name) return false;
                return scale.subject_name.toUpperCase() === subject;
            });
            
            // Try subject-specific scales first
            for (const scale of subjectScales) {
                if (marks >= scale.min_score && marks <= scale.max_score) {
                    return scale.points || 0;
                }
            }
            
            // If no subject-specific match, try general scales (subject_id is null)
            const generalScales = gradingScales.filter(scale => scale.subject_id === null);
            for (const scale of generalScales) {
                if (marks >= scale.min_score && marks <= scale.max_score) {
                    return scale.points || 0;
                }
            }
            
            // Fallback to any matching scale if still no match
            for (const scale of gradingScales) {
                if (marks >= scale.min_score && marks <= scale.max_score) {
                    console.log('WARNING: Using fallback scale from subject:', scale.subject_name, 'for subject:', subject);
                    return scale.points || 0;
                }
            }
            
            return 0;
        }

        // Function to get aggregate grade based on total points
        function getAggregateGrade(totalPoints) {
            if (!aggregateDistribution || aggregateDistribution.length === 0) {
                return '-';
            }
            for (const dist of aggregateDistribution) {
                if (totalPoints >= dist.min_points && totalPoints <= dist.max_points) {
                    return dist.grade_name;
                }
            }
            return '-';
        }
        
        // Load performance records
        async function loadPerformanceRecords() {
            try {
                const response = await fetch('api/performance.php');
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error loading performance records:', error);
                showNotification('Failed to load performance records', 'danger');
                return [];
            }
        }

        // Load results
        async function loadResults() {
            const container = document.getElementById('resultsContainer');
            const noResultsCard = document.getElementById('noResultsCard');
            
            container.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Loading results...</p></div>';
            noResultsCard.style.display = 'none';

            const response = await loadPerformanceRecords();
            console.log('API Response:', response);
            
            // Check if response has data
            if (!response.success) {
                container.innerHTML = '<div class="text-center py-5"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p class="text-danger">Failed to load performance records: ' + (response.error || 'Unknown error') + '</p></div>';
                noResultsCard.style.display = 'block';
                return;
            }
            
            const allPerformanceRecords = response.data || [];
            console.log('Total records loaded:', allPerformanceRecords.length);
            
            // Get filter values
            const classFilter = document.getElementById('classFilter').value;
            const streamFilter = document.getElementById('streamFilter').value;
            const termFilter = document.getElementById('termFilter').value;
            const yearFilter = document.getElementById('yearFilter').value;
            const examTypeFilter = document.getElementById('examTypeFilter').value;

            // Filter records
            const filtered = allPerformanceRecords.filter(record => {
                if (classFilter && record.class_name !== classFilter) return false;
                if (streamFilter && record.stream_name !== streamFilter) return false;
                if (termFilter && record.term !== termFilter) return false;
                if (yearFilter && record.year !== yearFilter) return false;
                if (examTypeFilter && record.exam_type_name !== examTypeFilter) return false;
                return true;
            });

            console.log('Filtered records:', filtered.length);

            if (filtered.length === 0) {
                container.innerHTML = '<div class="text-center py-5"><i class="fas fa-inbox fa-3x text-muted mb-3"></i><p class="text-muted">No results found for the selected filters</p></div>';
                noResultsCard.style.display = 'block';
                return;
            }

            // Group by exam type
            const groupedByExamType = {};
            filtered.forEach(record => {
                const examType = record.exam_type_name || 'Unknown Exam';
                if (!groupedByExamType[examType]) groupedByExamType[examType] = [];
                groupedByExamType[examType].push(record);
            });

            // Display separate table for each exam type
            let html = '';
            for (const [examType, records] of Object.entries(groupedByExamType)) {
                html += `
                    <h4 style="color: #FF6B35; margin-bottom: 15px; font-weight: 600; margin-top: 30px;">
                        <i class="fas fa-clipboard-list me-2"></i>${examType}
                    </h4>
                `;

                // Process records for this exam type
                const groupedByStudent = {};
                const allSubjects = new Set();

                records.forEach(record => {
                    const studentKey = `${record.admission_number}_${record.first_name}_${record.last_name}_${record.class_name}_${record.stream_name}`;
                    if (!groupedByStudent[studentKey]) {
                        groupedByStudent[studentKey] = {
                            admission_number: record.admission_number,
                            first_name: record.first_name,
                            last_name: record.last_name,
                            class_name: record.class_name,
                            stream_name: record.stream_name,
                            subjects: {}
                        };
                    }
                    groupedByStudent[studentKey].subjects[record.subject] = record;
                    allSubjects.add(record.subject);
                });

                // Sort subjects alphabetically
                const sortedSubjects = Array.from(allSubjects).sort();

                html += `
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table" style="min-width: 1200px;">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    ${sortedSubjects.map(subject => `<th title="${subject}">${abbreviateSubject(subject)}</th>`).join('')}
                                    <th>Total Marks</th>
                                    <th>Average</th>
                                    <th>Total Points</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                // Calculate total marks and points for each student to determine rank
                const studentsWithTotal = Object.values(groupedByStudent).map(student => {
                    let totalMarks = 0;
                    let totalPoints = 0;
                    let count = 0;

                    // Get assigned subjects for this student
                    const studentId = student.admission_number;
                    const assignedSubjects = studentSubjectAssignments[studentId] || [];

                    // Calculate points for each assigned subject
                    const subjectPoints = [];
                    Object.values(student.subjects).forEach(record => {
                        // Find if this subject is assigned and get its compulsory status
                        const assigned = assignedSubjects.find(s => s.subject_name === record.subject);
                        if (assigned) {
                            const marks = parseFloat(record.marks) || 0;
                            const points = getPointsFromMarks(marks, record.subject);
                            subjectPoints.push({
                                subject: record.subject,
                                marks: marks,
                                points: points,
                                is_compulsory: assigned.is_compulsory
                            });
                        }
                    });

                    // Separate compulsory and non-compulsory subjects
                    const compulsorySubjects = subjectPoints.filter(s => s.is_compulsory === 1);
                    const nonCompulsorySubjects = subjectPoints.filter(s => s.is_compulsory !== 1);

                    // Sort non-compulsory by points descending, then by marks descending (best performing first)
                    nonCompulsorySubjects.sort((a, b) => {
                        if (b.points !== a.points) {
                            return b.points - a.points;
                        }
                        return b.marks - a.marks;
                    });

                    // Calculate how many non-compulsory subjects we need to reach minimum
                    const compulsoryCount = compulsorySubjects.length;
                    const neededNonCompulsory = Math.max(0, schoolMinSubjects - compulsoryCount);

                    // Take top non-compulsory subjects to reach minimum
                    const selectedNonCompulsory = nonCompulsorySubjects.slice(0, neededNonCompulsory);

                    // Combine compulsory + selected non-compulsory for grading
                    const gradingSubjects = [...compulsorySubjects, ...selectedNonCompulsory];

                    // Calculate totals from grading subjects
                    gradingSubjects.forEach(sub => {
                        totalMarks += sub.marks;
                        totalPoints += sub.points;
                        count++;
                    });

                    console.log(`Student ${studentId}:`);
                    console.log(`  Assigned subjects: ${assignedSubjects.length}`);
                    console.log(`  Compulsory: ${compulsoryCount}`);
                    console.log(`  Non-compulsory selected: ${selectedNonCompulsory.length}`);
                    console.log(`  Total grading subjects: ${gradingSubjects.length}`);
                    console.log(`  Total points calculated: ${totalPoints}`);
                    console.log(`  Grading subjects:`, gradingSubjects.map(s => `${s.subject} (${s.points} pts)`).join(', '));

                    return {
                        ...student,
                        totalMarks,
                        average: count > 0 ? (totalMarks / count).toFixed(1) : 0,
                        totalPoints,
                        subjectCount: count,
                        assignedSubjectCount: assignedSubjects.length,
                        compulsoryCount,
                        gradingSubjects
                    };
                });

                // Filter students to only include those with minimum subjects for grading
                const eligibleStudents = studentsWithTotal.filter(student => student.assignedSubjectCount >= schoolMinSubjects);

                // Show message about students not meeting minimum subjects
                const ineligibleCount = studentsWithTotal.length - eligibleStudents.length;
                if (ineligibleCount > 0) {
                    html += `
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${ineligibleCount} student(s) not shown because they have fewer than ${schoolMinSubjects} subjects assigned.
                        </div>
                    `;
                }

                // Show message about grading method
                html += `
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Grading uses all compulsory subjects plus the best performing non-compulsory subjects to reach ${schoolMinSubjects} subjects total. Students must have at least ${schoolMinSubjects} subjects to be graded.
                    </div>
                `;

                // Sort eligible students by total points descending for ranking
                eligibleStudents.sort((a, b) => b.totalPoints - a.totalPoints);

                eligibleStudents.forEach((student, index) => {
                    const aggregateGrade = getAggregateGrade(student.totalPoints);
                    const gradingSubjectNames = student.gradingSubjects.map(s => s.subject);

                    html += `
                        <tr>
                            <td><strong>#${index + 1}</strong></td>
                            <td>${student.admission_number}</td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${student.class_name || '-'}</td>
                            <td>${student.stream_name || '-'}</td>
                            ${sortedSubjects.map(subject => {
                                const record = student.subjects[subject];
                                const assignedSubjects = studentSubjectAssignments[student.admission_number] || [];
                                const assigned = assignedSubjects.find(s => s.subject_name === subject);
                                const isGradingSubject = gradingSubjectNames.includes(subject);

                                if (record && assigned) {
                                    // Student has marks and is assigned to this subject
                                    const indicator = isGradingSubject ? '<span style="color: red; font-weight: bold; margin-left: 5px;">✓</span>' : '';
                                    return `<td>${record.marks} (${record.grade || '-'})${indicator}</td>`;
                                } else if (assigned) {
                                    // Student is assigned but no marks
                                    return `<td>-</td>`;
                                } else {
                                    // Student not assigned
                                    return `<td>-</td>`;
                                }
                            }).join('')}
                            <td><strong>${student.totalMarks}</strong></td>
                            <td><strong>${student.average}</strong></td>
                            <td><strong>${student.totalPoints}</strong></td>
                            <td><strong>${aggregateGrade}</strong></td>
                        </tr>
                    `;
                });

                html += `
                                    </tbody>
                                </table>
                            </div>
                `;
            }

            container.innerHTML = html;
        }
        
        
        // Send results via SMS to parents
        async function sendResultsViaSMS() {
            // Check if SMS is enabled
            if (!smsEnabled) {
                showNotification('SMS is not enabled for this school. Please contact administration.', 'warning');
                return;
            }

            // Get current results from the displayed table
            const resultsContainer = document.getElementById('resultsContainer');
            const tables = resultsContainer.querySelectorAll('table');
            
            if (tables.length === 0) {
                showNotification('Please load results first by clicking "Load Results".', 'warning');
                return;
            }

            // Get all students from the table with subject details
            const students = [];
            const examTypes = [];
            
            tables.forEach((table, tableIndex) => {
                // Get exam type from the table's heading
                const examTypeHeader = resultsContainer.querySelectorAll('h4')[tableIndex];
                const examType = examTypeHeader ? examTypeHeader.textContent.trim() : '';
                
                // Skip if exam type is "Unknown" or empty
                if (!examType || examType === 'Unknown' || examType === 'Unknown Exam') {
                    return;
                }
                
                examTypes.push(examType);
                
                const headers = table.querySelectorAll('th');
                const subjectNames = [];
                // Get subject names from headers (skip first 5 columns: Rank, Admission, Name, Class, Stream)
                // Use the title attribute which contains the full subject name
                for (let i = 5; i < headers.length - 4; i++) {
                    const header = headers[i];
                    const fullName = header.getAttribute('title') || header.textContent.trim();
                    subjectNames.push(fullName);
                }
                
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) {
                        const admissionNumber = cells[1].textContent.trim();
                        const studentName = cells[2].textContent.trim();
                        const totalMarks = cells[cells.length - 4].textContent.trim();
                        const totalPoints = cells[cells.length - 2].textContent.trim();
                        const grade = cells[cells.length - 1].textContent.trim();
                        
                        // Get student's assigned subjects
                        const assignedSubjects = studentSubjectAssignments[admissionNumber] || [];
                        const assignedSubjectNames = assignedSubjects.map(s => s.subject_name);
                        
                        // Get subject marks and grades (only for assigned subjects)
                        const subjects = [];
                        for (let i = 0; i < subjectNames.length; i++) {
                            const subjectName = subjectNames[i];
                            // Only include if subject is assigned to the student
                            if (assignedSubjectNames.includes(subjectName)) {
                                const cellIndex = 5 + i;
                                if (cells[cellIndex]) {
                                    const cellText = cells[cellIndex].textContent.trim();
                                    // Parse marks and grade from format like "50 (B+)"
                                    const match = cellText.match(/([\d.]+)\s*\(([^)]+)\)/);
                                    const marks = match ? match[1] : cellText;
                                    const subjectGrade = match ? match[2] : '-';
                                    subjects.push({
                                        name: subjectName,
                                        marks: marks,
                                        grade: subjectGrade
                                    });
                                }
                            }
                        }
                        
                        // Calculate final grade from aggregate points distribution
                        let finalGrade = '-';
                        const points = parseInt(totalPoints);
                        if (!isNaN(points)) {
                            for (const dist of aggregateDistribution) {
                                if (points >= dist.min_points && points <= dist.max_points) {
                                    finalGrade = dist.grade_name;
                                    break;
                                }
                            }
                        }
                        
                        students.push({
                            admission_number: admissionNumber,
                            name: studentName,
                            total_marks: totalMarks,
                            total_points: totalPoints,
                            grade: finalGrade,
                            subjects: subjects,
                            exam_type: examType
                        });
                    }
                });
            });

            if (students.length === 0) {
                showNotification('No student data found in results.', 'warning');
                return;
            }

            // Confirm before sending
            if (!confirm(`Send results to ${students.length} students?`)) {
                return;
            }

            // Send SMS to each student's parents
            let successCount = 0;
            let failureCount = 0;

            for (const student of students) {
                try {
                    // Get parent phone numbers for this student
                    const response = await fetch('api/get_student_parents.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            admission_number: student.admission_number,
                            school_id: schoolId
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success && data.parents && data.parents.length > 0) {
                        // Send to each parent
                        for (const parent of data.parents) {
                            if (parent.phone) {
                                // Function to format grade for SMS
                                function formatGradeForSMS(grade) {
                                    let formatted;
                                    if (grade.includes('+')) {
                                        formatted = grade.replace('+', '') + ' (PLUS)';
                                    } else if (grade.includes('-')) {
                                        formatted = grade.replace('-', '') + ' (MINUS)';
                                    } else {
                                        formatted = grade + ' (PLAIN)';
                                    }
                                    console.log(`Grade formatting: ${grade} -> ${formatted}`);
                                    return formatted;
                                }
                                
                                // Build SMS message with subject performance
                                let smsMessage = `${schoolName} - ${student.exam_type} Results for ${student.name} (${student.admission_number})\n\n`;
                                
                                console.log(`Building SMS for student ${student.admission_number}`);
                                
                                // Add subject marks and grades
                                student.subjects.forEach(subject => {
                                    const formattedGrade = formatGradeForSMS(subject.grade);
                                    smsMessage += `${subject.name}: ${subject.marks} (${formattedGrade})\n`;
                                });
                                
                                // Add summary
                                smsMessage += `\nTotal: ${student.total_marks}, Points: ${student.total_points}`;
                                const finalGradeFormatted = formatGradeForSMS(student.grade);
                                smsMessage += `\nFinal Grade: ${finalGradeFormatted}`;
                                
                                console.log(`Final SMS message:\n${smsMessage}`);
                                
                                const smsResponse = await fetch('../sms/api/send_sms.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        phone: parent.phone,
                                        message: smsMessage,
                                        school_id: schoolId,
                                        recipient_name: parent.name,
                                        recipient_type: 'parent',
                                        message_type: 'results'
                                    })
                                });

                                const smsData = await smsResponse.json();
                                
                                if (smsData.success) {
                                    successCount++;
                                } else {
                                    failureCount++;
                                }
                            }
                        }
                    } else {
                        failureCount++;
                    }
                } catch (error) {
                    console.error('Error sending SMS:', error);
                    failureCount++;
                }
            }

            showNotification(`SMS sending complete. Success: ${successCount}, Failed: ${failureCount}`, successCount > 0 ? 'success' : 'warning');
        }
        
        // Populate class and stream filters
        async function populateFilters() {
            const response = await loadPerformanceRecords();
            const allPerformanceRecords = response.success ? (response.data || []) : [];
            
            console.log('Populating filters with', allPerformanceRecords.length, 'records');
            
            // Get unique classes
            const classes = [...new Set(allPerformanceRecords.map(r => r.class_name).filter(Boolean))].sort();
            const classFilter = document.getElementById('classFilter');
            classes.forEach(cls => {
                const option = document.createElement('option');
                option.value = cls;
                option.textContent = cls;
                classFilter.appendChild(option);
            });

            // Get unique streams
            const streams = [...new Set(allPerformanceRecords.map(r => r.stream_name).filter(Boolean))].sort();
            const streamFilter = document.getElementById('streamFilter');
            streams.forEach(stream => {
                const option = document.createElement('option');
                option.value = stream;
                option.textContent = stream;
                streamFilter.appendChild(option);
            });

            // Get unique exam types
            const examTypes = [...new Set(allPerformanceRecords.map(r => r.exam_type_name).filter(Boolean))].sort();
            const examTypeFilter = document.getElementById('examTypeFilter');
            examTypes.forEach(examType => {
                const option = document.createElement('option');
                option.value = examType;
                option.textContent = examType;
                examTypeFilter.appendChild(option);
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            populateFilters();
        });
    </script>
</body>
</html>
