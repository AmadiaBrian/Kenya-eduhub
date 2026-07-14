<?php
// Teacher Attendance Management
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$class_name = $_SESSION['class_name'] ?? '';

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
            <a class="nav-link active" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
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
                        <button class="btn btn-success" onclick="saveAttendance()">
                            <i class="fas fa-save me-2"></i> Save Attendance
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span>Attendance History for Selected Date</span>
            </div>
            <div class="card-body">
                <div id="historySection" style="display: none;">
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
                            <tbody id="historyTable">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="noHistoryMessage">
                    <p class="text-muted">No attendance records found for the selected date. Load students and take attendance to see history.</p>
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
                        <tr data-student-id="${student.id}">
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
            const date = document.getElementById('attendanceDate').value;
            const classId = document.getElementById('classId').value;
            
            if (!date || !classId) {
                return;
            }
            
            try {
                const response = await fetch(`../schools/api/attendance.php?date=${date}&class_id=${classId}`);
                const data = await response.json();
                console.log('Attendance history response:', data);
                
                if (data.success && data.data.length > 0) {
                    const tbody = document.getElementById('historyTable');
                    tbody.innerHTML = data.data.map(record => `
                        <tr>
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
