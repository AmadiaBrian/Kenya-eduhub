<?php
// Teacher Duty View - Weekly Management System
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];

// Get teacher details
try {
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher details: " . $e->getMessage());
    $teacher = null;
}

// Handle leaveout chit creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_leaveout'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO leaveout_chits (school_id, teacher_id, student_id, reason, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $school_id,
            $teacher_id,
            $_POST['student_id'],
            $_POST['reason'],
            $teacher_id
        ]);
        $success = "Leaveout chit created successfully!";
    } catch (PDOException $e) {
        $error = "Failed to create leaveout chit.";
    }
}

// Handle incident reporting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_incident'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO incident_reports (school_id, teacher_id, incident_type, description, severity, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $school_id,
            $teacher_id,
            $_POST['incident_type'],
            $_POST['description'],
            $_POST['severity'],
            $teacher_id
        ]);
        $success = "Incident reported successfully!";
    } catch (PDOException $e) {
        $error = "Failed to report incident.";
    }
}

// Handle cleanliness check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_cleanliness'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO cleanliness_checks (school_id, teacher_id, area, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $school_id,
            $teacher_id,
            $_POST['area'],
            $_POST['status'],
            $_POST['notes'],
            $teacher_id
        ]);
        $success = "Cleanliness check recorded!";
    } catch (PDOException $e) {
        $error = "Failed to record cleanliness check.";
    }
}

// Get teacher's current weekly duty
try {
    $current_date = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT da.* 
        FROM duty_assignments da 
        WHERE da.teacher_id = ? AND da.school_id = ? 
        AND da.week_start <= ? AND da.week_end >= ?
        AND da.duty_type = 'weekly'
    ");
    $stmt->execute([$teacher_id, $school_id, $current_date, $current_date]);
    $current_duty = $stmt->fetch();
} catch (PDOException $e) {
    $current_duty = null;
}

// Get all students for leaveout chit dropdown
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE school_id = ? AND status = 'active' ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
}

// Get recent leaveout chits
try {
    $stmt = $pdo->prepare("
        SELECT lc.*, s.first_name, s.last_name 
        FROM leaveout_chits lc 
        JOIN students s ON lc.student_id = s.id 
        WHERE lc.school_id = ? AND lc.teacher_id = ?
        ORDER BY lc.created_at DESC LIMIT 10
    ");
    $stmt->execute([$school_id, $teacher_id]);
    $leaveout_chits = $stmt->fetchAll();
} catch (PDOException $e) {
    $leaveout_chits = [];
}

// Get recent incident reports
try {
    $stmt = $pdo->prepare("
        SELECT * FROM incident_reports 
        WHERE school_id = ? AND teacher_id = ?
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute([$school_id, $teacher_id]);
    $incidents = $stmt->fetchAll();
} catch (PDOException $e) {
    $incidents = [];
}

// Get recent cleanliness checks
try {
    $stmt = $pdo->prepare("
        SELECT * FROM cleanliness_checks 
        WHERE school_id = ? AND teacher_id = ?
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute([$school_id, $teacher_id]);
    $cleanliness_checks = $stmt->fetchAll();
} catch (PDOException $e) {
    $cleanliness_checks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>My Duties - <?php echo htmlspecialchars($teacher_name); ?></title>
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
        
        .logo {
            display: flex;
            align-items: center;
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .sidebar.collapsed {
            transform: translateX(-256px);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #202124;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            border-bottom: 1px solid #f1f3f4;
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
        
        .card h5 {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 12px;
        }
        
        .card-text {
            color: #5f6368;
            margin-bottom: 8px;
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
        
        .btn-success {
            background: #28a745;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        .duty-card {
            border-left: 4px solid var(--primary-color);
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s;
        }
        
        .duty-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .duty-card.completed {
            border-left-color: #28a745;
            opacity: 0.7;
        }
        
        .alert-info {
            background: #e8f0fe;
            border: 1px solid #d2e3fc;
            color: #1967d2;
            padding: 16px;
            border-radius: 8px;
        }
        
        .bg-light {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
        }
        
        .text-center h3 {
            font-size: 32px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .text-center p {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 0;
        }
        
        .footer {
            background: transparent;
            color: white;
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 40px;
        }
        
        .chit-preview {
            display: none;
            border: 2px solid #000;
            padding: 15px;
            margin: 20px auto;
            background: white;
            font-family: 'Courier New', monospace;
            width: 300px;
            font-size: 12px;
        }
        
        .chit-preview.show {
            display: block;
        }
        
        .chit-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .chit-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .chit-header h4 {
            margin: 5px 0 0;
            font-size: 14px;
            color: #FF6B35;
            font-weight: bold;
        }
        
        .chit-body {
            margin: 15px 0;
        }
        
        .chit-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .chit-label {
            width: 80px;
            font-weight: bold;
        }
        
        .chit-value {
            flex: 1;
            border-bottom: 1px dotted #000;
        }
        
        .chit-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        
        .chit-signature {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        
        .signature-line {
            width: 100px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 9px;
        }
        
        .printer-select {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .printer-select label {
            margin-right: 15px;
            font-weight: 500;
        }
        
        @media print {
            body, html {
                margin: 0;
                padding: 0;
                background: white;
            }
            header, aside, footer, main {
                display: none !important;
            }
            .chit-preview {
                display: block !important;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                background: white;
                box-sizing: border-box;
                font-family: 'Courier New', monospace;
            }
            .chit-preview .no-print {
                display: none !important;
            }
            .printer-select {
                display: none !important;
            }
            
            /* Thermal Printer Style */
            .chit-preview.thermal {
                max-width: 300px;
                margin: 0 auto;
                padding: 10px;
                border: none;
                font-size: 12px;
            }
            .chit-preview.thermal .chit-header {
                margin-bottom: 15px;
                text-align: center;
                border-bottom: 2px dashed #000;
                padding-bottom: 10px;
            }
            .chit-preview.thermal .chit-header h3 {
                font-size: 16px;
                margin: 0;
                font-weight: bold;
                text-transform: uppercase;
            }
            .chit-preview.thermal .chit-header h4 {
                font-size: 14px;
                margin: 5px 0 0;
                color: #FF6B35;
                font-weight: bold;
            }
            .chit-preview.thermal .chit-row {
                margin-bottom: 10px;
                font-size: 11px;
            }
            .chit-preview.thermal .chit-label {
                width: 80px;
                font-size: 11px;
                font-weight: bold;
            }
            .chit-preview.thermal .chit-value {
                font-size: 11px;
                border-bottom: 1px dotted #000;
            }
            .chit-preview.thermal .chit-footer {
                font-size: 10px;
                text-align: center;
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px dashed #000;
            }
            .chit-preview.thermal .chit-signature {
                margin-top: 25px;
            }
            .chit-preview.thermal .signature-line {
                width: 100px;
                border-top: 1px solid #000;
                text-align: center;
                font-size: 9px;
            }
            .chit-preview.thermal @page {
                size: 80mm auto;
                margin: 0;
            }
            
            /* A4 Printer Style */
            .chit-preview.a4 {
                max-width: 600px;
                margin: 0 auto;
                padding: 40px;
                border: 3px solid #000;
                font-size: 16px;
            }
            .chit-preview.a4 .chit-header {
                margin-bottom: 30px;
                text-align: center;
                border-bottom: 3px solid #000;
                padding-bottom: 20px;
            }
            .chit-preview.a4 .chit-header h3 {
                font-size: 32px;
                margin: 0;
                font-weight: bold;
                text-transform: uppercase;
            }
            .chit-preview.a4 .chit-header h4 {
                font-size: 24px;
                margin: 10px 0 0;
                color: #FF6B35;
                font-weight: bold;
            }
            .chit-preview.a4 .chit-row {
                margin-bottom: 20px;
                font-size: 16px;
            }
            .chit-preview.a4 .chit-label {
                width: 150px;
                font-size: 16px;
                font-weight: bold;
            }
            .chit-preview.a4 .chit-value {
                font-size: 16px;
                border-bottom: 2px solid #000;
            }
            .chit-preview.a4 .chit-footer {
                font-size: 14px;
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 2px solid #000;
            }
            .chit-preview.a4 .chit-signature {
                margin-top: 60px;
            }
            .chit-preview.a4 .signature-line {
                width: 200px;
                border-top: 2px solid #000;
                text-align: center;
                font-size: 12px;
            }
            .chit-preview.a4 @page {
                size: A4;
                margin: 20mm;
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
            
            .duty-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 14px;
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
            
            .chit-preview.a4 {
                padding: 10px;
                font-size: 10px;
            }
            
            .chit-preview.a4 .header-info {
                font-size: 10px;
            }
            
            .chit-preview.a4 .chit-title {
                font-size: 14px;
            }
            
            .chit-preview.a4 .info-row {
                font-size: 10px;
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
                <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <aside class="sidebar" id="sidebar">
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
            <i class="fas fa-calendar-alt"></i> Calendar
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
            <i class="fas fa-user-friends"></i> Parents
        </a>
        <a class="nav-link active" href="duty">
            <i class="fas fa-clipboard-list"></i> My Duties
        </a>
        <a class="nav-link" href="fees">
            <i class="fas fa-money-bill-wave"></i> Fee Payments
        </a>
        <a class="nav-link" href="profile">
            <i class="fas fa-user"></i> Profile
        </a>
        <a class="nav-link" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>
    
    <main class="main-content" id="mainContent">
        <?php if ($current_duty): ?>
            <div class="card" style="border-left: 4px solid #28a745; background: #d4edda;">
                <h4 class="mb-3" style="color: #155724;">
                    <i class="fas fa-user-shield me-2"></i>You are on Duty This Week
                </h4>
                <p class="mb-2">
                    <strong>Week:</strong> <?php echo date('F j, Y', strtotime($current_duty['week_start'])); ?> - <?php echo date('F j, Y', strtotime($current_duty['week_end'])); ?>
                </p>
                <p class="mb-0 text-muted">You are responsible for managing the school, handling sick students, reporting incidents, ensuring cleanliness, and maintaining order.</p>
            </div>
        <?php else: ?>
            <div class="card" style="border-left: 4px solid #FF6B35;">
                <h4 class="mb-3" style="color: #FF6B35;">
                    <i class="fas fa-info-circle me-2"></i>Not on Duty This Week
                </h4>
                <p class="mb-0 text-muted">You are not assigned to duty this week. Check back for your upcoming duty assignments.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($current_duty): ?>
            <div class="card">
                <h4 class="mb-4">Duty Management Tools</h4>
                
                <!-- Leaveout Chit Section -->
                <div class="mb-4">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h5 class="mb-0"><i class="fas fa-file-medical me-2"></i>Create Leaveout Chit (Sick Student)</h5>
                    </div>
                    
                    <form method="POST" id="chitForm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Select Student</label>
                                <div style="border: 2px solid #e8eaed; border-radius: 8px; background: white; padding: 16px;">
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($students as $student): ?>
                                            <div class="form-check mb-2" style="padding: 8px; border-bottom: 1px solid #f1f3f4;">
                                                <input class="form-check-input" type="radio" name="student_id" value="<?php echo $student['id']; ?>" id="student_<?php echo $student['id']; ?>" style="width: 18px; height: 18px;" required>
                                                <label class="form-check-label" for="student_<?php echo $student['id']; ?>" style="font-size: 14px; font-weight: 500; color: #202124;">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Reason for Leaveout</label>
                                <input type="text" class="form-control" name="reason" placeholder="e.g., Sick, Medical appointment" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="create_leaveout" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Create Chit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Incident Reporting Section -->
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Report Incident to Administration</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Incident Type</label>
                                <select class="form-select" name="incident_type" required>
                                    <option value="">Select type</option>
                                    <option value="disciplinary">Disciplinary</option>
                                    <option value="medical">Medical Emergency</option>
                                    <option value="security">Security Issue</option>
                                    <option value="damage">Property Damage</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Severity</label>
                                <select class="form-select" name="severity" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" placeholder="Describe the incident" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="report_incident" class="btn btn-danger w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Cleanliness Check Section -->
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-broom me-2"></i>Record Cleanliness Check</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Area</label>
                                <select class="form-select" name="area" required>
                                    <option value="">Select area</option>
                                    <option value="classrooms">Classrooms</option>
                                    <option value="corridors">Corridors</option>
                                    <option value="cafeteria">Cafeteria</option>
                                    <option value="playground">Playground</option>
                                    <option value="toilets">Toilets</option>
                                    <option value="library">Library</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Notes</label>
                                <input type="text" class="form-control" name="notes" placeholder="Any observations">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="record_cleanliness" class="btn btn-success w-100">
                                    <i class="fas fa-check me-2"></i>Record
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <h5 class="mb-3"><i class="fas fa-file-medical me-2"></i>Recent Leaveout Chits</h5>
                        <div class="mb-3">
                            <label class="form-label small">Printer Type:</label>
                            <select id="printerType" class="form-select form-select-sm">
                                <option value="thermal">Thermal Receipt (80mm)</option>
                                <option value="a4">A4/Letter</option>
                            </select>
                        </div>
                        <?php if (empty($leaveout_chits)): ?>
                            <p class="text-muted small">No leaveout chits created</p>
                        <?php else: ?>
                            <?php foreach ($leaveout_chits as $chit): ?>
                                <div class="mb-2 p-2 bg-light rounded small">
                                    <strong><?php echo htmlspecialchars($chit['first_name'] . ' ' . $chit['last_name']); ?></strong>
                                    <br>
                                    <?php echo htmlspecialchars($chit['reason']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('M j, g:i A', strtotime($chit['created_at'])); ?></small>
                                    <br>
                                    <button type="button" 
                                            data-student="<?php echo htmlspecialchars($chit['first_name'] . ' ' . $chit['last_name']); ?>"
                                            data-reason="<?php echo htmlspecialchars($chit['reason']); ?>"
                                            data-date="<?php echo date('F j, Y', strtotime($chit['created_at'])); ?>"
                                            data-teacher="<?php echo htmlspecialchars($teacher_name); ?>"
                                            data-school="<?php echo htmlspecialchars($teacher['school_name'] ?? 'School'); ?>"
                                            onclick="printExistingChit(this)" 
                                            class="btn btn-sm btn-primary mt-1">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <h5 class="mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Recent Incidents</h5>
                        <?php if (empty($incidents)): ?>
                            <p class="text-muted small">No incidents reported</p>
                        <?php else: ?>
                            <?php foreach ($incidents as $incident): ?>
                                <div class="mb-2 p-2 bg-light rounded small">
                                    <strong><?php echo ucfirst(htmlspecialchars($incident['incident_type'])); ?></strong>
                                    <br>
                                    <?php echo htmlspecialchars($incident['description']); ?>
                                    <br>
                                    <span class="badge badge-<?php echo $incident['severity'] === 'critical' ? 'danger' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($incident['severity'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <h5 class="mb-3"><i class="fas fa-broom me-2"></i>Cleanliness Checks</h5>
                        <?php if (empty($cleanliness_checks)): ?>
                            <p class="text-muted small">No checks recorded</p>
                        <?php else: ?>
                            <?php foreach ($cleanliness_checks as $check): ?>
                                <div class="mb-2 p-2 bg-light rounded small">
                                    <strong><?php echo ucfirst(htmlspecialchars($check['area'])); ?></strong>
                                    <br>
                                    <span class="badge badge-<?php echo $check['status'] === 'excellent' ? 'success' : ($check['status'] === 'poor' ? 'danger' : 'info'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($check['status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg-color); color: #5f6368; padding: 20px 0; text-align: center; border-top: 1px solid #e8eaed; z-index: 1000;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        
        function printExistingChit(button) {
            const printerType = document.getElementById('printerType').value;
            const chitContent = `
                <html>
                <head>
                    <title>Leaveout Chit</title>
                    <style>
                        body {
                            font-family: 'Courier New', monospace;
                            font-size: 12px;
                            width: ${printerType === 'thermal' ? '300px' : '600px'};
                            margin: 0 auto;
                            padding: ${printerType === 'thermal' ? '10px' : '40px'};
                            background: white;
                        }
                        .chit-header {
                            text-align: center;
                            border-bottom: ${printerType === 'thermal' ? '2px dashed' : '3px solid'} #000;
                            padding-bottom: ${printerType === 'thermal' ? '10px' : '20px'};
                            margin-bottom: ${printerType === 'thermal' ? '15px' : '30px'};
                        }
                        .chit-header h3 {
                            margin: 0;
                            font-size: ${printerType === 'thermal' ? '16px' : '32px'};
                            font-weight: bold;
                            text-transform: uppercase;
                        }
                        .chit-header h4 {
                            margin: ${printerType === 'thermal' ? '5px' : '10px'} 0 0;
                            font-size: ${printerType === 'thermal' ? '14px' : '24px'};
                            color: #FF6B35;
                            font-weight: bold;
                        }
                        .chit-row {
                            display: flex;
                            margin-bottom: ${printerType === 'thermal' ? '10px' : '20px'};
                            font-size: ${printerType === 'thermal' ? '11px' : '16px'};
                        }
                        .chit-label {
                            width: ${printerType === 'thermal' ? '80px' : '150px'};
                            font-weight: bold;
                        }
                        .chit-value {
                            flex: 1;
                            border-bottom: ${printerType === 'thermal' ? '1px dotted' : '2px solid'} #000;
                        }
                        .chit-footer {
                            text-align: center;
                            margin-top: ${printerType === 'thermal' ? '20px' : '30px'};
                            padding-top: ${printerType === 'thermal' ? '10px' : '20px'};
                            border-top: ${printerType === 'thermal' ? '1px dashed' : '2px solid'} #000;
                            font-size: ${printerType === 'thermal' ? '10px' : '14px'};
                        }
                        .chit-signature {
                            display: flex;
                            justify-content: space-between;
                            margin-top: ${printerType === 'thermal' ? '25px' : '60px'};
                        }
                        .signature-line {
                            width: ${printerType === 'thermal' ? '100px' : '200px'};
                            border-top: ${printerType === 'thermal' ? '1px' : '2px'} solid #000;
                            text-align: center;
                            font-size: ${printerType === 'thermal' ? '9px' : '12px'};
                        }
                        @page {
                            size: ${printerType === 'thermal' ? '80mm auto' : 'A4'};
                            margin: ${printerType === 'thermal' ? '0' : '20mm'};
                        }
                    </style>
                </head>
                <body>
                    <div class="chit-header">
                        <h3>LEAVEOUT CHIT</h3>
                        <h4>${button.dataset.school}</h4>
                    </div>
                    <div class="chit-body">
                        <div class="chit-row">
                            <div class="chit-label">Date:</div>
                            <div class="chit-value">${button.dataset.date}</div>
                        </div>
                        <div class="chit-row">
                            <div class="chit-label">Student Name:</div>
                            <div class="chit-value">${button.dataset.student}</div>
                        </div>
                        <div class="chit-row">
                            <div class="chit-label">Reason:</div>
                            <div class="chit-value">${button.dataset.reason}</div>
                        </div>
                        <div class="chit-row">
                            <div class="chit-label">Teacher on Duty:</div>
                            <div class="chit-value">${button.dataset.teacher}</div>
                        </div>
                    </div>
                    <div class="chit-footer">
                        <p>This student is permitted to leave the school premises for the reason stated above.</p>
                    </div>
                    <div class="chit-signature">
                        <div class="signature-line">
                            <small>Teacher on Duty</small>
                        </div>
                        <div class="signature-line">
                            <small>Principal/Admin</small>
                        </div>
                    </div>
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(chitContent);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
