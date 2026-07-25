<?php
// Teacher Attendance Management
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$class_name = $_SESSION['class_name'] ?? '';

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get teacher details and subject assignments
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo htmlspecialchars($teacher_name); ?></title>
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
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: none;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: none;
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
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
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
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
        }
        
        .btn-info {
            background: #0288d1;
            color: white;
        }
        
        .btn-info:hover {
            background: #01579b;
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
        
        .attendance-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #dadce0;
            background: white;
            color: #5f6368;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .attendance-btn:hover {
            background: #f1f3f4;
        }
        
        .attendance-btn.active {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
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
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }
            
            .table {
                min-width: 100%;
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
            <a class="nav-link active" href="attendance">
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
        <h1 class="page-title">Attendance Management</h1>
        
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
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Take Attendance</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <?php if ($teacher && $teacher['teacher_type'] === 'class_teacher'): ?>
                            <input type="text" class="form-control" id="classDisplay" value="<?php echo htmlspecialchars($class_name); ?>" readonly>
                            <input type="hidden" id="classId" value="<?php echo $class_id; ?>">
                        <?php else: ?>
                            <select class="form-control" id="classId">
                                <option value="">Select Class</option>
                                <?php foreach ($subject_assignments as $assignment): ?>
                                    <option value="<?php echo $assignment['class_id']; ?>"><?php echo htmlspecialchars($assignment['class_name']); ?> (<?php echo htmlspecialchars($assignment['subject']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="loadStudentsForAttendance()">
                            <i class="fas fa-search me-2"></i> Load Students
                        </button>
                    </div>
                </div>
                
                <div id="attendanceSection" style="display: none;">
                    <!-- Attendance Statistics -->
                    <div id="attendanceStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px;">
                        <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #137333;" id="presentCount">0</div>
                            <div style="font-size: 12px; color: #5f6368;">Present</div>
                        </div>
                        <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #c5221f;" id="absentCount">0</div>
                            <div style="font-size: 12px; color: #5f6368;">Absent</div>
                        </div>
                        <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #b06000;" id="lateCount">0</div>
                            <div style="font-size: 12px; color: #5f6368;">Late</div>
                        </div>
                        <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #1967d2;" id="excusedCount">0</div>
                            <div style="font-size: 12px; color: #5f6368;">Excused</div>
                        </div>
                        <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #5f6368;" id="attendancePercentage">0%</div>
                            <div style="font-size: 12px; color: #5f6368;">Attendance Rate</div>
                        </div>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                        <button class="btn btn-sm btn-success" onclick="markAllAs('present')" style="border-radius: 25px;">
                            <i class="fas fa-check-circle me-1"></i> Mark All Present
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="markAllAs('absent')" style="border-radius: 25px;">
                            <i class="fas fa-times-circle me-1"></i> Mark All Absent
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="markAllAs('late')" style="border-radius: 25px;">
                            <i class="fas fa-clock me-1"></i> Mark All Late
                        </button>
                        <button class="btn btn-sm btn-info" onclick="markAllAs('excused')" style="border-radius: 25px;">
                            <i class="fas fa-info-circle me-1"></i> Mark All Excused
                        </button>
                        <button class="btn btn-sm" onclick="clearAllStatus()" style="background: #f1f3f4; color: #5f6368; border-radius: 25px;">
                            <i class="fas fa-undo me-1"></i> Clear All
                        </button>
                    </div>
                    
                    <!-- Student Search -->
                    <div style="margin-bottom: 16px;">
                        <input type="text" id="studentSearch" class="form-control" placeholder="Search by admission number or student name..." oninput="filterStudents()" style="padding: 10px 12px;">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTable">
                                <tr><td colspan="4" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success" onclick="saveAttendance()" style="border-radius: 25px;">
                            <i class="fas fa-save me-2"></i> Save Attendance
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Attendance History</span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <select id="dateRange" class="form-control" style="width: auto; padding: 8px 12px; font-size: 13px;" onchange="loadAttendanceHistory()">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                    <div id="customDateRange" style="display: none; gap: 8px;">
                        <input type="date" id="startDate" class="form-control" style="width: auto; padding: 8px 12px; font-size: 13px;">
                        <input type="date" id="endDate" class="form-control" style="width: auto; padding: 8px 12px; font-size: 13px;">
                        <button class="btn btn-sm btn-primary" onclick="loadAttendanceHistory()" style="border-radius: 25px;">Apply</button>
                    </div>
                    <button class="btn btn-sm btn-info" onclick="exportAttendance()" style="border-radius: 25px;">
                        <i class="fas fa-download me-1"></i> Export CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="historySection" style="display: none;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="historyTable">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="noHistoryMessage">
                    <p class="text-muted">No attendance records found. Load students and take attendance to see history.</p>
                </div>
            </div>
        </div>
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
        
        // Load students for attendance
        async function loadStudentsForAttendance() {
            const date = document.getElementById('attendanceDate').value;
            const classId = document.getElementById('classId').value;
            
            if (!date || !classId) {
                alert('Please select date');
                return;
            }
            
            try {
                const response = await fetch(`../schools/api/students.php?class_id=${classId}`);
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('attendanceTable');
                    tbody.innerHTML = data.data.map(student => `
                        <tr data-student-id="${student.id}" data-admission-number="${student.admission_number.toLowerCase()}" data-student-name="${(student.first_name + ' ' + student.last_name).toLowerCase()}">
                            <td>${student.admission_number}</td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success attendance-btn" onclick="setStatus(this, 'present')">Present</button>
                                    <button type="button" class="btn btn-sm btn-danger attendance-btn" onclick="setStatus(this, 'absent')">Absent</button>
                                    <button type="button" class="btn btn-sm btn-warning attendance-btn" onclick="setStatus(this, 'late')">Late</button>
                                    <button type="button" class="btn btn-sm btn-info attendance-btn" onclick="setStatus(this, 'excused')">Excused</button>
                                </div>
                                <input type="hidden" class="attendance-status" value="">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" placeholder="Optional remarks">
                            </td>
                        </tr>
                    `).join('');
                    document.getElementById('attendanceSection').style.display = 'block';
                    
                    // Load existing attendance history for this date
                    loadAttendanceHistory();
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }
        
        function setStatus(btn, status) {
            const row = btn.closest('tr');
            const buttons = row.querySelectorAll('.attendance-btn');
            buttons.forEach(b => b.classList.remove('active', 'btn-dark'));
            btn.classList.add('active', 'btn-dark');
            row.querySelector('.attendance-status').value = status;
            updateAttendanceStats();
        }
        
        function markAllAs(status) {
            const rows = document.querySelectorAll('#attendanceTable tr[data-student-id]');
            rows.forEach(row => {
                const statusBtn = row.querySelector(`.attendance-btn[onclick*="${status}"]`);
                if (statusBtn) {
                    setStatus(statusBtn, status);
                }
            });
        }
        
        function clearAllStatus() {
            const rows = document.querySelectorAll('#attendanceTable tr[data-student-id]');
            rows.forEach(row => {
                const buttons = row.querySelectorAll('.attendance-btn');
                buttons.forEach(b => b.classList.remove('active', 'btn-dark'));
                row.querySelector('.attendance-status').value = '';
            });
            updateAttendanceStats();
        }
        
        function updateAttendanceStats() {
            const rows = document.querySelectorAll('#attendanceTable tr[data-student-id]');
            let present = 0, absent = 0, late = 0, excused = 0;
            
            rows.forEach(row => {
                const status = row.querySelector('.attendance-status').value;
                if (status === 'present') present++;
                else if (status === 'absent') absent++;
                else if (status === 'late') late++;
                else if (status === 'excused') excused++;
            });
            
            const total = present + absent + late + excused;
            const percentage = total > 0 ? Math.round((present / total) * 100) : 0;
            
            document.getElementById('presentCount').textContent = present;
            document.getElementById('absentCount').textContent = absent;
            document.getElementById('lateCount').textContent = late;
            document.getElementById('excusedCount').textContent = excused;
            document.getElementById('attendancePercentage').textContent = percentage + '%';
        }
        
        function filterStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#attendanceTable tr[data-student-id]');
            
            rows.forEach(row => {
                const admissionNumber = row.dataset.admissionNumber;
                const studentName = row.dataset.studentName;
                
                const matchesSearch = admissionNumber.includes(searchTerm) || studentName.includes(searchTerm);
                
                if (matchesSearch || searchTerm === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        async function saveAttendance() {
            const date = document.getElementById('attendanceDate').value;
            const rows = document.querySelectorAll('#attendanceTable tr[data-student-id]');
            const attendanceData = [];
            
            rows.forEach(row => {
                const studentId = row.dataset.studentId;
                const status = row.querySelector('.attendance-status').value;
                const remarks = row.querySelector('input[type="text"]').value;
                
                if (status) {
                    attendanceData.push({
                        student_id: studentId,
                        date: date,
                        status: status,
                        remarks: remarks
                    });
                }
            });
            
            console.log('Saving attendance data:', attendanceData);
            
            if (attendanceData.length === 0) {
                alert('Please mark attendance for at least one student');
                return;
            }
            
            try {
                const response = await fetch('../schools/api/attendance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ attendance: attendanceData })
                });
                const data = await response.json();
                console.log('Save attendance response:', data);
                
                if (data.success) {
                    alert('Attendance saved successfully! Records saved: ' + (data.count || attendanceData.length));
                    document.getElementById('attendanceSection').style.display = 'none';
                    // Clear the attendance table
                    document.getElementById('attendanceTable').innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';
                    // Load attendance history
                    await loadAttendanceHistory();
                } else {
                    alert('Failed to save attendance: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving attendance:', error);
                alert('An error occurred while saving attendance');
            }
        }
        
        async function loadAttendanceHistory() {
            const classId = document.getElementById('classId').value;
            const dateRange = document.getElementById('dateRange').value;
            const customDateRange = document.getElementById('customDateRange');
            
            // Show/hide custom date range inputs
            customDateRange.style.display = dateRange === 'custom' ? 'flex' : 'none';
            
            if (!classId) {
                return;
            }
            
            let startDate, endDate;
            const today = new Date();
            
            switch(dateRange) {
                case 'today':
                    startDate = endDate = today.toISOString().split('T')[0];
                    break;
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    startDate = weekStart.toISOString().split('T')[0];
                    endDate = today.toISOString().split('T')[0];
                    break;
                case 'month':
                    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    startDate = monthStart.toISOString().split('T')[0];
                    endDate = today.toISOString().split('T')[0];
                    break;
                case 'custom':
                    startDate = document.getElementById('startDate').value;
                    endDate = document.getElementById('endDate').value;
                    if (!startDate || !endDate) return;
                    break;
                default:
                    startDate = endDate = document.getElementById('attendanceDate').value;
            }
            
            try {
                const response = await fetch(`../schools/api/attendance.php?start_date=${startDate}&end_date=${endDate}&class_id=${classId}`);
                const data = await response.json();
                console.log('Attendance history response:', data);
                
                if (data.success && data.data.length > 0) {
                    const tbody = document.getElementById('historyTable');
                    tbody.innerHTML = data.data.map(record => `
                        <tr>
                            <td>${record.date}</td>
                            <td>${record.admission_number}</td>
                            <td>${record.first_name} ${record.last_name}</td>
                            <td><span class="badge bg-${getStatusBadgeClass(record.status)}">${record.status}</span></td>
                            <td>${record.remarks || '-'}</td>
                        </tr>
                    `).join('');
                    document.getElementById('historySection').style.display = 'block';
                    document.getElementById('noHistoryMessage').style.display = 'none';
                } else {
                    document.getElementById('historySection').style.display = 'none';
                    document.getElementById('noHistoryMessage').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading attendance history:', error);
            }
        }
        
        function getStatusBadgeClass(status) {
            switch(status.toLowerCase()) {
                case 'present': return 'success';
                case 'absent': return 'danger';
                case 'late': return 'warning';
                case 'excused': return 'info';
                default: return 'secondary';
            }
        }
        
        function exportAttendance() {
            const historySection = document.getElementById('historySection');
            if (historySection.style.display === 'none') {
                alert('No attendance data to export. Please load attendance history first.');
                return;
            }
            
            const table = document.getElementById('historyTable');
            const rows = table.querySelectorAll('tr');
            
            if (rows.length === 0) {
                alert('No attendance data to export.');
                return;
            }
            
            // Create CSV content
            let csvContent = 'Date,Admission No,Student Name,Status,Remarks\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const rowData = Array.from(cells).map(cell => {
                        // Get text content and escape commas
                        let text = cell.textContent.trim();
                        text = text.replace(/"/g, '""'); // Escape quotes
                        if (text.includes(',') || text.includes('"')) {
                            text = `"${text}"`;
                        }
                        return text;
                    });
                    csvContent += rowData.join(',') + '\n';
                }
            });
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            const dateRange = document.getElementById('dateRange').value;
            const timestamp = new Date().toISOString().split('T')[0];
            link.setAttribute('href', url);
            link.setAttribute('download', `attendance_${dateRange}_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Load attendance history on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAttendanceHistory();
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
