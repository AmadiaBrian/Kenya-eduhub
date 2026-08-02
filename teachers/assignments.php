<?php
// Teacher Assignments Page
// Authentication is handled by index.php router

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$stream_id = $_SESSION['stream_id'] ?? null;
$class_name = $_SESSION['class_name'] ?? '';
$stream_name = $_SESSION['stream_name'] ?? '';

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get teacher details
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
        $stmt = $pdo->prepare("SELECT ts.*, c.class_name, sub.subject_name
                             FROM teacher_subjects ts
                             JOIN classes c ON ts.class_id = c.id
                             JOIN subjects sub ON ts.subject_id = sub.id
                             WHERE ts.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $subject_assignments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subject assignments: " . $e->getMessage());
    }
}

// Get classes for class teachers
$classes = [];
if ($teacher && $teacher['teacher_type'] === 'class_teacher' && $class_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $classes = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch classes: " . $e->getMessage());
    }
}

// Handle file upload - now handled by AJAX API

// Get existing assignments
$assignments = [];
try {
    $query = "SELECT a.*, c.class_name, s.subject_name, t.first_name, t.last_name
              FROM assignments a
              LEFT JOIN classes c ON a.class_id = c.id
              LEFT JOIN subjects s ON a.subject_id = s.id
              JOIN teachers t ON a.teacher_id = t.id
              WHERE a.school_id = ?";
    $params = [$school_id];
    
    // Filter by teacher's assigned classes/subjects
    if ($teacher['teacher_type'] === 'class_teacher' && $class_id) {
        $query .= " AND (a.class_id = ? OR a.class_id IS NULL)";
        $params[] = $class_id;
    } elseif ($teacher['teacher_type'] === 'subject_teacher' && !empty($subject_assignments)) {
        $subject_ids = array_column($subject_assignments, 'subject_id');
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        $query .= " AND (a.subject_id IN ($placeholders) OR a.subject_id IS NULL)";
        $params = array_merge($params, $subject_ids);
    }
    
    $query .= " ORDER BY a.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll();
    
    // Debug logging
    error_log("Fetched " . count($assignments) . " assignments");
    foreach ($assignments as $index => $assignment) {
        error_log("Assignment [$index] ID: {$assignment['id']}, Title: '{$assignment['title']}', File: '{$assignment['file_name']}'");
    }
    
    // Get download counts and details separately
    foreach ($assignments as $key => $assignment) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignment_downloads WHERE assignment_id = ?");
        $stmt->execute([$assignment['id']]);
        $assignments[$key]['download_count'] = $stmt->fetch()['count'];
        
        if ($assignments[$key]['download_count'] > 0) {
            $stmt = $pdo->prepare("SELECT ad.*, 
                                  CASE ad.user_type
                                      WHEN 'teacher' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM teachers WHERE id = ad.user_id)
                                      WHEN 'parent' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM parents WHERE id = ad.user_id)
                                      WHEN 'student' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM students WHERE id = ad.user_id)
                                      ELSE ad.user_name
                                  END as full_name
                                  FROM assignment_downloads ad
                                  WHERE ad.assignment_id = ?
                                  ORDER BY ad.download_date DESC LIMIT 10");
            $stmt->execute([$assignment['id']]);
            $assignments[$key]['downloads'] = $stmt->fetchAll();
        } else {
            $assignments[$key]['downloads'] = [];
        }
        
        // Get comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignment_comments WHERE assignment_id = ?");
        $stmt->execute([$assignment['id']]);
        $assignments[$key]['comment_count'] = $stmt->fetch()['count'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch assignments: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Assignments - <?php echo htmlspecialchars($teacher_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: #202124;
        }
        
        .header {
            background: white;
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
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
            border-radius: 25px;
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
            border-radius: 25px;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }
        
        .sidebar {
            position: fixed;
            top: 64px;
            left: 0;
            width: 256px;
            height: calc(100vh - 64px);
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
        
        .main-content {
            margin-left: 256px;
            margin-top: 64px;
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 24px;
        }
        
        .card {
            background: var(--bg-color);
            border-radius: 8px;
            box-shadow: none;
            padding: 24px;
            margin-bottom: 24px;
            border: none;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .file-upload-wrapper {
            position: relative;
            width: 100%;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 2px dashed #dadce0;
            border-radius: 25px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background: #fff5f0;
        }
        
        .file-upload-label i {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .file-upload-label .upload-text {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 4px;
        }
        
        .file-upload-label .upload-subtext {
            font-size: 12px;
            color: #9aa0a6;
        }
        
        .file-upload-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-selected {
            display: none;
            padding: 12px 16px;
            background: #e8f5e9;
            border: 1px solid #137333;
            border-radius: 25px;
            color: #137333;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .file-selected i {
            margin-right: 8px;
        }
        
        .btn {
            padding: 12px 32px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        /* Action Buttons in Cards */
        .action-btn-download {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #FF6B35, #ff8c00);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .action-btn-download:hover {
            box-shadow: none;
            transform: none;
        }
        
        .action-btn-edit,
        .action-btn-duplicate,
        .action-btn-delete,
        .action-btn-analytics,
        .action-btn-preview {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            padding: 0;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .action-btn-edit {
            background: #e8f0fe;
            color: #1967d2;
        }
        
        .action-btn-edit:hover {
            background: #d2e3fc;
            box-shadow: none;
        }
        
        .action-btn-duplicate {
            background: #e6f4ea;
            color: #137333;
        }
        
        .action-btn-duplicate:hover {
            background: #ceead6;
            box-shadow: none;
        }
        
        .action-btn-delete {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .action-btn-delete:hover {
            background: #fad2cf;
            box-shadow: none;
        }
        
        .action-btn-analytics {
            background: #f3e8fd;
            color: #7b1fa2;
        }
        
        .action-btn-analytics:hover {
            background: #e1d4f5;
            box-shadow: none;
        }
        
        .action-btn-preview {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .action-btn-preview:hover {
            background: #ffe0b2;
            box-shadow: none;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #5f6368;
            border-bottom: 2px solid #dadce0;
        }
        
        .table td {
            padding: 12px;
            font-size: 13px;
            color: #202124;
            border-bottom: 1px solid #dadce0;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-syllabus {
            background: #e8f0fe;
            color: #1a73e8;
        }
        
        .badge-sentiment {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-notes {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-holiday {
            background: #fef7e0;
            color: #b06000;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #137333;
            border: 1px solid #137333;
        }
        
        .alert-danger {
            background: #ffebee;
            color: #c5221f;
            border: 1px solid #c5221f;
        }
        
        /* Loading spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 16px;
            font-weight: 500;
            color: #5f6368;
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
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* Assignment Cards */
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .assignment-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            transition: none;
        }
        
        .assignment-card:hover {
            box-shadow: none;
            transform: none;
        }
        
        .assignment-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .assignment-title {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .assignment-description {
            font-size: 13px;
            color: #5f6368;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .assignment-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .assignment-meta-item {
            font-size: 12px;
            color: #5f6368;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .assignment-meta-item i {
            color: #FF6B35;
            font-size: 12px;
        }
        
        .assignment-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: none;
        }
        
        .assignment-uploader {
            font-size: 12px;
            color: #5f6368;
        }
        
        .assignment-date {
            font-size: 12px;
            color: #5f6368;
        }
        
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
            <a class="nav-link" href="student-subjects">
                <i class="fas fa-book"></i> Student Subjects
            </a>
            <a class="nav-link active" href="assignments">
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
        <h1 class="page-title">Assignments</h1>
        <p class="page-subtitle">
            Upload syllabus, sentiments, notes, and holiday assignments
        </p>
        
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
        
        <div class="card">
            <h2 class="card-title">Upload Assignment</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Assignment Type *</label>
                    <select class="form-control" name="assignment_type" required>
                        <option value="">Select type</option>
                        <option value="syllabus">Syllabus</option>
                        <option value="sentiment">Sentiment</option>
                        <option value="notes">Notes</option>
                        <option value="holiday">Holiday Assignment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" class="form-control" name="title" placeholder="Enter assignment title" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Enter assignment description"></textarea>
                </div>
                
                <?php if ($teacher['teacher_type'] === 'class_teacher' && !empty($classes)): ?>
                    <div class="form-group">
                        <label>Class</label>
                        <select class="form-control" name="class_id">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($teacher['teacher_type'] === 'subject_teacher' && !empty($subject_assignments)): ?>
                    <div class="form-group">
                        <label>Subject</label>
                        <select class="form-control" name="subject_id">
                            <option value="">All Subjects</option>
                            <?php foreach ($subject_assignments as $assignment): ?>
                                <option value="<?php echo $assignment['subject_id']; ?>">
                                    <?php echo htmlspecialchars($assignment['subject_name']); ?> (<?php echo htmlspecialchars($assignment['class_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" class="form-control" name="due_date">
                </div>
                
                <div class="form-group">
                    <label>File *</label>
                    <div class="file-upload-wrapper">
                        <div class="file-upload-label" id="fileUploadLabel">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="upload-text">Click to upload or drag and drop</div>
                                <div class="upload-subtext">PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, JPEG, PNG (Max 10MB)</div>
                            </div>
                            <input type="file" class="file-upload-input" name="file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png" onchange="handleFileSelect(this)">
                        </div>
                        <div class="file-selected" id="fileSelected">
                            <i class="fas fa-check-circle"></i>
                            <span id="fileName"></span>
                        </div>
                    </div>
                </div>
                
                <button type="button" onclick="uploadAssignment()" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Upload Assignment
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2 class="card-title">Recent Assignments</h2>
            
            <!-- Search and Filter -->
            <div style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 25px;">
                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px; display: block;">Search</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by title or description..." onkeyup="filterAssignments()">
                    </div>
                    <div style="min-width: 150px;">
                        <label style="font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px; display: block;">Type</label>
                        <select id="typeFilter" class="form-control" onchange="filterAssignments()">
                            <option value="">All Types</option>
                            <option value="syllabus">Syllabus</option>
                            <option value="sentiment">Sentiment</option>
                            <option value="notes">Notes</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                    <div style="min-width: 150px;">
                        <label style="font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px; display: block;">Sort By</label>
                        <select id="sortFilter" class="form-control" onchange="filterAssignments()">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="title">Title A-Z</option>
                            <option value="title_desc">Title Z-A</option>
                        </select>
                    </div>
                    <button onclick="resetFilters()" class="btn" style="background: white; border: 1px solid #dadce0; color: #5f6368; padding: 12px 24px; border-radius: 25px;">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div id="bulkActions" style="margin-bottom: 16px; padding: 12px 16px; background: #e8f0fe; border-radius: 25px; display: none; align-items: center; gap: 12px;">
                <span style="font-size: 14px; color: #1967d2; font-weight: 500;">
                    <span id="selectedCount">0</span> assignments selected
                </span>
                <button onclick="bulkDelete()" class="btn" style="background: #d93025; color: white; padding: 8px 20px; border-radius: 25px; font-size: 13px;">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <button onclick="clearSelection()" class="btn" style="background: white; border: 1px solid #dadce0; color: #5f6368; padding: 8px 20px; border-radius: 25px; font-size: 13px;">
                    Clear Selection
                </button>
            </div>
            
            <p style="color: #666; font-size: 12px; margin-bottom: 10px;">Total assignments: <span id="totalCount"><?php echo count($assignments); ?></span></p>
            <?php if (empty($assignments)): ?>
                <p class="text-muted">No assignments found.</p>
            <?php else: ?>
                <div class="assignments-grid" id="assignmentsGrid">
                    <?php 
                    $counter = 0;
                    foreach ($assignments as $assignment): 
                        $counter++;
                        ?>
                        <?php
                        // Debug: Log each assignment being displayed
                        error_log("Displaying assignment [$counter] ID: {$assignment['id']}, Title: '{$assignment['title']}'");
                        
                        $badge_class = 'badge-syllabus';
                        $badge_text = 'Syllabus';
                        if ($assignment['assignment_type'] === 'sentiment') {
                            $badge_class = 'badge-sentiment';
                            $badge_text = 'Sentiment';
                        } elseif ($assignment['assignment_type'] === 'notes') {
                            $badge_class = 'badge-notes';
                            $badge_text = 'Notes';
                        } elseif ($assignment['assignment_type'] === 'holiday') {
                            $badge_class = 'badge-holiday';
                            $badge_text = 'Holiday';
                        }
                        ?>
                        <div class="assignment-card" data-id="<?php echo $assignment['id']; ?>" data-title="<?php echo htmlspecialchars($assignment['title']); ?>" data-description="<?php echo htmlspecialchars($assignment['description']); ?>" data-type="<?php echo $assignment['assignment_type']; ?>" data-date="<?php echo $assignment['created_at']; ?>" data-due-date="<?php echo $assignment['due_date']; ?>" data-comment-count="<?php echo $assignment['comment_count'] ?? 0; ?>">
                            <div class="assignment-card-header">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <input type="checkbox" class="assignment-checkbox" value="<?php echo $assignment['id']; ?>" onchange="updateBulkActions()">
                                    <div>
                                        <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($assignment['description']): ?>
                                <p class="assignment-description"><?php echo htmlspecialchars($assignment['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="assignment-meta">
                                <?php if ($assignment['class_name']): ?>
                                    <div class="assignment-meta-item">
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($assignment['class_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($assignment['subject_name']): ?>
                                    <div class="assignment-meta-item">
                                        <i class="fas fa-book"></i>
                                        <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($assignment['due_date']): ?>
                                    <div class="assignment-meta-item">
                                        <i class="fas fa-calendar"></i>
                                        Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="assignment-footer">
                                <div class="assignment-uploader">
                                    <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                </div>
                                <div class="assignment-date">
                                    <?php echo date('M d, Y', strtotime($assignment['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div style="margin-top: 12px; display: flex; gap: 8px;">
                                <a href="../api/download_assignment.php?assignment_id=<?php echo $assignment['id']; ?>" class="action-btn-download" style="flex: 1;">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button onclick="previewAssignment(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars($assignment['file_name']); ?>')" class="action-btn-preview" title="Preview">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="viewAnalytics(<?php echo $assignment['id']; ?>)" class="action-btn-analytics" title="View Analytics">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                                <button onclick="editAssignment(<?php echo $assignment['id']; ?>)" class="action-btn-edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="duplicateAssignment(<?php echo $assignment['id']; ?>)" class="action-btn-duplicate" title="Duplicate">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button onclick="deleteAssignment(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars($assignment['title']); ?>')" class="action-btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <?php if (!empty($assignment['download_count']) && (int)$assignment['download_count'] > 0): ?>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e8eaed;">
                                    <div style="font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">
                                        <i class="fas fa-download" style="color: #FF6B35;"></i> Downloads: <?php echo (int)$assignment['download_count']; ?>
                                    </div>
                                    <?php if (!empty($assignment['downloads'])): ?>
                                        <div style="margin-bottom: 8px;">
                                            <?php 
                                            $display_downloads = array_slice($assignment['downloads'], 0, 3);
                                            foreach ($display_downloads as $download): ?>
                                                <div style="font-size: 11px; color: #5f6368; padding: 4px 0; border-bottom: 1px solid #f1f3f4;">
                                                    <span style="color: #FF6B35;"><?php echo htmlspecialchars($download['full_name']); ?></span>
                                                    <span style="color: #9aa0a6;">(<?php echo ucfirst($download['user_type']); ?>)</span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($assignment['downloads']) > 3): ?>
                                                <div style="font-size: 11px; color: #9aa0a6; padding: 4px 0;">
                                                    +<?php echo count($assignment['downloads']) - 3; ?> more
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <button onclick="viewAnalytics(<?php echo $assignment['id']; ?>)" style="background: none; border: none; color: #1967d2; font-size: 11px; cursor: pointer; padding: 0;">
                                            <i class="fas fa-chart-bar"></i> View all downloads
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-text">Uploading assignment...</div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal - Google Material Design Style -->
    <div class="modal-overlay" id="deleteModal" style="display: none; backdrop-filter: blur(2px);">
        <div class="custom-modal" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
            <div class="custom-modal-body" style="padding: 24px 32px;">
                <div class="custom-modal-message">
                    <p style="font-size: 22px; font-weight: 400; color: #202124; margin-bottom: 16px;">Delete Assignment</p>
                    <p style="font-size: 14px; color: #5f6368; margin-bottom: 8px;">Are you sure you want to delete "<span id="deleteAssignmentTitle" style="font-weight: 500;"></span>"?</p>
                    <p style="color: #9aa0a6; font-size: 14px;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="custom-modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                <button type="button" class="custom-modal-btn custom-modal-btn-secondary" onclick="closeDeleteModal()" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                <button type="button" class="custom-modal-btn custom-modal-btn-primary" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Assignment Modal - Google Material Design Style -->
    <div class="modal-overlay" id="editModal" style="display: none; backdrop-filter: blur(2px);">
        <div class="custom-modal" style="max-width: 500px; border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
            <div class="custom-modal-body" style="padding: 24px 32px;">
                <div class="custom-modal-message">
                    <p style="font-size: 22px; font-weight: 400; color: #202124; margin-bottom: 16px;">Edit Assignment</p>
                    <form id="editForm">
                        <input type="hidden" id="editAssignmentId">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label style="font-size: 14px; color: #5f6368; font-weight: 500; margin-bottom: 8px; display: block;">Title *</label>
                            <input type="text" id="editTitle" class="form-control" required style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label style="font-size: 14px; color: #5f6368; font-weight: 500; margin-bottom: 8px; display: block;">Description</label>
                            <textarea id="editDescription" class="form-control" rows="3" style="border-radius: 8px; border: 1px solid #dadce0;"></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label style="font-size: 14px; color: #5f6368; font-weight: 500; margin-bottom: 8px; display: block;">Due Date</label>
                            <input type="date" id="editDueDate" class="form-control" style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                    </form>
                </div>
            </div>
            <div class="custom-modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                <button type="button" class="custom-modal-btn custom-modal-btn-secondary" onclick="closeEditModal()" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                <button type="button" class="custom-modal-btn custom-modal-btn-primary" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;" onclick="saveEdit()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Duplicate Assignment Modal - Google Material Design Style -->
    <div class="modal-overlay" id="duplicateModal" style="display: none; backdrop-filter: blur(2px);">
        <div class="custom-modal" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
            <div class="custom-modal-body" style="padding: 24px 32px;">
                <div class="custom-modal-message">
                    <p style="font-size: 22px; font-weight: 400; color: #202124; margin-bottom: 16px;">Duplicate Assignment</p>
                    <p style="font-size: 14px; color: #5f6368; margin-bottom: 8px;">Do you want to duplicate "<span id="duplicateAssignmentTitle" style="font-weight: 500;"></span>"?</p>
                    <p style="color: #9aa0a6; font-size: 14px;">A copy will be created with "(Copy)" added to the title.</p>
                </div>
            </div>
            <div class="custom-modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                <button type="button" class="custom-modal-btn custom-modal-btn-secondary" onclick="closeDuplicateModal()" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                <button type="button" class="custom-modal-btn custom-modal-btn-primary" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;" onclick="confirmDuplicate()">Duplicate</button>
            </div>
        </div>
    </div>
    
    <!-- Analytics Modal - Google Material Design Style -->
    <div class="modal-overlay" id="analyticsModal" style="display: none; backdrop-filter: blur(2px);">
        <div class="custom-modal" style="max-width: 600px; border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
            <div class="custom-modal-body" style="padding: 24px 32px;">
                <div class="custom-modal-message">
                    <p style="font-size: 22px; font-weight: 400; color: #202124; margin-bottom: 16px;">Download Analytics</p>
                    <p style="font-size: 14px; color: #5f6368; margin-bottom: 12px;">"<span id="analyticsAssignmentTitle" style="font-weight: 500;"></span>"</p>
                    
                    <div id="analyticsContent">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
                            <div style="background: #f8f9fa; padding: 16px; border-radius: 12px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 500; color: #7b1fa2;" id="totalDownloads">0</div>
                                <div style="font-size: 12px; color: #5f6368; margin-top: 4px;">Total Downloads</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 16px; border-radius: 12px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 500; color: #1967d2;" id="uniqueDownloaders">0</div>
                                <div style="font-size: 12px; color: #5f6368; margin-top: 4px;">Unique Users</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 16px; border-radius: 12px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 500; color: #188038;" id="lastDownload">-</div>
                                <div style="font-size: 12px; color: #5f6368; margin-top: 4px;">Last Download</div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <h4 style="font-size: 14px; font-weight: 500; color: #202124; margin-bottom: 12px;">Download History</h4>
                            <div id="downloadHistory" style="max-height: 200px; overflow-y: auto;">
                                <!-- Download history will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="custom-modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                <button type="button" class="custom-modal-btn custom-modal-btn-secondary" onclick="closeAnalyticsModal()" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal - Google Material Design Style -->
    <div class="modal-overlay" id="previewModal" style="display: none; backdrop-filter: blur(2px);">
        <div class="custom-modal" style="max-width: 900px; max-height: 90vh; border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
            <div class="custom-modal-body" style="padding: 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 32px 0 32px; border-bottom: none;">
                    <p style="font-size: 22px; font-weight: 400; color: #202124;">Preview: <span id="previewFileName" style="font-weight: 400; font-size: 18px;"></span></p>
                    <button onclick="closePreviewModal()" style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 50%;">
                        <i class="fas fa-times" style="color: #5f6368; font-size: 18px;"></i>
                    </button>
                </div>
                <div id="previewContent" style="padding: 24px 32px; height: calc(90vh - 80px); overflow: auto;">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function handleFileSelect(input) {
            const fileUploadLabel = document.getElementById('fileUploadLabel');
            const fileSelected = document.getElementById('fileSelected');
            const fileName = document.getElementById('fileName');
            
            if (input.files && input.files[0]) {
                fileUploadLabel.style.display = 'none';
                fileSelected.style.display = 'block';
                fileName.textContent = input.files[0].name;
            } else {
                fileUploadLabel.style.display = 'flex';
                fileSelected.style.display = 'none';
            }
        }
        
        function filterAssignments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const sortFilter = document.getElementById('sortFilter').value;
            const cards = document.querySelectorAll('.assignment-card');
            let visibleCount = 0;
            
            // Filter
            cards.forEach(card => {
                const title = card.dataset.title.toLowerCase();
                const description = card.dataset.description.toLowerCase();
                const type = card.dataset.type;
                
                const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
                const matchesType = typeFilter === '' || type === typeFilter;
                
                if (matchesSearch && matchesType) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Sort
            const grid = document.getElementById('assignmentsGrid');
            const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
            
            visibleCards.sort((a, b) => {
                switch(sortFilter) {
                    case 'newest':
                        return new Date(b.dataset.date) - new Date(a.dataset.date);
                    case 'oldest':
                        return new Date(a.dataset.date) - new Date(b.dataset.date);
                    case 'title':
                        return a.dataset.title.localeCompare(b.dataset.title);
                    case 'title_desc':
                        return b.dataset.title.localeCompare(a.dataset.title);
                    default:
                        return 0;
                }
            });
            
            // Reorder in DOM
            visibleCards.forEach(card => grid.appendChild(card));
            
            // Update count
            document.getElementById('totalCount').textContent = visibleCount;
        }
        
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('typeFilter').value = '';
            document.getElementById('sortFilter').value = 'newest';
            filterAssignments();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.assignment-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (checkboxes.length > 0) {
                bulkActions.style.display = 'flex';
                selectedCount.textContent = checkboxes.length;
            } else {
                bulkActions.style.display = 'none';
            }
        }
        
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.assignment-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateBulkActions();
        }
        
        async function bulkDelete() {
            const checkboxes = document.querySelectorAll('.assignment-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) return;
            
            if (!confirm(`Are you sure you want to delete ${ids.length} assignment(s)? This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('api/delete_assignments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove deleted cards from DOM
                    checkboxes.forEach(cb => {
                        const card = cb.closest('.assignment-card');
                        card.style.display = 'none';
                    });
                    
                    // Clear selection
                    clearSelection();
                    
                    // Update count
                    const visibleCards = document.querySelectorAll('.assignment-card[style="display: block;"]');
                    document.getElementById('totalCount').textContent = visibleCards.length;
                    
                    // Show success message
                    showNotification('success', data.message || 'Assignments deleted successfully');
                } else {
                    showNotification('error', data.error || 'Failed to delete assignments');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'An error occurred while deleting assignments');
            }
        }
        
        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '10000';
            notification.style.padding = '15px 20px';
            notification.style.borderRadius = '25px';
            notification.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            notification.style.animation = 'slideIn 0.3s ease';
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        let duplicateAssignmentId = null;
        
        function duplicateAssignment(assignmentId) {
            const card = document.querySelector(`.assignment-card[data-id="${assignmentId}"]`);
            const title = card.dataset.title;
            
            duplicateAssignmentId = assignmentId;
            document.getElementById('duplicateAssignmentTitle').textContent = title;
            document.getElementById('duplicateModal').style.display = 'flex';
        }
        
        function closeDuplicateModal() {
            document.getElementById('duplicateModal').style.display = 'none';
            duplicateAssignmentId = null;
        }
        
        async function confirmDuplicate() {
            if (!duplicateAssignmentId) return;
            
            try {
                const response = await fetch('api/duplicate_assignment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ assignment_id: duplicateAssignmentId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', 'Assignment duplicated successfully');
                    closeDuplicateModal();
                    // Reload page to show new assignment
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('error', data.error || 'Failed to duplicate assignment');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'An error occurred while duplicating assignment');
            }
        }
        
        async function viewAnalytics(assignmentId) {
            const card = document.querySelector(`.assignment-card[data-id="${assignmentId}"]`);
            const title = card.dataset.title;
            
            document.getElementById('analyticsAssignmentTitle').textContent = title;
            document.getElementById('analyticsModal').style.display = 'flex';
            
            // Load analytics data
            try {
                const response = await fetch(`api/get_assignment_analytics.php?assignment_id=${assignmentId}`);
                console.log('Analytics response status:', response.status);
                const data = await response.json();
                console.log('Analytics data:', data);
                
                if (data.success) {
                    document.getElementById('totalDownloads').textContent = data.total_downloads;
                    document.getElementById('uniqueDownloaders').textContent = data.unique_downloaders;
                    document.getElementById('lastDownload').textContent = data.last_download || '-';
                    
                    const historyHtml = data.downloads.map(download => `
                        <div style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #e8eaed;">
                            <div style="width: 32px; height: 32px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; color: #1967d2; font-size: 12px; font-weight: 500;">
                                ${download.full_name.charAt(0).toUpperCase()}
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 500; color: #202124;">${download.full_name}</div>
                                <div style="font-size: 11px; color: #5f6368;">${download.user_type} - ${download.download_date}</div>
                            </div>
                        </div>
                    `).join('');
                    
                    document.getElementById('downloadHistory').innerHTML = historyHtml || '<p style="color: #5f6368; font-size: 13px; text-align: center; padding: 20px;">No downloads yet</p>';
                } else {
                    console.error('Analytics error:', data.error);
                    document.getElementById('downloadHistory').innerHTML = `<p style="color: #c5221f; font-size: 13px; text-align: center; padding: 20px;">${data.error || 'Failed to load analytics'}</p>`;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('downloadHistory').innerHTML = '<p style="color: #c5221f; font-size: 13px; text-align: center; padding: 20px;">Error loading analytics</p>';
            }
        }
        
        function closeAnalyticsModal() {
            document.getElementById('analyticsModal').style.display = 'none';
        }
        
        function previewAssignment(assignmentId, fileName) {
            document.getElementById('previewFileName').textContent = fileName;
            document.getElementById('previewModal').style.display = 'flex';
            
            const fileExt = fileName.split('.').pop().toLowerCase();
            const previewContent = document.getElementById('previewContent');
            previewContent.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #5f6368;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                // Image preview
                const img = new Image();
                img.onload = function() {
                    previewContent.innerHTML = `<img src="api/get_assignment_file.php?assignment_id=${assignmentId}" style="max-width: 100%; height: auto; border-radius: 8px;">`;
                };
                img.onerror = function() {
                    previewContent.innerHTML = '<p style="color: #c5221f; text-align: center;">Failed to load image</p>';
                };
                img.src = `api/get_assignment_file.php?assignment_id=${assignmentId}`;
            } else if (fileExt === 'pdf') {
                // PDF preview - use object tag for better compatibility
                previewContent.innerHTML = `
                    <object data="api/get_assignment_file.php?assignment_id=${assignmentId}" type="application/pdf" style="width: 100%; height: 100%; border: none; border-radius: 8px;">
                        <p style="color: #5f6368; text-align: center; padding: 40px;">
                            Unable to display PDF inline. <a href="../api/download_assignment.php?assignment_id=${assignmentId}" style="color: #1967d2;">Click here to download</a>
                        </p>
                    </object>
                `;
            } else if (['txt'].includes(fileExt)) {
                // Text file preview
                fetch(`api/get_assignment_file.php?assignment_id=${assignmentId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to load');
                        return response.text();
                    })
                    .then(text => {
                        previewContent.innerHTML = `<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 13px; color: #202124;">${text}</pre>`;
                    })
                    .catch(() => {
                        previewContent.innerHTML = '<p style="color: #c5221f; text-align: center;">Failed to load preview</p>';
                    });
            } else {
                previewContent.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-file" style="font-size: 48px; color: #dadce0; margin-bottom: 16px;"></i>
                        <p style="color: #5f6368; margin-bottom: 12px;">Preview not available for .${fileExt} files</p>
                        <a href="../api/download_assignment.php?assignment_id=${assignmentId}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-download"></i> Download to View
                        </a>
                    </div>
                `;
            }
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
            document.getElementById('previewContent').innerHTML = '';
        }
        
        function editAssignment(assignmentId) {
            const card = document.querySelector(`.assignment-card[data-id="${assignmentId}"]`);
            const title = card.dataset.title;
            const description = card.dataset.description;
            const dueDate = card.dataset.dueDate || '';
            
            document.getElementById('editAssignmentId').value = assignmentId;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;
            document.getElementById('editDueDate').value = dueDate;
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        async function saveEdit() {
            const assignmentId = document.getElementById('editAssignmentId').value;
            const title = document.getElementById('editTitle').value;
            const description = document.getElementById('editDescription').value;
            const dueDate = document.getElementById('editDueDate').value;
            
            if (!title) {
                showNotification('error', 'Title is required');
                return;
            }
            
            try {
                const response = await fetch('api/update_assignment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        assignment_id: assignmentId,
                        title: title,
                        description: description,
                        due_date: dueDate
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', 'Assignment updated successfully');
                    closeEditModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('error', data.error || 'Failed to update assignment');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'An error occurred while updating assignment');
            }
        }
        
        async function uploadAssignment() {
            const form = document.getElementById('uploadForm');
            const formData = new FormData(form);
            
            // Show loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = 'flex';
            
            try {
                const response = await fetch('api/upload_assignment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                loadingOverlay.style.display = 'none';
                
                if (data.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'alert alert-success';
                    successDiv.style.position = 'fixed';
                    successDiv.style.top = '20px';
                    successDiv.style.right = '20px';
                    successDiv.style.zIndex = '10000';
                    successDiv.style.padding = '15px 20px';
                    successDiv.style.borderRadius = '8px';
                    successDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                    successDiv.style.animation = 'slideIn 0.3s ease';
                    successDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                    
                    document.body.appendChild(successDiv);
                    
                    // Reset form including file input
                    form.reset();
                    const fileInput = form.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.value = '';
                    }
                    
                    // Remove success message after 3 seconds and reload
                    setTimeout(() => {
                        successDiv.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => {
                            successDiv.remove();
                            location.reload();
                        }, 300);
                    }, 3000);
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.style.position = 'fixed';
                    errorDiv.style.top = '20px';
                    errorDiv.style.right = '20px';
                    errorDiv.style.zIndex = '10000';
                    errorDiv.style.padding = '15px 20px';
                    errorDiv.style.borderRadius = '8px';
                    errorDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                    errorDiv.style.animation = 'slideIn 0.3s ease';
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + data.error;
                    
                    document.body.appendChild(errorDiv);
                    
                    // Remove error message after 3 seconds
                    setTimeout(() => {
                        errorDiv.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => {
                            errorDiv.remove();
                        }, 300);
                    }, 3000);
                }
            } catch (error) {
                loadingOverlay.style.display = 'none';
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.style.position = 'fixed';
                errorDiv.style.top = '20px';
                errorDiv.style.right = '20px';
                errorDiv.style.zIndex = '10000';
                errorDiv.style.padding = '15px 20px';
                errorDiv.style.borderRadius = '8px';
                errorDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                errorDiv.style.animation = 'slideIn 0.3s ease';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error uploading assignment: ' + error.message;
                
                document.body.appendChild(errorDiv);
                
                // Remove error message after 3 seconds
                setTimeout(() => {
                    errorDiv.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        errorDiv.remove();
                    }, 300);
                }, 3000);
            }
        }
        
        let assignmentToDelete = null;
        
        function deleteAssignment(assignmentId, assignmentTitle) {
            assignmentToDelete = assignmentId;
            document.getElementById('deleteAssignmentTitle').textContent = assignmentTitle;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            assignmentToDelete = null;
        }
        
        async function confirmDelete() {
            if (!assignmentToDelete) return;
            
            try {
                const formData = new FormData();
                formData.append('assignment_id', assignmentToDelete);
                
                const response = await fetch('api/delete_assignment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Close modal
                closeDeleteModal();
                
                if (data.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'toast toast-success';
                    successDiv.innerHTML = `
                        <div class="toast-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="toast-content">
                            <div class="toast-title">Success</div>
                            <div class="toast-message">${data.message}</div>
                        </div>
                    `;
                    
                    document.body.appendChild(successDiv);
                    
                    // Remove success message after 3 seconds and reload
                    setTimeout(() => {
                        successDiv.classList.add('hide');
                        setTimeout(() => {
                            successDiv.remove();
                            location.reload();
                        }, 300);
                    }, 3000);
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'toast toast-error';
                    errorDiv.innerHTML = `
                        <div class="toast-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="toast-content">
                            <div class="toast-title">Error</div>
                            <div class="toast-message">${data.error}</div>
                        </div>
                    `;
                    
                    document.body.appendChild(errorDiv);
                    
                    // Remove error message after 3 seconds
                    setTimeout(() => {
                        errorDiv.classList.add('hide');
                        setTimeout(() => {
                            errorDiv.remove();
                        }, 300);
                    }, 3000);
                }
            } catch (error) {
                // Close modal
                closeDeleteModal();
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'toast toast-error';
                errorDiv.innerHTML = `
                    <div class="toast-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">Error</div>
                        <div class="toast-message">Error deleting assignment: ${error.message}</div>
                    </div>
                `;
                
                document.body.appendChild(errorDiv);
                
                // Remove error message after 3 seconds
                setTimeout(() => {
                    errorDiv.classList.add('hide');
                    setTimeout(() => {
                        errorDiv.remove();
                    }, 300);
                }, 3000);
            }
        }
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
