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
    <meta name="theme-color" content="#FF6B35">
    <title>Invoices - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
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
        
        .main-content {
            padding: 24px;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 25px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            background: var(--bg-color);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            width: auto;
        }

        @media (max-width: 768px) {
            .btn {
                width: 100% !important;
            }
            
            .card-header {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            
            .card-header .d-flex {
                flex-direction: column !important;
                width: 100% !important;
            }
            
            .form-control {
                margin-bottom: 12px;
            }
        }
        
        .form-select {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--bg-color);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            background: var(--bg-color);
        }
        }
        
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        /* Buttons */
        .btn {
            padding: 10px 24px;
            border-radius: 25px;
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

        .action-buttons-cell {
            display: flex;
            gap: 4px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-color);
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
            border: 1px solid #e0e0e0;
        }

        .filter-section .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #3c4043;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 1px solid #FF6B35;
            color: #FF6B35;
            padding: 6px 12px;
            border-radius: 25px;
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
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-outline-secondary:hover {
            background: #f1f3f4;
        }
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table thead th {
            background: #f0f0f0;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: bold;
            color: #000;
            white-space: nowrap;
        }

        .table td {
            padding: 12px;
            border: 2px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
        }

        .btn-action {
            background: transparent;
            color: #FF6B35;
            border: none;
            cursor: pointer;
            margin-right: 4px;
            padding: 0;
        }

        .btn-action:hover {
            color: #e55a2b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 4px;
        }
        
        /* Badge */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-paid { 
            background: #e6f4ea; 
            color: #137333; 
        }
        .status-partial { 
            background: #fff8e1; 
            color: #f57c00; 
        }
        .status-pending { 
            background: #fce8e6; 
            color: #c5221f; 
        }
        .status-overdue { 
            background: #f3e8fd; 
            color: #7b1fa2; 
        }
        
        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid rgba(19, 115, 51, 0.1);
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid rgba(197, 34, 31, 0.1);
        }
        
        /* Stat Cards */
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
            font-size: 24px;
            font-weight: 500;
            color: #202124;
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
            border-radius: 25px;
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
            border-radius: 25px;
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
            border-radius: 25px;
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
            .main-content {
                padding: 0;
                width: 100%;
            }

            .page-title {
                font-size: 18px;
                padding: 16px;
            }

            .card {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .filter-section {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .action-buttons {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .col-md-3 {
                width: 100%;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .stat-icon {
                margin: 0 auto;
            }

            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }

            .table {
                min-width: 100%;
                width: 100%;
                font-size: 11px;
            }

            .table thead th {
                padding: 8px 4px;
                font-size: 11px;
            }

            .table td {
                padding: 8px 4px;
                font-size: 11px;
            }

            .quick-access-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            .quick-access-item {
                padding: 12px !important;
            }
        }

        /* Icon colors */
        .quick-access-item:nth-child(1) .quick-access-icon { background: #e8f0fe; color: #4285F4; }
        .quick-access-item:nth-child(2) .quick-access-icon { background: #fce8e6; color: #EA4335; }
        .quick-access-item:nth-child(3) .quick-access-icon { background: #e6f4ea; color: #00AC47; }
        .quick-access-item:nth-child(4) .quick-access-icon { background: #fef7e0; color: #FFBA00; }
        .quick-access-item:nth-child(5) .quick-access-icon { background: #e8f0fe; color: #0066DA; }
        .quick-access-item:nth-child(6) .quick-access-icon { background: #fce8e6; color: #4285F4; }
        .fa-plus { color: #00AC47; }
        .fa-download { color: #4285F4; }
        .fa-edit { color: #0066DA; }
        .fa-trash { color: #EA4335; }
        .fa-eye { color: #4285F4; }
        .fa-print { color: #5f6368; }
        .fa-filter { color: #FF6B35; }
        .fa-file-invoice { color: #4285F4; }
        .fa-money-bill-wave { color: #00AC47; }
        .fa-check-circle { color: #00AC47; }
        .fa-exclamation-circle { color: #FFBA00; }
    </style>
</head>
<body class="dashboard-page">
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'invoices'; require_once 'includes/sidebar.php'; ?>
    
    <br>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Invoice Management</h1>
        
        <!-- Statistics Cards -->
        <div id="feeStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo number_format($stats['total']); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Invoices</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;">KES <?php echo number_format($stats['total_amount'], 2); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Amount</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;">KES <?php echo number_format($stats['total_paid'], 2); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Collected</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;">KES <?php echo number_format($stats['total_balance'], 2); ?></div>
                <div style="font-size: 12px; color: #5f6368;">Outstanding Balance</div>
            </div>
        </div>

        <!-- Invoice Generation Section -->
        <div class="filter-section">
            <div class="card-title">Generate Invoices</div>
            <form id="invoiceForm" class="form-grid">
                <div class="form-group">
                    <label>Class</label>
                    <select class="form-select" id="classSelect" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Term</label>
                    <select class="form-select" id="termSelect" required>
                        <option value="Term 1" <?php echo $current_term === 'Term 1' ? 'selected' : ''; ?>>Term 1</option>
                        <option value="Term 2" <?php echo $current_term === 'Term 2' ? 'selected' : ''; ?>>Term 2</option>
                        <option value="Term 3" <?php echo $current_term === 'Term 3' ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" class="form-control" id="yearSelect" value="<?php echo $current_year; ?>" required>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Generate
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="card-title">Filter Invoices</div>
            <form class="form-grid">
                <div class="form-group">
                    <label>Term</label>
                    <select class="form-select" id="filterTerm" onchange="filterInvoices()">
                        <option value="">All Terms</option>
                        <option value="Term 1" <?php echo ($_GET['term'] ?? '') === 'Term 1' ? 'selected' : ''; ?>>Term 1</option>
                        <option value="Term 2" <?php echo ($_GET['term'] ?? '') === 'Term 2' ? 'selected' : ''; ?>>Term 2</option>
                        <option value="Term 3" <?php echo ($_GET['term'] ?? '') === 'Term 3' ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" class="form-control" id="filterYear" value="<?php echo $current_year; ?>" onchange="filterInvoices()">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-select" id="filterStatus" onchange="filterInvoices()">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo ($_GET['status'] ?? '') === 'partial' ? 'selected' : ''; ?>>Partial</option>
                        <option value="paid" <?php echo ($_GET['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo ($_GET['status'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="clearFilters()">Clear Filters</button>
                </div>
            </form>
        </div>

        <!-- Invoices Table -->
        <div class="card">
            <div class="card-title">Invoices List</div>
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
                                        <div class="action-buttons-cell">
                                            <button class="btn-outline-primary" onclick="viewInvoice(<?php echo $invoice['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-outline-secondary" onclick="printInvoice(<?php echo $invoice['id']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <button class="btn-outline-secondary" onclick="deleteInvoice(<?php echo $invoice['id']; ?>)" style="color: #dc3545; border-color: #dc3545;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
    </main>
    
    <?php require_once '../includes/copywrite.php'; ?>
    
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
