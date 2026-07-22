<?php
// School Grading Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get school subjects
$subjects = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
    $stmt->execute([$school_id]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch subjects: " . $e->getMessage());
}

// Get existing grading scales
$grading_scales = [];
try {
    $stmt = $pdo->prepare("SELECT gs.*, s.subject_name 
                          FROM grading_scales gs 
                          LEFT JOIN subjects s ON gs.subject_id = s.id 
                          WHERE gs.school_id = ? 
                          ORDER BY s.subject_name, gs.min_score");
    $stmt->execute([$school_id]);
    $grading_scales = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch grading scales: " . $e->getMessage());
}

// Handle delete grading scale
if (isset($_GET['delete_scale']) && isset($_GET['scale_id'])) {
    $scale_id = $_GET['scale_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM grading_scales WHERE id = ? AND school_id = ?");
        $stmt->execute([$scale_id, $school_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Grading scale deleted successfully";
        } else {
            $error = "Failed to delete grading scale";
        }
        
        // Refresh grading scales
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name 
                              FROM grading_scales gs 
                              LEFT JOIN subjects s ON gs.subject_id = s.id 
                              WHERE gs.school_id = ? 
                              ORDER BY s.subject_name, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to delete grading scale: " . $e->getMessage());
        $error = "Failed to delete grading scale";
    }
}

// Handle delete all scales for a subject
if (isset($_GET['delete_subject_scales']) && isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM grading_scales WHERE subject_id = ? AND school_id = ?");
        $stmt->execute([$subject_id, $school_id]);
        
        $deleted_count = $stmt->rowCount();
        $success = "Deleted $deleted_count grading scales for the subject";
        
        // Refresh grading scales
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name 
                              FROM grading_scales gs 
                              LEFT JOIN subjects s ON gs.subject_id = s.id 
                              WHERE gs.school_id = ? 
                              ORDER BY s.subject_name, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to delete subject grading scales: " . $e->getMessage());
        $error = "Failed to delete grading scales";
    }
}

// Handle edit grading scale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_scale'])) {
    $scale_id = $_POST['scale_id'] ?? '';
    $min_score = $_POST['min_score'] ?? '';
    $max_score = $_POST['max_score'] ?? '';
    $grade_name = $_POST['grade_name'] ?? '';
    $grade_description = $_POST['grade_description'] ?? '';
    $points = $_POST['points'] ?? '0';
    
    $errors = [];
    if (empty($min_score)) $errors[] = 'Min score is required';
    if (empty($max_score)) $errors[] = 'Max score is required';
    if (empty($grade_name)) $errors[] = 'Grade name is required';
    if ($min_score < 0 || $max_score > 100) $errors[] = 'Scores must be between 0 and 100';
    if ($min_score > $max_score) $errors[] = 'Min score cannot be greater than max score';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE grading_scales SET min_score = ?, max_score = ?, grade_name = ?, grade_description = ?, points = ? WHERE id = ? AND school_id = ?");
            $stmt->execute([$min_score, $max_score, $grade_name, $grade_description, $points, $scale_id, $school_id]);
            
            if ($stmt->rowCount() > 0) {
                $success = "Grading scale updated successfully!";
            } else {
                $error = "Failed to update grading scale";
            }
            
            // Refresh grading scales
            $stmt = $pdo->prepare("SELECT gs.*, s.subject_name 
                                  FROM grading_scales gs 
                                  LEFT JOIN subjects s ON gs.subject_id = s.id 
                                  WHERE gs.school_id = ? 
                                  ORDER BY s.subject_name, gs.min_score");
            $stmt->execute([$school_id]);
            $grading_scales = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to update grading scale: " . $e->getMessage());
            $error = "Failed to update grading scale";
        }
    } else {
        $error = implode(', ', $errors);
    }
}

// Handle CSV template download
if (isset($_GET['download_template']) && isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    // Get subject details
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND school_id = ?");
    $stmt->execute([$subject_id, $school_id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        header('Location: grading.php?error=invalid_subject');
        exit;
    }
    
    // Generate CSV template
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $subject['subject_name'] . '_grading_template.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Min Score', 'Max Score', 'Grade Name', 'Grade Description', 'Points']);
    fputcsv($output, ['0', '40', 'F', 'Fail', '1']);
    fputcsv($output, ['41', '50', 'D', 'Pass', '2']);
    fputcsv($output, ['51', '60', 'C', 'Average', '5']);
    fputcsv($output, ['61', '70', 'B', 'Good', '8']);
    fputcsv($output, ['71', '80', 'A-', 'Very Good', '10']);
    fputcsv($output, ['81', '90', 'A', 'Excellent', '11']);
    fputcsv($output, ['91', '100', 'A+', 'Outstanding', '12']);
    fclose($output);
    
    exit;
}

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['grading_file'])) {
    $subject_id = $_POST['subject_id'] ?? null;
    
    error_log("=== GRADING UPLOAD DEBUG ===");
    error_log("School ID (session): " . $school_id);
    error_log("Subject ID: " . $subject_id);
    
    if (!$subject_id) {
        $error = 'Please select a subject';
    } else {
        $file = $_FILES['grading_file'];
        
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
                        
                        // Delete existing grading scales for this subject
                        $stmt = $pdo->prepare("DELETE FROM grading_scales WHERE school_id = ? AND subject_id = ?");
                        $stmt->execute([$school_id, $subject_id]);
                        error_log("Deleted existing grading scales for school_id=$school_id, subject_id=$subject_id");
                        
                        // Skip header row
                        fgetcsv($handle);
                        
                        $row_count = 0;
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            if (count($data) >= 3) {
                                $min_score = intval(trim($data[0]));
                                $max_score = intval(trim($data[1]));
                                $grade_name = trim($data[2]);
                                $grade_description = isset($data[3]) ? trim($data[3]) : '';
                                $points = isset($data[4]) ? intval(trim($data[4])) : 0;
                                
                                if ($min_score >= 0 && $max_score <= 100 && $min_score <= $max_score && !empty($grade_name)) {
                                    $stmt = $pdo->prepare("INSERT INTO grading_scales (school_id, subject_id, min_score, max_score, grade_name, grade_description, points) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([$school_id, $subject_id, $min_score, $max_score, $grade_name, $grade_description, $points]);
                                    $row_count++;
                                    error_log("Inserted: school_id=$school_id, subject_id=$subject_id, min=$min_score, max=$max_score, grade=$grade_name, points=$points");
                                }
                            }
                        }
                        
                        fclose($handle);
                        $pdo->commit();
                        
                        error_log("Total rows inserted: $row_count");
                        
                        if ($row_count > 0) {
                            $success = "Successfully imported $row_count grading scales for the subject";
                        } else {
                            $error = 'No valid grading scales found in the file. Please check the format.';
                        }
                        
                        // Refresh grading scales
                        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name 
                                              FROM grading_scales gs 
                                              LEFT JOIN subjects s ON gs.subject_id = s.id 
                                              WHERE gs.school_id = ? 
                                              ORDER BY s.subject_name, gs.min_score");
                        $stmt->execute([$school_id]);
                        $grading_scales = $stmt->fetchAll();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        fclose($handle);
                        error_log("Grading import error: " . $e->getMessage());
                        $error = 'Failed to import grading scales: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Failed to open uploaded file';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grading System - <?php echo htmlspecialchars($school_name); ?></title>
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
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            z-index: 999;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
        }
        
        .card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #e55a2b 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 6px rgba(255, 107, 53, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #e55a2b 0%, #d9480f 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 107, 53, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(255, 107, 53, 0.3);
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 2px solid #FF6B35;
            box-shadow: 0 2px 4px rgba(255, 107, 53, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-color: #e55a2b;
            color: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 6px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
        }
        
        .btn-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            border: none;
            box-shadow: 0 4px 6px rgba(255, 193, 7, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 193, 7, 0.4);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.3px;
        }
        
        .btn i {
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #1e8e3e;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
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
            transition: background 0.2s;
        }
        
        .menu-btn:hover {
            background: rgba(95, 99, 104, 0.1);
        }
        
        .menu-btn i {
            font-size: 20px;
            color: var(--primary-color);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 400;
            color: #202124;
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
        
        .sidebar.collapsed {
            transform: translateX(-256px);
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
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #5f6368;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -12px;
        }
        
        .col-md-6 {
            flex: 1;
            padding: 0 12px;
            max-width: 50%;
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table th,
        .table td {
            padding: 12px 16px;
            text-align: left;
            border: 1px solid #000;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }
        
        .table th {
            background: #f0f0f0;
            font-weight: 600;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 2px solid #000;
        }
        
        .table th:last-child {
            border-right: 2px solid #000;
        }
        
        .table td:last-child {
            border-right: 2px solid #000;
        }
        
        .table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        /* Mobile */
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
            
            .card {
                text-align: left;
            }
            
            .card form {
                text-align: left;
            }
            
            .row {
                flex-direction: column;
            }
            
            .col-md-6 {
                width: 100%;
                max-width: 100%;
                margin-bottom: 12px;
            }
            
            .form-control {
                border-radius: 8px;
            }
            
            .btn {
                width: 100%;
                border-radius: 8px;
                padding: 12px 16px;
                font-size: 16px;
            }
            
            .table {
                font-size: 12px;
                min-width: 600px;
            }
            
            .table th,
            .table td {
                padding: 8px 10px;
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
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
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
            <a class="nav-link active" href="grading">
                <i class="fas fa-chart-bar"></i> Grading System
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-dollar-sign"></i> Fees
            </a>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <h1 class="page-title">Grading System Management</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="card-title">Import Grading Scales</h2>
            <p class="text-muted mb-3">Download the CSV template for each subject, fill in the grading ranges, and upload it to define your grading system.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Select Subject</label>
                        <select class="form-control" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Download Template</label>
                        <button type="button" class="btn btn-outline-primary w-100" onclick="downloadTemplate()">
                            <i class="fas fa-download me-2"></i> Download CSV Template
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Upload Completed Template</label>
                    <input type="file" class="form-control" name="grading_file" accept=".csv" required>
                    <small class="text-muted">Upload the completed CSV file with your grading scales</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Upload Grading Scales
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2 class="card-title">Current Grading Scales</h2>
            <div class="table-responsive">
                <?php if (empty($grading_scales)): ?>
                    <p class="text-center text-muted">No grading scales defined yet</p>
                <?php else: ?>
                    <?php
                    // Group grading scales by subject
                    $grouped_scales = [];
                    foreach ($grading_scales as $scale) {
                        $subject_name = $scale['subject_name'] ?? 'General';
                        $subject_id = $scale['subject_id'] ?? null;
                        if (!isset($grouped_scales[$subject_name])) {
                            $grouped_scales[$subject_name] = [
                                'subject_id' => $subject_id,
                                'scales' => []
                            ];
                        }
                        $grouped_scales[$subject_name]['scales'][] = $scale;
                    }
                    ?>
                    
                    <?php foreach ($grouped_scales as $subject_name => $subject_data): ?>
                        <div style="margin-bottom: 30px;">
                            <h4 style="color: #FF6B35; margin-bottom: 15px; font-weight: 600;">
                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($subject_name); ?>
                                <?php if ($subject_data['subject_id']): ?>
                                    <a href="grading.php?delete_subject_scales=1&subject_id=<?php echo $subject_data['subject_id']; ?>" 
                                       class="btn btn-sm btn-danger float-end" 
                                       onclick="return confirm('Are you sure you want to delete ALL grading scales for <?php echo htmlspecialchars($subject_name); ?>?')">
                                        <i class="fas fa-trash-alt"></i> Delete All
                                    </a>
                                <?php endif; ?>
                            </h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Min Score</th>
                                        <th>Max Score</th>
                                        <th>Grade Name</th>
                                        <th>Description</th>
                                        <th>Points</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subject_data['scales'] as $scale): ?>
                                        <tr>
                                            <td><?php echo $scale['min_score']; ?></td>
                                            <td><?php echo $scale['max_score']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($scale['grade_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($scale['grade_description'] ?? '-'); ?></td>
                                            <td><strong><?php echo $scale['points'] ?? 0; ?></strong></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editScale(<?php echo $scale['id']; ?>, '<?php echo $scale['min_score']; ?>', '<?php echo $scale['max_score']; ?>', '<?php echo htmlspecialchars($scale['grade_name']); ?>', '<?php echo htmlspecialchars($scale['grade_description'] ?? ''); ?>', '<?php echo $scale['points'] ?? 0; ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="grading.php?delete_scale=1&scale_id=<?php echo $scale['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this grading scale?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function downloadTemplate() {
            const subjectId = document.querySelector('select[name="subject_id"]').value;
            if (!subjectId) {
                alert('Please select a subject first');
                return;
            }
            window.location.href = 'grading?download_template=1&subject_id=' + subjectId;
        }
        
        function editScale(id, minScore, maxScore, gradeName, gradeDescription, points) {
            document.getElementById('scale_id').value = id;
            document.getElementById('min_score').value = minScore;
            document.getElementById('max_score').value = maxScore;
            document.getElementById('grade_name').value = gradeName;
            document.getElementById('grade_description').value = gradeDescription;
            document.getElementById('points').value = points;
            
            const modal = document.getElementById('editModal');
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="color: #FF6B35; margin-bottom: 20px;">Edit Grading Scale</h3>
            <form method="POST">
                <input type="hidden" name="edit_scale" value="1">
                <input type="hidden" name="scale_id" id="scale_id">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #5f6368;">Min Score</label>
                    <input type="number" name="min_score" id="min_score" class="form-control" min="0" max="100" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #5f6368;">Max Score</label>
                    <input type="number" name="max_score" id="max_score" class="form-control" min="0" max="100" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #5f6368;">Grade Name</label>
                    <input type="text" name="grade_name" id="grade_name" class="form-control" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #5f6368;">Grade Description</label>
                    <input type="text" name="grade_description" id="grade_description" class="form-control">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #5f6368;">Points</label>
                    <input type="number" name="points" id="points" class="form-control" min="1" max="12" required>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; box-shadow: 0 4px 6px rgba(108, 117, 125, 0.3);" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
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
