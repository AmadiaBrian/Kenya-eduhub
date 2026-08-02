<?php
// Classes View for Examiners (Read-Only for Exam Purposes)
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

// Fetch class statistics
$totalClasses = 0;
$totalStreams = 0;
$totalStudents = 0;
$totalCapacity = 0;

try {
    $stmt = $pdo->prepare("SELECT id, capacity FROM classes WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
    
    foreach ($classes as $class) {
        $totalClasses++;
        $totalCapacity += $class['capacity'];
        
        // Count streams for this class
        $streamStmt = $pdo->prepare("SELECT COUNT(*) as count FROM streams WHERE class_id = ?");
        $streamStmt->execute([$class['id']]);
        $streamCount = $streamStmt->fetch()['count'];
        $totalStreams += $streamCount;
        
        // Count students for this class
        $studentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
        $studentStmt->execute([$class['id']]);
        $studentCount = $studentStmt->fetch()['count'];
        $totalStudents += $studentCount;
    }
} catch (PDOException $e) {
    error_log("Failed to fetch class stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Classes - <?php echo htmlspecialchars($examiner_name); ?></title>
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
                <a class="nav-link active" href="classes">
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
            <h1 class="page-title">Classes (View Only)</h1>
            
            <!-- Class Statistics -->
            <div id="classStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalClasses; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Classes</div>
                </div>
                <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $totalStreams; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Streams</div>
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
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter me-2"></i>Filter Classes
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchClass" placeholder="Search by class name or level..." onkeyup="filterClasses()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Level</label>
                            <select class="form-control" id="filterClassLevel" onchange="filterClasses()">
                                <option value="">All Levels</option>
                                <option value="Primary">Primary</option>
                                <option value="Secondary">Secondary</option>
                                <option value="College">College</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capacity</label>
                            <select class="form-control" id="filterClassCapacity" onchange="filterClasses()">
                                <option value="">All Capacities</option>
                                <option value="low">Low (&lt;30)</option>
                                <option value="medium">Medium (30-50)</option>
                                <option value="high">High (&gt;50)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <button class="btn btn-success w-100" onclick="exportClasses()">
                                <i class="fas fa-download me-2"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chalkboard me-2"></i>All Classes
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Level</th>
                                    <th>Capacity</th>
                                    <th>Streams</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody id="classesTable">
                                <tr><td colspan="5" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
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
        
        async function loadClasses() {
            try {
                const response = await fetch('../schools/api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('classesTable');
                    tbody.innerHTML = data.data.map(cls => `
                        <tr>
                            <td>${cls.class_name}</td>
                            <td>${cls.class_level}</td>
                            <td>${cls.capacity}</td>
                            <td>${cls.stream_count || 0}</td>
                            <td>${cls.student_count}</td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading classes:', error);
                document.getElementById('classesTable').innerHTML = '<tr><td colspan="5" class="text-center">Error loading classes</td></tr>';
            }
        }
        
        // Filter classes
        function filterClasses() {
            const search = document.getElementById('searchClass').value.toLowerCase();
            const level = document.getElementById('filterClassLevel').value;
            const capacity = document.getElementById('filterClassCapacity').value;
            
            const rows = document.querySelectorAll('#classesTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const className = cells[0].textContent.toLowerCase();
                const classLevel = cells[1].textContent.toLowerCase();
                const capacityValue = parseInt(cells[2].textContent);
                
                const matchesSearch = className.includes(search) || classLevel.includes(search);
                const matchesLevel = !level || classLevel.includes(level.toLowerCase());
                
                let matchesCapacity = true;
                if (capacity === 'low') {
                    matchesCapacity = capacityValue < 30;
                } else if (capacity === 'medium') {
                    matchesCapacity = capacityValue >= 30 && capacityValue <= 50;
                } else if (capacity === 'high') {
                    matchesCapacity = capacityValue > 50;
                }
                
                row.style.display = (matchesSearch && matchesLevel && matchesCapacity) ? '' : 'none';
            });
        }
        
        // Export classes to CSV
        function exportClasses() {
            const rows = document.querySelectorAll('#classesTable tr');
            let csvContent = 'Class Name,Level,Capacity,Streams,Students\n';
            
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
            link.setAttribute('download', `classes_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        loadClasses();
    </script>
</body>
</html>
