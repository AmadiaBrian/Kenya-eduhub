<?php
// Teacher Duty Assignments - Weekly Rotation System
// Authentication is handled by index.php router
$school_name = $_SESSION['school_name'] ?? 'School';
$school_id = $_SESSION['school_id'];

// Get school logo
try {
    $stmt = $pdo->prepare("SELECT logo FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $school_logo = $school['logo'] ?? null;
} catch (PDOException $e) {
    $school_logo = null;
}

// Handle weekly duty assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_weekly_duty'])) {
    try {
        $week_start = $_POST['week_start'];
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        
        // Insert multiple teachers for the week
        foreach ($_POST['teacher_ids'] as $teacher_id) {
            $stmt = $pdo->prepare("INSERT INTO duty_assignments (school_id, teacher_id, duty_type, week_start, week_end, assigned_by) VALUES (?, ?, 'weekly', ?, ?, ?)");
            $stmt->execute([
                $school_id,
                $teacher_id,
                $week_start,
                $week_end,
                $_SESSION['school_id']
            ]);
        }
        $success = "Weekly duty team assigned successfully!";
    } catch (PDOException $e) {
        $error = "Failed to assign weekly duty: " . $e->getMessage();
    }
}

// Handle duty deletion
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM duty_assignments WHERE id = ? AND school_id = ?");
        $stmt->execute([$_GET['delete'], $school_id]);
        $success = "Duty assignment deleted successfully!";
    } catch (PDOException $e) {
        $error = "Failed to delete duty assignment.";
    }
}

// Get all teachers for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM teachers WHERE school_id = ? ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    $teachers = [];
}

// Get all weekly duty assignments
try {
    $stmt = $pdo->prepare("
        SELECT da.*, t.first_name, t.last_name 
        FROM duty_assignments da 
        JOIN teachers t ON da.teacher_id = t.id 
        WHERE da.school_id = ? AND da.duty_type = 'weekly'
        ORDER BY da.week_start DESC
    ");
    $stmt->execute([$school_id]);
    $duty_assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    $duty_assignments = [];
}

// Group assignments by week
$weekly_assignments = [];
foreach ($duty_assignments as $assignment) {
    $week_key = $assignment['week_start'];
    if (!isset($weekly_assignments[$week_key])) {
        $weekly_assignments[$week_key] = [
            'week_start' => $assignment['week_start'],
            'week_end' => $assignment['week_end'],
            'teachers' => []
        ];
    }
    $weekly_assignments[$week_key]['teachers'][] = $assignment;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duty Assignments - <?php echo htmlspecialchars($school_name); ?></title>
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
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-color);
            border-bottom: 1px solid #e8eaed;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .header-right {
            display: flex;
            align-items: center;
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
        
        .school-avatar {
            width: 32px;
            height: 32px;
            background: #FFD700;
            border: 2px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: #FF6B35;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            border-right: 1px solid #e8eaed;
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
            transform: translateX(-100%);
        }
        
        .sidebar h6 {
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
            color: #202124;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
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
        
        .card {
            background: var(--card-bg);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card h4 {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .table {
            margin-top: 16px;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e8eaed;
            font-weight: 600;
            color: #202124;
            padding: 12px;
        }
        
        .table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .footer {
            background: transparent;
            color: white;
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 40px;
        }
        
        .weekly-schedule {
            border: 2px solid #e8eaed;
            border-radius: 8px;
            background: white;
            margin-bottom: 20px;
        }
        
        .weekly-schedule-header {
            background: linear-gradient(135deg, #FF6B35 0%, #e55a2b 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 6px 6px 0 0;
        }
        
        .weekly-schedule-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }
        
        .weekly-schedule-body {
            padding: 20px;
        }
        
        .teacher-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 20px;
            margin: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .teacher-badge:hover {
            background: #e8f0fe;
            border-color: #d2e3fc;
        }
        
        .schedule-date {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 12px;
        }
        
        .schedule-date strong {
            color: #202124;
        }
        
        .no-schedule {
            text-align: center;
            padding: 40px;
            color: #5f6368;
        }
        
        .no-schedule i {
            font-size: 48px;
            color: #e8eaed;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 32px; height: 32px; background: #FFD700; border: 2px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 16px;">
                        <span style="color: #FF6B35; font-size: 18px;">K</span><span style="color: #008000; font-size: 16px;">E</span>
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
    
    <aside class="sidebar" id="sidebar">
        <div class="p-3">
            <h6 class="mb-3">School Menu</h6>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="dashboard"><i class="fas fa-home me-2"></i>Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="students"><i class="fas fa-user-graduate me-2"></i>Students</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="teachers"><i class="fas fa-chalkboard-teacher me-2"></i>Teachers</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="duty-assignments"><i class="fas fa-clipboard-list me-2"></i>Duty Assignments</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="disciplinary"><i class="fas fa-exclamation-triangle me-2"></i>Disciplinary</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="attendance"><i class="fas fa-calendar-check me-2"></i>Attendance</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="fees"><i class="fas fa-money-bill-wave me-2"></i>Fees</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="performance"><i class="fas fa-chart-line me-2"></i>Performance</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="settings"><i class="fas fa-cog me-2"></i>Settings</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="profile"><i class="fas fa-user me-2"></i>Profile</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-danger" href="logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </li>
            </ul>
        </div>
    </aside>
    
    <main class="main-content" id="mainContent">
        <div class="card">
            <h4 class="mb-4">Assign Weekly Duty Team</h4>
            <p class="text-muted mb-4">Assign multiple teachers as the duty team for a full week. They will manage the school, handle sick students, report incidents, ensure cleanliness, and maintain order.</p>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Week Start Date (Monday)</label>
                        <input type="date" class="form-control" name="week_start" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Teachers on Duty (Multiple)</label>
                        <div style="border: 2px solid #e8eaed; border-radius: 8px; background: white; padding: 16px;">
                            <div style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($teachers as $teacher): ?>
                                    <div class="form-check mb-2" style="padding: 8px; border-bottom: 1px solid #f1f3f4;">
                                        <input class="form-check-input" type="checkbox" name="teacher_ids[]" value="<?php echo $teacher['id']; ?>" id="teacher_<?php echo $teacher['id']; ?>" style="width: 18px; height: 18px;">
                                        <label class="form-check-label" for="teacher_<?php echo $teacher['id']; ?>" style="font-size: 14px; font-weight: 500; color: #202124;">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <small class="text-muted">Select multiple teachers by checking the boxes</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="assign_weekly_duty" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Assign Weekly Duty Team
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 class="mb-0">Weekly Duty Schedule</h4>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Print Schedule
                </button>
            </div>
            <?php if (empty($weekly_assignments)): ?>
                <div class="no-schedule">
                    <i class="fas fa-calendar-times"></i>
                    <h5>No Weekly Duty Schedule</h5>
                    <p>Assign teachers to manage the school for weekly rotations.</p>
                </div>
            <?php else: ?>
                <?php foreach ($weekly_assignments as $week): ?>
                    <div class="weekly-schedule">
                        <div class="weekly-schedule-header">
                            <h5>
                                <i class="fas fa-calendar-week me-2"></i>
                                Week of <?php echo date('F j, Y', strtotime($week['week_start'])); ?>
                            </h5>
                        </div>
                        <div class="weekly-schedule-body">
                            <div class="schedule-date">
                                <strong>Period:</strong> <?php echo date('F j, Y', strtotime($week['week_start'])); ?> - <?php echo date('F j, Y', strtotime($week['week_end'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong style="color: #202124;">Teachers on Duty:</strong>
                            </div>
                            <div>
                                <?php foreach ($week['teachers'] as $teacher): ?>
                                    <span class="teacher-badge">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        <a href="?delete=<?php echo $teacher['id']; ?>" 
                                           class="text-danger ms-2"
                                           style="text-decoration: none;"
                                           onclick="return confirm('Remove this teacher from duty?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="footer">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
