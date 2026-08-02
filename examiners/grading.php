<?php
// Grading Management Page for Examiners
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['examiner_id']) || !isset($_SESSION['examiner_token'])) {
    header('Location: login');
    exit;
}

$examiner_id = $_SESSION['examiner_id'];
$examiner_name = $_SESSION['examiner_name'] ?? 'Examiner';
$school_id = $_SESSION['examiner_school_id'];

// Get school name
try {
    $stmt = $pdo->prepare("SELECT school_name FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $school_name = $school['school_name'] ?? 'School';
} catch (PDOException $e) {
    error_log("Error fetching school name: " . $e->getMessage());
    $school_name = 'School';
}

// Get school subjects (excluding those with existing grading scales)
$subjects = [];
try {
    $stmt = $pdo->prepare("SELECT s.* FROM subjects s
                          WHERE s.school_id = ? AND s.status = 'active'
                          AND s.id NOT IN (SELECT DISTINCT subject_id FROM grading_scales WHERE school_id = ?)
                          ORDER BY s.subject_name");
    $stmt->execute([$school_id, $school_id]);
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

// Get aggregate points distribution
$aggregate_distribution = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
    $stmt->execute([$school_id]);
    $aggregate_distribution = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch aggregate points distribution: " . $e->getMessage());
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
        header('Location: grading?error=invalid_subject');
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
                                }
                            }
                        }

                        fclose($handle);
                        $pdo->commit();

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

// Handle aggregate points distribution template download
if (isset($_GET['download_aggregate_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="aggregate_points_distribution_template.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Grade Name', 'Min Points', 'Max Points', 'Grade Description']);
    fputcsv($output, ['A', '78', '84', 'Excellent']);
    fputcsv($output, ['A-', '71', '77', 'Very Good']);
    fputcsv($output, ['B+', '64', '70', 'Good']);
    fputcsv($output, ['B', '57', '63', 'Fairly Good']);
    fputcsv($output, ['B-', '50', '56', 'Fair']);
    fputcsv($output, ['C+', '43', '49', 'Average']);
    fputcsv($output, ['C', '36', '42', 'Below Average']);
    fputcsv($output, ['C-', '29', '35', 'Poor']);
    fputcsv($output, ['D+', '22', '28', 'Very Poor']);
    fputcsv($output, ['D', '15', '21', 'Extremely Poor']);
    fputcsv($output, ['D-', '8', '14', 'Fail']);
    fputcsv($output, ['E', '0', '7', 'Fail']);
    fclose($output);

    exit;
}

// Handle aggregate points distribution CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['aggregate_file'])) {
    $file = $_FILES['aggregate_file'];

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

                    // Delete existing aggregate points distribution for this school
                    $stmt = $pdo->prepare("DELETE FROM aggregate_points_distribution WHERE school_id = ?");
                    $stmt->execute([$school_id]);

                    // Skip header row
                    fgetcsv($handle);

                    $row_count = 0;
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        if (count($data) >= 3) {
                            $grade_name = trim($data[0]);
                            $min_points = intval(trim($data[1]));
                            $max_points = intval(trim($data[2]));
                            $grade_description = isset($data[3]) ? trim($data[3]) : '';

                            if (!empty($grade_name) && $min_points >= 0 && $max_points >= 0 && $min_points <= $max_points) {
                                $stmt = $pdo->prepare("INSERT INTO aggregate_points_distribution (school_id, grade_name, min_points, max_points, grade_description) VALUES (?, ?, ?, ?, ?)");
                                $stmt->execute([$school_id, $grade_name, $min_points, $max_points, $grade_description]);
                                $row_count++;
                            }
                        }
                    }

                    fclose($handle);
                    $pdo->commit();

                    if ($row_count > 0) {
                        $success = "Successfully imported $row_count aggregate points distribution entries";
                    } else {
                        $error = 'No valid aggregate points distribution found in the file. Please check the format.';
                    }

                    // Refresh aggregate distribution
                    $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
                    $stmt->execute([$school_id]);
                    $aggregate_distribution = $stmt->fetchAll();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    fclose($handle);
                    error_log("Aggregate import error: " . $e->getMessage());
                    $error = 'Failed to import aggregate points distribution: ' . $e->getMessage();
                }
            } else {
                $error = 'Failed to open uploaded file';
            }
        }
    }
}

// Handle delete aggregate points distribution
if (isset($_GET['delete_aggregate']) && isset($_GET['aggregate_id'])) {
    $aggregate_id = $_GET['aggregate_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM aggregate_points_distribution WHERE id = ? AND school_id = ?");
        $stmt->execute([$aggregate_id, $school_id]);

        if ($stmt->rowCount() > 0) {
            $success = "Aggregate points distribution deleted successfully";
        } else {
            $error = "Failed to delete aggregate points distribution";
        }

        // Refresh aggregate distribution
        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$school_id]);
        $aggregate_distribution = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to delete aggregate points distribution: " . $e->getMessage());
        $error = "Failed to delete aggregate points distribution";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Grading System - <?php echo htmlspecialchars($examiner_name); ?></title>
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
            font-size: 18px;
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
        
        /* Sidebar */
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
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
        }
        
        .sidebar-title .chevron {
            transition: transform 0.3s ease;
        }
        
        .sidebar-title.collapsed .chevron {
            transform: rotate(-90deg);
        }
        
        .sidebar-links {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-links.collapsed {
            max-height: 0;
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
        
        .card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding: 16px 20px;
            font-weight: 500;
            color: var(--secondary-color);
            border-radius: 12px;
        }
        
        .card-body {
            padding: 20px;
            border-radius: 12px;
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
            border: 1px solid rgba(0,0,0,0.04);
            border-radius: 12px;
            padding: 8px 12px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26,115,232,0.1);
        }
        
        .btn-primary {
            background: #FF6B35;
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 500;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .btn-action {
           -background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
            cursor: pointer;
            border-radius: 4px;
            padding: 4px 8px;
        }
        
        .btn-action:hover {
            background: #e9ecef;
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
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #1e8e3e;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
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
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="school-avatar">
                <?php echo strtoupper(substr($examiner_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Main <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Examination <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="subjects">
                    <i class="fas fa-book"></i> Subjects
                </a>
                <a class="nav-link" href="exam-types">
                    <i class="fas fa-clipboard-list"></i> Exam Types
                </a>
                <a class="nav-link" href="performance">
                    <i class="fas fa-chart-line"></i> Performance
                </a>
                <a class="nav-link active" href="grading">
                    <i class="fas fa-chart-bar"></i> Grading System
                </a>
                <a class="nav-link" href="results">
                    <i class="fas fa-award"></i> Results
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                School <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="students">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a class="nav-link" href="classes">
                    <i class="fas fa-chalkboard"></i> Classes
                </a>
                <a class="nav-link" href="streams">
                    <i class="fas fa-layer-group"></i> Streams
                </a>
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link" href="timetable">
                    <i class="fas fa-calendar-alt"></i> Timetable
                </a>
                <a class="nav-link" href="calendar">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
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
                <div class="card-header">
                    <i class="fas fa-upload me-2"></i>Import Grading Scales
                </div>
                <div class="card-body">
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
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list me-2"></i>Current Grading Scales
                </div>
                <div class="card-body">
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
                                        <a href="grading?delete_subject_scales=1&subject_id=<?php echo $subject_data['subject_id']; ?>"
                                           class="btn btn-sm btn-action float-end"
                                           onclick="return confirm('Are you sure you want to delete ALL grading scales for <?php echo htmlspecialchars($subject_name); ?>?')">
                                            <i class="fas fa-trash-alt"></i> Delete All
                                        </a>
                                    <?php endif; ?>
                                </h4>
                                <div class="table-responsive" style="overflow-x: auto;">
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
                                                        <button class="btn btn-sm btn-action" onclick="editScale(<?php echo $scale['id']; ?>, '<?php echo $scale['min_score']; ?>', '<?php echo $scale['max_score']; ?>', '<?php echo htmlspecialchars($scale['grade_name']); ?>', '<?php echo htmlspecialchars($scale['grade_description'] ?? ''); ?>', '<?php echo $scale['points'] ?? 0; ?>')">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <a href="grading?delete_scale=1&scale_id=<?php echo $scale['id']; ?>"
                                                           class="btn btn-sm btn-action"
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
            </div>

            <!-- Aggregate Points Distribution Section -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2"></i>Aggregate Points Distribution
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Define the aggregate points distribution for overall student performance (e.g., KCSE grading). This is separate from subject-specific grading scales.</p>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Download Template</label>
                                <a href="grading?download_aggregate_template=1" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-download me-2"></i> Download CSV Template
                                </a>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload Completed Template</label>
                                <input type="file" class="form-control" name="aggregate_file" accept=".csv" required>
                                <small class="text-muted">Upload the completed CSV file with aggregate points distribution</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i> Upload Aggregate Points Distribution
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list me-2"></i>Current Aggregate Points Distribution
                </div>
                <div class="card-body">
                    <?php if (empty($aggregate_distribution)): ?>
                        <p class="text-center text-muted">No aggregate points distribution defined yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Grade Name</th>
                                        <th>Min Points</th>
                                        <th>Max Points</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aggregate_distribution as $aggregate): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($aggregate['grade_name']); ?></strong></td>
                                            <td><?php echo $aggregate['min_points']; ?></td>
                                            <td><?php echo $aggregate['max_points']; ?></td>
                                            <td><?php echo htmlspecialchars($aggregate['grade_description'] ?? '-'); ?></td>
                                            <td>
                                                <a href="grading?delete_aggregate=1&aggregate_id=<?php echo $aggregate['id']; ?>"
                                                   class="btn btn-sm btn-action"
                                                   onclick="return confirm('Are you sure you want to delete this aggregate points distribution entry?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSidebarSection(titleElement) {
            const linksContainer = titleElement.nextElementSibling;
            const isCollapsed = linksContainer.classList.contains('collapsed');
            
            linksContainer.classList.toggle('collapsed');
            titleElement.classList.toggle('collapsed');
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
                    <button type="button" class="btn" style="背景: #6c757d; color: white;" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
