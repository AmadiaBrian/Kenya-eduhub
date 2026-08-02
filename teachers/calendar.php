<?php
// Teacher Calendar View - Read-only view of school calendar
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];

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
    
    // Get active term for current year
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND year = ? AND is_active = 1");
    $stmt->execute([$school_id, $current_year]);
    $current_status['current_term'] = $stmt->fetch();
    
    // Check if today is a holiday
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1");
    $stmt->execute([$school_id, $today, $today]);
    $current_status['current_holiday'] = $stmt->fetch();
    $current_status['is_holiday'] = (bool)$current_status['current_holiday'];
    
    // Determine school status
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
    <title>Calendar - <?php echo htmlspecialchars($teacher_name); ?></title>
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: 1px solid rgba(0, 0, 0, 0.05);
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
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
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
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link active" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-award"></i> Results
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="student-subjects">
                <i class="fas fa-book"></i> Student Subjects
            </a>
            <a class="nav-link" href="assignments">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="duty">
                <i class="fas fa-clipboard-list"></i> Duty
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
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
        <h1 class="page-title">School Calendar</h1>
        
        <!-- Current Status Card -->
        <div class="card">
            <h2 class="card-title">Current School Status</h2>
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
        
        <!-- Terms -->
        <div class="card">
            <h2 class="card-title">Academic Terms</h2>
            <?php if (empty($terms)): ?>
                <p style="color: #5f6368;">No terms found.</p>
            <?php else: ?>
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
                                    <?php if ($term['is_active']): ?>
                                        <span class="status-badge status-in-session">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-break">Not Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Holidays -->
        <div class="card">
            <h2 class="card-title">School Holidays</h2>
            <?php if (empty($holidays)): ?>
                <p style="color: #5f6368;">No holidays found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Holiday Name</th>
                            <th>Description</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holidays as $holiday): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($holiday['holiday_name']); ?></td>
                                <td><?php echo htmlspecialchars($holiday['description'] ?? '-'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($holiday['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($holiday['end_date'])); ?></td>
                                <td><?php echo ucfirst($holiday['holiday_type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- School Events -->
        <div class="card">
            <h2 class="card-title">School Events</h2>
            <?php if (empty($school_events)): ?>
                <p style="color: #5f6368;">No events found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($school_events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['description'] ?? '-'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['event_time'] ?? '-'); ?></td>
                                <td><?php echo ucfirst($event['event_type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
    </script>
    
    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg-color); color: #5f6368; padding: 1rem; text-align: center; border-top: 1px solid #e8eaed; z-index: 1000;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
