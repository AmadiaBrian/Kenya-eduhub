<?php
// Timetable View for Examiners (Read-Only for Exam Purposes)
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

// Get timetables
$timetables = [];
try {
    $stmt = $pdo->prepare("SELECT t.*, c.class_name FROM timetables t LEFT JOIN classes c ON t.class_id = c.id WHERE t.school_id = ? ORDER BY t.created_at DESC");
    $stmt->execute([$school_id]);
    $timetables = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch timetables: " . $e->getMessage());
}

// Get timetable assignments for a specific timetable (if viewing)
$timetable_assignments = [];
$selected_timetable_id = $_GET['view_id'] ?? null;
$selected_timetable = null;
if ($selected_timetable_id) {
    // First get the selected timetable details
    try {
        $stmt = $pdo->prepare("SELECT t.*, c.class_name FROM timetables t LEFT JOIN classes c ON t.class_id = c.id WHERE t.id = ? AND t.school_id = ?");
        $stmt->execute([$selected_timetable_id, $school_id]);
        $selected_timetable = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to fetch timetable: " . $e->getMessage());
    }
    
    // Then get assignments
    if ($selected_timetable) {
        try {
            $stmt = $pdo->prepare("
                SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, ts.break_type,
                       s.subject_name, CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                       c.class_name, st.stream_name
                FROM timetable_assignments ta
                JOIN timetable_slots ts ON ta.slot_id = ts.id
                JOIN subjects s ON ta.subject_id = s.id
                JOIN teachers t ON ta.teacher_id = t.id
                JOIN classes c ON ta.class_id = c.id
                JOIN streams st ON ta.stream_id = st.id
                WHERE ta.timetable_id = ? AND ta.school_id = ?
                ORDER BY FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ts.start_time
            ");
            $stmt->execute([$selected_timetable_id, $school_id]);
            $timetable_assignments = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch timetable assignments: " . $e->getMessage());
        }
    }
}

// Get time slots
$time_slots = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM timetable_slots WHERE school_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
    $stmt->execute([$school_id]);
    $time_slots = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch time slots: " . $e->getMessage());
}

// Get school breaks
$school_breaks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 ORDER BY start_time");
    $stmt->execute([$school_id]);
    $school_breaks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch school breaks: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Timetable - <?php echo htmlspecialchars($examiner_name); ?></title>
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
        
        /* Badge */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-draft {
            background: #f1f3f4;
            color: #5f6368;
        }
        
        .badge-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-archived {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-secondary:hover {
            background: #3c4043;
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
                <a class="nav-link" href="streams">
                    <i class="fas fa-layer-group"></i> Streams
                </a>
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link active" href="timetable">
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
            <h1 class="page-title">Timetable (View Only)</h1>
            
            <?php if ($selected_timetable_id): ?>
                <!-- View Timetable -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-2"></i>Timetable View
                    </div>
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <a href="timetable" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Timetables
                            </a>
                        </div>
                        
                        <?php if ($selected_timetable): ?>
                            <div style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                                <h3 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars($selected_timetable['name']); ?></h3>
                                <p style="margin: 0; color: #5f6368;">
                                    <strong>Class:</strong> <?php echo htmlspecialchars($selected_timetable['class_name'] ?? '-'); ?> | 
                                    <strong>Year:</strong> <?php echo $selected_timetable['year']; ?> | 
                                    <strong>Term:</strong> <?php echo htmlspecialchars($selected_timetable['term']); ?> | 
                                    <strong>Type:</strong> <?php echo ucfirst($selected_timetable['timetable_type']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($timetable_assignments)): ?>
                            <div style="background: #e8f0fe; border: 1px solid #1967d2; padding: 16px; border-radius: 8px;">
                                <p style="margin: 0; color: #1967d2;">No assignments found for this timetable.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Stream</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($timetable_assignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assignment['day_of_week']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['start_time']); ?> - <?php echo htmlspecialchars($assignment['end_time']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['stream_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['notes'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
            
                <!-- Timetables List -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i>Timetables
                    </div>
                    <div class="card-body">
                        <?php if (empty($timetables)): ?>
                            <p style="color: #5f6368;">No timetables found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Year</th>
                                            <th>Term</th>
                                            <th>Class</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($timetables as $timetable): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($timetable['name']); ?></strong></td>
                                                <td><?php echo ucfirst($timetable['timetable_type']); ?></td>
                                                <td><?php echo $timetable['year']; ?></td>
                                                <td><?php echo htmlspecialchars($timetable['term']); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['class_name'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $timetable['status']; ?>">
                                                        <?php echo ucfirst($timetable['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-secondary btn-sm" onclick="viewTimetable(<?php echo $timetable['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Time Slots -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clock me-2"></i>Time Slots
                    </div>
                    <div class="card-body">
                        <?php if (empty($time_slots)): ?>
                            <p style="color: #5f6368;">No time slots found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($time_slots as $slot): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($slot['day_of_week']); ?></td>
                                                <td><?php echo htmlspecialchars($slot['start_time']); ?></td>
                                                <td><?php echo htmlspecialchars($slot['end_time']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- School Breaks -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-coffee me-2"></i>School-Wide Breaks
                    </div>
                    <div class="card-body">
                        <?php if (empty($school_breaks)): ?>
                            <p style="color: #5f6368;">No school breaks found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Break Name</th>
                                            <th>Type</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($school_breaks as $break): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($break['break_name']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $break['break_type'])); ?></td>
                                                <td><?php echo htmlspecialchars($break['start_time']); ?></td>
                                                <td><?php echo htmlspecialchars($break['end_time']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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
        
        function viewTimetable(id) {
            window.location.href = 'timetable?view_id=' + id;
        }
    </script>
</body>
</html>
