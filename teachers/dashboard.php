<?php
// Teacher Dashboard
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: index.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$stream_id = $_SESSION['stream_id'] ?? null;
$class_name = $_SESSION['class_name'] ?? '';
$stream_name = $_SESSION['stream_name'] ?? '';

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
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id = ? AND ap.term = 'Term 1' AND ap.year = YEAR(CURDATE())");
        $stmt->execute([$class_id]);
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
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id IN ($placeholders) AND ap.term = 'Term 1' AND ap.year = YEAR(CURDATE())");
        $stmt->execute($class_ids);
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
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .action-btn:hover {
            background: #e55a2b;
        }
        
        .action-btn i {
            margin-right: 8px;
        }
        
        .action-btn.secondary {
            background: var(--bg-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .action-btn.secondary:hover {
            background: #f1f3f4;
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
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="attendance.php">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="performance.php">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="students.php">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="parents.php">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="fees.php">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="api/logout.php">
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
                    <a href="attendance.php" class="action-btn">
                        <i class="fas fa-calendar-check"></i> Take Attendance
                    </a>
                    <a href="performance.php" class="action-btn secondary">
                        <i class="fas fa-chart-line"></i> Record Performance
                    </a>
                    <a href="students.php" class="action-btn secondary">
                        <i class="fas fa-user-graduate"></i> View Students
                    </a>
                    <a href="parents.php" class="action-btn secondary">
                        <i class="fas fa-users"></i> View Parents
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
