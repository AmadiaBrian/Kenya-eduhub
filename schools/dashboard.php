<?php
// Schools Dashboard
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

$school_name = $_SESSION['school_name'] ?? 'School';
$school_code = $_SESSION['school_code'] ?? '';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $_SESSION['school_id']);

// Get school logo
try {
    $stmt = $pdo->prepare("SELECT logo FROM schools WHERE id = ?");
    $stmt->execute([$_SESSION['school_id']]);
    $school = $stmt->fetch();
    $school_logo = $school['logo'] ?? null;
} catch (PDOException $e) {
    $school_logo = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Dashboard - <?php echo htmlspecialchars($school_name); ?></title>
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
            text-align: center;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        /* Quick Actions & School Info Grid */
        .quick-actions-school-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        /* School Info Table */
        .school-info-table {
            width: 100%;
        }
        
        .school-info-table td {
            padding: 10px 12px;
            border: 1px solid #000;
        }
        
        .school-info-table td:first-child {
            width: 40%;
            font-weight: 500;
        }
        
        .stat-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        
        .stat-card:hover {
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        .stat-label {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }
        
        .stat-icon.students { color: #FF6B35; }
        .stat-icon.classes { color: #FF6B35; }
        .stat-icon.parents { color: #FF6B35; }
        .stat-icon.performance { color: #FF6B35; }
        
        /* Responsive */
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
                padding-top: calc(var(--header-height) + 16px);
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            
            .stat-card {
                padding: 12px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                text-align: center;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin-bottom: 8px;
            }
            
            .stat-value {
                font-size: 20px;
                font-weight: 400;
                margin-bottom: 4px;
            }
            
            .stat-label {
                font-size: 11px;
                font-weight: 500;
                color: #5f6368;
                margin-bottom: 0;
            }
            
            .card-title[style*="justify-content: space-between"] {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .card-title[style*="justify-content: space-between"] .btn {
                width: 100%;
                text-align: center;
            }
            
            .quick-actions-school-info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .school-info-table {
                min-width: 100%;
            }
            
            .school-info-table td {
                padding: 8px;
                font-size: 12px;
            }
            
            .school-info-table td:first-child {
                width: 45%;
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
            
            .stat-card {
                text-align: center;
            }
            
            .stat-icon {
                margin: 0 auto;
            }
            
            .card-title {
                justify-content: center !important;
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
                padding: 8px;
                padding-top: calc(var(--header-height) + 8px);
            }
            
            .page-title {
                font-size: 20px;
                margin-bottom: 16px;
            }
            
            .main-content > p {
                font-size: 13px;
                margin-bottom: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            
            .stat-card {
                padding: 12px;
                text-align: center;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin-bottom: 8px;
            }
            
            .stat-value {
                font-size: 20px;
                margin-bottom: 4px;
            }
            
            .stat-label {
                font-size: 11px;
                margin-bottom: 0;
            }
            
            .card-title[style*="justify-content: space-between"] {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .card-title[style*="justify-content: space-between"] .btn {
                width: 100%;
                text-align: center;
            }
            
            .card {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .card-title {
                font-size: 16px;
                margin-bottom: 12px;
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
        
        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            color: #000000;
            background: #ffffff;
            border: 1px solid #000000;
        }
        
        .badge-active {
            background: #e6f4ea;
            color: #000000;
            border: 1px solid #137333;
        }
        
        .badge-pending {
            background: #fef7e0;
            color: #000000;
            border: 1px solid #f9ab00;
        }
        
        .badge-inactive {
            background: #fce8e6;
            color: #000000;
            border: 1px solid #c5221f;
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
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link active" href="dashboard">
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
            <a class="nav-link" href="fees">
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
            <a class="nav-link" href="duty-assignments">
                <i class="fas fa-clipboard-list"></i> Duty Assignments
            </a>
            <a class="nav-link" href="librarians">
                <i class="fas fa-book-reader"></i> Librarians
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
        <h1 class="page-title">Welcome</h1>
        <p style="color: #5f6368; margin-bottom: 32px;">School Code: <?php echo htmlspecialchars($school_code); ?></p>
        
        <!-- Calendar Status -->
        <div style="margin-bottom: 24px;">
            <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; border-left: 4px solid <?php echo $calendar_status['is_holiday'] ? '#c5221f' : ($calendar_status['current_term'] ? '#137333' : '#f9ab00'); ?>;">
                <?php if ($calendar_status['is_holiday']): ?>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <i class="fas fa-umbrella-beach" style="color: #c5221f; font-size: 20px;"></i>
                        <div>
                            <p style="color: #c5221f; font-weight: 600; margin: 0; font-size: 16px;">
                                School is on Holiday
                            </p>
                            <p style="color: #5f6368; margin: 4px 0 0 0; font-size: 14px;">
                                <?php echo htmlspecialchars($calendar_status['current_holiday']['holiday_name']); ?>
                                (<?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['end_date'])); ?>)
                            </p>
                        </div>
                    </div>
                <?php elseif ($calendar_status['current_term']): ?>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <i class="fas fa-school" style="color: #137333; font-size: 20px;"></i>
                        <div>
                            <p style="color: #137333; font-weight: 600; margin: 0; font-size: 16px;">
                                School is in Session
                            </p>
                            <p style="color: #5f6368; margin: 4px 0 0 0; font-size: 14px;">
                                <?php echo htmlspecialchars($calendar_status['current_term']['term_name']); ?>
                                (<?php echo date('M j, Y', strtotime($calendar_status['current_term']['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($calendar_status['current_term']['end_date'])); ?>)
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <i class="fas fa-calendar-times" style="color: #f9ab00; font-size: 20px;"></i>
                        <div>
                            <p style="color: #f9ab00; font-weight: 600; margin: 0; font-size: 16px;">
                                School is on Break
                            </p>
                            <p style="color: #5f6368; margin: 4px 0 0 0; font-size: 14px;">
                                No active term for current date
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon students">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon classes">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value" id="totalClasses">0</div>
                <div class="stat-label">Total Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon parents">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value" id="totalParents">0</div>
                <div class="stat-label">Registered Parents</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon performance">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value" id="avgPerformance">0%</div>
                <div class="stat-label">Avg Performance</div>
            </div>
        </div>
        
        <!-- Recent Students -->
        <div class="card">
            <div class="card-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="font-size: 18px; font-weight: 500;">Recent Students</span>
                <a href="students.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px; background: #FF6B35; border-color: #FF6B35; border-radius: 20px; text-decoration: none; color: white;">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Admission No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentStudents">
                        <tr>
                            <td colspan="5" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="students.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="quick-access-label">Students</div>
                </a>
                <a href="teachers.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="quick-access-label">Teachers</div>
                </a>
                <a href="classes.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
                <a href="attendance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="performance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="fees.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="quick-access-label">Fees</div>
                </a>
            </div>
        </div>
        
        <!-- Quick Actions & School Info -->
        <div class="quick-actions-school-info-grid">
            <div class="card">
                <h2 class="card-title">Quick Actions</h2>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="students.php?action=add" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus" style="margin-right: 8px;"></i> Add New Student
                    </a>
                    <a href="classes.php?action=add" class="btn btn-outline-primary">
                        <i class="fas fa-plus-circle" style="margin-right: 8px;"></i> Create Class
                    </a>
                    <a href="parents.php?action=add" class="btn btn-outline-primary">
                        <i class="fas fa-user-friends" style="margin-right: 8px;"></i> Register Parent
                    </a>
                    <a href="settings.php" class="btn btn-outline-primary">
                        <i class="fas fa-cog" style="margin-right: 8px;"></i> Admission Settings
                    </a>
                    <a href="attendance.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Take Attendance
                    </a>
                </div>
            </div>
            <div class="card">
                <h2 class="card-title">School Information</h2>
                <div class="table-responsive">
                    <table class="table school-info-table">
                        <tr>
                            <td><strong>School Code:</strong></td>
                            <td><?php echo htmlspecialchars($school_code); ?></td>
                        </tr>
                        <tr>
                            <td><strong>School Name:</strong></td>
                            <td><?php echo htmlspecialchars($school_name); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td><span style="background: #e6f4ea; color: #1e8e3e; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 500;">Active</span></td>
                        </tr>
                        <tr>
                            <td><strong>Academic Year:</strong></td>
                            <td>2026</td>
                        </tr>
                    </table>
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
            
            titleElement.classList.toggle('collapsed');
            linksContainer.classList.toggle('collapsed');
        }
        
        // Only enable collapsible sections on large screens
        function handleResize() {
            const sidebarTitles = document.querySelectorAll('.sidebar-title');
            const sidebarLinks = document.querySelectorAll('.sidebar-links');
            
            if (window.innerWidth <= 768) {
                // On mobile, expand all sections
                sidebarTitles.forEach(title => title.classList.remove('collapsed'));
                sidebarLinks.forEach(links => links.classList.remove('collapsed'));
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        // Load dashboard statistics
        async function loadStats() {
            try {
                // Load students
                const studentsRes = await fetch('api/students.php');
                const studentsData = await studentsRes.json();
                if (studentsData.success) {
                    document.getElementById('totalStudents').textContent = studentsData.data.length;
                    
                    // Display recent students
                    const recentStudents = studentsData.data.slice(0, 5);
                    const tbody = document.getElementById('recentStudents');
                    tbody.innerHTML = recentStudents.map(student => `
                        <tr>
                            <td>${student.admission_number}</td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${student.class_name || '-'}</td>
                            <td>${student.stream_name || '-'}</td>
                            <td><span class="badge badge-${student.status === 'active' ? 'active' : 'pending'}">${student.status}</span></td>
                        </tr>
                    `).join('');
                }
                
                // Load classes
                const classesRes = await fetch('api/classes.php');
                const classesData = await classesRes.json();
                if (classesData.success) {
                    document.getElementById('totalClasses').textContent = classesData.data.length;
                }
                
                // Load parents
                const parentsRes = await fetch('api/parents.php');
                const parentsData = await parentsRes.json();
                if (parentsData.success) {
                    document.getElementById('totalParents').textContent = parentsData.data.length;
                }
                
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        loadStats();
    </script>
    
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
