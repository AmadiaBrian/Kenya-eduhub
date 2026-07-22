<?php
// Finance Manager Reports Page
// Authentication is handled by index.php router
$finance_manager_id = $_SESSION['finance_manager_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get filter parameters
$filter_year = $_GET['year'] ?? date('Y');
$filter_term = $_GET['term'] ?? '';
$filter_class = $_GET['class'] ?? '';

// Get classes
$classes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Build query for fee collections (only successful/completed, only Tuition fees)
$query = "SELECT fp.*, s.first_name, s.last_name, s.admission_number, c.class_name 
          FROM fee_payments fp 
          JOIN students s ON fp.student_id = s.id 
          LEFT JOIN classes c ON s.class_id = c.id 
          WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)";
$params = [$school_id, $filter_year];

if ($filter_term) {
    $query .= " AND fp.term = ?";
    $params[] = $filter_term;
}

if ($filter_class) {
    $query .= " AND s.class_id = ?";
    $params[] = $filter_class;
}

$query .= " ORDER BY fp.payment_date DESC";

// Get fee collections
$fee_collections = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $fee_collections = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch fee collections: " . $e->getMessage());
}

// Calculate totals
$total_collected = 0;
foreach ($fee_collections as $collection) {
    $total_collected += $collection['amount'];
}

// Get outstanding balances by class and term (only Tuition fees, only completed payments)
$outstanding_by_class = [];
try {
    $query = "SELECT c.class_name, fs.term,
              (fs.amount * COUNT(DISTINCT s.id)) as total_fees, 
              COALESCE(SUM(fp.amount), 0) as total_paid
              FROM classes c
              JOIN fee_structure fs ON fs.class_id = c.id AND fs.school_id = ? AND fs.year = ? AND fs.fee_type = 'Tuition'
              LEFT JOIN students s ON s.class_id = c.id AND s.school_id = ? AND s.status = 'active'
              LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.year = fs.year AND fp.term = fs.term AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
              WHERE c.school_id = ?";
    $params = [$school_id, $filter_year, $school_id, $school_id];
    
    if ($filter_term) {
        $query .= " AND fs.term = ?";
        $params[] = $filter_term;
    }
    
    $query .= " GROUP BY c.id, c.class_name, fs.term ORDER BY c.class_name, fs.term";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $outstanding_by_class = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch outstanding balances: " . $e->getMessage());
}

// Get non-tuition fee structures and their collection status (only completed payments)
$non_tuition_fees = [];
try {
    $query = "SELECT fs.id, fs.fee_type, fs.term, fs.year, fs.amount, fs.description, c.class_name,
              COUNT(DISTINCT s.id) as student_count,
              COALESCE(SUM(fp.amount), 0) as total_collected
              FROM fee_structure fs
              JOIN classes c ON fs.class_id = c.id
              LEFT JOIN students s ON s.class_id = c.id AND s.school_id = fs.school_id AND s.status = 'active'
              LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.year = fs.year AND fp.term = fs.term AND (fp.fee_type = fs.fee_type OR fp.fee_type IS NULL) AND fp.status = 'completed'
              WHERE fs.school_id = ? AND fs.year = ? AND fs.fee_type != 'Tuition'";
    $params = [$school_id, $filter_year];
    
    if ($filter_term) {
        $query .= " AND fs.term = ?";
        $params[] = $filter_term;
    }
    
    if ($filter_class) {
        $query .= " AND fs.class_id = ?";
        $params[] = $filter_class;
    }
    
    $query .= " GROUP BY fs.id, fs.fee_type, fs.term, fs.year, fs.amount, fs.description, c.class_name
              ORDER BY fs.fee_type, fs.term, c.class_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $non_tuition_fees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch non-tuition fees: " . $e->getMessage());
}

// Get student-wise fee payment status for all fee types (only completed payments)
$student_fee_status = [];
try {
    $query = "SELECT s.id as student_id, s.admission_number, s.first_name, s.last_name, c.class_name,
              fs.fee_type, fs.term, fs.year, fs.amount as fee_amount,
              COALESCE(SUM(fp.amount), 0) as paid_amount
              FROM students s
              JOIN classes c ON s.class_id = c.id
              JOIN fee_structure fs ON fs.class_id = c.id
              LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.year = fs.year AND fp.term = fs.term 
                  AND (fp.fee_type = fs.fee_type OR (fp.fee_type IS NULL AND fs.fee_type = 'Tuition')) AND fp.status = 'completed'
              WHERE s.school_id = ? AND s.status = 'active' AND fs.year = ?";
    $params = [$school_id, $filter_year];
    
    if ($filter_term) {
        $query .= " AND fs.term = ?";
        $params[] = $filter_term;
    }
    
    if ($filter_class) {
        $query .= " AND fs.class_id = ?";
        $params[] = $filter_class;
    }
    
    $query .= " GROUP BY s.id, s.admission_number, s.first_name, s.last_name, c.class_name, 
              fs.fee_type, fs.term, fs.year, fs.amount
              ORDER BY c.class_name, s.last_name, s.first_name, fs.fee_type, fs.term";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $student_fee_status = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch student fee status: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo htmlspecialchars($finance_manager_name); ?></title>
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
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 4px;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #e8f5e9;
            color: #137333;
        }
        
        .badge-danger {
            background: #ffebee;
            color: #c5221f;
        }
        
        .summary-card {
            background: #fff3e0;
            border: 1px solid #FF6B35;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 28px;
            font-weight: 500;
            color: #FF6B35;
            margin-bottom: 8px;
        }
        
        .summary-label {
            font-size: 14px;
            color: #5f6368;
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
            }
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }
            
            .table {
                min-width: 100%;
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
                <?php echo strtoupper(substr($finance_manager_name, 0, 1)); ?>
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
            <a class="nav-link" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice"></i> Invoices
            </a>
            <a class="nav-link active" href="reports">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a class="nav-link" href="reminders">
                <i class="fas fa-bell"></i> Payment Reminders
            </a>
            <a class="nav-link" href="account">
                <i class="fas fa-wallet"></i> Account Balance
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
        <h1 class="page-title">Financial Reports</h1>
        
        <!-- Filter Card -->
        <div class="card">
            <h2 class="card-title">Filter Reports</h2>
            <form method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Year</label>
                        <select class="form-control" name="year">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Term</label>
                        <select class="form-control" name="term">
                            <option value="">All Terms</option>
                            <option value="Term 1" <?php echo $filter_term === 'Term 1' ? 'selected' : ''; ?>>Term 1</option>
                            <option value="Term 2" <?php echo $filter_term === 'Term 2' ? 'selected' : ''; ?>>Term 2</option>
                            <option value="Term 3" <?php echo $filter_term === 'Term 3' ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="class">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Generate Reports Card -->
        <div class="card">
            <h2 class="card-title">Generate Financial Reports</h2>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-control" id="report_type">
                        <option value="daily_revenue">Daily Revenue Report</option>
                        <option value="monthly_revenue">Monthly Revenue Report</option>
                        <option value="class_collection">Class-wise Collection Report</option>
                        <option value="payment_method">Payment Method Breakdown</option>
                        <option value="term_summary">Term-wise Summary</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Format</label>
                    <select class="form-control" id="report_format">
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV (Excel)</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Year</label>
                    <select class="form-control" id="report_year">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" onclick="generateFinancialReport()" style="background-color: #ff6600; color: white; border: none;">
                        <i class="fas fa-file-pdf me-2"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Summary Card -->
        <div class="summary-card">
            <div class="summary-value">KES <?php echo number_format($total_collected, 2); ?></div>
            <div class="summary-label">Total Collected (<?php echo htmlspecialchars($filter_year); ?><?php echo $filter_term ? ' - ' . htmlspecialchars($filter_term) : ''; ?>)</div>
        </div>
        
        <!-- Fee Collections Table -->
        <div class="card">
            <h2 class="card-title">Fee Collections</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Admission No</th>
                            <th>Class</th>
                            <th>Amount</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fee_collections)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No fee collections found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fee_collections as $collection): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($collection['receipt_number']); ?></td>
                                    <td><?php echo htmlspecialchars($collection['first_name'] . ' ' . $collection['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($collection['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($collection['class_name'] ?? '-'); ?></td>
                                    <td><strong>KES <?php echo number_format($collection['amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($collection['term']); ?></td>
                                    <td><?php echo htmlspecialchars($collection['year']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($collection['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($collection['payment_method']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Outstanding Balances -->
        <div class="card">
            <h2 class="card-title">Outstanding Balances by Class and Term (Tuition Only)</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Total Fees</th>
                            <th>Total Paid</th>
                            <th>Outstanding Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($outstanding_by_class)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No data available</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($outstanding_by_class as $balance): ?>
                                <?php $outstanding = $balance['total_fees'] - $balance['total_paid']; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($balance['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($balance['term']); ?></td>
                                    <td>KES <?php echo number_format($balance['total_fees'], 2); ?></td>
                                    <td>KES <?php echo number_format($balance['total_paid'], 2); ?></td>
                                    <td><strong>KES <?php echo number_format($outstanding, 2); ?></strong></td>
                                    <td>
                                        <?php if ($outstanding > 0): ?>
                                            <span class="badge badge-danger">Outstanding</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Fully Paid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Non-Tuition Fees -->
        <div class="card">
            <h2 class="card-title">Non-Tuition Fees (Separate from Balance)</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Amount</th>
                            <th>Students</th>
                            <th>Collected</th>
                            <th>Pending</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($non_tuition_fees)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No non-tuition fees found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($non_tuition_fees as $fee): ?>
                                <?php $pending = ($fee['amount'] * $fee['student_count']) - $fee['total_collected']; ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fee['fee_type']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($fee['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['term']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['year']); ?></td>
                                    <td>KES <?php echo number_format($fee['amount'], 2); ?></td>
                                    <td><?php echo $fee['student_count']; ?></td>
                                    <td>KES <?php echo number_format($fee['total_collected'], 2); ?></td>
                                    <td><strong>KES <?php echo number_format($pending, 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($fee['description'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Student Fee Payment Status -->
        <div class="card">
            <h2 class="card-title">Student Fee Payment Status (All Fee Types)</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Admission No</th>
                            <th>Class</th>
                            <th>Fee Type</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Fee Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($student_fee_status)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No fee structures found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($student_fee_status as $status): ?>
                                <?php $balance = $status['fee_amount'] - $status['paid_amount']; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($status['first_name'] . ' ' . $status['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($status['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($status['class_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($status['fee_type']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($status['term']); ?></td>
                                    <td><?php echo htmlspecialchars($status['year']); ?></td>
                                    <td>KES <?php echo number_format($status['fee_amount'], 2); ?></td>
                                    <td>KES <?php echo number_format($status['paid_amount'], 2); ?></td>
                                    <td><strong>KES <?php echo number_format($balance, 2); ?></strong></td>
                                    <td>
                                        <?php if ($balance <= 0): ?>
                                            <span style="color: #137333; font-weight: 500;">Paid</span>
                                        <?php elseif ($status['paid_amount'] > 0): ?>
                                            <span style="color: #f9ab00; font-weight: 500;">KES <?php echo number_format($status['paid_amount'], 2); ?> paid</span>
                                        <?php else: ?>
                                            <span style="color: #c5221f; font-weight: 500;">Not Paid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

        async function generateFinancialReport() {
            const reportType = document.getElementById('report_type').value;
            const format = document.getElementById('report_format').value;
            const year = document.getElementById('report_year').value;
            
            try {
                const formData = new FormData();
                formData.append('report_type', reportType);
                formData.append('format', format);
                formData.append('year', year);
                
                const response = await fetch('/Kenyaeduhub/finance-managers/api/generate_financial_report.php', {
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
                        link.href = '/Kenyaeduhub' + data.report_url;
                        link.download = data.report_filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        // Open in new tab on desktop
                        window.open('/Kenyaeduhub' + data.report_url, '_blank');
                    }
                } else {
                    alert('Failed to generate report: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error generating report: ' + error.message);
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
