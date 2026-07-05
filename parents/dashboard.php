<?php
// Parent Dashboard
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['parent_id'])) {
    header('Location: index.php');
    exit;
}

$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Initialize variables
$display_children = [];
$child_borrowings = [];

// Get parent details and their children
try {
    $stmt = $pdo->prepare("SELECT p.*, s.school_name FROM parents p JOIN schools s ON p.school_id = s.id WHERE p.id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    
    // Get children of this parent using student_parents table
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Get borrowings only for this parent's children
    $child_borrowings = [];
    foreach ($children as $child) {
        $child_id = $child['id'];
        $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.isbn
                               FROM book_borrowings bb
                               LEFT JOIN books b ON bb.book_id = b.id
                               WHERE bb.borrower_id = ? AND bb.borrower_type = 'student'
                               ORDER BY bb.borrow_date DESC");
        $stmt->execute([$child_id]);
        $borrowings = $stmt->fetchAll();
        if (!empty($borrowings)) {
            $child_borrowings[$child_id] = $borrowings;
        }
    }
    
    // Use parent's actual children for display
    $display_children = $children;
    
    // Get all students in the school for statistics
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE s.school_id = ? AND s.status = 'active'");
    $stmt->execute([$school_id]);
    $all_students = $stmt->fetchAll();
    
    // Get statistics for all students in school
    $stats = [
        'total_children' => count($all_students),
        'total_fees_due' => 0,
        'attendance_rate' => 0,
        'performance_records' => 0
    ];
    
    foreach ($all_students as $child) {
        // Calculate fee balance
        $child_id = $child['id'];
        $class_id = $child['class_id'];
        
        $current_term = 'Term 1';
        $current_year = date('Y');
        
        $total_fees = 0;
        if ($class_id) {
            $stmt = $pdo->prepare("SELECT amount FROM fee_structure WHERE school_id = ? AND class_id = ? AND term = ? AND year = ?");
            $stmt->execute([$school_id, $class_id, $current_term, $current_year]);
            $fee_structure = $stmt->fetch();
            if ($fee_structure) {
                $total_fees = $fee_structure['amount'];
            }
        }
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM fee_payments WHERE student_id = ?");
        $stmt->execute([$child_id]);
        $payments = $stmt->fetch();
        $total_paid = $payments['total_paid'];
        
        $balance = $total_fees - $total_paid;
        $stats['total_fees_due'] += max(0, $balance);
        
        // Get attendance records
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute([$child_id]);
        $attendance_total = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status = 'present'");
        $stmt->execute([$child_id]);
        $attendance_present = $stmt->fetch()['present'];
        
        if ($attendance_total > 0) {
            $stats['attendance_rate'] += ($attendance_present / $attendance_total) * 100;
        }
        
        // Get performance records
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance WHERE student_id = ? AND term = ? AND year = ?");
        $stmt->execute([$child_id, $current_term, $current_year]);
        $stats['performance_records'] += $stmt->fetch()['total'];
    }
    
    // Calculate average attendance rate
    if ($stats['total_children'] > 0) {
        $stats['attendance_rate'] = round($stats['attendance_rate'] / $stats['total_children'], 1);
    }
    
} catch (PDOException $e) {
    error_log("Failed to fetch parent details: " . $e->getMessage());
    $parent = null;
    $children = [];
    $stats = [
        'total_children' => 0,
        'total_fees_due' => 0,
        'attendance_rate' => 0,
        'performance_records' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($parent_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            transition: box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
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
        
        .stat-change {
            font-size: 12px;
            color: #5f6368;
        }
        
        .stat-change.positive {
            color: #1e8e3e;
        }
        
        .stat-change.negative {
            color: #d93025;
        }
        
        /* Children List */
        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .child-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        
        .child-card:hover {
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        .child-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .child-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 18px;
        }
        
        .child-info h3 {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .child-info p {
            font-size: 13px;
            color: #5f6368;
            margin: 0;
        }
        
        .child-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 16px;
        }
        
        .child-stat {
            text-align: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .child-stat-value {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
        }
        
        .child-stat-label {
            font-size: 12px;
            color: #5f6368;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 12px;
            text-align: center;
        }
        
        .badge-warning {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .badge-success {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-secondary {
            background: #f1f3f4;
            color: #5f6368;
        }
        
        /* Table */
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
                padding: 8px;
            }
            
            .card {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .card-title {
                font-size: 16px;
                margin-bottom: 12px;
            }
            
            .page-title {
                font-size: 20px;
                margin-bottom: 16px;
            }
            
            .page-subtitle {
                font-size: 13px;
                margin-bottom: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stat-label {
                font-size: 11px;
            }
            
            .children-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .child-card {
                padding: 12px;
            }
            
            .child-name {
                font-size: 16px;
            }
            
            .child-stat-label {
                font-size: 11px;
            }
            
            .child-stat-value {
                font-size: 14px;
            }
            
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
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
        <div class="header-right">
            <div class="user-avatar">
                <?php echo strtoupper(substr($parent_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="children.php">
                <i class="fas fa-child"></i> My Children
            </a>
            <a class="nav-link" href="performance.php">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance.php">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="fees.php">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
            Welcome, <?php echo htmlspecialchars($parent_name); ?>
            <?php if ($school_name): ?>
                | <?php echo htmlspecialchars($school_name); ?>
            <?php endif; ?>
        </p>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Children</div>
                <div class="stat-value"><?php echo $stats['total_children']; ?></div>
                <div class="stat-change">Enrolled</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Fees Due</div>
                <div class="stat-value">KES <?php echo number_format($stats['total_fees_due']); ?></div>
                <div class="stat-change <?php echo $stats['total_fees_due'] > 0 ? 'negative' : 'positive'; ?>">
                    <?php echo $stats['total_fees_due'] > 0 ? 'Outstanding' : 'Paid'; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Attendance Rate</div>
                <div class="stat-value"><?php echo $stats['attendance_rate']; ?>%</div>
                <div class="stat-change positive">Last 30 days</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Performance Records</div>
                <div class="stat-value"><?php echo $stats['performance_records']; ?></div>
                <div class="stat-change">This term</div>
            </div>
        </div>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="performance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="attendance.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="fees.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="quick-access-label">Fee Payments</div>
                </a>
                <a href="children.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="quick-access-label">My Children</div>
                </a>
                <a href="profile.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="quick-access-label">Profile</div>
                </a>
                <a href="#" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="quick-access-label">Messages</div>
                </a>
            </div>
        </div>
        
        <!-- Children Card -->
        <div class="card">
            <h2 class="card-title">My Children</h2>
            <?php if (empty($children)): ?>
                <p class="text-muted">No children enrolled yet.</p>
            <?php else: ?>
                <div class="children-grid">
                    <?php foreach ($children as $child): ?>
                        <div class="child-card">
                            <div class="child-header">
                                <div class="child-avatar">
                                    <?php echo strtoupper(substr($child['first_name'], 0, 1)); ?>
                                </div>
                                <div class="child-info">
                                    <h3><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($child['admission_number']); ?></p>
                                    <p><?php echo htmlspecialchars($child['class_name'] ?? 'No Class'); ?> <?php echo htmlspecialchars($child['stream_name'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="child-stats">
                                <div class="child-stat">
                                    <div class="child-stat-value">
                                        <?php 
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                                        $stmt->execute([$child['id']]);
                                        echo $stmt->fetch()['total'];
                                        ?>
                                    </div>
                                    <div class="child-stat-label">Attendance Days</div>
                                </div>
                                <div class="child-stat">
                                    <div class="child-stat-value">
                                        <?php 
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance WHERE student_id = ? AND term = 'Term 1' AND year = YEAR(CURDATE())");
                                        $stmt->execute([$child['id']]);
                                        echo $stmt->fetch()['total'];
                                        ?>
                                    </div>
                                    <div class="child-stat-label">Performance Records</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Library Books Card -->
        <div class="card">
            <h2 class="card-title">Library Books (My Children)</h2>
            <?php 
            $has_borrowings = false;
            if (!empty($children)) {
                foreach ($children as $child): 
                    if (!empty($child_borrowings[$child['id']])):
                        $has_borrowings = true;
            ?>
                <div style="margin-bottom: 32px;">
                    <h3 style="font-size: 16px; font-weight: 500; color: #202124; margin-bottom: 16px;">
                        <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?> (<?php echo htmlspecialchars($child['admission_number']); ?>)
                    </h3>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($child_borrowings[$child['id']] as $borrowing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrowing['title']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['author']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['isbn']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($borrowing['due_date'])); ?></td>
                                        <td>
                                            <?php if ($borrowing['status'] == 'borrowed'): ?>
                                                <span class="badge badge-warning">Borrowed</span>
                                            <?php elseif ($borrowing['status'] == 'returned'): ?>
                                                <span class="badge badge-success">Returned</span>
                                            <?php elseif ($borrowing['status'] == 'lost'): ?>
                                                <span class="badge badge-danger">Lost</span>
                                            <?php elseif ($borrowing['status'] == 'overdue'): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?php echo htmlspecialchars($borrowing['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php 
                    endif; 
                endforeach; 
            }
            ?>
            
            <?php if (!$has_borrowings): ?>
                <p class="text-muted">No library books borrowed by your children yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Activity Card -->
        <div class="card">
            <h2 class="card-title">Recent Activity</h2>
            <div class="activity-list">
                <div style="display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #e8eaed;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e8f0fe; color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 14px; color: #202124; margin-bottom: 4px;">Attendance records updated</div>
                        <div style="font-size: 12px; color: #5f6368;">Today</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #e8eaed;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e8f0fe; color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 14px; color: #202124; margin-bottom: 4px;">Performance reports available</div>
                        <div style="font-size: 12px; color: #5f6368;">Yesterday</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; padding: 12px 0;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e8f0fe; color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 14px; color: #202124; margin-bottom: 4px;">Fee payment received</div>
                        <div style="font-size: 12px; color: #5f6368;">2 days ago</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
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
