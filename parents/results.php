<?php
// Parent Results Management - View Child's Results
// Authentication is handled by index.php router
$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

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

// Get parent details and their children
$children = [];
$school_logo = '';
try {
    $stmt = $pdo->prepare("SELECT p.*, s.school_name, s.logo FROM parents p JOIN schools s ON p.school_id = s.id WHERE p.id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    $school_logo = $parent['logo'] ?? '';
    
    // Get children of this parent
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch parent details: " . $e->getMessage());
    $parent = null;
}

// Get school subject limits
$school_min_subjects = 7;
$school_max_subjects = 8;
if ($parent) {
    try {
        $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
        $stmt->execute([$parent['school_id']]);
        $school_limits = $stmt->fetch();
        $school_min_subjects = $school_limits['min_subjects'] ?? 7;
        $school_max_subjects = $school_limits['max_subjects'] ?? 8;
    } catch (PDOException $e) {
        error_log("Failed to fetch school subject limits: " . $e->getMessage());
    }
}

// Get all grading scales for this school
$grading_scales = [];
if ($parent) {
    try {
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id, s.school_id as subject_school_id
                              FROM grading_scales gs
                              LEFT JOIN subjects s ON gs.subject_id = s.id
                              WHERE gs.school_id = ?
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$parent['school_id']]);
        $grading_scales = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch grading scales: " . $e->getMessage());
    }
}

// Get aggregate points distribution
$aggregate_distribution = [];
if ($parent) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$parent['school_id']]);
        $aggregate_distribution = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch aggregate distribution: " . $e->getMessage());
    }
}

// Get student subject assignments for filtering total points calculation
$student_subject_assignments = [];
if ($parent) {
    try {
        $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN students st ON ss.student_id = st.id
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE ss.school_id = ?");
        $stmt->execute([$parent['school_id']]);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - <?php echo htmlspecialchars($parent_name); ?></title>
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
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .menu-btn:hover {
            background: #f1f3f4;
        }

        .menu-btn i {
            font-size: 20px;
            color: #000;
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
        
        /* Print styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0.5cm;
            }
            
            .header, .sidebar, .card, .btn, .form-control, .form-select, label, .modal, .modal-backdrop {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                margin-top: 0 !important;
                padding: 5px !important;
            }
            
            .page-title {
                display: block !important;
                margin-bottom: 5px;
                font-size: 12px !important;
            }
            
            #resultsContainer {
                display: block !important;
            }
            
            .table {
                border: 1px solid #000 !important;
                font-size: 7px !important;
                width: 100% !important;
                table-layout: fixed !important;
            }
            
            .table th, .table td {
                border: 1px solid #000 !important;
                padding: 2px 1px !important;
                font-size: 7px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            
            .table th {
                font-size: 7px !important;
                font-weight: 600 !important;
            }
            
            body {
                background: white !important;
                font-size: 8px !important;
            }
            
            h4 {
                display: block !important;
                margin-top: 10px;
                margin-bottom: 5px;
                font-size: 10px !important;
                page-break-before: auto;
            }
            
            /* Hide modal elements */
            #printModal {
                display: none !important;
            }
            
            /* Ensure table fits on page */
            .table-responsive {
                overflow: visible !important;
            }
            
            /* Hide less important columns in portrait */
            .table th:nth-child(n+7),
            .table td:nth-child(n+7) {
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
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link active" href="results">
                <i class="fas fa-award"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="assignments">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
            </a>
            <a class="nav-link" href="fines">
                <i class="fas fa-exclamation-triangle"></i> Fines
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
        <h1 class="page-title">Student Results</h1>
        
        <div class="card mb-4">
            <div class="card-title">Filter Results</div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Select Child</label>
                    <select class="form-control" id="childFilter">
                        <option value="">All Children</option>
                        <?php foreach ($children as $child): ?>
                            <option value="<?php echo $child['id']; ?>">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name'] . ' (' . $child['admission_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select class="form-control" id="termFilter">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo htmlspecialchars($term); ?>">
                                <?php echo htmlspecialchars($term); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select class="form-control" id="yearFilter">
                        <option value="">All Years</option>
                        <?php for ($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                            <option value="<?php echo $year; ?>">
                                <?php echo $year; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Exam Type</label>
                    <select class="form-control" id="examTypeFilter">
                        <option value="">All Exam Types</option>
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
                    <button class="btn btn-success w-100" onclick="showPrintDialog()">
                        <i class="fas fa-print me-1"></i> Print Results
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-secondary w-100" onclick="resetFilters()">
                        <i class="fas fa-redo me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <div id="resultsContainer">
            <div class="text-center py-5">
                <i class="fas fa-award fa-3x text-muted mb-3"></i>
                <p class="text-muted">Select filters and click "Load Results" to view student results</p>
            </div>
        </div>
    </main>

    <!-- Print Dialog Modal -->
    <div class="modal fade" id="printModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Exam to Print</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Choose Exam Type:</label>
                        <select class="form-control" id="printExamSelect">
                            <option value="">Select an exam...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="printSelectedExam()">Print</button>
                </div>
            </div>
        </div>
    </div>

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // School configuration
        const schoolMinSubjects = <?php echo json_encode($school_min_subjects); ?>;
        const schoolMaxSubjects = <?php echo json_encode($school_max_subjects); ?>;
        const aggregateDistribution = <?php echo json_encode($aggregate_distribution); ?>;
        const studentSubjectAssignments = <?php echo json_encode($student_subject_assignments); ?>;
        const gradingScales = <?php echo json_encode($grading_scales); ?>;
        const parentChildren = <?php echo json_encode($children); ?>;
        const schoolName = <?php echo json_encode($school_name); ?>;
        const schoolLogo = <?php echo json_encode($school_logo); ?>;

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

        // Function to show notification
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

        // Load performance records
        async function loadPerformanceRecords() {
            try {
                const response = await fetch('../schools/api/performance.php');
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
            container.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Loading results...</p></div>';

            const response = await loadPerformanceRecords();
            console.log('API Response:', response);
            
            // Check if response has data
            if (!response.success) {
                container.innerHTML = '<div class="text-center py-5"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><p class="text-danger">Failed to load performance records: ' + (response.error || 'Unknown error') + '</p></div>';
                return;
            }
            
            const allPerformanceRecords = response.data || [];
            console.log('Total records loaded:', allPerformanceRecords.length);
            
            // Get filter values
            const childFilter = document.getElementById('childFilter').value;
            const termFilter = document.getElementById('termFilter').value;
            const yearFilter = document.getElementById('yearFilter').value;
            const examTypeFilter = document.getElementById('examTypeFilter').value;

            // Get parent's children and their class/stream info
            const selectedChild = childFilter ? parentChildren.find(c => c.id.toString() === childFilter) : parentChildren[0];
            
            console.log('Selected child:', selectedChild);
            console.log('Parent children:', parentChildren);
            console.log('Child filter value:', childFilter);
            
            // If a child is selected, get their class and stream
            let targetClass = null;
            let targetStream = null;
            let childIds = parentChildren.map(c => c.id.toString());
            
            if (selectedChild) {
                targetClass = selectedChild.class_name;
                targetStream = selectedChild.stream_name;
                console.log('Target class:', targetClass, 'Target stream:', targetStream);
            } else if (parentChildren.length > 0) {
                // If no child selected, use first child's class
                targetClass = parentChildren[0].class_name;
                targetStream = parentChildren[0].stream_name;
                console.log('Using first child - Class:', targetClass, 'Stream:', targetStream);
            }
            
            // Filter records - show all students in the same class/stream as selected child
            const filtered = allPerformanceRecords.filter(record => {
                console.log('Checking record:', record.admission_number, 'Class:', record.class_name, 'Stream:', record.stream_name);
                // Must be in the same class/stream as the selected child
                if (targetClass && record.class_name !== targetClass) {
                    console.log('  - Filtered out: class mismatch');
                    return false;
                }
                if (targetStream && record.stream_name !== targetStream) {
                    console.log('  - Filtered out: stream mismatch');
                    return false;
                }
                
                if (termFilter && record.term !== termFilter) {
                    console.log('  - Filtered out: term mismatch');
                    return false;
                }
                if (yearFilter && String(record.year) !== String(yearFilter)) {
                    console.log('  - Filtered out: year mismatch (record:', record.year, 'filter:', yearFilter, ')');
                    return false;
                }
                if (examTypeFilter && record.exam_type_name !== examTypeFilter) {
                    console.log('  - Filtered out: exam type mismatch');
                    return false;
                }
                console.log('  - PASSED filter');
                return true;
            });

            console.log('Filtered records:', filtered.length);

            if (filtered.length === 0) {
                container.innerHTML = '<div class="text-center py-5"><i class="fas fa-inbox fa-3x text-muted mb-3"></i><p class="text-muted">No results found for the selected filters</p></div>';
                return;
            }

            // Group by exam type
            const groupedByExamType = {};
            filtered.forEach(record => {
                // Skip records without exam type
                if (!record.exam_type_name) return;
                
                const examType = record.exam_type_name;
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

                // Get selected child's assigned subjects
                const selectedChildAdmission = selectedChild ? selectedChild.admission_number : null;
                const childAssignedSubjects = studentSubjectAssignments[selectedChildAdmission] || [];
                const childSubjectNames = childAssignedSubjects.map(s => s.subject_name);

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
                    // Only add subjects that are assigned to the selected child
                    if (childSubjectNames.includes(record.subject)) {
                        groupedByStudent[studentKey].subjects[record.subject] = record;
                        allSubjects.add(record.subject);
                    }
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

        // Populate exam type filter
        async function populateFilters() {
            const allPerformanceRecords = await loadPerformanceRecords();
            const response = allPerformanceRecords.success ? (allPerformanceRecords.data || []) : [];
            
            // Get first child's class/stream for filtering
            const firstChild = parentChildren[0];
            const targetClass = firstChild ? firstChild.class_name : null;
            const targetStream = firstChild ? firstChild.stream_name : null;
            
            // Filter to students in the same class/stream as parent's child
            const classRecords = response.filter(r => {
                if (targetClass && r.class_name !== targetClass) return false;
                if (targetStream && r.stream_name !== targetStream) return false;
                return true;
            });
            
            console.log('Populating filters with', classRecords.length, 'records from class:', targetClass);
            
            // Get unique exam types
            const examTypes = [...new Set(classRecords.map(r => r.exam_type_name).filter(Boolean))].sort();
            const examTypeFilter = document.getElementById('examTypeFilter');
            examTypes.forEach(examType => {
                const option = document.createElement('option');
                option.value = examType;
                option.textContent = examType;
                examTypeFilter.appendChild(option);
            });
        }

        // Show print dialog
        function showPrintDialog() {
            const container = document.getElementById('resultsContainer');
            if (container.innerHTML.trim() === '' || container.innerHTML.includes('Select filters and click')) {
                showNotification('Please load results before printing', 'warning');
                return;
            }
            
            // Get available exam types from the current results
            const examHeaders = container.querySelectorAll('h4');
            const examSelect = document.getElementById('printExamSelect');
            examSelect.innerHTML = '<option value="">Select an exam...</option>';
            
            examHeaders.forEach(header => {
                // Extract exam type from header text (remove icon and extra text)
                let examName = header.textContent.trim();
                // Remove the icon character and any leading/trailing whitespace
                examName = examName.replace(/^[^\w\s]+/, '').trim();
                // Remove any trailing whitespace
                examName = examName.trim();
                
                if (examName) {
                    const option = document.createElement('option');
                    option.value = examName;
                    option.textContent = examName;
                    examSelect.appendChild(option);
                }
            });
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('printModal'));
            modal.show();
        }

        // Print selected exam
        function printSelectedExam() {
            const examSelect = document.getElementById('printExamSelect');
            const selectedExam = examSelect.value;
            
            if (!selectedExam) {
                showNotification('Please select an exam to print', 'warning');
                return;
            }
            
            // Close the modal first
            const modal = bootstrap.Modal.getInstance(document.getElementById('printModal'));
            modal.hide();
            
            // Wait for modal to close, then generate report
            setTimeout(() => {
                generateStudentReport(selectedExam);
            }, 300);
        }
        
        // Generate detailed student report
        function generateStudentReport(examType) {
            const container = document.getElementById('resultsContainer');
            const selectedChild = parentChildren.find(c => c.id.toString() === document.getElementById('childFilter').value) || parentChildren[0];
            
            if (!selectedChild) {
                showNotification('No child selected', 'warning');
                return;
            }
            
            // Get the selected exam's data
            const examHeaders = container.querySelectorAll('h4');
            let examData = null;
            let examHeader = null;
            
            examHeaders.forEach(header => {
                const headerExam = header.textContent.trim().replace(/^[^\w\s]+/, '').trim();
                if (headerExam === examType) {
                    examHeader = header;
                    const table = header.nextElementSibling.querySelector('table');
                    if (table) {
                        examData = table;
                    }
                }
            });
            
            if (!examData) {
                showNotification('Could not find exam data', 'error');
                return;
            }
            
            // Generate report HTML
            const reportHTML = generateReportHTML(selectedChild, examType, examData);
            
            // Create print window with proper styling
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<!DOCTYPE html><html><head><title>Student Performance Report</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('* { margin: 0; padding: 0; box-sizing: border-box; }');
            printWindow.document.write('body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 20px; font-size: 12px; background: #fff; color: #000; }');
            printWindow.document.write('.report-header { text-align: center; margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, #FF6B35 0%, #ff8c5a 100%); color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }');
            printWindow.document.write('.report-header .school-logo { width: 80px; height: 80px; margin: 0 auto 15px auto; display: block; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }');
            printWindow.document.write('.report-header .school-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; color: #fff; }');
            printWindow.document.write('.report-header .report-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #fff; }');
            printWindow.document.write('.student-info { display: flex; justify-content: space-between; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 2px solid #FF6B35; border-radius: 8px; }');
            printWindow.document.write('.student-info .info-item { margin: 8px 0; color: #000; }');
            printWindow.document.write('.student-info .info-label { font-weight: bold; color: #FF6B35; }');
            printWindow.document.write('.report-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }');
            printWindow.document.write('.report-table th { background: #FF6B35; color: #fff; padding: 12px; text-align: left; font-weight: bold; border: 1px solid #e65a2b; }');
            printWindow.document.write('.report-table td { border: 1px solid #ddd; padding: 12px; text-align: left; color: #000; }');
            printWindow.document.write('.report-table tr:nth-child(even) { background: #f8f9fa; }');
            printWindow.document.write('.summary { margin-top: 20px; padding: 20px; border: 2px solid #FF6B35; border-radius: 8px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }');
            printWindow.document.write('.summary .summary-title { font-size: 16px; font-weight: bold; color: #FF6B35; margin-bottom: 15px; text-align: center; text-transform: uppercase; letter-spacing: 1px; }');
            printWindow.document.write('.summary .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }');
            printWindow.document.write('.summary .info-item { text-align: center; padding: 12px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e0e0e0; }');
            printWindow.document.write('.summary .info-label { display: block; font-size: 11px; font-weight: bold; color: #666; margin-bottom: 5px; text-transform: uppercase; }');
            printWindow.document.write('.summary .info-value { display: block; font-size: 18px; font-weight: bold; color: #FF6B35; }');
            printWindow.document.write('.report-footer { margin-top: 30px; padding: 15px; text-align: center; border-top: 2px solid #FF6B35; background: #f8f9fa; }');
            printWindow.document.write('.report-footer .footer-logo { font-size: 20px; font-weight: bold; margin-bottom: 10px; color: #FF6B35; }');
            printWindow.document.write('.report-footer .footer-text { font-size: 11px; color: #666; line-height: 1.6; }');
            printWindow.document.write('.report-footer .kenya-text { color: #FF6B35; font-weight: bold; }');
            printWindow.document.write('.report-footer .eduhub-text { color: #008000; font-weight: bold; }');
            printWindow.document.write('@media print { @page { size: A4; margin: 0.7cm; } body { margin: 0; padding: 0; font-size: 12px; background: #fff; } .report-header { padding: 20px; margin-bottom: 20px; background: #fff !important; } .report-header .school-logo { width: 75px; height: 75px; margin-bottom: 14px; } .report-header .school-name { font-size: 24px; margin-bottom: 6px; color: #FF6B35 !important; } .report-header .report-title { font-size: 17px; margin-bottom: 12px; color: #333 !important; } .student-info { padding: 16px; margin-bottom: 20px; } .student-info .info-item { margin: 7px 0; font-size: 11px; } .report-table { margin-top: 14px; } .report-table th { padding: 11px; font-size: 11px; } .report-table td { padding: 11px; font-size: 12px; } .summary { padding: 20px; margin-top: 20px; } .summary .summary-title { font-size: 17px; margin-bottom: 14px; } .summary .summary-grid { gap: 14px; } .summary .info-item { padding: 12px; } .summary .info-label { font-size: 10px; margin-bottom: 6px; } .summary .info-value { font-size: 20px; } .report-footer { padding: 18px; margin-top: 30px; } .report-footer .footer-logo { font-size: 20px; margin-bottom: 12px; } .report-footer .footer-text { font-size: 10px; line-height: 1.6; } .report-header .school-logo { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .report-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .report-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(reportHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            
            // Wait for content to load, then print
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            }, 500);
        }
        
        // Generate report HTML
        function generateReportHTML(student, examType, table) {
            const rows = table.querySelectorAll('tr');
            const subjects = [];
            const studentRow = null;
            
            // Get subject columns (skip first 5 columns: Rank, Admission, Name, Class, Stream)
            const headers = rows[0].querySelectorAll('th');
            for (let i = 5; i < headers.length - 4; i++) {
                subjects.push({
                    name: headers[i].textContent.trim(),
                    index: i
                });
            }
            
            // Find the student's row
            let studentData = null;
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td');
                if (cells[1].textContent.trim() === student.admission_number) {
                    studentData = cells;
                    break;
                }
            }
            
            if (!studentData) {
                return '<p>Student data not found</p>';
            }
            
            // Build subject rows
            let subjectRows = '';
            subjects.forEach(sub => {
                const cell = studentData[sub.index];
                const cellText = cell.textContent.trim();
                // Parse marks and grade from format like "50 (B+)"
                const match = cellText.match(/([\d.]+)\s*\(([^)]+)\)/);
                const marks = match ? match[1] : cellText;
                const grade = match ? match[2] : '-';
                subjectRows += `
                    <tr>
                        <td>${sub.name}</td>
                        <td>${marks}</td>
                        <td>${grade}</td>
                    </tr>
                `;
            });
            
            // Get summary data
            const totalMarks = studentData[subjects.length + 5].textContent.trim();
            const average = studentData[subjects.length + 6].textContent.trim();
            const totalPoints = studentData[subjects.length + 7].textContent.trim();
            const grade = studentData[subjects.length + 8].textContent.trim();
            
            return `
                <div class="report-header">
                    <img src="${schoolLogo || '../assets/images/logo-DRV3mraH.png'}" alt="School Logo" class="school-logo" onerror="this.src='../assets/images/logo2-UFkwg77b.png'">
                    <div class="school-name">${schoolName || 'School Name'}</div>
                    <div class="report-title">STUDENT PERFORMANCE REPORT</div>
                </div>
                
                <div class="student-info">
                    <div>
                        <div class="info-item"><span class="info-label">Student Name:</span> ${student.first_name} ${student.last_name}</div>
                        <div class="info-item"><span class="info-label">Admission No:</span> ${student.admission_number}</div>
                        <div class="info-item"><span class="info-label">Class:</span> ${student.class_name}</div>
                        <div class="info-item"><span class="info-label">Stream:</span> ${student.stream_name}</div>
                    </div>
                    <div>
                        <div class="info-item"><span class="info-label">Exam Type:</span> ${examType}</div>
                        <div class="info-item"><span class="info-label">Year:</span> ${new Date().getFullYear()}</div>
                        <div class="info-item"><span class="info-label">Date:</span> ${new Date().toLocaleDateString()}</div>
                    </div>
                </div>
                
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${subjectRows}
                    </tbody>
                </table>
                
                <div class="summary">
                    <div class="summary-title">Performance Summary</div>
                    <div class="summary-grid">
                        <div class="info-item">
                            <span class="info-label">Total Marks</span>
                            <span class="info-value">${totalMarks}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Average</span>
                            <span class="info-value">${average}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Points</span>
                            <span class="info-value">${totalPoints}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Overall Grade</span>
                            <span class="info-value">${grade}</span>
                        </div>
                    </div>
                </div>
                
                <div class="report-footer">
                    <div class="footer-logo"><span class="kenya-text">Kenya</span> <span class="eduhub-text">EduHub</span></div>
                    <div class="footer-text">
                        <span class="kenya-text">Kenya</span> <span class="eduhub-text">EduHub</span> - Comprehensive School Management System<br>
                        Empowering Education Through Technology • Streamlining Academic Excellence<br>
                        © ${new Date().getFullYear()} <span class="kenya-text">Kenya</span> <span class="eduhub-text">EduHub</span>. All Rights Reserved.
                    </div>
                </div>
            `;
        }

        // Print results function (deprecated, kept for backward compatibility)
        function printResults() {
            showPrintDialog();
        }

        // Reset filters function
        function resetFilters() {
            document.getElementById('childFilter').value = '';
            document.getElementById('termFilter').value = '';
            document.getElementById('yearFilter').value = '';
            document.getElementById('examTypeFilter').value = '';
            
            showNotification('Filters reset. Click "Load Results" to apply.', 'info');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            populateFilters();
        });
    </script>
</body>
</html>
