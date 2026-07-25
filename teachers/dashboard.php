<?php
// Teacher Dashboard
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$stream_id = $_SESSION['stream_id'] ?? null;
$class_name = $_SESSION['class_name'] ?? '';
$stream_name = $_SESSION['stream_name'] ?? '';

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

// Get teacher details
try {
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher details: " . $e->getMessage());
    $teacher = null;
}

// Get subject assignments for subject teachers
$subject_assignments = [];
if ($teacher && $teacher['teacher_type'] === 'subject_teacher') {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, c.class_name 
                             FROM teacher_subjects ts
                             JOIN classes c ON ts.class_id = c.id
                             WHERE ts.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $subject_assignments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subject assignments: " . $e->getMessage());
    }
}

// Get statistics for teacher's class(es)
$stats = [];
try {
    if ($teacher['teacher_type'] === 'class_teacher' && $class_id) {
        // Class teacher - statistics for their class
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ? AND status = 'active'");
        $stmt->execute([$class_id]);
        $stats['total_students'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = CURDATE()");
        $stmt->execute([$class_id]);
        $stats['attendance_today'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = CURDATE() AND a.status = 'present'");
        $stmt->execute([$class_id]);
        $stats['present_today'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id = ? AND ap.term = ? AND ap.year = YEAR(CURDATE())");
        $stmt->execute([$class_id, $current_term]);
        $stats['performance_records'] = $stmt->fetch()['total'];
    } elseif ($teacher['teacher_type'] === 'subject_teacher' && !empty($subject_assignments)) {
        // Subject teacher - statistics for all assigned classes
        $class_ids = array_column($subject_assignments, 'class_id');
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE class_id IN ($placeholders) AND status = 'active'");
        $stmt->execute($class_ids);
        $stats['total_students'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id IN ($placeholders) AND a.date = CURDATE()");
        $stmt->execute($class_ids);
        $stats['attendance_today'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id IN ($placeholders) AND a.date = CURDATE() AND a.status = 'present'");
        $stmt->execute($class_ids);
        $stats['present_today'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id IN ($placeholders) AND ap.term = ? AND ap.year = YEAR(CURDATE())");
        $stmt->execute(array_merge($class_ids, [$current_term]));
        $stats['performance_records'] = $stmt->fetch()['total'];
    } else {
        $stats['total_students'] = 0;
        $stats['attendance_today'] = 0;
        $stats['present_today'] = 0;
        $stats['performance_records'] = 0;
    }
} catch (PDOException $e) {
    error_log("Failed to fetch statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($teacher_name); ?></title>
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
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 24px;
        }
        
        .stat-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            transition: box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .stat-card:hover {
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        .stat-label {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .stat-change {
            font-size: 12px;
            color: #5f6368;
        }
        
        .stat-change.positive {
            color: #1e8e3e;
        }
        
        .stat-change.negative {
            color: #d93025;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .action-card {
            background: white;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            text-decoration: none;
            color: #202124;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
        }
        
        .action-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }
        
        .action-card.primary {
            background: linear-gradient(135deg, var(--primary-color), #ff8c00);
            color: white;
            border: none;
        }
        
        .action-card.primary:hover {
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #e8f0fe;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .action-card.primary .action-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .action-title {
            font-size: 14px;
            font-weight: 500;
            color: inherit;
        }
        
        .action-description {
            font-size: 12px;
            color: inherit;
            opacity: 0.8;
            margin-top: 4px;
        }
        
        /* Activity List */
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e8eaed;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8f0fe;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-size: 14px;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .activity-time {
            font-size: 12px;
            color: #5f6368;
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
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
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
                <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link active" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="assignments">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="duty">
                <i class="fas fa-clipboard-list"></i> My Duties
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
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
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
            Welcome, <?php echo htmlspecialchars($teacher_name); ?>
            <?php if ($teacher['school_name']): ?>
                | <?php echo htmlspecialchars($teacher['school_name']); ?>
            <?php endif; ?>
            <?php if ($class_name): ?>
                | Class: <?php echo htmlspecialchars($class_name); ?>
                <?php if ($stream_name): ?>
                    - <?php echo htmlspecialchars($stream_name); ?>
                <?php endif; ?>
            <?php endif; ?>
        </p>
        
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
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?php echo $stats['total_students'] ?? 0; ?></div>
                <div class="stat-change">In your class</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Attendance Today</div>
                <div class="stat-value"><?php echo $stats['attendance_today'] ?? 0; ?></div>
                <div class="stat-change">Records taken</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Present Today</div>
                <div class="stat-value"><?php echo $stats['present_today'] ?? 0; ?></div>
                <div class="stat-change positive">
                    <?php 
                    $attendance_rate = $stats['attendance_today'] > 0 
                        ? round(($stats['present_today'] / $stats['attendance_today']) * 100, 1) 
                        : 0;
                    echo $attendance_rate . '%';
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Performance Records</div>
                <div class="stat-value"><?php echo $stats['performance_records'] ?? 0; ?></div>
                <div class="stat-change">This term</div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Quick Actions Card -->
            <div class="card">
                <h2 class="card-title">Quick Actions</h2>
                <div class="quick-actions">
                    <a href="attendance" class="action-card primary">
                        <div class="action-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="action-title">Take Attendance</div>
                        <div class="action-description">Mark daily attendance</div>
                    </a>
                    <a href="performance" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="action-title">Record Performance</div>
                        <div class="action-description">Update student grades</div>
                    </a>
                    <a href="students" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="action-title">View Students</div>
                        <div class="action-description">Manage student records</div>
                    </a>
                    <a href="parents" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="action-title">View Parents</div>
                        <div class="action-description">Contact parents</div>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity Card -->
            <div class="card">
                <h2 class="card-title">Recent Activity</h2>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Attendance taken for your class</div>
                            <div class="activity-time">Today</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Performance records updated</div>
                            <div class="activity-time">Yesterday</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">New student enrolled in your class</div>
                            <div class="activity-time">2 days ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
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
