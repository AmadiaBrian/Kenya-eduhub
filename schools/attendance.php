<?php
// Attendance Management Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

$school_name = $_SESSION['school_name'] ?? 'School';
$school_id = $_SESSION['school_id'];

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

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

// Get selected filters
$selected_class = $_GET['class_id'] ?? '';
$selected_stream = $_GET['stream_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

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
    <meta name="theme-color" content="#FF6B35">
    <title>Attendance - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #F0F4F9;
            --card-bg: #F0F4F9;
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

        .material-symbols-outlined {
            vertical-align: middle;
            font-size: 20px;
        }

        .main-content {
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 60px;
            text-align: center;
        }

        .card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 25px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 25px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            background: var(--bg-color);
        }

        .btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            width: auto;
        }

        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 0;
                width: 100%;
            }

            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 24px;
                padding: 16px;
            }

            .card {
                padding: 16px;
                margin: 0 16px 16px 16px;
            }

            .card[style*="padding: 0"] {
                padding: 0 !important;
                margin: 0 12px 16px 12px !important;
            }

            .btn {
                width: 100%;
            }

            .card-header {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .card-header .d-flex {
                flex-direction: column !important;
                width: 100% !important;
            }

            .table-responsive {
                margin: 0;
                padding: 0;
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
                width: 100%;
            }

            .d-flex.flex-column {
                gap: 8px;
                padding: 16px;
                margin-bottom: 16px;
            }

            .table td {
                padding: 4px 6px;
                font-size: 11px;
                line-height: 1.2;
                vertical-align: middle;
            }

            .table thead th {
                padding: 4px 6px;
                font-size: 11px;
                line-height: 1.2;
                vertical-align: middle;
            }

            .table tbody tr {
                line-height: 1.2;
            }

            .quick-access-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            .quick-access-item {
                padding: 12px !important;
            }

            #attendanceStats {
                margin: 0 0 24px 0 !important;
                padding: 16px !important;
            }

            .card[style*="padding: 24px"] {
                padding: 16px !important;
                margin: 0 4px 16px 4px !important;
            }

            .card[style*="padding: 0"] {
                margin: 0 0 16px 0 !important;
                overflow: hidden;
            }

            .card[style*="padding: 0"] .table-responsive {
                padding: 0 !important;
                margin: 0 !important;
            }

            .filter-section[style*="padding: 24px"] {
                padding: 16px !important;
                margin: 0 4px 16px 4px !important;
            }

            .action-buttons[style*="padding: 24px"] {
                padding: 16px !important;
                margin: 0 4px 16px 4px !important;
            }
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-color);
            border-radius: 25px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e8eaed;
        }

        .filter-section .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
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
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table thead th {
            background: #f0f0f0;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: bold;
            color: #000;
        }

        .table td {
            padding: 12px;
            border: 2px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
            display: block;
        }

        .btn-action {
            background: transparent;
            color: #FF6B35;
            border: none;
            cursor: pointer;
            margin-right: 4px;
            padding: 0;
        }

        .btn-action:hover {
            color: #e55a2b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 4px;
        }

        @media (max-width: 768px) {
            .main-content {
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
                margin: 0 -16px;
                padding: 0 16px;
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
                width: 100%;
            }

            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 25px;
                border: 1px solid #dadce0;
                margin-bottom: 12px;
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
                border-bottom: 1px solid #e0e0e0;
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
            border-radius: 25px;
            border: 1px solid #e8eaed;
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

        /* Icon colors */
        .quick-access-item:nth-child(1) .quick-access-icon { background: #e8f0fe; color: #4285F4; }
        .quick-access-item:nth-child(2) .quick-access-icon { background: #fce8e6; color: #EA4335; }
        .quick-access-item:nth-child(3) .quick-access-icon { background: #e6f4ea; color: #00AC47; }
        .quick-access-item:nth-child(4) .quick-access-icon { background: #fef7e0; color: #FFBA00; }
        .quick-access-item:nth-child(5) .quick-access-icon { background: #e8f0fe; color: #0066DA; }
        .quick-access-item:nth-child(6) .quick-access-icon { background: #fce8e6; color: #4285F4; }
        .fa-plus { color: #00AC47; }
        .fa-download { color: #4285F4; }
        .fa-edit { color: #0066DA; }
        .fa-trash { color: #EA4335; }
    </style>
</head>
<body class="dashboard-page">
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'attendance'; require_once 'includes/sidebar.php'; ?>
    
    <br>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Attendance Management</h1>
        
        <!-- Calendar Status Alert -->
        <?php if ($calendar_status['is_holiday']): ?>
            <div class="card" style="background: rgba(255, 107, 53, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px rgba(255, 107, 53, 0.3); padding: 40px; min-height: 150px;">
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="color: white; font-size: 32px;">beach_access</span>
                    <div>
                        <p style="color: white; font-weight: 600; margin: 0; font-size: 16px;">
                            School is on Holiday
                        </p>
                        <p style="color: white; margin: 6px 0 0 0; font-size: 14px;">
                            <?php echo htmlspecialchars($calendar_status['current_holiday']['holiday_name']); ?>
                        </p>
                        <p style="color: white; margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">
                            <?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['start_date'])); ?> -
                            <?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['end_date'])); ?>
                        </p>
                    </div>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <p style="color: white; margin: 0; font-size: 13px;">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">calendar_month</span>
                        Academic Year: <?php echo $calendar_status['current_year']; ?>
                    </p>
                    <p style="color: white; margin: 8px 0 0 0; font-size: 13px;">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">schedule</span>
                        Today: <?php echo date('F j, Y'); ?>
                    </p>
                </div>
            </div>
        <?php elseif ($calendar_status['school_status'] === 'break'): ?>
            <div class="card" style="background: rgba(255, 107, 53, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px rgba(255, 107, 53, 0.3); padding: 40px; min-height: 150px;">
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="color: white; font-size: 32px;">pause_circle</span>
                    <div>
                        <p style="color: white; font-weight: 600; margin: 0; font-size: 16px;">
                            School is on Break
                        </p>
                        <p style="color: white; margin: 6px 0 0 0; font-size: 14px;">No active term is currently set. Please activate a term in the Calendar.</p>
                    </div>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <p style="color: white; margin: 0; font-size: 13px;">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">calendar_month</span>
                        Academic Year: <?php echo $calendar_status['current_year']; ?>
                    </p>
                    <p style="color: white; margin: 8px 0 0 0; font-size: 13px;">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">schedule</span>
                        Today: <?php echo date('F j, Y'); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Attendance Statistics -->
        <div id="attendanceStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin: 0 0 24px 0; padding: 24px;">
            <?php
            $totalRecords = 0;
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $excusedCount = 0;
            
            try {
                $stmt = $pdo->prepare("SELECT a.status FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.school_id = ?");
                $stmt->execute([$school_id]);
                $records = $stmt->fetchAll();
                
                foreach ($records as $record) {
                    $totalRecords++;
                    if ($record['status'] === 'present') $presentCount++;
                    if ($record['status'] === 'absent') $absentCount++;
                    if ($record['status'] === 'late') $lateCount++;
                    if ($record['status'] === 'excused') $excusedCount++;
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch attendance stats: " . $e->getMessage());
            }
            ?>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalRecords; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Records</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $presentCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Present</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $absentCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Absent</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $lateCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Late</div>
            </div>
            <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #5f6368;"><?php echo $excusedCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Excused</div>
            </div>
        </div>
        
        <!-- Quick Access -->
        <div class="card" style="margin: 0 0 24px 0; padding: 24px;">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="dashboard.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <span class="material-symbols-outlined" style="color: #5f6368;">dashboard</span>
                    </div>
                    <div class="quick-access-label">Dashboard</div>
                </a>
                <a href="students.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <span class="material-symbols-outlined" style="color: #5f6368;">school</span>
                    </div>
                    <div class="quick-access-label">Students</div>
                </a>
                <a href="teachers.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <span class="material-symbols-outlined" style="color: #5f6368;">person_apron</span>
                    </div>
                    <div class="quick-access-label">Teachers</div>
                </a>
                <a href="performance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <span class="material-symbols-outlined" style="color: #5f6368;">show_chart</span>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="fees.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <span class="material-symbols-outlined" style="color: #5f6368;">payments</span>
                    </div>
                    <div class="quick-access-label">Fees</div>
                </a>
                <a href="classes.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <span class="material-symbols-outlined" style="color: #5f6368;">class</span>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="filter-section" style="margin: 0 0 24px 0; padding: 24px;">
            <div class="card-title">Filter Students</div>
            <form method="GET" action="" class="form-grid">
                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id" id="classSelect" class="form-control" required onchange="updateStreams(); this.form.submit()">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stream</label>
                    <select name="stream_id" id="streamSelect" class="form-control" onchange="this.form.submit()">
                        <option value="">All Streams</option>
                        <?php foreach ($streams as $stream): ?>
                            <option value="<?php echo $stream['id']; ?>" <?php echo $selected_stream == $stream['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stream['stream_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="this.form.submit()">
                </div>
                <div class="form-group">
                    <label>From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from ?? ''); ?>" onchange="this.form.submit()">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to ?? ''); ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <!-- Action Buttons -->
        <?php if ($selected_class && !empty($students)): ?>
        <div class="action-buttons" style="margin: 0 0 24px 0; padding: 24px;">
            <button class="btn btn-primary" onclick="saveAttendance()">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">save</span> Save Attendance
            </button>
            <button class="btn btn-success" onclick="exportAttendance()">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">download</span> Export
            </button>
        </div>
        <?php endif; ?>

        <!-- Attendance Table -->
        <?php if ($selected_class && !empty($students)): ?>
        <div class="card" style="padding: 24px; margin: 0 0 24px 0;">
            <div class="card-title">Mark Attendance - <?php echo htmlspecialchars($selected_date); ?></div>
            <div class="table-responsive">
                <form id="attendanceForm">
                    <table class="table" style="min-width: 800px;">
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
                                    <td><?php echo htmlspecialchars($student['first_name']); ?> <?php echo htmlspecialchars($student['last_name']); ?></td>
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
        <?php elseif ($selected_class): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <span class="material-symbols-outlined fa-4x text-muted mb-3" style="font-size: 4rem;">groups</span>
                <h3>No Students Found</h3>
                <p class="text-muted">No students found in the selected class/stream.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <span class="material-symbols-outlined fa-4x text-muted mb-3" style="font-size: 4rem;">filter_list</span>
                <h3>Select a Class</h3>
                <p class="text-muted">Please select a class to view and mark attendance.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Past Attendance Records -->
        <?php if (!empty($past_attendance)): ?>
        <div class="card" style="padding: 0; margin: 0 0 24px 0;">
            <div class="card-title" style="padding: 16px;">Past Attendance Records</div>
            <div class="table-responsive" style="padding: 0; width: 100%; overflow-x: auto;">
                <table class="table" style="width: 100%; margin: 0;">
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
                                <td><?php echo htmlspecialchars($record['first_name']); ?> <?php echo htmlspecialchars($record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['stream_name'] ?? '-'); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($record['status'])); ?></td>
                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
            <p class="text-muted mt-2" style="padding: 0 16px 16px 16px;">Showing last 100 records. Use date filters to narrow down results.</p>
        </div>
        <?php elseif ($selected_class): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <span class="material-symbols-outlined fa-4x text-muted mb-3" style="font-size: 4rem;">history</span>
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(attendanceData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance saved successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to save attendance');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
        
        // Export attendance to CSV
        function exportAttendance() {
            const rows = document.querySelectorAll('#attendanceForm tbody tr');
            let csvContent = 'Adm No,Student Name,Class,Stream,Status,Remarks\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const rowData = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent,
                    cells[3].textContent,
                    cells[4].querySelector('select')?.value || '',
                    cells[5].querySelector('input')?.value || ''
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
            link.setAttribute('download', `attendance_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
    
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
