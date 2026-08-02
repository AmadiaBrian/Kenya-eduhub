<?php
// Streams View for Examiners (Read-Only for Exam Purposes)
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

// Get stream statistics
$totalStreams = 0;
$totalStudents = 0;
$totalCapacity = 0;
$totalClasses = 0;

try {
    // Count unique stream names
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT stream_name) as count FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = ?");
    $stmt->execute([$school_id]);
    $totalStreams = $stmt->fetch()['count'];

    // Get all streams for capacity and student counts
    $stmt = $pdo->prepare("SELECT s.id, s.capacity, c.class_name FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = ?");
    $stmt->execute([$school_id]);
    $streams = $stmt->fetchAll();

    $classNames = [];
    foreach ($streams as $stream) {
        $totalCapacity += $stream['capacity'];
        if (!in_array($stream['class_name'], $classNames)) {
            $classNames[] = $stream['class_name'];
        }

        // Count students for this stream
        $studentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE stream_id = ?");
        $studentStmt->execute([$stream['id']]);
        $studentCount = $studentStmt->fetch()['count'];
        $totalStudents += $studentCount;
    }
    $totalClasses = count($classNames);
} catch (PDOException $e) {
    error_log("Failed to fetch stream stats: " . $e->getMessage());
}

// Get classes for filter dropdown
$classes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get streams data
$streamsData = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.stream_name, s.capacity, 
               GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') as class_names,
               GROUP_CONCAT(DISTINCT c.class_level ORDER BY c.class_level SEPARATOR ', ') as class_levels,
               SUM(s.capacity) as total_capacity,
               COUNT(DISTINCT s.id) as stream_count,
               (SELECT COUNT(*) FROM students st WHERE st.stream_id = s.id) as student_count
        FROM streams s 
        JOIN classes c ON s.class_id = c.id 
        WHERE c.school_id = ?
        GROUP BY s.stream_name
        ORDER BY s.stream_name
    ");
    $stmt->execute([$school_id]);
    $streamsData = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch streams: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Streams - <?php echo htmlspecialchars($examiner_name); ?></title>
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
            transition: transform 0.3s ease;
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
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
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
                <a class="nav-link active" href="streams">
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
            <h1 class="page-title">Streams (View Only)</h1>
            
            <!-- Stream Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalStreams; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Streams</div>
                </div>
                <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $totalClasses; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Classes with Streams</div>
                </div>
                <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $totalStudents; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Students</div>
                </div>
                <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $totalCapacity; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Capacity</div>
                </div>
            </div>
            
            <!-- Streams Table -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-layer-group me-2"></i>All Streams
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" class="form-control" id="searchStream" placeholder="Search by stream name or class..." onkeyup="filterStreams()">
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <select class="form-control" id="filterClass" onchange="filterStreams()">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_name']; ?>"><?php echo htmlspecialchars($class['class_name']); ?> (<?php echo htmlspecialchars($class['class_level']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <select class="form-control" id="filterStreamCapacity" onchange="filterStreams()">
                                <option value="">All Capacities</option>
                                <option value="low">Low (&lt;30)</option>
                                <option value="medium">Medium (30-50)</option>
                                <option value="high">High (&gt;50)</option>
                            </select>
                        </div>
                        <button class="btn btn-success" onclick="exportStreams()">
                            <i class="fas fa-download me-2"></i> Export
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="streamsTable">
                            <thead>
                                <tr>
                                    <th>Stream Name</th>
                                    <th>Classes</th>
                                    <th>Levels</th>
                                    <th>Total Capacity</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($streamsData)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No streams found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($streamsData as $stream): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stream['stream_name']); ?></td>
                                            <td><?php echo htmlspecialchars($stream['class_names']); ?></td>
                                            <td><?php echo htmlspecialchars($stream['class_levels']); ?></td>
                                            <td><?php echo $stream['total_capacity']; ?></td>
                                            <td><?php echo $stream['student_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
            
            linksContainer.classList.toggle('collapsed');
            titleElement.classList.toggle('collapsed');
        }
        
        // Filter streams
        function filterStreams() {
            const search = document.getElementById('searchStream').value.toLowerCase();
            const classFilter = document.getElementById('filterClass').value.toLowerCase();
            const capacity = document.getElementById('filterStreamCapacity').value;
            
            const rows = document.querySelectorAll('#streamsTable tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const streamName = cells[0].textContent.toLowerCase();
                const className = cells[1].textContent.toLowerCase();
                const capacityValue = parseInt(cells[3].textContent);
                
                const matchesSearch = streamName.includes(search) || className.includes(search);
                const matchesClass = !classFilter || className.includes(classFilter);
                
                let matchesCapacity = true;
                if (capacity === 'low') {
                    matchesCapacity = capacityValue < 30;
                } else if (capacity === 'medium') {
                    matchesCapacity = capacityValue >= 30 && capacityValue <= 50;
                } else if (capacity === 'high') {
                    matchesCapacity = capacityValue > 50;
                }
                
                row.style.display = (matchesSearch && matchesClass && matchesCapacity) ? '' : 'none';
            });
        }
        
        // Export streams to CSV
        function exportStreams() {
            const rows = document.querySelectorAll('#streamsTable tbody tr');
            let csvContent = 'Stream Name,Class,Level,Capacity,Students\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const rowData = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent,
                    cells[3].textContent,
                    cells[4].textContent
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
            link.setAttribute('download', `streams_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
