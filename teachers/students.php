<?php
// Teacher Student Affairs
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
    <meta name="theme-color" content="#FF6B35">
    <title>Students - <?php echo htmlspecialchars($teacher_name); ?></title>
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
        
        .btn-info {
            background: #0288d1;
            color: white;
        }
        
        .btn-info:hover {
            background: #01579b;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
        }
        
        .btn-action:hover {
            background: #e9ecef;
        }
        
        .btn-action i {
            color: #000;
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
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-award"></i> Results
            </a>
            <a class="nav-link active" href="students">
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
        <h1 class="page-title">Student Affairs</h1>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Filter Students</span>
                <button class="btn btn-sm btn-info" onclick="exportStudents()" style="border-radius: 25px;">
                    <i class="fas fa-download me-1"></i> Export CSV
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchStudents" placeholder="Name or admission no" oninput="filterStudents()" style="border-radius: 25px;">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Class</label>
                        <?php if ($teacher && $teacher['teacher_type'] === 'class_teacher'): ?>
                            <input type="text" class="form-control" id="filterClassDisplay" value="<?php echo htmlspecialchars($class_name); ?>" readonly style="border-radius: 25px;">
                            <input type="hidden" id="filterClassId" value="<?php echo $class_id; ?>">
                        <?php else: ?>
                            <select class="form-control" id="filterClassId" onchange="filterStudents()" style="border-radius: 25px;">
                                <option value="">All Classes</option>
                                <?php foreach ($subject_assignments as $assignment): ?>
                                    <option value="<?php echo $assignment['class_id']; ?>"><?php echo htmlspecialchars($assignment['class_name']); ?> (<?php echo htmlspecialchars($assignment['subject']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stream</label>
                        <select class="form-control" id="filterStream" onchange="filterStudents()" style="border-radius: 25px;">
                            <option value="">All Streams</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gender</label>
                        <select class="form-control" id="filterGender" onchange="filterStudents()" style="border-radius: 25px;">
                            <option value="">All</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="filterStatus" onchange="filterStudents()" style="border-radius: 25px;">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-secondary w-100" onclick="resetFilters()" style="border-radius: 25px;">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Student Statistics -->
        <div id="studentStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;" id="totalStudents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Total Students</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;" id="activeStudents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Active</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;" id="inactiveStudents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Inactive</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;" id="totalBalance">KES 0</div>
                <div style="font-size: 12px; color: #5f6368;">Total Balance</div>
            </div>
            <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #5f6368;" id="maleStudents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Male</div>
            </div>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;" id="femaleStudents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Female</div>
            </div>
        </div>
        
        <div id="feeTypeTables">
            <div class="card">
                <div class="card-header">
                    <span>Loading...</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr><td colspan="8" class="text-center">Loading students...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Disciplinary Records Section -->
        <div class="card" id="disciplinarySection" style="margin-top: 24px;">
            <div class="card-header">
                <span id="disciplinaryTitle">Disciplinary Records - Class</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="disciplinaryTable">
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Date</th>
                                <th>Action Type</th>
                                <th>Severity</th>
                                <th>Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="disciplinaryTableBody">
                            <tr><td colspan="7" class="text-center">Loading disciplinary records...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Student Details Modal - Google Material Design Style -->
    <div class="modal fade" id="studentDetailsModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <div id="studentDetailsContent">
                        <!-- Student details will be loaded here -->
                    </div>
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
        
        let allStudents = [];
        
        // Load students
        async function loadStudents() {
            const classId = document.getElementById('filterClassId').value;
            const status = document.getElementById('filterStatus').value;
            
            let url = '../schools/api/students.php';
            const params = [];
            if (classId) params.push(`class_id=${classId}`);
            if (status) params.push(`status=${status}`);
            if (params.length) url += '?' + params.join('&');
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    allStudents = data.data;
                    populateStreamFilter(allStudents);
                    updateStudentStats(allStudents);
                    filterStudents();
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }
        
        function populateStreamFilter(students) {
            const streams = [...new Set(students.map(s => s.stream_name).filter(s => s))];
            const streamSelect = document.getElementById('filterStream');
            streamSelect.innerHTML = '<option value="">All Streams</option>';
            streams.forEach(streamName => {
                streamSelect.innerHTML += `<option value="${streamName}">${streamName}</option>`;
            });
        }
        
        function filterStudents() {
            const searchTerm = document.getElementById('searchStudents').value.toLowerCase();
            const classId = document.getElementById('filterClassId').value;
            const stream = document.getElementById('filterStream').value;
            const gender = document.getElementById('filterGender').value;
            const status = document.getElementById('filterStatus').value;
            
            const filtered = allStudents.filter(student => {
                const matchesSearch = !searchTerm || 
                    student.admission_number.toLowerCase().includes(searchTerm) ||
                    `${student.first_name} ${student.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesClass = !classId || student.class_id == classId;
                const matchesStream = !stream || student.stream_name === stream;
                const matchesGender = !gender || student.gender === gender;
                const matchesStatus = !status || student.status === status;
                
                return matchesSearch && matchesClass && matchesStream && matchesGender && matchesStatus;
            });
            
            updateStudentStats(filtered);
            renderStudents(filtered);
        }
        
        function renderStudents(students) {
            const container = document.getElementById('feeTypeTables');
            
            // Group students by fee types present in their fee_balances
            const feeTypeGroups = {};
            students.forEach(student => {
                if (student.fee_balances) {
                    Object.keys(student.fee_balances).forEach(feeType => {
                        if (!feeTypeGroups[feeType]) {
                            feeTypeGroups[feeType] = [];
                        }
                        feeTypeGroups[feeType].push(student);
                    });
                }
            });
            
            // If no fee types found, show message
            if (Object.keys(feeTypeGroups).length === 0) {
                container.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <span>No Fee Data</span>
                        </div>
                        <div class="card-body">
                            <p class="text-center text-muted">No fee data available for these students.</p>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Generate a table for each fee type
            let html = '';
            Object.keys(feeTypeGroups).sort().forEach(feeType => {
                const students = feeTypeGroups[feeType];
                html += `
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <span>${feeType} Fee Balances</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Admission No</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Class</th>
                                            <th>Stream</th>
                                            <th>Status</th>
                                            <th>Total Fees</th>
                                            <th>Total Paid</th>
                                            <th>Balance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${students.map(student => {
                                            const feeData = student.fee_balances[feeType] || { total_fees: 0, total_paid: 0, balance: 0 };
                                            const balance = feeData.balance;
                                            const balanceClass = balance > 0 ? 'text-danger' : (balance < 0 ? 'text-success' : 'text-muted');
                                            const balanceText = balance > 0 ? `KES ${balance.toFixed(2)} (Due)` : (balance < 0 ? `KES ${Math.abs(balance).toFixed(2)} (Overpaid)` : 'KES 0.00 (Paid)');
                                            
                                            return `
                                                <tr>
                                                    <td>${student.admission_number}</td>
                                                    <td>${student.first_name} ${student.last_name}</td>
                                                    <td>${student.gender}</td>
                                                    <td>${student.class_name || '-'}</td>
                                                    <td>${student.stream_name || '-'}</td>
                                                    <td>
                                                        <span class="badge ${student.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                                            ${student.status}
                                                        </span>
                                                    </td>
                                                    <td>KES ${feeData.total_fees.toFixed(2)}</td>
                                                    <td>KES ${feeData.total_paid.toFixed(2)}</td>
                                                    <td class="${balanceClass}">
                                                        <strong>${balanceText}</strong>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-action" onclick="viewStudentDetails(${student.id})" style="border-radius: 25px;">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        function updateStudentStats(students) {
            const total = students.length;
            const active = students.filter(s => s.status === 'active').length;
            const inactive = students.filter(s => s.status === 'inactive').length;
            const male = students.filter(s => s.gender === 'Male').length;
            const female = students.filter(s => s.gender === 'Female').length;
            
            let totalBalance = 0;
            students.forEach(student => {
                if (student.fee_balances) {
                    Object.values(student.fee_balances).forEach(feeData => {
                        totalBalance += feeData.balance || 0;
                    });
                }
            });
            
            document.getElementById('totalStudents').textContent = total;
            document.getElementById('activeStudents').textContent = active;
            document.getElementById('inactiveStudents').textContent = inactive;
            document.getElementById('maleStudents').textContent = male;
            document.getElementById('femaleStudents').textContent = female;
            document.getElementById('totalBalance').textContent = `KES ${totalBalance.toFixed(2)}`;
        }
        
        function resetFilters() {
            document.getElementById('searchStudents').value = '';
            document.getElementById('filterStream').value = '';
            document.getElementById('filterGender').value = '';
            document.getElementById('filterStatus').value = '';
            filterStudents();
        }
        
        function exportStudents() {
            if (allStudents.length === 0) {
                alert('No student data to export.');
                return;
            }
            
            // Get current filtered students
            const searchTerm = document.getElementById('searchStudents').value.toLowerCase();
            const classId = document.getElementById('filterClassId').value;
            const stream = document.getElementById('filterStream').value;
            const gender = document.getElementById('filterGender').value;
            const status = document.getElementById('filterStatus').value;
            
            const filtered = allStudents.filter(student => {
                const matchesSearch = !searchTerm || 
                    student.admission_number.toLowerCase().includes(searchTerm) ||
                    `${student.first_name} ${student.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesClass = !classId || student.class_id == classId;
                const matchesStream = !stream || student.stream_name === stream;
                const matchesGender = !gender || student.gender === gender;
                const matchesStatus = !status || student.status === status;
                
                return matchesSearch && matchesClass && matchesStream && matchesGender && matchesStatus;
            });
            
            if (filtered.length === 0) {
                alert('No matching students to export.');
                return;
            }
            
            // Create CSV content
            let csvContent = 'Admission No,Name,Gender,Class,Stream,Status,Total Fees,Total Paid,Balance\n';
            
            filtered.forEach(student => {
                let totalFees = 0, totalPaid = 0, balance = 0;
                if (student.fee_balances) {
                    Object.values(student.fee_balances).forEach(feeData => {
                        totalFees += feeData.total_fees || 0;
                        totalPaid += feeData.total_paid || 0;
                        balance += feeData.balance || 0;
                    });
                }
                
                const rowData = [
                    student.admission_number,
                    `${student.first_name} ${student.last_name}`,
                    student.gender,
                    student.class_name || '-',
                    student.stream_name || '-',
                    student.status,
                    totalFees.toFixed(2),
                    totalPaid.toFixed(2),
                    balance.toFixed(2)
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
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            const timestamp = new Date().toISOString().split('T')[0];
            link.setAttribute('href', url);
            link.setAttribute('download', `students_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // View student details
        async function viewStudentDetails(studentId) {
            try {
                const response = await fetch('../schools/api/students.php');
                const data = await response.json();
                if (data.success) {
                    const student = data.data.find(s => s.id === studentId);
                    if (student) {
                        const content = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Admission Number:</strong> ${student.admission_number}</p>
                                    <p><strong>Name:</strong> ${student.first_name} ${student.last_name}</p>
                                    <p><strong>Gender:</strong> ${student.gender}</p>
                                    <p><strong>Date of Birth:</strong> ${student.date_of_birth}</p>
                                    <p><strong>Admission Date:</strong> ${student.admission_date}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Class:</strong> ${student.class_name || 'Not assigned'}</p>
                                    <p><strong>Stream:</strong> ${student.stream_name || 'Not assigned'}</p>
                                    <p><strong>Status:</strong> ${student.status}</p>
                                </div>
                            </div>
                        `;
                        document.getElementById('studentDetailsContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('studentDetailsModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading student details:', error);
            }
        }
        
        // Load disciplinary records for the class
        async function loadDisciplinaryRecords() {
            const disciplinaryTableBody = document.getElementById('disciplinaryTableBody');
            disciplinaryTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading disciplinary records...</td></tr>';
            
            try {
                const response = await fetch('../schools/api/disciplinary.php?type=records');
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const records = data.data;
                    let html = '';
                    
                    records.forEach(record => {
                        const severityClass = record.severity === 'critical' ? 'bg-danger' : 
                                             record.severity === 'severe' ? 'bg-warning' : 
                                             record.severity === 'moderate' ? 'bg-info' : 'bg-secondary';
                        
                        const statusClass = record.status === 'resolved' ? 'bg-success' : 
                                           record.status === 'active' ? 'bg-primary' : 
                                           record.status === 'pending' ? 'bg-secondary' : 'bg-warning';
                        
                        html += `
                            <tr>
                                <td>${record.admission_number}</td>
                                <td>${record.student_name}</td>
                                <td>${new Date(record.incident_date).toLocaleDateString()}</td>
                                <td><span class="badge badge-${record.action_type}">${record.action_type}</span></td>
                                <td><span class="badge ${severityClass}">${record.severity}</span></td>
                                <td>${record.title}</td>
                                <td><span class="badge ${statusClass}">${record.status}</span></td>
                            </tr>
                        `;
                    });
                    
                    disciplinaryTableBody.innerHTML = html;
                } else {
                    disciplinaryTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No disciplinary records found.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading disciplinary records:', error);
                disciplinaryTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading disciplinary records.</td></tr>';
            }
        }
        
        // Initialize
        loadStudents();
        loadDisciplinaryRecords();
    </script>
    <script src="../assets/js/notifications.js"></script>
    
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
