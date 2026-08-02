<?php
// Academic Calendar View for Examiners (Read-Only for Exam Purposes)
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

// Get Terms
$terms = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? ORDER BY year DESC, term_number ASC");
    $stmt->execute([$school_id]);
    $terms = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
}

// Get Holidays
$holidays = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND is_active = 1 ORDER BY start_date ASC");
    $stmt->execute([$school_id]);
    $holidays = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch holidays: " . $e->getMessage());
}

// Get School Events
$school_events = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_events WHERE school_id = ? AND is_active = 1 ORDER BY event_date ASC");
    $stmt->execute([$school_id]);
    $school_events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch school events: " . $e->getMessage());
}

// Get Current Academic Status
$current_status = [
    'current_year' => date('Y'),
    'current_term' => null,
    'is_holiday' => false,
    'current_holiday' => null,
    'school_status' => 'unknown'
];

try {
    $today = date('Y-m-d');
    $current_year = date('Y');
    
    // Get active term for current year (regardless of date range)
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND year = ? AND is_active = 1");
    $stmt->execute([$school_id, $current_year]);
    $current_status['current_term'] = $stmt->fetch();
    
    // Check if today is a holiday
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1");
    $stmt->execute([$school_id, $today, $today]);
    $current_status['current_holiday'] = $stmt->fetch();
    $current_status['is_holiday'] = (bool)$current_status['current_holiday'];
    
    // Determine school status - holidays override term status
    if ($current_status['is_holiday']) {
        $current_status['school_status'] = 'holiday';
    } elseif ($current_status['current_term']) {
        $current_status['school_status'] = 'in_session';
    } else {
        $current_status['school_status'] = 'break';
    }
} catch (PDOException $e) {
    error_log("Failed to get current status: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Academic Calendar - <?php echo htmlspecialchars($examiner_name); ?></title>
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
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-in-session {
            background: #e6f4ea;
            color: #137333;
        }
        
        .status-holiday {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .status-break {
            background: #fef7e0;
            color: #f9ab00;
        }
        
        .status-closed {
            background: #f1f3f4;
            color: #5f6368;
        }
        
        .status-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .status-upcoming {
            background: #e8f0fe;
            color: #1967d2;
        }
        
        .status-completed {
            background: #f1f3f4;
            color: #5f6368;
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
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link" href="timetable">
                    <i class="fas fa-calendar-alt"></i> Timetable
                </a>
                <a class="nav-link active" href="calendar">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <h1 class="page-title">Academic Calendar (View Only)</h1>
            
            <!-- Current Status Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Current School Status
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                        <span class="status-badge status-<?php echo $current_status['school_status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $current_status['school_status'])); ?>
                        </span>
                        <span style="color: #5f6368;">
                            <?php echo date('F j, Y'); ?>
                        </span>
                    </div>
                    
                    <p style="color: #5f6368; margin-bottom: 8px;">
                        <strong>Current Year:</strong> <?php echo $current_status['current_year']; ?>
                    </p>
                    
                    <?php if ($current_status['current_term']): ?>
                        <p style="color: #5f6368; margin-bottom: 8px;">
                            <strong>Current Term:</strong> <?php echo htmlspecialchars($current_status['current_term']['term_name']); ?>
                            (<?php echo date('M j, Y', strtotime($current_status['current_term']['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($current_status['current_term']['end_date'])); ?>)
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($current_status['current_holiday']): ?>
                        <p style="color: #c5221f; margin-bottom: 8px;">
                            <strong>Holiday:</strong> <?php echo htmlspecialchars($current_status['current_holiday']['holiday_name']); ?>
                            (<?php echo date('M j, Y', strtotime($current_status['current_holiday']['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($current_status['current_holiday']['end_date'])); ?>)
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Terms -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar me-2"></i>Terms
                </div>
                <div class="card-body">
                    <?php if (empty($terms)): ?>
                        <p style="color: #5f6368;">No terms found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th>Term Name</th>
                                        <th>Term Number</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($terms as $term): ?>
                                        <tr>
                                            <td><?php echo $term['year']; ?></td>
                                            <td><?php echo htmlspecialchars($term['term_name']); ?></td>
                                            <td><?php echo $term['term_number']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($term['start_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($term['end_date'])); ?></td>
                                            <td>
                                                <?php 
                                                $today = date('Y-m-d');
                                                $start_date = date('Y-m-d', strtotime($term['start_date']));
                                                $end_date = date('Y-m-d', strtotime($term['end_date']));
                                                
                                                if ($term['is_active']): ?>
                                                    <span class="status-badge status-active">Active</span>
                                                <?php elseif ($end_date < $today): ?>
                                                    <span class="status-badge status-completed">Completed</span>
                                                <?php elseif ($start_date > $today): ?>
                                                    <span class="status-badge status-upcoming">Upcoming</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-completed">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Holidays -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-umbrella-beach me-2"></i>Holidays
                </div>
                <div class="card-body">
                    <?php if (empty($holidays)): ?>
                        <p style="color: #5f6368;">No holidays found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Holiday Name</th>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($holidays as $holiday): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($holiday['holiday_name']); ?></td>
                                            <td><?php echo ucfirst($holiday['holiday_type']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($holiday['start_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($holiday['end_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($holiday['description'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- School Events -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-day me-2"></i>School Events
                </div>
                <div class="card-body">
                    <?php if (empty($school_events)): ?>
                        <p style="color: #5f6368;">No events found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($school_events as $event): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                            <td><?php echo ucfirst($event['event_type']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                            <td><?php echo $event['event_time'] ? date('h:i A', strtotime($event['event_time'])) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($event['description'] ?? '-'); ?></td>
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
