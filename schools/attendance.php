<?php
// Attendance Management Page
// Authentication is handled by index.php router
$school_name = $_SESSION['school_name'] ?? 'School';
$school_id = $_SESSION['school_id'];

// Fetch classes for this school
try {
    $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

// Fetch streams for this school (all streams for the dropdown)
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

// Fetch streams for selected class
$streams = [];
if ($selected_class) {
    try {
        $stmt = $pdo->prepare("SELECT id, stream_name FROM streams WHERE class_id = ? ORDER BY stream_name");
        $stmt->execute([$selected_class]);
        $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $streams = [];
    }
}

// Get selected filters
$selected_class = $_GET['class_id'] ?? '';
$selected_stream = $_GET['stream_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch students based on filters
$students = [];
if ($selected_class) {
    try {
        $query = "SELECT s.id, s.admission_number, s.first_name, s.last_name, s.class_id, s.stream_id,
                  c.class_name, st.stream_name,
                  (SELECT status FROM attendance WHERE student_id = s.id AND date = ?) as attendance_status,
                  (SELECT remarks FROM attendance WHERE student_id = s.id AND date = ?) as attendance_remarks
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams st ON s.stream_id = st.id
                  WHERE s.school_id = ? AND s.class_id = ?";
        $params = [$selected_date, $selected_date, $school_id, $selected_class];
        
        if ($selected_stream) {
            $query .= " AND s.stream_id = ?";
            $params[] = $selected_stream;
        }
        
        $query .= " ORDER BY s.admission_number";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $students = [];
    }
}

// Fetch past attendance records
$past_attendance = [];
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
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    if ($date_from) {
        $query .= " AND a.date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND a.date <= ?";
        $params[] = $date_to;
    }
    
    $query .= " ORDER BY a.date DESC, s.admission_number LIMIT 100";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $past_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $past_attendance = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
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
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
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
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
        }
        
        .btn-danger {
            background: #d93025;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b92b20;
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
        
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }
            
            .header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                padding: 0 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                transform: none !important;
            }
            
            .logo span {
                font-size: 18px;
            }
            
            .menu-btn {
                padding: 8px;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .menu-btn:hover {
                background: rgba(0,0,0,0.04);
            }
            
            .sidebar {
                position: fixed !important;
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
            }
            
            .table {
                min-width: 600px;
                width: 100%;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
            }
            
            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #dadce0;
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            }
            
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e8eaed;
            }
            
            .card-header .btn {
                width: 100%;
            }
            
            .card {
                text-align: center;
            }
            
            .card-header {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 12px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
            .main-content {
                padding: 12px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 12px;
            }
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
            
            /* Quick Access Mobile */
            .quick-access-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .quick-access-item {
                padding: 12px;
            }
            
            .quick-access-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
                color: #FF6B35;
                margin-bottom: 8px;
            }
            
            .quick-access-label {
                font-size: 11px;
            }
        }
        
        /* Quick Access */
        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
        }
        
        .quick-access-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .quick-access-item:hover {
            background: #fff3e0;
            transform: translateY(-2px);
        }
        
        .quick-access-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: transparent;
            color: #FF6B35;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .quick-access-label {
            font-size: 13px;
            font-weight: 500;
            color: #202124;
            text-align: center;
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
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
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
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-chalkboard"></i> Classes
            </a>
            <a class="nav-link" href="streams">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link" href="subjects">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link active" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Attendance Management</h1>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="dashboard.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="quick-access-label">Dashboard</div>
                </a>
                <a href="students.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="quick-access-label">Students</div>
                </a>
                <a href="teachers.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="quick-access-label">Teachers</div>
                </a>
                <a href="performance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="fees.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="quick-access-label">Fees</div>
                </a>
                <a href="classes.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
            </div>
        </div>
        
        <!-- Attendance Filter -->
        <div class="card">
            <h2 class="card-title">Filter Students</h2>
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" id="classSelect" class="form-control" required onchange="updateStreams(); this.form.submit()">
                        <option value="">Select Class</option>
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
                        <?php foreach ($streams as $stream): ?>
                            <option value="<?php echo $stream['id']; ?>" <?php echo $selected_stream == $stream['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stream['stream_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from ?? ''); ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to ?? ''); ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <!-- Attendance Table -->
        <?php if ($selected_class && !empty($students)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Mark Attendance - <?php echo htmlspecialchars($selected_date); ?></h2>
                <button class="btn btn-primary" onclick="saveAttendance()">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <form id="attendanceForm">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Adm No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['stream_name'] ?? '-'); ?></td>
                                    <td>
                                        <select name="attendance[<?php echo $student['id']; ?>][status]" class="form-control">
                                            <option value="">Select Status</option>
                                            <option value="present" <?php echo $student['attendance_status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo $student['attendance_status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo $student['attendance_status'] == 'late' ? 'selected' : ''; ?>>Late</option>
                                            <option value="excused" <?php echo $student['attendance_status'] == 'excused' ? 'selected' : ''; ?>>Excused</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance[<?php echo $student['id']; ?>][remarks]" class="form-control" value="<?php echo htmlspecialchars($student['attendance_remarks'] ?? ''); ?>" placeholder="Remarks">
                                        <input type="hidden" name="attendance[<?php echo $student['id']; ?>][student_id]" value="<?php echo $student['id']; ?>">
                                        <input type="hidden" name="attendance[<?php echo $student['id']; ?>][date]" value="<?php echo htmlspecialchars($selected_date); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <?php elseif ($selected_class): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h3>No Students Found</h3>
                <p class="text-muted">No students found in the selected class/stream.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-filter fa-4x text-muted mb-3"></i>
                <h3>Select a Class</h3>
                <p class="text-muted">Please select a class to view and mark attendance.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Past Attendance Records -->
        <?php if (!empty($past_attendance)): ?>
        <div class="card">
            <h2 class="card-title">Past Attendance Records</h2>
            <div class="card-body">
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
                            <?php foreach ($past_attendance as $record): ?>
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
                <p class="text-muted mt-2">Showing last 100 records. Use date filters to narrow down results.</p>
            </div>
        </div>
        <?php elseif ($selected_class): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                <h3>No Past Attendance Records</h3>
                <p class="text-muted">No attendance records found for the selected filters.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store all streams data for dynamic filtering
        const allStreamsData = <?php echo json_encode($all_streams); ?>;
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
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

        function saveAttendance() {
            const form = document.getElementById('attendanceForm');
            const formData = new FormData(form);
            const attendanceData = [];
            
            for (let [key, value] of formData.entries()) {
                const match = key.match(/attendance\[(\d+)\]\[(.+)\]/);
                if (match) {
                    const studentId = match[1];
                    const field = match[2];
                    let record = attendanceData.find(r => r.student_id === studentId);
                    if (!record) {
                        record = { student_id: studentId };
                        attendanceData.push(record);
                    }
                    record[field] = value;
                }
            }
            
            // Filter out records without status
            attendanceData = attendanceData.filter(r => r.status && r.status !== '');
            
            if (attendanceData.length === 0) {
                alert('Please mark attendance for at least one student.');
                return;
            }
            
            fetch('api/attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ attendance: attendanceData })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance saved successfully! ' + data.count + ' records updated.');
                } else {
                    alert('Error saving attendance: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving attendance. Please try again.');
            });
        }

        // Initialize streams on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStreams();
        });
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
