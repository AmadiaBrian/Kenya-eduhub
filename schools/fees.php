<?php
// Fees Management Page
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

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);
$current_term_data = $calendar_status['current_term'];
$active_term = $current_term_data['term_name'] ?? 'Term 1';

// Get school admission prefix
try {
    $stmt = $pdo->prepare("SELECT admission_prefix FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $admission_prefix = $school['admission_prefix'] ?? '';
} catch (PDOException $e) {
    error_log("Failed to fetch school prefix: " . $e->getMessage());
    $admission_prefix = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Fees - <?php echo htmlspecialchars($school_name); ?></title>
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
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
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
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
            width: 100%;
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
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: 600;
            color: #000;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 12px;
            border: 1px solid #000;
            color: #000;
            font-size: 13px;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }
            
            .header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                padding: 0 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                transform: none !important;
            }
            
            .logo span {
                font-size: 18px;
            }
            
            .menu-btn {
                padding: 8px;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .menu-btn:hover {
                background: rgba(0,0,0,0.04);
            }
            
            .sidebar {
                position: fixed !important;
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
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
            }
            
            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #dadce0;
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
                border-bottom: 1px solid #e8eaed;
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
            .header {
                padding: 0 12px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
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
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
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
            border-radius: 8px;
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
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
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
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
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
            <a class="nav-link" href="exam-types">
                <i class="fas fa-clipboard-list"></i> Exam Types
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="grading">
                <i class="fas fa-chart-bar"></i> Grading
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-clipboard-list"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link active" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
            <a class="nav-link" href="finance-managers">
                <i class="fas fa-user-tie"></i> Finance Managers
            </a>
            <a class="nav-link" href="account">
                <i class="fas fa-wallet"></i> Account Balance
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="disciplinary">
                <i class="fas fa-shield-alt"></i> Disciplinary
            </a>
            <a class="nav-link" href="disciplinary-action-types">
                <i class="fas fa-list-alt"></i> Disciplinary Types
            </a>
            <a class="nav-link" href="librarians">
                <i class="fas fa-book-reader"></i> Librarians
            </a>
            <a class="nav-link" href="duty-assignments">
                <i class="fas fa-clipboard-list"></i> Duty Assignments
            </a>
            <a class="nav-link" href="examination-heads">
                <i class="fas fa-user-tie"></i> Examination Heads
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Fees Management</h1>
        
        <!-- Calendar Status Alert -->
        <?php if ($calendar_status['is_holiday']): ?>
            <div style="background: #fce8e6; border: 1px solid #c5221f; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
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
            <div style="background: #fef7e0; border: 1px solid #f9ab00; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-info-circle" style="color: #f9ab00; font-size: 20px;"></i>
                    <div>
                        <strong style="color: #b06000;">School is on Break</strong>
                        <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">No active term is currently set. Please activate a term in the Calendar.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Fee Statistics -->
        <div id="feeStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <?php
            $totalPayments = 0;
            $totalCollected = 0;
            $totalStructure = 0;
            $totalOutstanding = 0;
            $paymentCount = 0;
            
            try {
                // Count total payments
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM fee_payments fp JOIN students s ON fp.student_id = s.id WHERE s.school_id = ? AND fp.status = 'completed'");
                $stmt->execute([$school_id]);
                $paymentData = $stmt->fetch();
                $paymentCount = $paymentData['count'];
                $totalPayments = $paymentCount;
                
                // Calculate actual outstanding balance matching the balances query logic
                $stmt = $pdo->prepare("
                    SELECT SUM(fs.amount - COALESCE(fp.paid, 0)) as outstanding
                    FROM fee_structure fs
                    JOIN classes c ON fs.class_id = c.id
                    LEFT JOIN (
                        SELECT student_id, term, year, fee_type, SUM(amount) as paid
                        FROM fee_payments 
                        WHERE status = 'completed'
                        GROUP BY student_id, term, year, fee_type
                    ) fp ON fs.class_id IN (
                        SELECT class_id FROM students WHERE school_id = ?
                    ) AND fs.term = fp.term AND fs.year = fp.year 
                    AND (fs.fee_type = fp.fee_type OR (fp.fee_type IS NULL AND fs.fee_type = 'Tuition'))
                    WHERE c.school_id = ?
                ");
                $stmt->execute([$school_id, $school_id]);
                $outstandingData = $stmt->fetch();
                $totalOutstanding = $outstandingData['outstanding'] ?? 0;
                
                // Total collected (all completed payments)
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fee_payments fp JOIN students s ON fp.student_id = s.id WHERE s.school_id = ? AND fp.status = 'completed'");
                $stmt->execute([$school_id]);
                $collectedData = $stmt->fetch();
                $totalCollected = $collectedData['total'] ?? 0;
                
                // Total fee structure amount
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fee_structure fs JOIN classes c ON fs.class_id = c.id WHERE c.school_id = ?");
                $stmt->execute([$school_id]);
                $structureData = $stmt->fetch();
                $totalStructure = $structureData['total'] ?? 0;
                
            } catch (PDOException $e) {
                error_log("Failed to fetch fee stats: " . $e->getMessage());
            }
            ?>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalPayments; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Payments</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;">KES <?php echo number_format($totalCollected); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Collected</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;">KES <?php echo number_format($totalStructure); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Fee Structure Total</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;">KES <?php echo number_format($totalOutstanding); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Outstanding Balance</div>
            </div>
        </div>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="dashboard" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="quick-access-label">Dashboard</div>
                </a>
                <a href="students" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="quick-access-label">Students</div>
                </a>
                <a href="teachers" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="quick-access-label">Teachers</div>
                </a>
                <a href="attendance" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="performance" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="classes" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
            </div>
        </div>
        
        <!-- Fee Structure Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Fee Structure</span>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeStructureModal">
                        <i class="fas fa-plus me-2"></i> Add Fee Structure
                    </button>
                    <button class="btn btn-success" onclick="exportFeeStructure()">
                        <i class="fas fa-download me-2"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control" id="filterStructureClass" onchange="filterFeeStructure()">
                            <option value="">All Classes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterStructureTerm" onchange="filterFeeStructure()">
                            <option value="">All Terms</option>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" id="filterStructureYear" placeholder="Year" onchange="filterFeeStructure()">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchStructure" placeholder="Search..." onkeyup="filterFeeStructure()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Term</th>
                                <th>Year</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="feeStructureTable">
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Fee Payments Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Fee Payments</span>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="fas fa-plus me-2"></i> Record Payment
                    </button>
                    <button class="btn btn-success" onclick="exportPayments()">
                        <i class="fas fa-download me-2"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchPayment" placeholder="Search student..." onkeyup="filterPayments()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterPaymentMethod" onchange="filterPayments()">
                            <option value="">All Methods</option>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterPaymentTerm" onchange="filterPayments()">
                            <option value="">All Terms</option>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="filterPaymentDate" onchange="filterPayments()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Student</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Term</th>
                                <th>Actions</th>
                                <th>Statement</th>
                            </tr>
                        </thead>
                        <tbody id="paymentsTable">
                            <tr><td colspan="9" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Fee Balances Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <span>Student Fee Balances</span>
                <div class="d-flex gap-2 flex-wrap">
                    <select class="form-control form-control-sm" id="filterClass" style="width: 150px;" onchange="loadStreamsForClass()">
                        <option value="">All Classes</option>
                    </select>
                    <select class="form-control form-control-sm" id="filterStream" style="width: 150px;">
                        <option value="">All Streams</option>
                    </select>
                    <input type="text" class="form-control form-control-sm" id="searchAdmission" placeholder="Admission No" style="width: 150px;">
                    <input type="text" class="form-control form-control-sm" id="searchName" placeholder="Student Name" style="width: 150px;">
                    <select class="form-control form-control-sm" id="balanceTerm" style="width: 120px;">
                        <option value="">Select Term</option>
                    </select>
                    <select class="form-control form-control-sm" id="balanceYear" style="width: 100px;">
                        <option value="">Select Year</option>
                    </select>
                    <button class="btn btn-sm btn-primary" onclick="loadBalances()">
                        <i class="fas fa-search"></i> View
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="sendFeeReminders()">
                        <i class="fas fa-bell"></i> Send Reminders
                    </button>
                    <button class="btn btn-sm btn-success" onclick="sendFeeBalancesViaSMS()">
                        <i class="fas fa-sms"></i> Send SMS Balances
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllBalances" onchange="toggleAllBalances()"></th>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Stream</th>
                                <th>Fee Type</th>
                                <th>Term</th>
                                <th>Year</th>
                                <th>Fee Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="balancesTable">
                            <tr><td colspan="12" class="text-center">Select filters and click View</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Fee Structure Modal - Google Material Design Style -->
    <div class="modal fade" id="addFeeStructureModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" id="feeStructureModalTitle" style="font-size: 22px; font-weight: 400; color: #202124;">Add Fee Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addFeeStructureForm">
                        <input type="hidden" id="feeStructureId">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Class</label>
                                <select class="form-control" id="feeClassId" required style="border-radius: 8px; border: 1px solid #dadce0;">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Term</label>
                                <select class="form-control" id="feeTerm" required style="border-radius: 8px; border: 1px solid #dadce0;">
                                    <option value="">Select Term</option>
                                    <option value="Term 1">Term 1</option>
                                    <option value="Term 2">Term 2</option>
                                    <option value="Term 3">Term 3</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Fee Type</label>
                                <input type="text" class="form-control" id="feeType" value="Tuition" required placeholder="e.g., Tuition, Remedial, Exam Fees" style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Year</label>
                                <input type="number" class="form-control" id="feeYear" value="2026" required style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Amount (KES)</label>
                                <input type="number" class="form-control" id="feeAmount" required style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Description</label>
                            <textarea class="form-control" id="feeDescription" rows="2" style="border-radius: 8px; border: 1px solid #dadce0;"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveFeeStructureBtn" onclick="saveFeeStructure()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Add Fee Structure</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Payment Modal - Google Material Design Style -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Record Fee Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addPaymentForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student Admission Number</label>
                                <div class="input-group">
                                    <?php if (!empty($admission_prefix)): ?>
                                        <span class="input-group-text"><?php echo htmlspecialchars($admission_prefix); ?>/</span>
                                    <?php endif; ?>
                                    <input type="text" class="form-control" id="paymentAdmissionNumber" placeholder="Enter admission number" required>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-info">Current Prefix: <?php echo htmlspecialchars($admission_prefix ?: 'None'); ?></span>
                                    <small class="text-muted ms-2">
                                        <?php if (!empty($admission_prefix)): ?>
                                            Enter only the number part (e.g., 7280)
                                        <?php else: ?>
                                            Enter the full admission number
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount (KES)</label>
                                <input type="number" class="form-control" id="paymentAmount" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="paymentDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" id="paymentMethod" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="M-Pesa">M-Pesa</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Term</label>
                                <select class="form-control" id="paymentTerm" required>
                                    <option value="">Select Term</option>
                                    <option value="Term 1">Term 1</option>
                                    <option value="Term 2">Term 2</option>
                                    <option value="Term 3">Term 3</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" id="paymentYear" value="2026" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction ID (Optional)</label>
                            <input type="text" class="form-control" id="paymentTransactionId" placeholder="e.g., M-Pesa transaction ID">
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="addPayment()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Record Payment</button>
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
        
        // Load classes dropdown
        async function loadClassesDropdown() {
            try {
                const response = await fetch('api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const options = data.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                    document.getElementById('feeClassId').innerHTML = '<option value="">Select Class</option>' + options;
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }
        
        // Load fee structures
        async function loadFeeStructures() {
            try {
                const response = await fetch('api/fees.php?type=structure');
                const data = await response.json();
                if (data.success) {
                    window.feeStructureData = data.data;
                    renderFeeStructure(data.data);
                    populateClassFilter(data.data);
                }
            } catch (error) {
                console.error('Error loading fee structures:', error);
            }
        }
        
        // Render fee structure to table
        function renderFeeStructure(structures) {
            const tbody = document.getElementById('feeStructureTable');
            tbody.innerHTML = structures.map(fee => `
                <tr>
                    <td>${fee.class_name}</td>
                    <td>${fee.term}</td>
                    <td>${fee.year}</td>
                    <td>KES ${fee.amount.toLocaleString()}</td>
                    <td>${fee.description || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-action me-1" onclick="editFeeStructure(${fee.id}, '${fee.class_id}', '${fee.term}', ${fee.year}, '${fee.fee_type}', ${fee.amount}, '${fee.description || ''}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-action" onclick="deleteFeeStructure(${fee.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Populate class filter dropdown
        function populateClassFilter(structures) {
            const classes = [...new Set(structures.map(s => s.class_id))];
            const classSelect = document.getElementById('filterStructureClass');
            classSelect.innerHTML = '<option value="">All Classes</option>';
            classes.forEach(classId => {
                const className = structures.find(s => s.class_id === classId)?.class_name;
                classSelect.innerHTML += `<option value="${classId}">${className}</option>`;
            });
        }
        
        // Load payments
        async function loadPayments() {
            try {
                const response = await fetch('api/fees.php?type=payments');
                const data = await response.json();
                if (data.success) {
                    window.paymentsData = data.data;
                    renderPayments(data.data);
                }
            } catch (error) {
                console.error('Error loading payments:', error);
            }
        }
        
        // Render payments to table
        function renderPayments(payments) {
            const tbody = document.getElementById('paymentsTable');
            tbody.innerHTML = payments.map(payment => `
                <tr>
                    <td>${payment.receipt_number}</td>
                    <td>${payment.student_name}</td>
                    <td><strong>${payment.fee_type || 'Tuition'}</strong></td>
                    <td>KES ${payment.amount.toLocaleString()}</td>
                    <td>${payment.payment_date}</td>
                    <td>${payment.payment_method}</td>
                    <td>${payment.term}</td>
                    <td>
                        <button class="btn btn-sm btn-action" onclick="deletePayment(${payment.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-action" onclick="generateFeeStatement(${payment.student_id})">
                            <i class="fas fa-file-invoice"></i> Statement
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Add fee structure
        async function addFeeStructure() {
            const feeData = {
                class_id: document.getElementById('feeClassId').value,
                term: document.getElementById('feeTerm').value,
                year: document.getElementById('feeYear').value,
                fee_type: document.getElementById('feeType').value,
                amount: document.getElementById('feeAmount').value,
                description: document.getElementById('feeDescription').value
            };
            
            try {
                const response = await fetch('api/fees.php?type=structure', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(feeData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Fee structure added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addFeeStructureModal')).hide();
                    document.getElementById('addFeeStructureForm').reset();
                    loadFeeStructures();
                } else {
                    alert(data.error || 'Failed to add fee structure');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Edit fee structure
        function editFeeStructure(id, classId, term, year, feeType, amount, description) {
            document.getElementById('feeStructureId').value = id;
            document.getElementById('feeClassId').value = classId;
            document.getElementById('feeTerm').value = term;
            document.getElementById('feeYear').value = year;
            document.getElementById('feeType').value = feeType;
            document.getElementById('feeAmount').value = amount;
            document.getElementById('feeDescription').value = description;
            document.getElementById('feeStructureModalTitle').textContent = 'Edit Fee Structure';
            document.getElementById('saveFeeStructureBtn').textContent = 'Update Fee Structure';
            
            const modal = new bootstrap.Modal(document.getElementById('addFeeStructureModal'));
            modal.show();
        }
        
        // Save fee structure (add or update)
        async function saveFeeStructure() {
            const feeId = document.getElementById('feeStructureId').value;
            const feeData = {
                id: feeId || null,
                class_id: document.getElementById('feeClassId').value,
                term: document.getElementById('feeTerm').value,
                year: document.getElementById('feeYear').value,
                fee_type: document.getElementById('feeType').value,
                amount: document.getElementById('feeAmount').value,
                description: document.getElementById('feeDescription').value
            };
            
            try {
                const response = await fetch('api/fees.php?type=structure', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(feeData)
                });
                const data = await response.json();
                if (data.success) {
                    alert(feeId ? 'Fee structure updated successfully!' : 'Fee structure added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addFeeStructureModal')).hide();
                    document.getElementById('addFeeStructureForm').reset();
                    document.getElementById('feeStructureId').value = '';
                    document.getElementById('feeStructureModalTitle').textContent = 'Add Fee Structure';
                    document.getElementById('saveFeeStructureBtn').textContent = 'Add Fee Structure';
                    loadFeeStructures();
                } else {
                    alert(data.error || 'Failed to save fee structure');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Add payment
        async function addPayment() {
            const prefix = "<?php echo addslashes($admission_prefix); ?>";
            const admissionNumber = document.getElementById('paymentAdmissionNumber').value.trim();
            const fullAdmissionNumber = prefix ? prefix + '/' + admissionNumber : admissionNumber;
            
            console.log('Prefix:', prefix);
            console.log('Admission Number Input:', admissionNumber);
            console.log('Full Admission Number:', fullAdmissionNumber);
            
            const paymentData = {
                admission_number: fullAdmissionNumber,
                amount: document.getElementById('paymentAmount').value,
                payment_date: document.getElementById('paymentDate').value,
                payment_method: document.getElementById('paymentMethod').value,
                term: document.getElementById('paymentTerm').value,
                year: document.getElementById('paymentYear').value,
                transaction_id: document.getElementById('paymentTransactionId').value
            };
            
            try {
                const response = await fetch('api/fees.php?type=payment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(paymentData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Payment recorded successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addPaymentModal')).hide();
                    document.getElementById('addPaymentForm').reset();
                    loadPayments();
                } else {
                    alert(data.error || 'Failed to record payment');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Delete fee structure
        async function deleteFeeStructure(id) {
            if (confirm('Are you sure you want to delete this fee structure?')) {
                try {
                    const response = await fetch(`api/fees.php?type=structure&id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Fee structure deleted successfully!');
                        loadFeeStructures();
                    } else {
                        alert(data.error || 'Failed to delete fee structure');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        // Delete payment
        async function deletePayment(id) {
            if (confirm('Are you sure you want to delete this payment?')) {
                try {
                    const response = await fetch(`api/fees.php?type=payment&id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Payment deleted successfully!');
                        loadPayments();
                    } else {
                        alert(data.error || 'Failed to delete payment');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        // Filter fee structure
        function filterFeeStructure() {
            const classFilter = document.getElementById('filterStructureClass').value;
            const termFilter = document.getElementById('filterStructureTerm').value;
            const yearFilter = document.getElementById('filterStructureYear').value;
            const searchFilter = document.getElementById('searchStructure').value.toLowerCase();
            
            if (!window.feeStructureData) return;
            
            const filtered = window.feeStructureData.filter(item => {
                const matchesClass = !classFilter || item.class_id == classFilter;
                const matchesTerm = !termFilter || item.term === termFilter;
                const matchesYear = !yearFilter || item.year == yearFilter;
                const matchesSearch = !searchFilter || 
                    item.class_name.toLowerCase().includes(searchFilter) ||
                    item.description.toLowerCase().includes(searchFilter);
                
                return matchesClass && matchesTerm && matchesYear && matchesSearch;
            });
            
            renderFeeStructure(filtered);
        }
        
        // Filter payments
        function filterPayments() {
            const searchFilter = document.getElementById('searchPayment').value.toLowerCase();
            const methodFilter = document.getElementById('filterPaymentMethod').value;
            const termFilter = document.getElementById('filterPaymentTerm').value;
            const dateFilter = document.getElementById('filterPaymentDate').value;
            
            if (!window.paymentsData) return;
            
            const filtered = window.paymentsData.filter(item => {
                const matchesSearch = !searchFilter || 
                    item.student_name.toLowerCase().includes(searchFilter) ||
                    item.admission_number.toLowerCase().includes(searchFilter);
                const matchesMethod = !methodFilter || item.payment_method === methodFilter;
                const matchesTerm = !termFilter || item.term === termFilter;
                const matchesDate = !dateFilter || item.payment_date === dateFilter;
                
                return matchesSearch && matchesMethod && matchesTerm && matchesDate;
            });
            
            renderPayments(filtered);
        }
        
        // Export fee structure to CSV
        function exportFeeStructure() {
            if (!window.feeStructureData) return;
            
            let csvContent = 'Class,Term,Year,Amount,Description\n';
            
            window.feeStructureData.forEach(item => {
                const rowData = [
                    item.class_name,
                    item.term,
                    item.year,
                    item.amount,
                    item.description || ''
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
            link.setAttribute('download', `fee_structure_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Export payments to CSV
        function exportPayments() {
            if (!window.paymentsData) return;
            
            let csvContent = 'Receipt No,Student Name,Admission No,Fee Type,Amount,Payment Date,Method,Term\n';
            
            window.paymentsData.forEach(item => {
                const rowData = [
                    item.receipt_number,
                    item.student_name,
                    item.admission_number,
                    item.fee_type,
                    item.amount,
                    item.payment_date,
                    item.payment_method,
                    item.term
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
            link.setAttribute('download', `fee_payments_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Toggle all balance checkboxes
        function toggleAllBalances() {
            const selectAll = document.getElementById('selectAllBalances');
            const checkboxes = document.querySelectorAll('.balance-checkbox');
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = selectAll.checked;
                }
            });
        }
        
        // Send fee reminders to selected parents
        async function sendFeeReminders() {
            const selectedCheckboxes = document.querySelectorAll('.balance-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                await notificationSystem.alert('Please select at least one student with outstanding balance to send reminders.');
                return;
            }
            
            const students = [];
            selectedCheckboxes.forEach(cb => {
                console.log('Checkbox data:', {
                    studentId: cb.dataset.studentId,
                    studentName: cb.dataset.studentName,
                    admission: cb.dataset.admission,
                    balance: cb.dataset.balance,
                    feeType: cb.dataset.feeType,
                    term: cb.dataset.term,
                    year: cb.dataset.year,
                    feeAmount: cb.dataset.feeAmount,
                    paidAmount: cb.dataset.paidAmount
                });
                
                students.push({
                    student_id: cb.dataset.studentId,
                    student_name: cb.dataset.studentName,
                    admission_number: cb.dataset.admission,
                    balance: cb.dataset.balance,
                    fee_type: cb.dataset.feeType,
                    term: cb.dataset.term,
                    year: cb.dataset.year,
                    fee_amount: cb.dataset.feeAmount,
                    paid_amount: cb.dataset.paidAmount
                });
            });
            
            console.log('Students data being sent:', students);
            
            const confirmed = await notificationSystem.confirm(`Send fee reminders for ${students.length} selected fee record(s)?`);
            if (!confirmed) {
                return;
            }
            
            // Show loading spinner
            const sendBtn = document.querySelector('button[onclick="sendFeeReminders()"]');
            const originalText = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            try {
                const response = await fetch('api/send_fee_reminders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ students: students })
                });
                const data = await response.json();
                
                console.log('API response:', data);
                
                if (data.success) {
                    notificationSystem.success('Success', `Reminders sent successfully to ${data.sent_count} parent(s).`);
                    // Clear selections
                    document.getElementById('selectAllBalances').checked = false;
                    selectedCheckboxes.forEach(cb => cb.checked = false);
                } else {
                    notificationSystem.error('Error', data.error || 'Failed to send reminders');
                }
            } catch (error) {
                console.error('Error sending reminders:', error);
                notificationSystem.error('Error', 'An error occurred while sending reminders');
            } finally {
                // Restore button
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
            }
        }
        
        // Send reminders to all parents with outstanding balances
        async function sendRemindersToAll() {
            const confirmed = await notificationSystem.confirm('Send fee reminders to ALL parents with outstanding balances in the school?');
            
            if (!confirmed) {
                return;
            }
            
            // Show loading spinner
            const sendBtn = document.querySelector('button[onclick="sendRemindersToAll()"]');
            
            if (!sendBtn) {
                notificationSystem.error('Error', 'Send to All button not found');
                return;
            }
            
            const originalText = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            try {
                const response = await fetch('api/send_fee_reminders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ send_to_all: true })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    notificationSystem.success('Success', `Reminders sent successfully to ${data.sent_count} parent(s).`);
                } else {
                    notificationSystem.error('Error', data.error || 'Failed to send reminders');
                }
            } catch (error) {
                console.error('Error sending reminders to all:', error);
                notificationSystem.error('Error', 'An error occurred while sending reminders');
            } finally {
                // Restore button
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
            }
        }
        
        // Load balances
        async function loadBalances() {
            const term = document.getElementById('balanceTerm').value;
            const year = document.getElementById('balanceYear').value;
            const tbody = document.getElementById('balancesTable');
            
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Loading...</td></tr>';
            
            try {
                const response = await fetch(`api/fees.php?type=balances&term=${term}&year=${year}`);
                const data = await response.json();
                console.log('Balances response:', data);
                console.log('Number of records:', data.data ? data.data.length : 0);
                
                if (data.success) {
                    if (data.data && data.data.length > 0) {
                        window.balancesData = data.data; // Store data for filtering
                        console.log('Sample record:', data.data[0]);
                        renderBalances(data.data);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No balances found for selected term/year</td></tr>';
                        window.balancesData = [];
                    }
                } else {
                    tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger">Error: ${data.error || 'Unknown error'}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading balances:', error);
                tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger">Error: ${error.message}</td></tr>`;
            }
        }
        
        // Render balances to table
        function renderBalances(balances) {
            const tbody = document.getElementById('balancesTable');
            tbody.innerHTML = balances.map(balance => {
                const balanceValue = parseFloat(balance.balance) || 0;
                const hasOutstanding = balanceValue > 0;
                return `
                <tr>
                    <td><input type="checkbox" class="balance-checkbox" data-student-id="${balance.student_id}" data-balance="${balanceValue}" data-student-name="${balance.student_name}" data-admission="${balance.admission_number}" ${hasOutstanding ? '' : 'disabled'}></td>
                    <td>${balance.admission_number}</td>
                    <td>${balance.student_name}</td>
                    <td>${balance.class_name || '-'}</td>
                    <td><strong>${balance.fee_type || 'Tuition'}</strong></td>
                    <td>${balance.term}</td>
                    <td>${balance.year}</td>
                    <td>KES ${balance.fee_amount.toLocaleString()}</td>
                    <td>KES ${balance.paid_amount.toLocaleString()}</td>
                    <td>KES ${balanceValue.toLocaleString()}</td>
                    <td>
                        <span class="badge ${balanceValue <= 0 ? 'bg-success' : 'bg-warning'}">
                            ${balanceValue <= 0 ? 'Paid' : 'Balance Due'}
                        </span>
                    </td>
                </tr>
            `}).join('');
        }
        
        // Filter balances by admission number or name
        function filterBalances() {
            const searchTerm = document.getElementById('searchBalances').value.toLowerCase().trim();
            console.log('Search term:', searchTerm);
            
            if (!window.balancesData || window.balancesData.length === 0) {
                console.log('No data loaded');
                // If no data loaded, show message
                const tbody = document.getElementById('balancesTable');
                if (tbody.innerHTML.includes('Select term and year') || tbody.innerHTML.includes('No balances found')) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-warning">Please select term and year and click View to load balances first</td></tr>';
                }
                return;
            }
            
            console.log('Total records before filter:', window.balancesData.length);
            
            // If search is empty, show all
            if (searchTerm === '') {
                console.log('Search empty, showing all');
                renderBalances(window.balancesData);
                return;
            }
            
            const filtered = window.balancesData.filter(balance => {
                const admissionNum = (balance.admission_number || '').toLowerCase();
                const studentName = (balance.student_name || '').toLowerCase();
                
                // Check admission number (handles both full "NDS/1" and partial "1" or "NDS")
                const admissionMatch = admissionNum.includes(searchTerm);
                
                // Check student name
                const nameMatch = studentName.includes(searchTerm);
                
                console.log(`Checking: Admission="${admissionNum}" Match=${admissionMatch}, Name="${studentName}" Match=${nameMatch}`);
                
                return admissionMatch || nameMatch;
            });
            
            console.log('Records after filter:', filtered.length);
            
            renderBalances(filtered);
            
            // Show message if no results
            if (filtered.length === 0) {
                console.log('No matches found');
                const tbody = document.getElementById('balancesTable');
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-warning">No matching records found for "' + searchTerm + '"</td></tr>';
            }
        }
        
        // Load classes filter dropdown
        async function loadClassesFilter() {
            try {
                const response = await fetch('api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('filterClass');
                    select.innerHTML = '<option value="">All Classes</option>' + 
                        data.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }
        
        // Load streams for selected class
        async function loadStreamsForClass() {
            const classId = document.getElementById('filterClass').value;
            const streamSelect = document.getElementById('filterStream');
            
            if (!classId) {
                streamSelect.innerHTML = '<option value="">All Streams</option>';
                return;
            }
            
            try {
                const response = await fetch(`api/streams.php?class_id=${classId}`);
                const data = await response.json();
                if (data.success) {
                    streamSelect.innerHTML = '<option value="">All Streams</option>' + 
                        data.data.map(s => `<option value="${s.id}">${s.stream_name}</option>`).join('');
                }
            } catch (error) {
                console.error('Error loading streams:', error);
            }
        }
        
        // Clear all filters
        function clearFilters() {
            document.getElementById('filterClass').value = '';
            document.getElementById('filterStream').value = '';
            document.getElementById('filterStream').innerHTML = '<option value="">All Streams</option>';
            document.getElementById('searchAdmission').value = '';
            document.getElementById('searchName').value = '';
            document.getElementById('balancesTable').innerHTML = '<tr><td colspan="11" class="text-center">Select filters and click View</td></tr>';
            window.balancesData = [];
        }
        
        // Load balances with filters
        async function loadBalances() {
            const term = document.getElementById('balanceTerm').value;
            const year = document.getElementById('balanceYear').value;
            const classId = document.getElementById('filterClass').value;
            const streamId = document.getElementById('filterStream').value;
            const admissionNo = document.getElementById('searchAdmission').value.trim();
            const studentName = document.getElementById('searchName').value.trim();
            
            const tbody = document.getElementById('balancesTable');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Loading...</td></tr>';
            
            try {
                let url = `api/fees.php?type=balances&term=${term}&year=${year}`;
                if (classId) url += `&class_id=${classId}`;
                if (streamId) url += `&stream_id=${streamId}`;
                if (admissionNo) url += `&admission_number=${encodeURIComponent(admissionNo)}`;
                if (studentName) url += `&student_name=${encodeURIComponent(studentName)}`;
                
                const response = await fetch(url);
                const data = await response.json();
                console.log('Balances response:', data);
                console.log('Number of records:', data.data ? data.data.length : 0);
                
                if (data.success) {
                    if (data.data && data.data.length > 0) {
                        window.balancesData = data.data;
                        console.log('Sample record:', data.data[0]);
                        renderBalances(data.data);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="11" class="text-center">No balances found for selected filters</td></tr>';
                        window.balancesData = [];
                    }
                } else {
                    tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error: ${data.error || 'Unknown error'}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading balances:', error);
                tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error: ${error.message}</td></tr>`;
            }
        }
        
        // Render balances to table
        function renderBalances(balances) {
            const tbody = document.getElementById('balancesTable');
            tbody.innerHTML = balances.map(balance => {
                const balanceVal = parseFloat(balance.balance) || 0;
                const hasOut = balanceVal > 0;
                return `
                <tr>
                    <td><input type="checkbox" class="balance-checkbox" data-student-id="${balance.student_id}" data-balance="${balanceVal}" data-student-name="${balance.student_name}" data-admission="${balance.admission_number}" data-fee-type="${balance.fee_type || 'Tuition'}" data-term="${balance.term}" data-year="${balance.year}" data-fee-amount="${balance.fee_amount}" data-paid-amount="${balance.paid_amount}" ${hasOut ? '' : 'disabled'}></td>
                    <td>${balance.admission_number}</td>
                    <td>${balance.student_name}</td>
                    <td>${balance.class_name || '-'}</td>
                    <td>${balance.stream_name || '-'}</td>
                    <td><strong>${balance.fee_type || 'Tuition'}</strong></td>
                    <td>${balance.term}</td>
                    <td>${balance.year}</td>
                    <td>KES ${balance.fee_amount.toLocaleString()}</td>
                    <td>KES ${balance.paid_amount.toLocaleString()}</td>
                    <td>KES ${balanceVal.toLocaleString()}</td>
                    <td>
                        <span class="badge ${balanceVal <= 0 ? 'bg-success' : 'bg-warning'}">
                            ${balanceVal <= 0 ? 'Paid' : 'Balance Due'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="generateFeeStatement(${balance.student_id})">
                            <i class="fas fa-file-invoice"></i> Statement
                        </button>
                    </td>
                </tr>
            `}).join('');
        }
        
        // Generate fee statement for a student
        async function generateFeeStatement(studentId) {
            try {
                const formData = new FormData();
                formData.append('student_id', studentId);
                
                const response = await fetch('api/generate_fee_statement.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Check if mobile/small screen
                    const isMobile = window.innerWidth <= 768;
                    
                    if (isMobile) {
                        // Download directly on mobile
                        const link = document.createElement('a');
                        link.href = '/Kenyaeduhub' + data.statement_url;
                        link.download = data.statement_filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        // Open in new tab on desktop
                        window.open('/Kenyaeduhub' + data.statement_url, '_blank');
                    }
                } else {
                    alert('Failed to generate fee statement: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error generating fee statement: ' + error.message);
            }
        }
        
        // Initialize
        loadClassesDropdown();
        loadClassesFilter();
        loadFeeStructures();
        loadPayments();
        populateTermYearFilters();
        
        // Populate term and year filters from database
        async function populateTermYearFilters() {
            const activeTerm = "<?php echo $active_term; ?>";
            const currentYear = "<?php echo date('Y'); ?>";
            
            console.log('Active term from PHP:', activeTerm);
            
            const termSelect = document.getElementById('balanceTerm');
            termSelect.innerHTML = '<option value="">Select Term</option>';
            
            try {
                // Get terms from terms table
                const termsResponse = await fetch('api/fees.php?type=terms');
                const termsData = await termsResponse.json();
                console.log('Terms response:', termsData);
                
                if (termsData.success && termsData.data && termsData.data.length > 0) {
                    let termSelected = false;
                    termsData.data.forEach(term => {
                        const termName = term.term_name;
                        const selected = termName === activeTerm ? 'selected' : '';
                        if (selected) termSelected = true;
                        termSelect.innerHTML += `<option value="${termName}" ${selected}>${termName}</option>`;
                        console.log('Added term:', termName, 'selected:', selected);
                    });
                    
                    if (!termSelected && termsData.data.length > 0) {
                        termSelect.value = termsData.data[0].term_name;
                        console.log('Active term not found, selected:', termsData.data[0].term_name);
                    }
                } else {
                    console.log('No terms found, using standard terms');
                    // Use standard terms as fallback
                    const standardTerms = ['Term 1', 'Term 2', 'Term 3'];
                    standardTerms.forEach(term => {
                        const selected = term === activeTerm ? 'selected' : '';
                        termSelect.innerHTML += `<option value="${term}" ${selected}>${term}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error fetching terms:', error);
                // Use standard terms as fallback
                const standardTerms = ['Term 1', 'Term 2', 'Term 3'];
                standardTerms.forEach(term => {
                    const selected = term === activeTerm ? 'selected' : '';
                    termSelect.innerHTML += `<option value="${term}" ${selected}>${term}</option>`;
                });
            }
            
            // Get years from fee structures
            try {
                const structureResponse = await fetch('api/fees.php?type=structure');
                const structureData = await structureResponse.json();
                console.log('Fee structures response:', structureData);
                
                if (structureData.success && structureData.data && structureData.data.length > 0) {
                    const years = [...new Set(structureData.data.map(fs => fs.year))].sort().reverse();
                    console.log('Available years:', years);
                    
                    const yearSelect = document.getElementById('balanceYear');
                    yearSelect.innerHTML = '<option value="">Select Year</option>';
                    years.forEach(year => {
                        const selected = year == currentYear ? 'selected' : '';
                        yearSelect.innerHTML += `<option value="${year}" ${selected}>${year}</option>`;
                    });
                    
                    const smsYearSelect = document.getElementById('smsYear');
                    smsYearSelect.innerHTML = '<option value="">Select Year</option>';
                    years.forEach(year => {
                        const selected = year == currentYear ? 'selected' : '';
                        smsYearSelect.innerHTML += `<option value="${year}" ${selected}>${year}</option>`;
                    });
                } else {
                    const yearSelect = document.getElementById('balanceYear');
                    yearSelect.innerHTML = `<option value="${currentYear}" selected>${currentYear}</option>`;
                    const smsYearSelect = document.getElementById('smsYear');
                    smsYearSelect.innerHTML = `<option value="${currentYear}" selected>${currentYear}</option>`;
                }
            } catch (error) {
                console.error('Error fetching years:', error);
                const yearSelect = document.getElementById('balanceYear');
                yearSelect.innerHTML = `<option value="${currentYear}" selected>${currentYear}</option>`;
                const smsYearSelect = document.getElementById('smsYear');
                smsYearSelect.innerHTML = `<option value="${currentYear}" selected>${currentYear}</option>`;
            }
        }
        
        // Send fee balances via SMS
        async function sendFeeBalancesViaSMS() {
            const selectedCheckboxes = document.querySelectorAll('.balance-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                alert('Please select at least one student with outstanding balance to send SMS.');
                return;
            }
            
            // Get the current term and year from the balance filters
            const balanceType = 'term'; // Always use term balance
            const year = document.getElementById('balanceYear').value;
            const term = document.getElementById('balanceTerm').value;
            
            if (!year || !term) {
                alert('Please select term and year from the balance filters first.');
                return;
            }
            
            // Get selected student data with fee details
            const students = [];
            selectedCheckboxes.forEach(cb => {
                students.push({
                    student_id: cb.dataset.studentId,
                    admission_number: cb.dataset.admission,
                    student_name: cb.dataset.studentName,
                    balance: cb.dataset.balance,
                    fee_type: cb.dataset.feeType,
                    fee_amount: cb.dataset.feeAmount,
                    paid_amount: cb.dataset.paidAmount
                });
            });
            
            console.log('Sending SMS with data:', { students, balanceType, year, term });
            
            // Show loading
            const sendBtn = document.querySelector('button[onclick="sendFeeBalancesViaSMS()"]');
            const originalText = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            try {
                const response = await fetch('api/send_fee_balance_sms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        students: students,
                        balance_type: balanceType,
                        year: year,
                        term: term
                    })
                });
                
                const data = await response.json();
                console.log('SMS API Response:', data);
                
                if (data.success) {
                    let message = `SMS sent successfully to ${data.sent_count} parent(s). ${data.failed_count || 0} failed.`;
                    if (data.error_details && data.error_details.length > 0) {
                        message += '\n\nError details:\n' + data.error_details.join('\n');
                    }
                    alert(message);
                    // Clear selections
                    document.getElementById('selectAllBalances').checked = false;
                    selectedCheckboxes.forEach(cb => cb.checked = false);
                } else {
                    alert(data.error || 'Failed to send SMS');
                }
            } catch (error) {
                console.error('Error sending SMS:', error);
                alert('An error occurred while sending SMS');
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
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
