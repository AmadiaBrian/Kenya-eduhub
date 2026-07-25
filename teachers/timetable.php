<?php
// Teacher Timetable View
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get teacher's timetable assignments
$timetable_assignments = [];
try {
    $stmt = $pdo->prepare("
        SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, ts.break_type,
               s.subject_name, t.name as timetable_name, t.academic_year, t.term, t.status,
               c.class_name, st.stream_name
        FROM timetable_assignments ta
        JOIN timetable_slots ts ON ta.slot_id = ts.id
        JOIN subjects s ON ta.subject_id = s.id
        JOIN timetables t ON ta.timetable_id = t.id
        JOIN classes c ON ta.class_id = c.id
        JOIN streams st ON ta.stream_id = st.id
        WHERE ta.teacher_id = ? AND ta.school_id = ?
        ORDER BY FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ts.start_time
    ");
    $stmt->execute([$teacher_id, $school_id]);
    $timetable_assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher timetable: " . $e->getMessage());
}

// Get school-wide breaks
$school_breaks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 ORDER BY start_time");
    $stmt->execute([$school_id]);
    $school_breaks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch school breaks: " . $e->getMessage());
}

// Group assignments by day for better display
$assignments_by_day = [];
$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days_order as $day) {
    $assignments_by_day[$day] = [];
}
foreach ($timetable_assignments as $assignment) {
    $assignments_by_day[$assignment['day_of_week']][] = $assignment;
}

// Apply breaks to weekdays only (Monday-Friday)
$breaks_by_day = [];
$weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($days_order as $day) {
    if (in_array($day, $weekdays)) {
        $breaks_by_day[$day] = $school_breaks; // Breaks apply to weekdays
    } else {
        $breaks_by_day[$day] = []; // No breaks on weekends
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable - <?php echo htmlspecialchars($teacher_name); ?></title>
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
        
        .teacher-avatar {
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
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 24px;
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
        
        .day-section {
            margin-bottom: 24px;
        }
        
        .day-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
            border: 1px solid #d2e3fc;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-break {
            background: #fce8e6;
            color: #c5221f;
        }
        
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
                <div style="width: 32px; height: 32px; background: #FFD700; border: 2px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 16px;">
                        <span style="color: #FF6B35; font-size: 20px;">K</span><span style="color: #008000; font-size: 16px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="teacher-avatar">
                <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard" class="nav-link">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="profile" class="nav-link">
            <i class="fas fa-user"></i> Profile
        </a>
        <a href="timetable" class="nav-link active">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>
        <a href="attendance" class="nav-link">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a href="calendar" class="nav-link">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a href="assignments" class="nav-link">
            <i class="fas fa-tasks"></i> Assignments
        </a>
        <a href="students" class="nav-link">
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a href="performance" class="nav-link">
            <i class="fas fa-chart-line"></i> Performance
        </a>
        <a href="duty" class="nav-link">
            <i class="fas fa-clipboard-list"></i> Duty
        </a>
        <a href="fees" class="nav-link">
            <i class="fas fa-money-bill-wave"></i> Fees
        </a>
        <a href="parents" class="nav-link">
            <i class="fas fa-users"></i> Parents
        </a>
        <button class="nav-link" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">My Timetable</h1>
        
        <!-- Calendar Status -->
        <div style="margin-bottom: 24px;">
            <?php if ($calendar_status['is_holiday']): ?>
                <div style="background: #fce8e6; border: 1px solid #c5221f; padding: 16px; border-radius: 8px;">
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
                <div style="background: #fef7e0; border: 1px solid #f9ab00; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-info-circle" style="color: #f9ab00; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #b06000;">School is on Break</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">No active term is currently set.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #e6f4ea; border: 1px solid #137333; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-check-circle" style="color: #137333; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #137333;">School is In Session</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                                <?php if ($calendar_status['current_term']): ?>
                                    Active Term: <?php echo htmlspecialchars($calendar_status['current_term']['term_name']); ?> 
                                    (<?php echo date('M j, Y', strtotime($calendar_status['current_term']['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($calendar_status['current_term']['end_date'])); ?>)
                                <?php else: ?>
                                    Year: <?php echo $calendar_status['current_year']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($timetable_assignments) && empty($school_breaks)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No timetable assignments found. Contact your school administrator to get assigned to a timetable.
            </div>
        <?php else: ?>
            <?php if (!empty($school_breaks)): ?>
                <div class="card mb-4" style="background: #fff3cd; border: 1px solid #ffc107;">
                    <div class="card-body">
                        <h5 class="card-title" style="color: #856404;">
                            <i class="fas fa-clock me-2"></i>School-Wide Breaks
                        </h5>
                        <p style="color: #856404; margin-bottom: 10px;">These breaks apply to weekdays (Monday-Friday):</p>
                        <?php foreach ($school_breaks as $break): ?>
                            <span class="badge me-2" style="background: #ffc107; color: #000;">
                                <i class="fas fa-coffee me-1"></i>
                                <?php echo htmlspecialchars($break['break_name']); ?>: 
                                <?php echo htmlspecialchars($break['start_time']); ?> - <?php echo htmlspecialchars($break['end_time']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php foreach ($days_order as $day): ?>
                <?php if (!empty($assignments_by_day[$day])): ?>
                    <div class="day-section">
                        <h3 class="day-title"><?php echo $day; ?></h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Class</th>
                                        <th>Stream</th>
                                        <th>Subject</th>
                                        <th>Timetable</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments_by_day[$day] as $assignment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($assignment['start_time']); ?></strong> - 
                                                <?php echo htmlspecialchars($assignment['end_time']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['stream_name']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($assignment['timetable_name']); ?><br>
                                                <small style="color: #5f6368;">
                                                    <?php echo $assignment['academic_year']; ?> - <?php echo htmlspecialchars($assignment['term']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($assignment['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
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
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'index.php?route=logout';
            }
        }
    </script>
</body>
</html>
