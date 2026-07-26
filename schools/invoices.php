<?php
// Invoice Management Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get current term and year (default to current)
$current_term = $_GET['term'] ?? '';
$current_year = $_GET['year'] ?? date('Y');

// Get classes for this school
try {
    $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

// Get invoices based on filters
$invoices = [];
try {
    $query = "SELECT i.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name, c.class_name,
              (SELECT COALESCE(SUM(fp.amount), 0) 
               FROM fee_payments fp 
               WHERE fp.student_id = i.student_id 
               AND fp.term = i.term 
               AND fp.year = i.year 
               AND fp.fee_type = 'Tuition' 
               AND fp.status = 'completed') as actual_paid
              FROM invoices i
              LEFT JOIN students s ON i.student_id = s.id
              LEFT JOIN classes c ON i.class_id = c.id
              WHERE i.school_id = ?";
    
    $params = [$school_id];
    
    if (!empty($_GET['term'])) {
        $query .= " AND i.term = ?";
        $params[] = $_GET['term'];
    }
    
    if (!empty($_GET['year'])) {
        $query .= " AND i.year = ?";
        $params[] = $_GET['year'];
    }
    
    if (!empty($_GET['status'])) {
        $query .= " AND i.status = ?";
        $params[] = $_GET['status'];
    }
    
    $query .= " ORDER BY i.issue_date DESC, i.invoice_number DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("School ID: " . $school_id);
    error_log("Invoices found: " . count($invoices));
    error_log("Query: " . $query);
    error_log("Params: " . json_encode($params));
    error_log("GET params: " . json_encode($_GET));
    
    // Calculate status and balance for each invoice
    foreach ($invoices as &$invoice) {
        $actual_paid = $invoice['actual_paid'];
        $actual_balance = $invoice['total_amount'] - $actual_paid;
        
        if ($actual_balance <= 0) {
            $invoice['calculated_status'] = 'paid';
            $actual_balance = 0;
        } elseif ($actual_paid > 0) {
            $invoice['calculated_status'] = 'partial';
        } elseif (strtotime($invoice['due_date']) < time()) {
            $invoice['calculated_status'] = 'overdue';
        } else {
            $invoice['calculated_status'] = 'pending';
        }
        
        $invoice['actual_balance'] = $actual_balance;
    }
} catch (PDOException $e) {
    error_log("Error fetching invoices: " . $e->getMessage());
    $invoices = [];
}

// Get invoice statistics
try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
        SUM(total_amount) as total_amount,
        SUM((SELECT COALESCE(SUM(fp.amount), 0) 
             FROM fee_payments fp 
             WHERE fp.student_id = i.student_id 
             AND fp.term = i.term 
             AND fp.year = i.year 
             AND fp.fee_type = 'Tuition' 
             AND fp.status = 'completed')) as total_paid,
        SUM(total_amount - (SELECT COALESCE(SUM(fp.amount), 0) 
                            FROM fee_payments fp 
                            WHERE fp.student_id = i.student_id 
                            AND fp.term = i.term 
                            AND fp.year = i.year 
                            AND fp.fee_type = 'Tuition' 
                            AND fp.status = 'completed')) as total_balance
        FROM invoices i WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        'paid' => 0,
        'partial' => 0,
        'pending' => 0,
        'overdue' => 0,
        'total_amount' => 0,
        'total_paid' => 0,
        'total_balance' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - <?php echo htmlspecialchars($school_name); ?></title>
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
            font-size: 14px;
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
            border-right: 1px solid #e8eaed;
            overflow-y: auto;
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
            border-bottom: 1px solid #f1f3f4;
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
            color: #202124;
            text-decoration: none;
            transition: background 0.2s;
            gap: 12px;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            color: #FF6B35;
        }
        
        .nav-link:hover {
            background: #FFF3E0;
        }
        
        .nav-link.active {
            background: #FFF3E0;
            color: #FF6B35;
            font-weight: 500;
        }
        
        .nav-link.active i {
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
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
        }
        
        /* Stat Cards */
        .stat-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            color: #FF6B35;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--secondary-color);
        }
        
        /* Table */
        .table {
            background: var(--bg-color);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e8eaed;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #FF6B35;
        }
        
        .table th {
            font-weight: 600;
            color: #202124;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 14px 16px;
            border-bottom: 1px solid #e8eaed;
            background: #f8f9fa;
        }
        
        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e8eaed;
            vertical-align: middle;
            font-size: 13px;
            color: #202124;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #e8eaed;
        }
        
        .table tbody tr:last-child {
            border-bottom: none;
        }
        
        .table tbody tr:hover {
            background: #FFF3E0;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-paid { background: #e6f4ea; color: #137333; }
        .status-partial { background: #fef7e0; color: #b06000; }
        .status-pending { background: #fce8e6; color: #c5221f; }
        .status-overdue { background: #f3e8fd; color: #7b1fa2; }
        
        /* Buttons */
        .btn-primary {
            background: #FF6B35;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 1px solid #FF6B35;
            color: #FF6B35;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-outline-primary:hover {
            background: #FFF3E0;
        }
        
        .btn-outline-secondary {
            background: transparent;
            border: 1px solid #dadce0;
            color: #5f6368;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-outline-secondary:hover {
            background: #f1f3f4;
        }
        
        /* Forms */
        .form-select, .form-control {
            border-radius: 4px;
            border: 1px solid #dadce0;
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.1);
            outline: none;
        }
        
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 4px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
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
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .stat-icon {
                margin: 0 auto;
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
                <div style="width: 32px; height: 32px; background: #FFD700; border: 2px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 16px;">
                        <span style="color: #FF6B35; font-size: 20px;">K</span><span style="color: #008000; font-size: 16px;">E</span>
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
                Academic <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
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
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Academic Records <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link" href="performance">
                    <i class="fas fa-chart-line"></i> Performance
                </a>
                <a class="nav-link" href="results">
                    <i class="fas fa-clipboard-list"></i> Results
                </a>
                <a class="nav-link" href="disciplinary">
                    <i class="fas fa-shield-alt"></i> Disciplinary
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Financial <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="fees">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>
                <a class="nav-link active" href="invoices">
                    <i class="fas fa-file-invoice-dollar"></i> Invoices
                </a>
                <a class="nav-link" href="finance-managers">
                    <i class="fas fa-user-tie"></i> Finance Managers
                </a>
                <a class="nav-link" href="account">
                    <i class="fas fa-wallet"></i> Account Balance
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Administrative <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="parents">
                    <i class="fas fa-users"></i> Parents
                </a>
                <a class="nav-link" href="librarians">
                    <i class="fas fa-book"></i> Librarians
                </a>
                <a class="nav-link" href="duty-assignments">
                    <i class="fas fa-clipboard-list"></i> Duty Assignments
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Settings <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a class="nav-link" href="profile">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a class="nav-link" href="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Invoice Management</h1>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label">Total Invoices</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">KES <?php echo number_format($stats['total_amount'], 2); ?></div>
                        <div class="stat-label">Total Amount</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">KES <?php echo number_format($stats['total_paid'], 2); ?></div>
                        <div class="stat-label">Total Collected</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">KES <?php echo number_format($stats['total_balance'], 2); ?></div>
                        <div class="stat-label">Outstanding Balance</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Invoice Generation Form -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Generate Invoices</div>
            </div>
            <div class="card-body">
                <form id="invoiceForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select class="form-select" id="classSelect" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Term</label>
                        <select class="form-select" id="termSelect" required>
                            <option value="Term 1" <?php echo $current_term === 'Term 1' ? 'selected' : ''; ?>>Term 1</option>
                            <option value="Term 2" <?php echo $current_term === 'Term 2' ? 'selected' : ''; ?>>Term 2</option>
                            <option value="Term 3" <?php echo $current_term === 'Term 3' ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" id="yearSelect" value="<?php echo $current_year; ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Filter Invoices</div>
            </div>
            <div class="card-body">
                <form class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Term</label>
                        <select class="form-select" id="filterTerm" onchange="filterInvoices()">
                            <option value="">All Terms</option>
                            <option value="Term 1" <?php echo ($_GET['term'] ?? '') === 'Term 1' ? 'selected' : ''; ?>>Term 1</option>
                            <option value="Term 2" <?php echo ($_GET['term'] ?? '') === 'Term 2' ? 'selected' : ''; ?>>Term 2</option>
                            <option value="Term 3" <?php echo ($_GET['term'] ?? '') === 'Term 3' ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" id="filterYear" value="<?php echo $current_year; ?>" onchange="filterInvoices()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="filterStatus" onchange="filterInvoices()">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="partial" <?php echo ($_GET['status'] ?? '') === 'partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="paid" <?php echo ($_GET['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="overdue" <?php echo ($_GET['status'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn-primary w-100" onclick="clearFilters()">Clear Filters</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Invoices Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Invoices List</div>
                <button class="btn-primary" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="invoicesTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Term/Year</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Issue Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($invoice['student_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($invoice['admission_number']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($invoice['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['term'] . ' ' . $invoice['year']); ?></td>
                                    <td>KES <?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td>KES <?php echo number_format($invoice['actual_paid'], 2); ?></td>
                                    <td><strong>KES <?php echo number_format($invoice['actual_balance'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $invoice['calculated_status']; ?>">
                                            <?php echo ucfirst($invoice['calculated_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></td>
                                    <td>
                                        <button class="btn-outline-primary" onclick="viewInvoice(<?php echo $invoice['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-outline-secondary" onclick="printInvoice(<?php echo $invoice['id']; ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="btn-outline-secondary" onclick="deleteInvoice(<?php echo $invoice['id']; ?>)" style="color: #dc3545; border-color: #dc3545;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-file-invoice-dollar" style="font-size: 48px; color: #dadce0;"></i>
                                        <p class="mt-2 text-muted">No invoices found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Log invoice data for debugging
        console.log('School ID: <?php echo $school_id; ?>');
        console.log('Invoices count: <?php echo count($invoices); ?>');
        console.log('Invoices data:', <?php echo json_encode($invoices); ?>);
        console.log('Statistics:', <?php echo json_encode($stats); ?>);
        
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Mobile handling
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            }
        }
        
        // Toggle sidebar section
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
        
        // Export table to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(18);
            doc.text('Invoices Report', 14, 22);
            
            // Add school name
            doc.setFontSize(12);
            doc.text('<?php echo htmlspecialchars($school_name); ?>', 14, 30);
            
            // Add date
            doc.setFontSize(10);
            doc.text('Generated: ' + new Date().toLocaleDateString(), 14, 38);
            
            // Prepare table data
            const tableData = [];
            const headers = ['Invoice #', 'Student', 'Class', 'Term/Year', 'Total', 'Paid', 'Balance', 'Status', 'Issue Date'];
            
            // Get all rows from table
            const rows = document.querySelectorAll('#invoicesTable tbody tr');
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach(cell => {
                    rowData.push(cell.innerText.trim());
                });
                tableData.push(rowData);
            });
            
            // Generate table
            doc.autoTable({
                head: [headers],
                body: tableData,
                startY: 45,
                styles: {
                    fontSize: 9,
                    cellPadding: 3,
                },
                headStyles: {
                    fillColor: [255, 107, 53],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                },
                columnStyles: {
                    0: { cellWidth: 25 }, // Invoice #
                    1: { cellWidth: 30 }, // Student
                    2: { cellWidth: 20 }, // Class
                    3: { cellWidth: 20 }, // Term/Year
                    4: { cellWidth: 20 }, // Total
                    5: { cellWidth: 20 }, // Paid
                    6: { cellWidth: 20 }, // Balance
                    7: { cellWidth: 20 }, // Status
                    8: { cellWidth: 25 }, // Issue Date
                }
            });
            
            // Save PDF
            doc.save('invoices_report_' + new Date().toISOString().split('T')[0] + '.pdf');
        }
        
        // Generate invoices
        document.getElementById('invoiceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const classId = document.getElementById('classSelect').value;
            const term = document.getElementById('termSelect').value;
            const year = document.getElementById('yearSelect').value;
            
            if (!classId || !term || !year) {
                notificationSystem.warning('Missing Information', 'Please fill in all fields');
                return;
            }
            
            if (!confirm('Generate invoices for all students in this class?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('class_id', classId);
            formData.append('term', term);
            formData.append('year', year);
            
            fetch('api/invoice_generation.php?action=generate_class_invoices', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notificationSystem.success('Invoices Generated', `Generated ${data.success_count} invoices successfully. ${data.error_count} failed.`);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    notificationSystem.error('Error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notificationSystem.error('Error', 'Error generating invoices: ' + error.message);
            });
        });
        
        // Filter invoices
        function filterInvoices() {
            const term = document.getElementById('filterTerm').value;
            const year = document.getElementById('filterYear').value;
            const status = document.getElementById('filterStatus').value;
            
            let url = 'invoices?';
            if (term) url += 'term=' + term + '&';
            if (year) url += 'year=' + year + '&';
            if (status) url += 'status=' + status + '&';
            
            window.location.href = url.slice(0, -1);
        }
        
        // Clear filters
        function clearFilters() {
            window.location.href = 'invoices';
        }
        
        // View invoice details
        function viewInvoice(invoiceId) {
            notificationSystem.info('Invoice Details', 'View invoice details for ID: ' + invoiceId);
        }
        
        // Print invoice
        function printInvoice(invoiceId) {
            window.open('print_invoice.php?id=' + invoiceId, '_blank');
        }
        
        // Delete invoice
        function deleteInvoice(invoiceId) {
            notificationSystem.confirm('Are you sure you want to delete this invoice? This action cannot be undone.', {
                confirmText: 'Delete',
                cancelText: 'Cancel'
            }).then(confirmed => {
                if (confirmed) {
                    fetch('api/invoice_generation.php?action=delete_invoice', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ invoice_id: invoiceId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            notificationSystem.success('Invoice Deleted', 'Invoice has been deleted successfully');
                            location.reload();
                        } else {
                            notificationSystem.error('Error', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        notificationSystem.error('Error', 'Error deleting invoice');
                    });
                }
            });
        }
    </script>
</body>
</html>
