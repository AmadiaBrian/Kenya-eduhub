<?php
// Student Subjects Assignment Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Authentication check
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['school_id'])) {
    header('Location: ../index.php?route=login');
    exit;
}

$teacher_id = $_SESSION['teacher_id'] ?? null;
$school_id = $_SESSION['school_id'] ?? null;
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$teacher_class_id = $_SESSION['class_id'] ?? null;
$teacher_stream_id = $_SESSION['stream_id'] ?? null;

// Check if teacher has an assigned class
if (!$teacher_class_id) {
    echo "<!DOCTYPE html><html><head><title>No Class Assigned</title></head><body>";
    echo "<h1>No Class Assigned</h1>";
    echo "<p>You have not been assigned to any class yet. Please contact the school administrator.</p>";
    echo "<a href='dashboard'>Return to Dashboard</a>";
    echo "</body></html>";
    exit;
}

// Fetch students - only from teacher's assigned class
$students = [];
try {
    if ($school_id && $teacher_class_id) {
        $query = "SELECT s.*, c.class_name, st.stream_name
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams st ON s.stream_id = st.id
                  WHERE s.school_id = ? AND s.class_id = ?";
        $params = [$school_id, $teacher_class_id];

        if ($teacher_stream_id) {
            $query .= " AND s.stream_id = ?";
            $params[] = $teacher_stream_id;
        }

        $query .= " ORDER BY s.first_name, s.last_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch students: " . $e->getMessage());
}

// Fetch subjects
$subjects = [];
try {
    if ($school_id) {
        $stmt = $pdo->prepare("SELECT s.*, sc.category_name FROM subjects s
                               LEFT JOIN subject_categories sc ON s.category_id = sc.id
                               WHERE s.school_id = ? AND s.status = 'active' ORDER BY s.subject_name");
        $stmt->execute([$school_id]);
        $subjects = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch subjects: " . $e->getMessage());
}

// Fetch classes - only teacher's assigned class
$classes = [];
try {
    if ($school_id && $_SESSION['class_id']) {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? AND id = ?");
        $stmt->execute([$school_id, $_SESSION['class_id']]);
        $classes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Fetch streams - if teacher has stream, show only that; otherwise show all streams for the class
$streams = [];
try {
    if ($teacher_class_id) {
        if ($teacher_stream_id) {
            // Teacher assigned to specific stream - show only that stream
            $stmt = $pdo->prepare("SELECT * FROM streams WHERE id = ? AND class_id = ?");
            $stmt->execute([$teacher_stream_id, $teacher_class_id]);
            $streams = $stmt->fetchAll();
        } else {
            // Teacher assigned to class only - show all streams for that class
            $stmt = $pdo->prepare("SELECT * FROM streams WHERE class_id = ?");
            $stmt->execute([$teacher_class_id]);
            $streams = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log("Failed to fetch streams: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Student Subjects - <?php echo htmlspecialchars($teacher_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease, margin-left 0.3s ease;
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
            padding-bottom: 80px;
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
            border: 2px solid #000;
            width: 100%;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        .table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #000;
        }
        
        .table th {
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px;
            font-weight: bold;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
            background: #f5f5f5;
        }
        
        .table td {
            padding: 10px;
            border: 1px solid #000;
            color: #000;
            font-size: 12px;
            vertical-align: middle;
        }
        
        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        .checkbox-cell {
            text-align: center;
            width: 50px;
        }
        
        .checkbox-cell input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #1e8e3e;
            color: white;
        }

        .alert-error {
            background: #d93025;
            color: white;
        }

        .alert-warning {
            background: #f9ab00;
            color: #202124;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                z-index: 9999;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-bottom: 80px;
            }
            
            .header {
                padding: 0 16px;
            }
            
            .logo {
                font-size: 14px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                font-size: 11px;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
            }
            
            .checkbox-cell {
                width: 40px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }
            
            .filter-section {
                flex-direction: column;
                gap: 12px;
            }
            
            .filter-section .form-select,
            .filter-section .btn {
                width: 100%;
            }
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
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
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
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link active" href="student-subjects">
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
    
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Student Subject Assignments</h1>
        
        <div class="card">
            <h2 class="card-title">Assign Subjects to Students</h2>
            
            <form id="assignmentForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Filter by Class</label>
                        <select class="form-control" id="filterClass" onchange="filterStudents()">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Stream</label>
                        <select class="form-control" id="filterStream" onchange="filterStudents()">
                            <option value="">All Streams</option>
                            <?php foreach ($streams as $stream): ?>
                                <option value="<?php echo $stream['id']; ?>"><?php echo htmlspecialchars($stream['stream_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Select Subject</label>
                        <select class="form-control" id="selectSubject" required>
                            <option value="">Select Subject</option>
                            <?php if (empty($subjects)): ?>
                                <option value="" disabled>No active subjects available. Please add subjects in the school portal.</option>
                            <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?> <?php echo $subject['category_name'] ? '(' . htmlspecialchars($subject['category_name']) . ')' : ''; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" onclick="selectAllStudents()">
                        <i class="fas fa-check-double me-2"></i> Select All Students
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="deselectAllStudents()">
                        <i class="fas fa-times me-2"></i> Deselect All
                    </button>
                    <button type="button" class="btn btn-warning" onclick="autoAssignCompulsory()">
                        <i class="fas fa-magic me-2"></i> Auto-Assign Compulsory Subjects
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i> Save Assignments
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2 class="card-title">Students</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell"><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()"></th>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Current Subjects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTable">
                        <?php foreach ($students as $student): ?>
                            <tr data-class-id="<?php echo $student['class_id']; ?>" data-stream-id="<?php echo $student['stream_id']; ?>">
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="student-checkbox" value="<?php echo $student['id']; ?>">
                                </td>
                                <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($student['stream_name'] ?? '-'); ?></td>
                                <td id="subjects-<?php echo $student['id']; ?>">Loading...</td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="editStudentSubjects(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Edit Student Subjects Modal -->
    <div class="modal fade" id="editStudentSubjectsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student Subjects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="editStudentName"></h6>
                    <div id="currentSubjectsList" class="mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">Remove Subject</label>
                        <select class="form-control" id="removeSubjectSelect">
                            <option value="">Select subject to remove</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="removeStudentSubject()">Remove Subject</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                // Mobile: toggle the 'show' class
                sidebar.classList.toggle('show');
            } else {
                // Desktop: toggle the 'collapsed' and 'expanded' classes
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }
        
        function filterStudents() {
            const classId = document.getElementById('filterClass').value;
            const streamId = document.getElementById('filterStream').value;
            
            const rows = document.querySelectorAll('#studentsTable tr');
            rows.forEach(row => {
                const rowClassId = row.getAttribute('data-class-id');
                const rowStreamId = row.getAttribute('data-stream-id');
                
                const matchesClass = !classId || rowClassId === classId;
                const matchesStream = !streamId || rowStreamId === streamId;
                
                row.style.display = (matchesClass && matchesStream) ? '' : 'none';
            });
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = selectAll.checked;
                }
            });
        }
        
        function selectAllStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = true;
                }
            });
            document.getElementById('selectAllCheckbox').checked = true;
        }
        
        function deselectAllStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAllCheckbox').checked = false;
        }
        
        // Load student subjects on page load
        async function loadStudentSubjects() {
            const studentIds = [];
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                studentIds.push(checkbox.value);
            });

            try {
                const response = await fetch('api/student-subjects.php?student_ids=' + studentIds.join(','));
                const data = await response.json();

                if (data.success) {
                    data.data.forEach(assignment => {
                        const cell = document.getElementById('subjects-' + assignment.student_id);
                        if (cell) {
                            cell.textContent = assignment.subject_names || 'No subjects';
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading student subjects:', error);
            }
        }

        // Handle form submission
        document.getElementById('assignmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const subjectId = document.getElementById('selectSubject').value;

            if (!subjectId) {
                showNotification('warning', 'Please select subject');
                return;
            }

            const selectedStudents = [];
            document.querySelectorAll('.student-checkbox:checked').forEach(checkbox => {
                selectedStudents.push(checkbox.value);
            });

            if (selectedStudents.length === 0) {
                showNotification('warning', 'Please select at least one student');
                return;
            }

            try {
                const response = await fetch('api/student-subjects.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_ids: selectedStudents,
                        subject_id: subjectId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', 'Subjects assigned successfully');
                    loadStudentSubjects();
                } else {
                    showNotification('error', data.error || 'Failed to assign subjects');
                }
            } catch (error) {
                showNotification('error', 'An error occurred');
            }
        });
        
        // Initial load
        loadStudentSubjects();

        let currentEditStudentId = null;
        let currentStudentSubjects = [];

        function editStudentSubjects(studentId, studentName) {
            currentEditStudentId = studentId;
            document.getElementById('editStudentName').textContent = 'Student: ' + studentName;

            // Fetch student's current subjects
            fetch('api/student-subjects.php?student_ids=' + studentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        const assignment = data.data[0];
                        const subjectNames = assignment.subject_names ? assignment.subject_names.split(',') : [];

                        // Display current subjects
                        document.getElementById('currentSubjectsList').innerHTML = '<strong>Current Subjects:</strong><br>' +
                            (subjectNames.length > 0 ? subjectNames.join(', ') : 'No subjects assigned');

                        // Get subject IDs for this student
                        fetch('api/student-subjects.php?get_subject_ids=1&student_id=' + studentId)
                            .then(response => response.json())
                            .then(subjectData => {
                                if (subjectData.success) {
                                    currentStudentSubjects = subjectData.data;

                                    // Populate remove dropdown
                                    const select = document.getElementById('removeSubjectSelect');
                                    select.innerHTML = '<option value="">Select subject to remove</option>';
                                    subjectData.data.forEach(sub => {
                                        const option = document.createElement('option');
                                        option.value = sub.subject_id;
                                        option.textContent = sub.subject_name;
                                        select.appendChild(option);
                                    });
                                }
                            });
                    } else {
                        document.getElementById('currentSubjectsList').innerHTML = 'No subjects assigned';
                        document.getElementById('removeSubjectSelect').innerHTML = '<option value="">No subjects to remove</option>';
                    }
                });

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editStudentSubjectsModal'));
            modal.show();
        }

        function removeStudentSubject() {
            const subjectId = document.getElementById('removeSubjectSelect').value;
            if (!subjectId || !currentEditStudentId) {
                showNotification('warning', 'Please select a subject to remove');
                return;
            }

            if (!confirm('Are you sure you want to remove this subject from the student?')) {
                return;
            }

            fetch('api/student-subjects.php?student_id=' + currentEditStudentId + '&subject_id=' + subjectId, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Subject removed successfully');
                    bootstrap.Modal.getInstance(document.getElementById('editStudentSubjectsModal')).hide();
                    loadStudentSubjects();
                } else {
                    showNotification('error', data.error || 'Failed to remove subject');
                }
            })
            .catch(error => {
                showNotification('error', 'An error occurred');
            });
        }

        function autoAssignCompulsory() {
            if (!confirm('This will automatically assign all compulsory subjects to all students in your class. Continue?')) {
                return;
            }

            const formData = new FormData();
            formData.append('auto_assign_compulsory', '1');
            formData.append('class_id', <?php echo $teacher_class_id; ?>);
            <?php if ($teacher_stream_id): ?>
            formData.append('stream_id', <?php echo $teacher_stream_id; ?>);
            <?php endif; ?>

            fetch('api/student-subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Successfully assigned compulsory subjects to students');
                    loadStudentSubjects();
                } else {
                    showNotification('error', data.error || 'Failed to assign compulsory subjects');
                }
            })
            .catch(error => {
                showNotification('error', 'An error occurred');
            });
        }
    </script>
    
    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg-color); color: #5f6368; padding: 20px 0; text-align: center; border-top: 1px solid #e8eaed; z-index: 1000;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
