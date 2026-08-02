<?php
// Attendance View for Examiners (Read-Only for Exam Purposes)
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

// Get selected filters
$selected_class = $_GET['class_id'] ?? '';
$selected_stream = $_GET['stream_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Fetch attendance statistics
$totalRecords = 0;
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$excusedCount = 0;

try {
    $stmt = $pdo->prepare("SELECT a.status FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.school_id = ?");
    $stmt->execute([$school_id]);
    $records = $stmt->fetchAll();
    
    foreach ($records as $record) {
        $totalRecords++;
        if ($record['status'] === 'present') $presentCount++;
        if ($record['status'] === 'absent') $absentCount++;
        if ($record['status'] === 'late') $lateCount++;
        if ($record['status'] === 'excused') $excusedCount++;
    }
} catch (PDOException $e) {
    error_log("Failed to fetch attendance stats: " . $e->getMessage());
}

// Fetch attendance records based on filters
$attendance_records = [];
try {
    $query = "SELECT a.*, s.admission_number, s.first_name, s.last_name, c.class_name, st.stream_name
              FROM attendance a
              JOIN students s ON a.student_id = s.id
              LEFT JOIN classes c ON s.class_id = c.id
              LEFT JOIN streams st ON s.stream_id = st.id
              WHERE s.school_id = ?";
    $params = [$school_id];
    
    if ($selected_class) {
        $query .= " AND s.class_id = ?";
        $params[] = $selected_class;
    }
    
    if ($selected_stream) {
        $query .= " AND s.stream_id = ?";
        $params[] = $selected_stream;
    }
    
    // Filter by date range if specified
    if ($date_from) {
        $query .= " AND a.date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND a.date <= ?";
        $params[] = $date_to;
    }
    
    // If single date selected, filter by that date
    if ($selected_date && !$date_from && !$date_to) {
        $query .= " AND a.date = ?";
        $params[] = $selected_date;
    }
    
    $query .= " ORDER BY a.date DESC, s.admission_number LIMIT 200";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch attendance records: " . $e->getMessage());
    $attendance_records = [];
}

// Fetch classes for filters
try {
    $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

// Fetch streams for filters
try {
    $stmt = $pdo->prepare("SELECT s.id, s.stream_name, s.class_id, c.class_name 
                          FROM streams s 
                          LEFT JOIN classes c ON s.class_id = c.id 
                          WHERE c.school_id = ? 
                          ORDER BY c.class_name, s.stream_name");
    $stmt->execute([$school_id]);
    $all_streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_streams = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Attendance - <?php echo htmlspecialchars($examiner_name); ?></title>
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
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
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
        
        .form-control {
            border: 1px solid rgba(0,0,0,0.04);
            border-radius: 12px;
            padding: 8px 12px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26,115,232,0.1);
        }
        
        .btn-success {
            background: #1e8e3e;
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 500;
        }
        
        .btn-success:hover {
            background: #137333;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .bg-success {
            background: #e6f4ea;
            color: #137333;
        }
        
        .bg-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .bg-warning {
            background: #fef7e0;
            color: #b06000;
        }
        
        .bg-info {
            background: #e8f0fe;
            color: #1967d2;
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
                <a class="nav-link active" href="attendance">
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
            <h1 class="page-title">Attendance Records (View Only)</h1>
            
            <!-- Attendance Statistics -->
            <div id="attendanceStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalRecords; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Records</div>
                </div>
                <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $presentCount; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Present</div>
                </div>
                <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $absentCount; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Absent</div>
                </div>
                <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $lateCount; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Late</div>
                </div>
                <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #5f6368;"><?php echo $excusedCount; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Excused</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter me-2"></i>Filter Attendance Records
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" id="classSelect" class="form-control" onchange="updateStreams(); this.form.submit()">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stream</label>
                            <select name="stream_id" id="streamSelect" class="form-control" onchange="this.form.submit()">
                                <option value="">All Streams</option>
                                <?php if ($selected_class): ?>
                                    <?php 
                                    $class_streams = array_filter($all_streams, fn($s) => $s['class_id'] == $selected_class);
                                    foreach ($class_streams as $stream): ?>
                                        <option value="<?php echo $stream['id']; ?>" <?php echo $selected_stream == $stream['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($stream['stream_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Records Table -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-check me-2"></i>Attendance Records
                </div>
                <div class="card-body">
                    <?php if (!empty($attendance_records)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Adm No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['stream_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            echo match($record['status']) {
                                                'present' => 'bg-success',
                                                'absent' => 'bg-danger',
                                                'late' => 'bg-warning',
                                                'excused' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                            ?>">
                                            <?php echo ucfirst(htmlspecialchars($record['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted mt-2">Showing last 200 records. Use date filters to narrow down results.</p>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No attendance records found for the selected filters.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: #f8f9fa; padding: 12px 24px; text-align: center; font-size: 12px; color: #5f6368; z-index: 998;">
        &copy; <?php echo date('Y'); ?> Kenya EduHub - Examiners Portal
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store all streams data for dynamic filtering
        const allStreamsData = <?php echo json_encode($all_streams); ?>;
        
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

        function updateStreams() {
            const classSelect = document.getElementById('classSelect');
            const streamSelect = document.getElementById('streamSelect');
            const selectedClass = classSelect.value;
            
            // Clear current options
            streamSelect.innerHTML = '<option value="">All Streams</option>';
            
            if (selectedClass) {
                // Filter streams for selected class
                const classStreams = allStreamsData.filter(s => s.class_id == selectedClass);
                classStreams.forEach(stream => {
                    const option = document.createElement('option');
                    option.value = stream.id;
                    option.textContent = stream.stream_name;
                    streamSelect.appendChild(option);
                });
            } else {
                // Show all streams grouped by class
                const groupedStreams = {};
                allStreamsData.forEach(stream => {
                    if (!groupedStreams[stream.class_name]) {
                        groupedStreams[stream.class_name] = [];
                    }
                    groupedStreams[stream.class_name].push(stream);
                });
                
                for (const className in groupedStreams) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = className;
                    groupedStreams[className].forEach(stream => {
                        const option = document.createElement('option');
                        option.value = stream.id;
                        option.textContent = stream.stream_name;
                        optgroup.appendChild(option);
                    });
                    streamSelect.appendChild(optgroup);
                }
            }
        }
    </script>
</body>
</html>
