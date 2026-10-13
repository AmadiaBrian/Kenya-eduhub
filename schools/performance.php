<?php
// Performance Tracking Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

$school_name = $_SESSION['school_name'] ?? 'School';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $_SESSION['school_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Performance - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
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
            text-align: center;
        }
        
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

        .btn-danger {
            background: #d93025;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b92b20;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            margin: 0;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        .table thead {
            background: #FF6B35 !important;
            border-bottom: 2px solid #000;
        }

        .table thead th {
            background: #FF6B35 !important;
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #ffffff !important;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .table td {
            padding: 12px;
            font-size: 13px;
            color: #202124;
            border: 1px solid #e0e0e0;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .table tbody tr:hover {
            background: #f1f3f4;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .card {
                padding: 16px;
            }
            
            .table-responsive {
                margin: 0 -16px;
                padding: 0 16px;
                overflow-x: auto;
            }
            
            .table {
                min-width: 600px;
                font-size: 12px;
            }
            
            .table th, .table td {
                padding: 8px 4px;
            }
            
            .btn {
                width: 100%;
            }
            
            .col-md-2, .col-md-3, .col-md-4 {
                margin-bottom: 12px;
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
            color: #202124;
            transition: all 0.2s;
        }
        
        .quick-access-item:hover {
            background: #f1f3f4;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .quick-access-icon {
            font-size: 24px;
            color: #FF6B35;
            margin-bottom: 8px;
        }
        
        .quick-access-label {
            font-size: 13px;
            font-weight: 500;
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
<body>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'performance'; require_once 'includes/sidebar.php'; ?>
    
    <br>
    
  
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'performance'; require_once 'includes/sidebar.php'; ?>
    
    <br>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Academic Performance</h1>
        
        <!-- Calendar Status Alert -->
        <?php if ($calendar_status['is_holiday']): ?>
            <div style="background: #fce8e6; border: 1px solid #e0e0e0; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-exclamation-triangle" style="color: #c5221f; font-size: 20px;"></i>
                    <div>
                        <strong style="color: #c5221f;">School is on Holiday</strong>
                        <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                            <?php echo htmlspecialchars($calendar_status['current_holiday']['holiday_name']); ?> 
                            (<?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['end_date'])); ?>)
                        </p>
                    </div>
                </div>
            </div>
        <?php elseif ($calendar_status['school_status'] === 'break'): ?>
            <div style="background: #fef7e0; border: 1px solid #e0e0e0; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-info-circle" style="color: #f9ab00; font-size: 20px;"></i>
                    <div>
                        <strong style="color: #b06000;">School is on Break</strong>
                        <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">No active term is currently set. Please activate a term in the Calendar.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
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

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-success" onclick="exportPerformance()">
                <i class="fas fa-download me-2"></i> Export
            </button>
        </div>

        <!-- Search and Filter Section -->
        <div class="filter-section">
            <div class="card-title">Search & Filter</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" class="form-control" id="searchPerformance" placeholder="Search by name or admission number" onkeyup="filterPerformanceRecords()">
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select class="form-control" id="filterClass" onchange="filterPerformanceRecords()">
                        <option value="">All Classes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stream</label>
                    <select class="form-control" id="filterStream" onchange="filterPerformanceRecords()">
                        <option value="">All Streams</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Term</label>
                    <select class="form-control" id="filterTerm" onchange="filterPerformanceRecords()">
                        <option value="">All Terms</option>
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <select class="form-control" id="filterYear" onchange="filterPerformanceRecords()">
                        <option value="">All Years</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
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
        </div>

        <div class="card">
            <div id="performanceTableContainer">
                <p class="text-center">Loading performance records...</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
                    document.getElementById('performanceTableContainer').innerHTML = '<p class="text-center">No performance records found</p>';
                }
            } catch (error) {
                console.error('Error loading performance records:', error);
                document.getElementById('performanceTableContainer').innerHTML = '<p class="text-center">Error loading performance records</p>';
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
            
            const container = document.getElementById('performanceTableContainer');

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
                            <div style="margin-bottom: 30px;">
                                <h4 style="color: #FF6B35; margin-bottom: 15px; font-weight: 600;">
                                    <i class="fas fa-book"></i> ${subjectName}
                                </h4>
                                <div class="table-responsive" style="overflow-x: auto;">
                                    <table class="table" style="min-width: 800px;">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
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

                        // Sort records by marks descending for ranking
                        const sortedRecords = [...groupedBySubject[subjectName]].sort((a, b) => b.marks - a.marks);

                        sortedRecords.forEach((record, index) => {
                            html += `
                                <tr>
                                    <td><strong>#${index + 1}</strong></td>
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
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    // Show single table when specific subject is selected
                    // Sort by marks descending for ranking
                    const sortedRecords = [...filtered].sort((a, b) => b.marks - a.marks);

                    container.innerHTML = `
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table" style="min-width: 800px;">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
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
                                    ${sortedRecords.map((record, index) => `
                                        <tr>
                                            <td><strong>#${index + 1}</strong></td>
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
    
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
