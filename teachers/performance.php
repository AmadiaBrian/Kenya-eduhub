<?php
// Teacher Performance Management
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

// Get active term from calendar status
$active_term = $calendar_status['current_term']['term_name'] ?? null;

// Get terms from database for current year
$terms = [];
try {
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $current_year]);
    $term_records = $stmt->fetchAll();
    foreach ($term_records as $term) {
        $terms[] = $term['term_name'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

if (empty($terms)) {
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

// Use active term if available, otherwise use first term
$current_term = $active_term ?? ($terms[0] ?? 'Term 1');

// Get teacher details and subject assignments
try {
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher details: " . $e->getMessage());
    $teacher = null;
}

// Get streams for the teacher's class (for class teachers)
$streams = [];
if ($teacher && $teacher['stream_id']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM streams WHERE id = ?");
        $stmt->execute([$teacher['stream_id']]);
        $streams = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch streams: " . $e->getMessage());
    }
} elseif ($teacher && $teacher['class_id']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM streams WHERE class_id = ?");
        $stmt->execute([$teacher['class_id']]);
        $streams = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch streams: " . $e->getMessage());
    }
}

// Get subject assignments for subject teachers
$subject_assignments = [];
if ($teacher && $teacher['teacher_type'] === 'subject_teacher') {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, c.class_name, s.subject_name 
                             FROM teacher_subjects ts
                             JOIN classes c ON ts.class_id = c.id
                             LEFT JOIN subjects s ON ts.subject_id = s.id
                             WHERE ts.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $subject_assignments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subject assignments: " . $e->getMessage());
    }
}

// Handle CSV template download
if (isset($_GET['download_template']) && $_GET['download_template'] === 'true') {
    $streamId = $_GET['stream_id'] ?? '';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="performance_template.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Admission Number', 'Marks']);
    
    // Get students from the selected stream
    if ($streamId) {
        try {
            $stmt = $pdo->prepare("SELECT admission_number FROM students WHERE stream_id = ? AND school_id = ?");
            $stmt->execute([$streamId, $teacher['school_id']]);
            $students = $stmt->fetchAll();
            
            foreach ($students as $student) {
                fputcsv($output, [$student['admission_number'], '']);
            }
        } catch (PDOException $e) {
            error_log("Failed to fetch students for template: " . $e->getMessage());
            // Fallback to sample data if query fails
            fputcsv($output, ['ADM001', '']);
            fputcsv($output, ['ADM002', '']);
            fputcsv($output, ['ADM003', '']);
        }
    } else {
        // Sample data if no stream selected
        fputcsv($output, ['ADM001', '']);
        fputcsv($output, ['ADM002', '']);
        fputcsv($output, ['ADM003', '']);
    }
    
    fclose($output);
    
    exit;
}

// Handle CSV upload for bulk performance entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['performance_file']) && isset($_POST['bulk_upload'])) {
    $term = $_POST['term'] ?? '';
    $year = $_POST['year'] ?? '';
    $exam_type_id = intval($_POST['exam_type_id'] ?? 0);
    $subject = strtoupper(trim($_POST['subject'] ?? '')); // Normalize subject to uppercase
    $streamId = $_POST['streamId'] ?? '';
    
    // Fetch grading scales for grade calculation
    $bulk_grading_scales = [];
    try {
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id, s.school_id as subject_school_id 
                              FROM grading_scales gs 
                              LEFT JOIN subjects s ON gs.subject_id = s.id 
                              WHERE gs.school_id = ? 
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$teacher['school_id']]);
        $bulk_grading_scales = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch grading scales for bulk upload: " . $e->getMessage());
    }
    
    if (!$term || !$year || !$exam_type_id || !$subject || !$streamId) {
        $error = 'Please fill all required fields';
    } else {
        $file = $_FILES['performance_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload error';
        } else {
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file_ext !== 'csv') {
                $error = 'Please upload a CSV file. Download the template first.';
            } else {
                $handle = fopen($file['tmp_name'], 'r');
                
                if ($handle) {
                    try {
                        $pdo->beginTransaction();
                        
                        // Skip header row
                        fgetcsv($handle);
                        
                        $row_count = 0;
                        $error_count = 0;
                        $processed_admissions = []; // Track processed admission numbers to prevent duplicates
                        
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            if (count($data) >= 2) {
                                $admission_number = trim($data[0]);
                                $marks = floatval(trim($data[1]));
                                
                                if (!empty($admission_number) && $marks >= 0 && $marks <= 100) {
                                    // Skip if this admission number was already processed in this upload
                                    if (in_array($admission_number, $processed_admissions)) {
                                        error_log("Skipping duplicate admission number in CSV: $admission_number");
                                        $error_count++;
                                        continue;
                                    }
                                    
                                    // Find student by admission number
                                    $stmt = $pdo->prepare("SELECT id FROM students WHERE admission_number = ? AND school_id = ?");
                                    $stmt->execute([$admission_number, $teacher['school_id']]);
                                    $student = $stmt->fetch();
                                    
                                    if ($student) {
                                        // Calculate grade using grading system
                                        $grade = 'No match';
                                        $gradeDescription = '';
                                        foreach ($bulk_grading_scales as $scale) {
                                            if ($marks >= $scale['min_score'] && $marks <= $scale['max_score']) {
                                                $grade = $scale['grade_name'];
                                                $gradeDescription = $scale['grade_description'] ?? '';
                                                break;
                                            }
                                        }
                                        
                                        // Use grade description as remarks
                                        $finalRemarks = $gradeDescription;
                                        
                                        // Check if performance record already exists
                                        $stmt = $pdo->prepare("SELECT id FROM academic_performance WHERE student_id = ? AND term = ? AND year = ? AND subject = ? AND exam_type_id = ?");
                                        $stmt->execute([$student['id'], $term, $year, $subject, $exam_type_id]);
                                        $existing = $stmt->fetch();
                                        
                                        if ($existing) {
                                            // Update existing record
                                            $stmt = $pdo->prepare("UPDATE academic_performance SET marks = ?, grade = ?, remarks = ? WHERE id = ?");
                                            $stmt->execute([$marks, $grade, $finalRemarks, $existing['id']]);
                                        } else {
                                            // Insert new record
                                            $stmt = $pdo->prepare("INSERT INTO academic_performance (student_id, term, year, subject, exam_type_id, marks, grade, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                            $stmt->execute([$student['id'], $term, $year, $subject, $exam_type_id, $marks, $grade, $finalRemarks]);
                                        }
                                        
                                        $row_count++;
                                        $processed_admissions[] = $admission_number; // Mark as processed
                                    } else {
                                        $error_count++;
                                    }
                                }
                            }
                        }
                        
                        fclose($handle);
                        $pdo->commit();
                        
                        if ($row_count > 0) {
                            $success = "Successfully imported $row_count performance records" . ($error_count > 0 ? " ($error_count skipped - student not found)" : "");
                            
                            // Set session variables to auto-load the imported records
                            $_SESSION['bulk_upload_success'] = true;
                            $_SESSION['bulk_upload_term'] = $term;
                            $_SESSION['bulk_upload_year'] = $year;
                            $_SESSION['bulk_upload_subject'] = $subject;
                            $_SESSION['bulk_upload_stream_id'] = $streamId;
                            $_SESSION['bulk_upload_class_id'] = $teacher['teacher_type'] === 'class_teacher' ? $class_id : null;
                        } else {
                            $error = 'No valid performance records found in the file. Please check the format.';
                        }
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        fclose($handle);
                        error_log("Performance import error: " . $e->getMessage());
                        $error = 'Failed to import performance records: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Get grading scales for the teacher's school with subject information
$grading_scales = [];
$all_subjects = [];
$exam_types = [];
if ($teacher) {
    try {
        // Fetch all subjects for the school
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
        $stmt->execute([$teacher['school_id']]);
        $all_subjects = $stmt->fetchAll();

        // Fetch exam types for the school
        $stmt = $pdo->prepare("SELECT * FROM exam_types WHERE school_id = ? AND is_active = 1 ORDER BY exam_type_name");
        $stmt->execute([$teacher['school_id']]);
        $exam_types = $stmt->fetchAll();
        
        error_log("=== GRADING SYSTEM DEBUG (PHP) ===");
        error_log("Teacher ID: " . $teacher_id);
        error_log("Teacher school_id: " . $teacher['school_id']);
        error_log("Teacher subject_id: " . ($teacher['subject_id'] ?? 'null'));
        error_log("Teacher subject: " . ($teacher['subject'] ?? 'null'));
        error_log("Teacher type: " . ($teacher['teacher_type'] ?? 'null'));
        error_log("Found " . count($all_subjects) . " subjects for school");
        
        // Check what subjects this teacher teaches
        if ($teacher['teacher_type'] === 'subject_teacher') {
            $stmt = $pdo->prepare("SELECT ts.*, s.subject_name, s.school_id as subject_school_id 
                                  FROM teacher_subjects ts
                                  JOIN subjects s ON ts.subject_id = s.id
                                  WHERE ts.teacher_id = ?");
            $stmt->execute([$teacher_id]);
            $teacher_subjects = $stmt->fetchAll();
            error_log("Teacher teaches " . count($teacher_subjects) . " subjects:");
            foreach ($teacher_subjects as $ts) {
                error_log("  - Subject ID: " . $ts['subject_id'] . ", Name: " . $ts['subject_name'] . ", School: " . $ts['subject_school_id']);
            }
        }
        
        // Get all grading scales
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id, s.school_id as subject_school_id 
                              FROM grading_scales gs 
                              LEFT JOIN subjects s ON gs.subject_id = s.id 
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute();
        $grading_scales = $stmt->fetchAll();
        
        error_log("Found " . count($grading_scales) . " grading scales for subjects in school_id=" . $teacher['school_id']);
    } catch (PDOException $e) {
        error_log("Failed to fetch grading scales: " . $e->getMessage());
    }
}

// Get subjects without performance records
$subjects_without_performance = [];
if ($teacher) {
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT s.* FROM subjects s
                             WHERE s.school_id = ? AND s.status = 'active'
                             AND s.id NOT IN (
                                 SELECT DISTINCT ap.subject_id
                                 FROM academic_performance ap
                                 JOIN students st ON ap.student_id = st.id
                                 WHERE st.school_id = ?
                             )
                             ORDER BY s.subject_name");
        $stmt->execute([$teacher['school_id'], $teacher['school_id']]);
        $subjects_without_performance = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subjects without performance: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance - <?php echo htmlspecialchars($teacher_name); ?></title>
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
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .menu-btn:hover {
            background: #f1f3f4;
        }

        .menu-btn i {
            font-size: 20px;
            color: #000;
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
                padding: 16px;
            }
            
            .menu-btn {
                display: block !important;
            }
            
            .card {
                padding: 16px;
            }
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }
            
            .table {
                min-width: 100%;
                font-size: 12px;
            }
            
            .table th, .table td {
                padding: 8px 4px;
            }
            
            .form-control, .form-select {
                font-size: 14px;
            }
            
            .btn {
                font-size: 14px;
                padding: 8px 12px;
            }
            
            .col-md-3, .col-md-4, .col-md-2 {
                margin-bottom: 12px;
            }
            
            .page-title {
                font-size: 18px;
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
            <a class="nav-link active" href="performance">
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
        <h1 class="page-title">Academic Performance</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Bulk Performance Upload</span>
                <button class="btn btn-sm btn-info" onclick="downloadPerformanceTemplate()" style="border-radius: 25px;">
                    <i class="fas fa-download me-1"></i> Download Template
                </button>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term" required style="border-radius: 25px;">
                                <?php foreach($terms as $term): ?>
                                    <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $current_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" value="2026" required style="border-radius: 25px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Exam Type</label>
                            <select class="form-control" name="exam_type_id" required style="border-radius: 25px;">
                                <option value="">Select Exam Type</option>
                                <?php foreach ($exam_types as $exam_type): ?>
                                    <option value="<?php echo $exam_type['id']; ?>">
                                        <?php echo htmlspecialchars($exam_type['exam_type_name']); ?> (<?php echo htmlspecialchars($exam_type['exam_type_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Subject</label>
                            <?php if ($teacher && $teacher['teacher_type'] === 'class_teacher'): ?>
                                <select class="form-control" name="subject" id="bulkSubject" style="border-radius: 25px;">
                                    <option value="">Select Subject</option>
                                    <?php foreach ($all_subjects as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control" name="subject" id="bulkSubject" required readonly style="border-radius: 25px;">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stream</label>
                            <select class="form-control" name="streamId" id="bulkStreamId" required style="border-radius: 25px;">
                                <option value="">Select Stream</option>
                                <?php foreach ($streams as $stream): ?>
                                    <option value="<?php echo $stream['id']; ?>">
                                        <?php echo htmlspecialchars($stream['stream_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Modern File Upload Zone -->
                    <div class="mb-3">
                        <label class="form-label">Upload CSV File</label>
                        <div id="dropZone" style="border: 2px dashed #dadce0; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8f9fa;">
                            <input type="file" class="form-control" name="performance_file" accept=".csv" required id="fileInput" style="display: none;">
                            <div id="dropZoneContent">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #dadce0; margin-bottom: 12px;"></i>
                                <p style="color: #5f6368; margin-bottom: 4px; font-size: 13px;">Drag and drop CSV file here</p>
                                <p style="color: #9aa0a6; font-size: 12px;">or click to browse</p>
                            </div>
                            <div id="fileSelected" style="display: none;">
                                <i class="fas fa-file-csv" style="font-size: 32px; color: #137333; margin-bottom: 8px;"></i>
                                <p style="color: #202124; font-weight: 500; margin-bottom: 4px; font-size: 13px;" id="fileName"></p>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()" style="border-radius: 25px; font-size: 12px; padding: 6px 12px;">
                                    <i class="fas fa-times me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="bulk_upload" value="true" class="btn btn-primary" style="border-radius: 25px; padding: 12px 32px;">
                        <i class="fas fa-upload me-2"></i> Upload Performance Records
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Record Performance</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label">Term</label>
                        <select class="form-control" id="term">
                            <?php foreach($terms as $term): ?>
                                <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $current_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" id="year" value="2026">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Exam Type</label>
                        <select class="form-control" id="examTypeId" required>
                            <option value="">Select Exam Type</option>
                            <?php foreach ($exam_types as $exam_type): ?>
                                <option value="<?php echo $exam_type['id']; ?>">
                                    <?php echo htmlspecialchars($exam_type['exam_type_name']); ?> (<?php echo htmlspecialchars($exam_type['exam_type_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Class</label>
                        <?php if ($teacher && $teacher['teacher_type'] === 'class_teacher'): ?>
                            <input type="text" class="form-control" id="classDisplay" value="<?php echo htmlspecialchars($class_name); ?>" readonly>
                            <input type="hidden" id="classId" value="<?php echo $class_id; ?>">
                        <?php else: ?>
                            <select class="form-control" id="classId" onchange="updateSubject()">
                                <option value="">Select Class</option>
                                <?php foreach ($subject_assignments as $assignment): ?>
                                    <option value="<?php echo $assignment['class_id']; ?>" data-subject="<?php echo htmlspecialchars($assignment['subject_name'] ?? $assignment['subject']); ?>" data-subject-id="<?php echo $assignment['subject_id'] ?? ''; ?>">
                                        <?php echo htmlspecialchars($assignment['class_name']); ?> - <?php echo htmlspecialchars($assignment['subject_name'] ?? $assignment['subject']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="subject" value="">
                            <input type="hidden" id="subjectId" value="">
                            <?php if (!empty($subject_assignments)): ?>
                                <script>
                                    // Auto-fill bulk upload fields with first assignment for subject teachers
                                    document.getElementById('bulkSubject').value = '<?php echo htmlspecialchars($subject_assignments[0]['subject_name'] ?? $subject_assignments[0]['subject']); ?>';
                                    document.getElementById('bulkStreamId').value = ''; // Let teacher select stream
                                </script>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Subject</label>
                        <?php if ($teacher && $teacher['teacher_type'] === 'class_teacher'): ?>
                            <select class="form-control" id="subjectInput" onchange="updateSubjectFromDropdown()">
                                <option value="">Select Subject</option>
                                <?php foreach ($all_subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <script>
                                // Class teachers select from school subjects
                                function updateSubjectFromDropdown() {
                                    const subject = document.getElementById('subjectInput').value;
                                    document.getElementById('subject').value = subject;
                                    document.getElementById('bulkSubject').value = subject;
                                }
                            </script>
                        <?php else: ?>
                            <input type="text" class="form-control" id="subjectInput" readonly placeholder="Auto-filled from class selection">
                        <?php endif; ?>
                        <input type="hidden" id="subject" value="">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stream</label>
                        <select class="form-control" id="streamId">
                            <option value="">All Streams</option>
                            <?php foreach ($streams as $stream): ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['stream_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="loadStudentsForPerformance()">
                            <i class="fas fa-search me-2"></i> Load
                        </button>
                    </div>
                </div>
                
                <div id="performanceSection" style="display: none;">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subjectDisplay" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grading System</label>
                            <input type="text" class="form-control" id="gradingSystem" readonly>
                        </div>
                    </div>
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table" style="min-width: 600px;">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Exam Type</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Points</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="performanceTable">
                                <tr><td colspan="7" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success" onclick="savePerformance()">
                            <i class="fas fa-save me-2"></i> Save Performance
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Performance Records</span>
                <button class="btn btn-sm btn-info" onclick="exportPerformance()" style="border-radius: 25px;">
                    <i class="fas fa-download me-1"></i> Export CSV
                </button>
            </div>
            <div class="card-body">
                <!-- Performance Statistics -->
                <div id="performanceStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px;">
                    <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #1967d2;" id="totalRecords">0</div>
                        <div style="font-size: 12px; color: #5f6368;">Total Records</div>
                    </div>
                    <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #137333;" id="averageScore">0</div>
                        <div style="font-size: 12px; color: #5f6368;">Average Score</div>
                    </div>
                    <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #c5221f;" id="highestScore">0</div>
                        <div style="font-size: 12px; color: #5f6368;">Highest Score</div>
                    </div>
                    <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #b06000;" id="lowestScore">0</div>
                        <div style="font-size: 12px; color: #5f6368;">Lowest Score</div>
                    </div>
                    <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #5f6368;" id="passRate">0%</div>
                        <div style="font-size: 12px; color: #5f6368;">Pass Rate (50%+)</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchPerformance" placeholder="Search by name or admission number">
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterClass">
                            <option value="">All Classes</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterStream">
                            <option value="">All Streams</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterTerm">
                            <option value="">All Terms</option>
                            <?php foreach($terms as $term): ?>
                                <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $current_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterYear">
                            <option value="">All Years</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterSubject">
                            <option value="">All Subjects</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" id="filterExamType">
                            <option value="">All Exam Types</option>
                            <?php foreach ($exam_types as $exam_type): ?>
                                <option value="<?php echo $exam_type['id']; ?>">
                                    <?php echo htmlspecialchars($exam_type['exam_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button class="btn btn-primary" onclick="filterPerformanceRecords()" style="border-radius: 25px;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilters()" style="border-radius: 25px;">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>

                <?php if (!empty($subjects_without_performance)): ?>
                <div class="card mb-4" style="background: #fff3e0; border: 2px solid #FF6B35;">
                    <div class="card-header" style="background: #FF6B35; color: white; border: none;">
                        <h5 style="margin: 0; font-size: 16px; font-weight: 500;">
                            <i class="fas fa-exclamation-triangle me-2"></i> Subjects Without Performance Records
                        </h5>
                    </div>
                    <div class="card-body">
                        <p style="margin-bottom: 12px; color: #5f6368; font-size: 13px;">The following subjects have no performance records recorded yet:</p>
                        <div class="table-responsive">
                            <table class="table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="background: #fff3e0; border: 1px solid #FF6B35; color: #000;">Subject Name</th>
                                        <th style="background: #fff3e0; border: 1px solid #FF6B35; color: #000;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects_without_performance as $subject): ?>
                                        <tr>
                                            <td style="border: 1px solid #FF6B35; font-weight: 500;">
                                                <i class="fas fa-book me-2" style="color: #FF6B35;"></i>
                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                            </td>
                                            <td style="border: 1px solid #FF6B35;">
                                                <span style="background: #fce8e6; color: #c5221f; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                                    No Records
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div id="performanceTableContainer">
                    <p class="text-center">Loading performance records...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Details Modal - Google Material Design Style -->
    <div class="modal fade" id="performanceDetailsModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Performance Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <div id="performanceDetailsContent">
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('=== PERFORMANCE PAGE LOADED ===');
        console.log('Grading scales loaded from PHP:', <?php echo json_encode($grading_scales); ?>);
        console.log('Number of grading scales:', <?php echo count($grading_scales); ?>);
        console.log('First grading scale sample:', <?php echo !empty($grading_scales) ? json_encode($grading_scales[0]) : 'null'; ?>);
        
        // Auto-load performance records after bulk upload
        <?php if (isset($_SESSION['bulk_upload_success']) && $_SESSION['bulk_upload_success']): ?>
            console.log('Auto-loading imported performance records...');
            document.getElementById('term').value = '<?php echo $_SESSION['bulk_upload_term'] ?? ''; ?>';
            document.getElementById('year').value = '<?php echo $_SESSION['bulk_upload_year'] ?? ''; ?>';
            document.getElementById('subject').value = '<?php echo $_SESSION['bulk_upload_subject'] ?? ''; ?>';
            document.getElementById('streamId').value = '<?php echo $_SESSION['bulk_upload_stream_id'] ?? ''; ?>';
            <?php if (isset($_SESSION['bulk_upload_class_id']) && $_SESSION['bulk_upload_class_id']): ?>
                document.getElementById('classId').value = '<?php echo $_SESSION['bulk_upload_class_id']; ?>';
            <?php endif; ?>
            
            // Clear session variables
            <?php 
            unset($_SESSION['bulk_upload_success']);
            unset($_SESSION['bulk_upload_term']);
            unset($_SESSION['bulk_upload_year']);
            unset($_SESSION['bulk_upload_subject']);
            unset($_SESSION['bulk_upload_stream_id']);
            unset($_SESSION['bulk_upload_class_id']);
            ?>
            
            // Auto-load students
            setTimeout(() => {
                loadStudentsForPerformance();
            }, 500);
        <?php endif; ?>
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        // Update subject when class is selected (for subject teachers)
        function updateSubject() {
            const classSelect = document.getElementById('classId');
            const selectedOption = classSelect.options[classSelect.selectedIndex];
            const subject = selectedOption.getAttribute('data-subject') || '';
            const subjectId = selectedOption.getAttribute('data-subject-id') || '';
            document.getElementById('subject').value = subject;
            document.getElementById('subjectId').value = subjectId;
            document.getElementById('subjectInput').value = subject;
            
            // Auto-fill bulk upload fields
            document.getElementById('bulkSubject').value = subject;
        }
        
        // Download performance template
        function downloadPerformanceTemplate() {
            const streamId = document.getElementById('bulkStreamId').value;
            if (!streamId) {
                alert('Please select a stream first to generate the template with student admission numbers');
                return;
            }
            window.location.href = 'performance?download_template=true&stream_id=' + streamId;
        }
        
        // Load students for performance
        async function loadStudentsForPerformance() {
            const term = document.getElementById('term').value;
            const year = document.getElementById('year').value;
            const classId = document.getElementById('classId').value;
            const streamId = document.getElementById('streamId').value;
            const subject = document.getElementById('subject').value;
            
            if (!term || !year) {
                alert('Please fill term and year');
                return;
            }
            
            // For bulk upload, we might only have streamId, not classId
            if (!classId && !streamId) {
                alert('Please select a class or stream');
                return;
            }
            
            try {
                let url = '../schools/api/students.php?';
                if (classId) {
                    url += `class_id=${classId}`;
                }
                if (streamId) {
                    url += (classId ? '&' : '') + `stream_id=${streamId}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    // Fetch performance records for these students
                    const perfResponse = await fetch(`../schools/api/performance.php?term=${term}&year=${year}&subject=${subject}`);
                    const perfData = await perfResponse.json();
                    
                    const performanceRecords = perfData.success ? perfData.data : [];
                    
                    const tbody = document.getElementById('performanceTable');
                    const examTypeId = document.getElementById('examTypeId').value;
                    const examTypeSelect = document.getElementById('examTypeId');
                    const examTypeName = examTypeSelect.options[examTypeSelect.selectedIndex]?.text || '';
                    
                    tbody.innerHTML = data.data.map(student => {
                        // Find existing performance record for this student
                        const existingRecord = performanceRecords.find(r => r.student_id === student.id);
                        
                        return `
                        <tr data-student-id="${student.id}">
                            <td>${student.admission_number}</td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${examTypeName}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm marks-input"
                                       min="0" max="100" placeholder="0-100"
                                       value="${existingRecord ? existingRecord.marks : ''}"
                                       onchange="calculateGrade(this)">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm grade-input" readonly
                                       value="${existingRecord ? existingRecord.grade : ''}">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm points-input" readonly
                                       value="${existingRecord && existingRecord.grade_points ? existingRecord.grade_points : ''}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm remarks-input" placeholder="Optional"
                                       value="${existingRecord ? existingRecord.remarks : ''}">
                            </td>
                        </tr>
                    `}).join('');
                    document.getElementById('performanceSection').style.display = 'block';
                    document.getElementById('subjectDisplay').value = subject;
                    
                    // Show which grading system is being used
                    const gradingScales = <?php echo json_encode($grading_scales); ?>;
                    
                    console.log('=== GRADING SYSTEM DEBUG (JS) ===');
                    console.log('Available grading scales:', gradingScales);
                    console.log('Number of grading scales:', gradingScales.length);
                    console.log('Performance records loaded:', performanceRecords.length);
                    
                    // Calculate stream average from existing records
                    let totalMarks = 0;
                    let studentCount = 0;
                    performanceRecords.forEach(record => {
                        const marks = parseFloat(record.marks) || 0;
                        totalMarks += marks;
                        studentCount++;
                    });
                    const streamAverage = studentCount > 0 ? (totalMarks / studentCount).toFixed(2) : 0;
                    
                    // Find grade for stream average
                    let streamGrade = 'No match';
                    for (const scale of gradingScales) {
                        if (parseFloat(streamAverage) >= scale.min_score && parseFloat(streamAverage) <= scale.max_score) {
                            streamGrade = scale.grade_name;
                            break;
                        }
                    }
                    
                    // Just show the grading scales directly for now
                    if (gradingScales.length > 0) {
                        document.getElementById('gradingSystem').value = 'School grading system loaded (' + gradingScales.length + ' grade ranges) | Stream Average: ' + streamAverage + ' (' + streamGrade + ')';
                        document.getElementById('gradingSystem').style.color = 'green';
                        console.log('SUCCESS: Grading system available');
                        console.log('Stream Average:', streamAverage, 'Grade:', streamGrade);
                    } else {
                        document.getElementById('gradingSystem').value = 'ERROR: No grading system defined';
                        document.getElementById('gradingSystem').style.color = 'red';
                        document.getElementById('gradingSystem').style.fontWeight = 'bold';
                        console.log('ERROR: No grading system found');
                    }
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }
        
        function calculateGrade(input) {
            const marks = parseFloat(input.value);
            
            // Use grading scales from database
            const gradingScales = <?php echo json_encode($grading_scales); ?>;
            
            let grade = '';
            let gradeDescription = '';
            let points = 0;
            
            console.log('Calculating grade for marks:', marks);
            console.log('Available grading scales:', gradingScales.length);
            
            // Find the matching grade range
            for (const scale of gradingScales) {
                if (marks >= scale.min_score && marks <= scale.max_score) {
                    grade = scale.grade_name;
                    gradeDescription = scale.grade_description || '';
                    points = scale.points || 0;
                    console.log('Matched grade:', grade, 'for range:', scale.min_score, '-', scale.max_score, 'Description:', gradeDescription, 'Points:', points);
                    break;
                }
            }
            
            // If no grading scales found or no match, show error
            if (!grade) {
                console.log('No grading scale match');
                grade = 'No match';
                points = 0;
            }
            
            const row = input.closest('tr');
            row.querySelector('.grade-input').value = grade;
            
            // Update points field
            const pointsInput = row.querySelector('.points-input');
            if (pointsInput) {
                pointsInput.value = points;
            }
            
            // Auto-fill remarks with grade description if remarks field is empty
            const remarksInput = row.querySelector('.remarks-input');
            if (remarksInput && !remarksInput.value && gradeDescription) {
                remarksInput.value = gradeDescription;
            }
        }
        
        async function savePerformance() {
            const term = document.getElementById('term').value;
            const year = document.getElementById('year').value;
            const examTypeId = document.getElementById('examTypeId').value;
            const subject = document.getElementById('subject').value;
            const rows = document.querySelectorAll('#performanceTable tr[data-student-id]');
            const performanceData = [];
            
            if (!subject) {
                alert('Please enter subject name');
                return;
            }
            
            if (!examTypeId) {
                alert('Please select an exam type');
                return;
            }
            
            rows.forEach(row => {
                const studentId = row.dataset.studentId;
                const marks = row.querySelector('.marks-input').value;
                const grade = row.querySelector('.grade-input').value;
                const remarks = row.querySelector('.remarks-input').value;
                
                if (marks !== '') {
                    performanceData.push({
                        student_id: studentId,
                        term: term,
                        year: year,
                        exam_type_id: examTypeId,
                        subject: subject,
                        marks: marks,
                        grade: grade,
                        remarks: remarks
                    });
                }
            });
            
            if (performanceData.length === 0) {
                alert('Please enter marks for at least one student');
                return;
            }
            
            try {
                const response = await fetch('../schools/api/performance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ performance: performanceData })
                });
                const data = await response.json();
                console.log('Save performance response:', data);
                
                if (data.success) {
                    alert('Performance saved successfully! Records saved: ' + (data.count || 'unknown'));
                    document.getElementById('performanceSection').style.display = 'none';
                    // Clear the performance table
                    document.getElementById('performanceTable').innerHTML = '';
                    // Load performance records
                    await loadPerformanceRecords();
                } else {
                    alert('Failed to save performance: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        let allPerformanceRecords = [];
        
        async function loadPerformanceRecords() {
            try {
                const response = await fetch('../schools/api/performance.php');
                const data = await response.json();
                console.log('Performance records response:', data);

                if (data.success && data.data.length > 0) {
                    allPerformanceRecords = data.data;
                    populateClassFilter(data.data);
                    populateStreamFilter(data.data);
                    populateSubjectFilter(data.data);
                    filterPerformanceRecords();
                } else {
                    document.getElementById('performanceTableContainer').innerHTML = '<p class="text-center">No performance records found</p>';
                }
            } catch (error) {
                console.error('Error loading performance records:', error);
                document.getElementById('performanceTableContainer').innerHTML = '<p class="text-center">Error loading performance records</p>';
            }
        }
        
        function populateSubjectFilter(records) {
            const subjects = [...new Set(records.map(r => r.subject))];
            const subjectSelect = document.getElementById('filterSubject');
            subjectSelect.innerHTML = '<option value="">All Subjects</option>';
            subjects.forEach(subject => {
                subjectSelect.innerHTML += `<option value="${subject}">${subject}</option>`;
            });
        }
        
        function populateClassFilter(records) {
            const classes = [...new Set(records.map(r => r.class_name).filter(c => c))];
            const classSelect = document.getElementById('filterClass');
            classSelect.innerHTML = '<option value="">All Classes</option>';
            classes.forEach(className => {
                classSelect.innerHTML += `<option value="${className}">${className}</option>`;
            });
        }
        
        function populateStreamFilter(records) {
            const streams = [...new Set(records.map(r => r.stream_name).filter(s => s))];
            const streamSelect = document.getElementById('filterStream');
            streamSelect.innerHTML = '<option value="">All Streams</option>';
            streams.forEach(streamName => {
                streamSelect.innerHTML += `<option value="${streamName}">${streamName}</option>`;
            });
        }
        
        function filterPerformanceRecords() {
            const searchTerm = document.getElementById('searchPerformance').value.toLowerCase();
            const classFilter = document.getElementById('filterClass').value;
            const streamFilter = document.getElementById('filterStream').value;
            const termFilter = document.getElementById('filterTerm').value;
            const yearFilter = document.getElementById('filterYear').value;
            const subjectFilter = document.getElementById('filterSubject').value;
            const examTypeFilter = document.getElementById('filterExamType').value;

            const filtered = allPerformanceRecords.filter(record => {
                const matchesSearch = !searchTerm ||
                    record.admission_number.toLowerCase().includes(searchTerm) ||
                    `${record.first_name} ${record.last_name}`.toLowerCase().includes(searchTerm);

                const matchesClass = !classFilter || record.class_name === classFilter;
                const matchesStream = !streamFilter || record.stream_name === streamFilter;
                const matchesTerm = !termFilter || record.term === termFilter;
                const matchesYear = !yearFilter || record.year == yearFilter;
                const matchesSubject = !subjectFilter || record.subject === subjectFilter;
                const matchesExamType = !examTypeFilter || record.exam_type_id == examTypeFilter;

                return matchesSearch && matchesClass && matchesStream && matchesTerm && matchesYear && matchesSubject && matchesExamType;
            });
            
            // Update statistics
            updatePerformanceStats(filtered);

            const container = document.getElementById('performanceTableContainer');

            if (filtered.length > 0) {
                // Group by subject if no subject filter is applied
                if (!subjectFilter) {
                    const groupedBySubject = {};
                    filtered.forEach(record => {
                        const subjectName = record.subject || 'No Subject';
                        if (!groupedBySubject[subjectName]) {
                            groupedBySubject[subjectName] = [];
                        }
                        groupedBySubject[subjectName].push(record);
                    });

                    // Sort subjects alphabetically
                    const sortedSubjects = Object.keys(groupedBySubject).sort();

                    let html = '';
                    sortedSubjects.forEach(subjectName => {
                        html += `
                            <div style="margin-bottom: 30px;">
                                <h4 style="color: #FF6B35; margin-bottom: 15px; font-weight: 600;">
                                    <i class="fas fa-book"></i> ${subjectName}
                                </h4>
                                <div class="table-responsive" style="overflow-x: auto;">
                                    <table class="table" style="min-width: 800px;">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Admission No</th>
                                                <th>Student Name</th>
                                                <th>Class</th>
                                                <th>Stream</th>
                                                <th>Term</th>
                                                <th>Year</th>
                                                <th>Exam Type</th>
                                                <th>Marks</th>
                                                <th>Grade</th>
                                                <th>Points</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;

                        // Sort records by marks descending for ranking
                        const sortedRecords = [...groupedBySubject[subjectName]].sort((a, b) => b.marks - a.marks);

                        sortedRecords.forEach((record, index) => {
                            html += `
                                <tr>
                                    <td><strong>#${index + 1}</strong></td>
                                    <td>${record.admission_number}</td>
                                    <td>${record.first_name} ${record.last_name}</td>
                                    <td>${record.class_name || '-'}</td>
                                    <td>${record.stream_name || '-'}</td>
                                    <td>${record.term}</td>
                                    <td>${record.year}</td>
                                    <td>${record.exam_type_name || '-'}</td>
                                    <td>${record.marks}</td>
                                    <td>${record.grade || '-'}</td>
                                    <td><strong>${record.grade_points || '-'}</strong></td>
                                    <td>${record.remarks || '-'}</td>
                                </tr>
                            `;
                        });

                        html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    // Show single table when specific subject is selected
                    // Sort by marks descending for ranking
                    const sortedRecords = [...filtered].sort((a, b) => b.marks - a.marks);

                    container.innerHTML = `
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table" style="min-width: 800px;">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Stream</th>
                                        <th>Term</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Points</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${sortedRecords.map((record, index) => `
                                        <tr>
                                            <td><strong>#${index + 1}</strong></td>
                                            <td>${record.admission_number}</td>
                                            <td>${record.first_name} ${record.last_name}</td>
                                            <td>${record.class_name || '-'}</td>
                                            <td>${record.stream_name || '-'}</td>
                                            <td>${record.term}</td>
                                            <td>${record.year}</td>
                                            <td>${record.subject}</td>
                                            <td>${record.exam_type_name || '-'}</td>
                                            <td>${record.marks}</td>
                                            <td>${record.grade || '-'}</td>
                                            <td><strong>${record.grade_points || '-'}</strong></td>
                                            <td>${record.remarks || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            } else {
                container.innerHTML = '<p class="text-center">No matching records found</p>';
            }
        }
        
        function resetFilters() {
            document.getElementById('searchPerformance').value = '';
            document.getElementById('filterClass').value = '';
            document.getElementById('filterStream').value = '';
            document.getElementById('filterTerm').value = '';
            document.getElementById('filterYear').value = '';
            document.getElementById('filterSubject').value = '';
            filterPerformanceRecords();
        }
        
        function updatePerformanceStats(records) {
            const total = records.length;
            const marks = records.map(r => parseFloat(r.marks) || 0);
            const average = total > 0 ? (marks.reduce((a, b) => a + b, 0) / total).toFixed(1) : 0;
            const highest = total > 0 ? Math.max(...marks) : 0;
            const lowest = total > 0 ? Math.min(...marks) : 0;
            const passed = marks.filter(m => m >= 50).length;
            const passRate = total > 0 ? Math.round((passed / total) * 100) : 0;
            
            document.getElementById('totalRecords').textContent = total;
            document.getElementById('averageScore').textContent = average;
            document.getElementById('highestScore').textContent = highest;
            document.getElementById('lowestScore').textContent = lowest;
            document.getElementById('passRate').textContent = passRate + '%';
        }
        
        function exportPerformance() {
            if (allPerformanceRecords.length === 0) {
                alert('No performance data to export.');
                return;
            }
            
            // Get current filtered records
            const searchTerm = document.getElementById('searchPerformance').value.toLowerCase();
            const classFilter = document.getElementById('filterClass').value;
            const streamFilter = document.getElementById('filterStream').value;
            const termFilter = document.getElementById('filterTerm').value;
            const yearFilter = document.getElementById('filterYear').value;
            const subjectFilter = document.getElementById('filterSubject').value;
            
            const filtered = allPerformanceRecords.filter(record => {
                const matchesSearch = !searchTerm || 
                    record.admission_number.toLowerCase().includes(searchTerm) ||
                    `${record.first_name} ${record.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesClass = !classFilter || record.class_name === classFilter;
                const matchesStream = !streamFilter || record.stream_name === streamFilter;
                const matchesTerm = !termFilter || record.term === termFilter;
                const matchesYear = !yearFilter || record.year == yearFilter;
                const matchesSubject = !subjectFilter || record.subject === subjectFilter;
                
                return matchesSearch && matchesClass && matchesStream && matchesTerm && matchesYear && matchesSubject;
            });
            
            if (filtered.length === 0) {
                alert('No matching records to export.');
                return;
            }
            
            // Create CSV content
            let csvContent = 'Admission No,Student Name,Class,Stream,Term,Year,Subject,Marks,Grade,Points,Remarks\n';
            
            filtered.forEach(record => {
                const rowData = [
                    record.admission_number,
                    `${record.first_name} ${record.last_name}`,
                    record.class_name || '-',
                    record.stream_name || '-',
                    record.term,
                    record.year,
                    record.subject,
                    record.marks,
                    record.grade || '-',
                    record.grade_points || '-',
                    record.remarks || '-'
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
            link.setAttribute('download', `performance_records_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Add event listeners for real-time filtering
        document.addEventListener('DOMContentLoaded', function() {
            loadPerformanceRecords();
            
            document.getElementById('searchPerformance').addEventListener('input', filterPerformanceRecords);
            document.getElementById('filterClass').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterStream').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterTerm').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterYear').addEventListener('change', filterPerformanceRecords);
            document.getElementById('filterSubject').addEventListener('change', filterPerformanceRecords);
            
            // File upload drag and drop functionality
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const dropZoneContent = document.getElementById('dropZoneContent');
            const fileSelected = document.getElementById('fileSelected');
            const fileName = document.getElementById('fileName');
            
            // Click to browse
            dropZone.addEventListener('click', () => fileInput.click());
            
            // File input change
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    showSelectedFile(e.target.files[0].name);
                }
            });
            
            // Drag and drop events
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#FF6B35';
                dropZone.style.background = '#fff3e0';
            });
            
            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#dadce0';
                dropZone.style.background = '#f8f9fa';
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#dadce0';
                dropZone.style.background = '#f8f9fa';
                
                if (e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    if (file.name.endsWith('.csv')) {
                        fileInput.files = e.dataTransfer.files;
                        showSelectedFile(file.name);
                    } else {
                        alert('Please upload a CSV file');
                    }
                }
            });
        });
        
        function showSelectedFile(name) {
            document.getElementById('dropZoneContent').style.display = 'none';
            document.getElementById('fileSelected').style.display = 'block';
            document.getElementById('fileName').textContent = name;
        }
        
        function clearFile() {
            document.getElementById('fileInput').value = '';
            document.getElementById('dropZoneContent').style.display = 'block';
            document.getElementById('fileSelected').style.display = 'none';
        }
        
        // View performance details
        async function viewPerformanceDetails(recordId) {
            try {
                // Get all performance records and find the one with matching ID
                const response = await fetch('../schools/api/performance.php');
                const data = await response.json();
                
                if (data.success) {
                    const record = data.data.find(r => r.id === recordId);
                    if (record) {
                        // Calculate grade using grading system
                        const gradingScales = <?php echo json_encode($grading_scales); ?>;
                        const marks = parseFloat(record.marks);
                        let calculatedGrade = 'No match';
                        
                        for (const scale of gradingScales) {
                            if (marks >= scale.min_score && marks <= scale.max_score) {
                                calculatedGrade = scale.grade_name;
                                break;
                            }
                        }
                        
                        const content = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Student:</strong> ${record.first_name} ${record.last_name}</p>
                                    <p><strong>Admission Number:</strong> ${record.admission_number}</p>
                                    <p><strong>Class:</strong> ${record.class_name || '-'}</p>
                                    <p><strong>Stream:</strong> ${record.stream_name || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Term:</strong> ${record.term}</p>
                                    <p><strong>Year:</strong> ${record.year}</p>
                                    <p><strong>Subject:</strong> ${record.subject}</p>
                                    <p><strong>Marks:</strong> ${record.marks}</p>
                                    <p><strong>Grade:</strong> ${calculatedGrade}</p>
                                    <p><strong>Remarks:</strong> ${record.remarks || '-'}</p>
                                </div>
                            </div>
                        `;
                        document.getElementById('performanceDetailsContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('performanceDetailsModal')).show();
                    } else {
                        alert('Performance record not found');
                    }
                }
            } catch (error) {
                console.error('Error loading performance details:', error);
                alert('Failed to load performance details');
            }
        }
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
