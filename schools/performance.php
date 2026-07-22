<?php
// Performance Tracking Page
// Authentication is handled by index.php router

$school_name = $_SESSION['school_name'] ?? 'School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link active" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
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
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Academic Performance</h1>
        
        <!-- Performance Statistics -->
        <div id="performanceStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <?php
            $school_id = $_SESSION['school_id'];
            $totalRecords = 0;
            $averageMarks = 0;
            $highestGrade = 0;
            $totalStudents = 0;
            
            try {
                $stmt = $pdo->prepare("SELECT pr.marks, pr.student_id FROM academic_performance pr JOIN students s ON pr.student_id = s.id WHERE s.school_id = ?");
                $stmt->execute([$school_id]);
                $records = $stmt->fetchAll();
                
                $studentIds = [];
                $totalMarks = 0;
                foreach ($records as $record) {
                    $totalRecords++;
                    $totalMarks += $record['marks'];
                    if (!in_array($record['student_id'], $studentIds)) {
                        $studentIds[] = $record['student_id'];
                    }
                    if ($record['marks'] > $highestGrade) {
                        $highestGrade = $record['marks'];
                    }
                }
                $totalStudents = count($studentIds);
                $averageMarks = $totalRecords > 0 ? round($totalMarks / $totalRecords, 1) : 0;
            } catch (PDOException $e) {
                error_log("Failed to fetch performance stats: " . $e->getMessage());
            }
            ?>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalRecords; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Records</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $averageMarks; ?>%</div>
                <div style="font-size: 12px; color: #5f6368;">Average Marks</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $highestGrade; ?>%</div>
                <div style="font-size: 12px; color: #5f6368;">Highest Grade</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $totalStudents; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Students Assessed</div>
            </div>
        </div>
        
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
                <a href="teachers.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="quick-access-label">Teachers</div>
                </a>
                <a href="attendance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="fees.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="quick-access-label">Fees</div>
                </a>
                <a href="classes.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Performance Records</span>
                <button class="btn btn-success" onclick="exportPerformance()">
                    <i class="fas fa-download me-2"></i> Export
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchPerformance" placeholder="Search by name or admission number" onkeyup="filterPerformanceRecords()">
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterClass" onchange="filterPerformanceRecords()">
                            <option value="">All Classes</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterStream" onchange="filterPerformanceRecords()">
                            <option value="">All Streams</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterTerm" onchange="filterPerformanceRecords()">
                            <option value="">All Terms</option>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterYear" onchange="filterPerformanceRecords()">
                            <option value="">All Years</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterSubject" onchange="filterPerformanceRecords()">
                            <option value="">All Subjects</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button class="btn btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
                <div id="recordsContainer" style="display: flex; flex-direction: column; gap: 20px;">
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table" style="min-width: 800px;">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Term</th>
                                    <th>Year</th>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="recordsTable">
                                <tr><td colspan="10" class="text-center">Loading performance records...</td></tr>
                            </tbody>
                        </table>
                    </div>
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
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        let allPerformanceRecords = [];
        
        async function loadPerformanceRecords() {
            try {
                const response = await fetch('api/performance.php');
                const data = await response.json();
                console.log('Performance records response:', data);
                
                if (data.success && data.data.length > 0) {
                    allPerformanceRecords = data.data;
                    populateClassFilter(data.data);
                    populateStreamFilter(data.data);
                    populateSubjectFilter(data.data);
                    filterPerformanceRecords();
                } else {
                    document.getElementById('recordsTable').innerHTML = '<tr><td colspan="10" class="text-center">No performance records found</td></tr>';
                }
            } catch (error) {
                console.error('Error loading performance records:', error);
                document.getElementById('recordsTable').innerHTML = '<tr><td colspan="8" class="text-center">Error loading performance records</td></tr>';
            }
        }
        
        function populateSubjectFilter(records) {
            const subjects = [...new Set(records.map(r => r.subject))];
            const subjectSelect = document.getElementById('filterSubject');
            subjectSelect.innerHTML = '<option value="">All Subjects</option>';
            subjects.forEach(subject => {
                subjectSelect.innerHTML += `<option value="${subject}">${subject}</option>`;
            });
        }
        
        function populateClassFilter(records) {
            const classes = [...new Set(records.map(r => r.class_name).filter(c => c))];
            const classSelect = document.getElementById('filterClass');
            classSelect.innerHTML = '<option value="">All Classes</option>';
            classes.forEach(className => {
                classSelect.innerHTML += `<option value="${className}">${className}</option>`;
            });
        }
        
        function populateStreamFilter(records) {
            const streams = [...new Set(records.map(r => r.stream_name).filter(s => s))];
            const streamSelect = document.getElementById('filterStream');
            streamSelect.innerHTML = '<option value="">All Streams</option>';
            streams.forEach(streamName => {
                streamSelect.innerHTML += `<option value="${streamName}">${streamName}</option>`;
            });
        }
        
        function filterPerformanceRecords() {
            const searchTerm = document.getElementById('searchPerformance').value.toLowerCase();
            const classFilter = document.getElementById('filterClass').value;
            const streamFilter = document.getElementById('filterStream').value;
            const termFilter = document.getElementById('filterTerm').value;
            const yearFilter = document.getElementById('filterYear').value;
            const subjectFilter = document.getElementById('filterSubject').value;
            
            const filtered = allPerformanceRecords.filter(record => {
                const matchesSearch = !searchTerm || 
                    record.admission_number.toLowerCase().includes(searchTerm) ||
                    `${record.first_name} ${record.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesClass = !classFilter || record.class_name === classFilter;
                const matchesStream = !streamFilter || record.stream_name === streamFilter;
                const matchesTerm = !termFilter || record.term === termFilter;
                const matchesYear = !yearFilter || record.year == yearFilter;
                const matchesSubject = !subjectFilter || record.subject === subjectFilter;
                
                return matchesSearch && matchesClass && matchesStream && matchesTerm && matchesYear && matchesSubject;
            });
            
            const container = document.getElementById('recordsContainer');
            if (filtered.length > 0) {
                // Group by subject if no subject filter is applied
                if (!subjectFilter) {
                    const groupedBySubject = {};
                    filtered.forEach(record => {
                        const subjectName = record.subject || 'No Subject';
                        if (!groupedBySubject[subjectName]) {
                            groupedBySubject[subjectName] = [];
                        }
                        groupedBySubject[subjectName].push(record);
                    });
                    
                    // Sort subjects alphabetically
                    const sortedSubjects = Object.keys(groupedBySubject).sort();
                    
                    let html = '';
                    sortedSubjects.forEach(subjectName => {
                        html += `
                            <div class="card">
                                <div class="card-header" style="background-color: #e8f0fe; font-weight: bold;">
                                    <i class="fas fa-book"></i> ${subjectName} (${groupedBySubject[subjectName].length} records)
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto;">
                                        <table class="table" style="min-width: 800px;">
                                            <thead>
                                                <tr>
                                                    <th>Admission No</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th>Stream</th>
                                                    <th>Term</th>
                                                    <th>Year</th>
                                                    <th>Marks</th>
                                                    <th>Grade</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                        `;
                        
                        groupedBySubject[subjectName].forEach(record => {
                            html += `
                                <tr>
                                    <td>${record.admission_number}</td>
                                    <td>${record.first_name} ${record.last_name}</td>
                                    <td>${record.class_name || '-'}</td>
                                    <td>${record.stream_name || '-'}</td>
                                    <td>${record.term}</td>
                                    <td>${record.year}</td>
                                    <td>${record.marks}</td>
                                    <td>${record.grade || '-'}</td>
                                    <td>${record.remarks || '-'}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    // Show single table when specific subject is selected
                    container.innerHTML = `
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table" style="min-width: 800px;">
                                <thead>
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Stream</th>
                                        <th>Term</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filtered.map(record => `
                                        <tr>
                                            <td>${record.admission_number}</td>
                                            <td>${record.first_name} ${record.last_name}</td>
                                            <td>${record.class_name || '-'}</td>
                                            <td>${record.stream_name || '-'}</td>
                                            <td>${record.term}</td>
                                            <td>${record.year}</td>
                                            <td>${record.subject}</td>
                                            <td>${record.marks}</td>
                                            <td>${record.grade || '-'}</td>
                                            <td>${record.remarks || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            } else {
                container.innerHTML = '<p class="text-center">No matching records found</p>';
            }
        }
        
        function resetFilters() {
            document.getElementById('searchPerformance').value = '';
            document.getElementById('filterClass').value = '';
            document.getElementById('filterStream').value = '';
            document.getElementById('filterTerm').value = '';
            document.getElementById('filterYear').value = '';
            document.getElementById('filterSubject').value = '';
            filterPerformanceRecords();
        }
        
        // Export performance to CSV
        function exportPerformance() {
            const searchTerm = document.getElementById('searchPerformance').value.toLowerCase();
            const classFilter = document.getElementById('filterClass').value;
            const streamFilter = document.getElementById('filterStream').value;
            const termFilter = document.getElementById('filterTerm').value;
            const yearFilter = document.getElementById('filterYear').value;
            const subjectFilter = document.getElementById('filterSubject').value;
            
            const filtered = allPerformanceRecords.filter(record => {
                const matchesSearch = !searchTerm || 
                    record.admission_number.toLowerCase().includes(searchTerm) ||
                    `${record.first_name} ${record.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesClass = !classFilter || record.class_name === classFilter;
                const matchesStream = !streamFilter || record.stream_name === streamFilter;
                const matchesTerm = !termFilter || record.term === termFilter;
                const matchesYear = !yearFilter || record.year == yearFilter;
                const matchesSubject = !subjectFilter || record.subject === subjectFilter;
                
                return matchesSearch && matchesClass && matchesStream && matchesTerm && matchesYear && matchesSubject;
            });
            
            let csvContent = 'Admission No,Student Name,Class,Stream,Term,Year,Subject,Marks,Grade,Remarks\n';
            
            filtered.forEach(record => {
                const rowData = [
                    record.admission_number,
                    `${record.first_name} ${record.last_name}`,
                    record.class_name || '-',
                    record.stream_name || '-',
                    record.term,
                    record.year,
                    record.subject,
                    record.marks,
                    record.grade || '-',
                    record.remarks || '-'
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
            link.setAttribute('download', `performance_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Add event listeners for real-time filtering
        document.addEventListener('DOMContentLoaded', function() {
            loadPerformanceRecords();
            
            document.getElementById('searchPerformance').addEventListener('input', filterPerformanceRecords);
            document.getElementById('filterClass').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterStream').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterTerm').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterYear').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterSubject').addEventListener('change', filterPerformanceRecords);
        });
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
