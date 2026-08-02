<?php
// Fines Management Page
// Authentication is handled by index.php router
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

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

// Fine calculation settings (can be moved to database config)
// Fines for overdue books (assumed lost if not returned)
$fine_per_day = 10; // Currency units per day overdue
$max_fine = 500; // Maximum fine cap
$overdue_threshold_days = 15; // Days after which book is assumed lost

// Handle fine payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_fine'])) {
    $fine_id = $_POST['fine_id'] ?? '';
    $payment_amount = (float)($_POST['payment_amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $transaction_reference = trim($_POST['transaction_reference'] ?? '');
    $mpesa_phone = trim($_POST['mpesa_phone'] ?? '');
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM library_fines WHERE id = ?");
        $stmt->execute([$fine_id]);
        $fine = $stmt->fetch();
        
        if ($fine) {
            $remaining_amount = $fine['amount'] - $fine['amount_paid'];
            
            if ($payment_amount > $remaining_amount) {
                $payment_amount = $remaining_amount;
            }
            
            $new_amount_paid = $fine['amount_paid'] + $payment_amount;
            $new_status = $new_amount_paid >= $fine['amount'] ? 'paid' : 'partial';
            
            // For MPESA, generate transaction reference if not provided
            if ($payment_method === 'mpesa' && empty($transaction_reference)) {
                $transaction_reference = 'MPESA-' . time() . '-' . rand(1000, 9999);
            }
            
            $stmt = $pdo->prepare("UPDATE library_fines SET amount_paid = ?, status = ?, payment_date = NOW(), payment_method = ?, transaction_reference = ? WHERE id = ?");
            $stmt->execute([$new_amount_paid, $new_status, $payment_method, $transaction_reference, $fine_id]);
            
            // Log the payment
            $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'fine_payment', ?, 'librarian', ?)");
            $payment_details = "Payment: $payment_amount via $payment_method";
            if ($payment_method === 'mpesa') {
                $payment_details .= " (Phone: $mpesa_phone, Ref: $transaction_reference)";
            }
            $stmt->execute([$fine['book_id'], $fine['school_id'], $librarian_id, $payment_details]);
            
            $success = 'Payment recorded successfully!';
        } else {
            $errors[] = 'Fine not found';
        }
    } catch (PDOException $e) {
        error_log("Failed to process payment: " . $e->getMessage());
        $errors[] = 'Failed to process payment. Please try again.';
    }
}

// Handle fine waiver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['waive_fine'])) {
    $fine_id = $_POST['fine_id'] ?? '';
    $waiver_reason = trim($_POST['waiver_reason'] ?? '');
    
    try {
        $stmt = $pdo->prepare("UPDATE library_fines SET status = 'waived', waiver_reason = ?, waived_by = ?, waived_date = NOW() WHERE id = ?");
        $stmt->execute([$waiver_reason, $librarian_id, $fine_id]);
        
        // Log the waiver
        $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'fine_waived', ?, 'librarian', ?)");
        $stmt->execute([$fine['book_id'], $fine['school_id'], $librarian_id, "Fine waived: $waiver_reason for fine ID: $fine_id"]);
        
        $success = 'Fine waived successfully!';
    } catch (PDOException $e) {
        error_log("Failed to waive fine: " . $e->getMessage());
        $errors[] = 'Failed to waive fine. Please try again.';
    }
}

// Fines are only created when books are returned in poor condition or marked as lost
// No automatic fines for overdue books that haven't been returned yet

// Get all fines (including overdue, damaged, and lost books)
$fines = [];
try {
    $stmt = $pdo->prepare("SELECT lf.*, b.title, b.author,
              CASE 
                  WHEN lf.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  WHEN lf.user_type = 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
              END as user_name,
              CASE 
                  WHEN lf.user_type = 'student' THEN s.admission_number
                  WHEN lf.user_type = 'teacher' THEN t.email
              END as user_identifier,
              CASE 
                  WHEN lf.user_type = 'student' THEN (SELECT p.phone FROM parents p JOIN student_parents sp ON p.id = sp.parent_id WHERE sp.student_id = s.id AND sp.is_primary = 1 LIMIT 1)
                  WHEN lf.user_type = 'teacher' THEN t.phone
              END as user_contact
              FROM library_fines lf
              JOIN books b ON lf.book_id = b.id
              LEFT JOIN students s ON lf.user_type = 'student' AND lf.user_id = s.id
              LEFT JOIN teachers t ON lf.user_type = 'teacher' AND lf.user_id = t.id
              ORDER BY lf.issue_date DESC");
    $stmt->execute();
    $fines = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch fines: " . $e->getMessage());
}

// Calculate fine statistics and book status statistics
$fine_stats = [];
$book_status_stats = [];
try {
    // Fine statistics (all types)
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_fines,
        SUM(amount) as total_amount,
        SUM(amount_paid) as total_paid,
        SUM(CASE WHEN status != 'paid' AND status != 'waived' THEN (amount - amount_paid) ELSE 0 END) as unpaid_amount,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
        SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
        SUM(CASE WHEN status = 'waived' THEN 1 ELSE 0 END) as waived_count,
        SUM(CASE WHEN fine_type = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
        SUM(CASE WHEN fine_type = 'damaged' THEN 1 ELSE 0 END) as damaged_count,
        SUM(CASE WHEN fine_type = 'lost' THEN 1 ELSE 0 END) as lost_count
        FROM library_fines");
    $stmt->execute();
    $fine_stats = $stmt->fetch();
    
    // Book status statistics
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_books,
        SUM(available_copies) as available_copies,
        SUM(total_copies) as total_copies,
        SUM(CASE WHEN `condition` = 'new' THEN 1 ELSE 0 END) as new_books,
        SUM(CASE WHEN `condition` = 'good' THEN 1 ELSE 0 END) as good_books,
        SUM(CASE WHEN `condition` = 'fair' THEN 1 ELSE 0 END) as fair_books,
        SUM(CASE WHEN `condition` = 'poor' THEN 1 ELSE 0 END) as poor_books,
        SUM(CASE WHEN `condition` = 'damaged' THEN 1 ELSE 0 END) as damaged_books
        FROM books WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $book_status_stats = $stmt->fetch();
    
    // Get damaged/lost book counts from fines
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN fine_type = 'damaged' THEN 1 ELSE 0 END) as damaged_fines,
        SUM(CASE WHEN fine_type = 'lost' THEN 1 ELSE 0 END) as lost_fines
        FROM library_fines WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $fine_type_stats = $stmt->fetch();
    
    $book_status_stats['damaged_fines'] = $fine_type_stats['damaged_fines'] ?? 0;
    $book_status_stats['lost_fines'] = $fine_type_stats['lost_fines'] ?? 0;
    
} catch (PDOException $e) {
    error_log("Failed to fetch statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Fines - <?php echo htmlspecialchars($librarian_name); ?></title>
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
            border-radius: 25px;
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
            transition: margin-left 0.3s;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Cards */
        .card {
            background: transparent;
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: transparent;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #5f6368;
            font-weight: 500;
        }

        .stat-sublabel {
            font-size: 10px;
            color: #9aa0a6;
            margin-top: 4px;
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
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 25px;
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
        
        .btn-secondary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-danger {
            background: #FF6B35;
            color: white;
        }
        
        .btn-success {
            background: #FF6B35;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 25px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        /* Status badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-warning {
            background: #fef7e0;
            color: #b06000;
        }
        
        .badge-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-info {
            background: #e8f0fe;
            color: #1967d2;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fad2cf;
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
            
            .header {
                padding: 0 16px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .card {
                padding: 16px;
            }
            
            .card-title {
                font-size: 16px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 8px 10px;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }
            
            .btn-sm {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .form-label {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
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
            <span style="font-size: 14px; color: #5f6368; font-weight: 500;"><?php echo htmlspecialchars($school_name); ?></span>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <a href="dashboard" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="books" class="nav-link">
                <i class="fas fa-book"></i>
                <span>Books</span>
            </a>
            <a href="categories" class="nav-link">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
            <a href="borrow" class="nav-link">
                <i class="fas fa-hand-holding"></i>
                <span>Borrow Book</span>
            </a>
            <a href="return" class="nav-link">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
            <a href="reservations" class="nav-link">
                <i class="fas fa-bookmark"></i>
                <span>Reservations</span>
            </a>
            <a href="fines" class="nav-link active">
                <i class="fas fa-money-bill-wave"></i>
                <span>Fines</span>
            </a>
            <a href="reports" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="import_export" class="nav-link">
                <i class="fas fa-exchange-alt"></i>
                <span>Import/Export</span>
            </a>
            <a href="profile" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <button class="nav-link" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 style="margin-bottom: 24px;">Fine Management</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Fine Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $fine_stats['total_fines'] ?? 0; ?></div>
                <div class="stat-label">Total Fines</div>
                <div class="stat-sublabel">All types</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($fine_stats['total_amount'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Amount</div>
                <div class="stat-sublabel">Currency units</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $fine_stats['overdue_count'] ?? 0; ?></div>
                <div class="stat-label">Overdue Fines</div>
                <div class="stat-sublabel">Late returns</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $fine_stats['damaged_count'] ?? 0; ?></div>
                <div class="stat-label">Damaged Fines</div>
                <div class="stat-sublabel">Need repair</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $fine_stats['lost_count'] ?? 0; ?></div>
                <div class="stat-label">Lost Fines</div>
                <div class="stat-sublabel">Missing books</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $fine_stats['unpaid_count'] ?? 0; ?></div>
                <div class="stat-label">Unpaid Fines</div>
                <div class="stat-sublabel">Pending payment</div>
            </div>
        </div>
        
        <!-- Fines List -->
        <div class="card">
            <h2 class="card-title">All Fines</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Issue Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fines)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No fines found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fines as $fine): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fine['title']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($fine['user_name']); ?>
                                        <br>
                                        <small style="color: #5f6368;"><?php echo htmlspecialchars($fine['user_identifier']); ?></small>
                                    </td>
                                    <td><?php echo number_format($fine['amount'], 2); ?></td>
                                    <td><?php echo number_format($fine['amount_paid'], 2); ?></td>
                                    <td><?php echo number_format($fine['amount'] - $fine['amount_paid'], 2); ?></td>
                                    <td>
                                        <?php if (!empty($fine['payment_method'])): ?>
                                            <span style="text-transform: uppercase;"><?php echo htmlspecialchars($fine['payment_method']); ?></span>
                                            <?php if ($fine['payment_method'] === 'mpesa' && !empty($fine['receipt_number'])): ?>
                                                <br>
                                                <small style="color: #5f6368;"><?php echo htmlspecialchars($fine['receipt_number']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #5f6368;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fine['status'] === 'unpaid'): ?>
                                            <span class="badge badge-danger">Unpaid</span>
                                        <?php elseif ($fine['status'] === 'partial'): ?>
                                            <span class="badge badge-warning">Partial</span>
                                        <?php elseif ($fine['status'] === 'paid'): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php elseif ($fine['status'] === 'waived'): ?>
                                            <span class="badge badge-info">Waived</span>
                                        <?php else: ?>
                                            <span class="badge"><?php echo ucfirst($fine['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($fine['issue_date'])); ?></td>
                                    <td>
                                        <?php if ($fine['status'] === 'unpaid' || $fine['status'] === 'partial' || $fine['status'] === 'pending'): ?>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-primary" onclick="openPaymentModal(<?php echo $fine['id']; ?>, <?php echo $fine['amount'] - $fine['amount_paid']; ?>, '<?php echo htmlspecialchars($fine['user_contact'] ?? ''); ?>')">
                                                    <i class="fas fa-money-bill"></i> Pay
                                                </button>
                                                <button class="btn btn-sm btn-secondary" onclick="openWaiverModal(<?php echo $fine['id']; ?>)">
                                                    <i class="fas fa-hand-paper"></i> Waive
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal - Google Material Design Style -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-body" style="padding: 32px;">
                    <h3 style="font-size: 22px; font-weight: 400; color: #202124; margin: 0 0 24px 0; line-height: 28px;">Record Payment</h3>
                    <form id="finePaymentForm">
                        <input type="hidden" name="fine_id" id="paymentFineId">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">Payment Method *</label>
                            <select class="form-control" name="payment_method" id="paymentMethod" required onchange="togglePaymentFields()" style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit;">
                                <option value="">-- Select Payment Method --</option>
                                <option value="cash">Cash</option>
                                <option value="mpesa">MPESA</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 16px;" id="mpesaFields" style="display: none;">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">MPESA Phone Number *</label>
                            <input type="text" class="form-control" name="phone" id="mpesaPhone" placeholder="07XXXXXXXX" style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit;">
                        </div>
                        <div style="margin-bottom: 16px;" id="amountField" style="display: none;">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">Payment Amount *</label>
                            <input type="number" class="form-control" name="amount" id="paymentAmount" step="0.01" min="0" style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit;">
                        </div>
                        <button type="submit" class="btn btn-primary" id="submitPaymentBtn" disabled style="background: #FF6B35; color: white; border: none; padding: 10px 24px; border-radius: 25px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; width: 100%;">
                            Record Payment
                        </button>
                    </form>
                    <div id="paymentMessage" style="margin-top: 16px; display: none;"></div>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; padding: 10px 24px; border-radius: 4px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Status Modal - Google Material Design Style -->
    <div class="modal fade" id="paymentStatusModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-body" style="padding: 32px; text-align: center;">
                    <div id="paymentStatusIcon" style="margin-bottom: 20px;"></div>
                    <h3 id="paymentStatusTitle" style="font-size: 22px; font-weight: 400; color: #202124; margin: 0 0 12px 0; line-height: 28px;"></h3>
                    <p id="paymentStatusMessage" style="font-size: 14px; color: #5f6368; margin: 0; line-height: 20px;"></p>
                </div>
                <div class="modal-footer" id="paymentStatusFooter" style="border: none; padding: 0 24px 24px 24px; display: none; justify-content: flex-end;">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="background: #FF6B35; color: white; border: none; padding: 10px 24px; border-radius: 25px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Waiver Modal - Google Material Design Style -->
    <div class="modal fade" id="waiverModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-body" style="padding: 32px;">
                    <h3 style="font-size: 22px; font-weight: 400; color: #202124; margin: 0 0 24px 0; line-height: 28px;">Waive Fine</h3>
                    <form method="POST">
                        <input type="hidden" name="fine_id" id="waiverFineId">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">Waiver Reason *</label>
                            <textarea class="form-control" name="waiver_reason" rows="3" required placeholder="Enter reason for waiving this fine" style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit; resize: vertical;"></textarea>
                        </div>
                        <button type="submit" name="waive_fine" style="background: #FF6B35; color: white; border: none; padding: 10px 24px; border-radius: 25px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; width: 100%;">
                            Waive Fine
                        </button>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; padding: 10px 24px; border-radius: 4px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
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
        
        function logout() {
            fetch('api/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'logout';
                } else {
                    alert('Logout failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'logout';
            });
        }
        
        function openPaymentModal(fineId, maxAmount, userContact) {
            document.getElementById('paymentFineId').value = fineId;
            document.getElementById('paymentAmount').value = maxAmount;
            document.getElementById('paymentAmount').max = maxAmount;
            
            // Pre-fill MPESA phone number if available
            if (userContact) {
                document.getElementById('mpesaPhone').value = userContact;
            } else {
                document.getElementById('mpesaPhone').value = '';
            }
            
            // Reset payment method selection
            document.getElementById('paymentMethod').value = '';
            togglePaymentFields();
            
            // Reset message
            document.getElementById('paymentMessage').style.display = 'none';
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }
        
        function togglePaymentFields() {
            const paymentMethod = document.getElementById('paymentMethod').value;
            const mpesaFields = document.getElementById('mpesaFields');
            const amountField = document.getElementById('amountField');
            const submitBtn = document.getElementById('submitPaymentBtn');
            
            if (paymentMethod === 'mpesa') {
                mpesaFields.style.display = 'block';
                amountField.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<img src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg" alt="M-Pesa" style="height: 24px; width: auto; margin-right: 8px; vertical-align: middle;"> Pay with M-Pesa';
            } else if (paymentMethod === 'cash') {
                mpesaFields.style.display = 'none';
                amountField.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-money-bill me-2"></i> Record Cash Payment';
            } else {
                mpesaFields.style.display = 'none';
                amountField.style.display = 'none';
                submitBtn.disabled = true;
            }
        }
        
        // Payment form handler
        document.getElementById('finePaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const paymentMethod = document.getElementById('paymentMethod').value;
            const fineId = document.getElementById('paymentFineId').value;
            const amount = document.getElementById('paymentAmount').value;
            const phone = document.getElementById('mpesaPhone').value;
            const messageDiv = document.getElementById('paymentMessage');
            const submitBtn = document.getElementById('submitPaymentBtn');
            
            if (paymentMethod === 'cash') {
                // Handle cash payment with traditional form submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const inputs = [
                    {name: 'pay_fine', value: '1'},
                    {name: 'fine_id', value: fineId},
                    {name: 'payment_amount', value: amount},
                    {name: 'payment_method', value: 'cash'},
                    {name: 'transaction_reference', value: ''}
                ];
                
                inputs.forEach(input => {
                    const inputEl = document.createElement('input');
                    inputEl.type = 'hidden';
                    inputEl.name = input.name;
                    inputEl.value = input.value;
                    form.appendChild(inputEl);
                });
                
                document.body.appendChild(form);
                form.submit();
            } else if (paymentMethod === 'mpesa') {
                // Handle MPESA payment with AJAX
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
                
                showPaymentStatusModal('Initiating Payment', 'Please wait while we initiate your M-Pesa payment request...', 'loading');
                
                const formData = new FormData();
                formData.append('fine_id', fineId);
                formData.append('amount', amount);
                formData.append('phone', phone);
                
                fetch('api/mpesa_fine_payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('MPESA Payment Response:', data);
                    
                    if (data.success) {
                        updatePaymentStatusModal('Payment Initiated', data.message + '<br><br><small>Transaction ID: ' + data.CheckoutRequestID + '</small>', 'info');
                        
                        // Start checking payment status
                        if (data.CheckoutRequestID) {
                            checkFinePaymentStatus(data.CheckoutRequestID);
                        }
                        
                        // Close payment modal
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    } else {
                        updatePaymentStatusModal('Payment Failed', data.error, 'error');
                        bootstrap.Modal.getInstance(document.getElementById('paymentStatusModal')).hide();
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).show();
                    }
                })
                .catch(error => {
                    console.error('MPESA Payment Network Error:', error);
                    updatePaymentStatusModal('Network Error', 'Failed to connect to payment server. Please check your internet connection and try again.', 'error');
                    bootstrap.Modal.getInstance(document.getElementById('paymentStatusModal')).hide();
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).show();
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-mobile-alt me-2"></i> Pay with MPESA';
                });
            }
        });
        
        function checkFinePaymentStatus(checkoutRequestID) {
            let attempts = 0;
            const maxAttempts = 60;
            
            console.log('Starting fine payment status check for:', checkoutRequestID);
            
            showPaymentStatusModal('Checking Payment Status', 'Please check your phone and enter your M-Pesa PIN to complete the payment.<br><br><small>We are checking for your payment status...</small>', 'loading');
            
            const checkInterval = setInterval(() => {
                attempts++;
                console.log(`Payment status check attempt ${attempts}/${maxAttempts}`);
                
                const formData = new FormData();
                formData.append('checkoutRequestID', checkoutRequestID);
                
                fetch('api/check_fine_payment_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Payment status response:', data);
                    
                    if (data.found) {
                        clearInterval(checkInterval);
                        
                        if (data.status === 'success') {
                            console.log('Payment successful:', data);
                            
                            let successMessage = 'Your payment has been processed successfully.<br><br>';
                            successMessage += '<strong>Receipt:</strong> ' + data.mpesa_receipt + '<br>';
                            successMessage += '<strong>Amount:</strong> KES ' + data.amount + '<br>';
                            
                            if (data.phone_number) {
                                successMessage += '<strong>Phone:</strong> ' + data.phone_number + '<br>';
                            }
                            if (data.transaction_date) {
                                successMessage += '<strong>Date:</strong> ' + data.transaction_date + '<br>';
                            }
                            if (data.result_description) {
                                successMessage += '<strong>Status:</strong> ' + data.result_description + '<br>';
                            }
                            if (data.remaining > 0) {
                                successMessage += '<strong>Remaining Balance:</strong> KES ' + data.remaining + '<br>';
                            }
                            
                            updatePaymentStatusModal('Payment Successful!', successMessage, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 5000);
                        } else if (data.status === 'failed') {
                            console.error('Payment failed:', data);
                            updatePaymentStatusModal('Payment Failed', data.error_message, 'error');
                            clearInterval(checkInterval);
                        } else {
                            console.log('Payment still pending:', data);
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        updatePaymentStatusModal('Payment Timeout', 'Payment status check timed out. Please refresh the page to check the payment status.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Payment status check error:', error);
                    if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        updatePaymentStatusModal('Payment Error', 'Failed to check payment status. Please refresh the page.', 'error');
                    }
                });
            }, 2000);
        }
        
        function showPaymentStatusModal(title, message, type) {
            document.getElementById('paymentStatusTitle').textContent = title;
            document.getElementById('paymentStatusMessage').innerHTML = message;
            
            // Hide close button during loading
            document.getElementById('paymentStatusFooter').style.display = 'none';
            
            let icon = '';
            if (type === 'loading') {
                icon = '<svg class="loading-spinner" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
            } else if (type === 'success') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34A853"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                // Show close button on success
                document.getElementById('paymentStatusFooter').style.display = 'flex';
            } else if (type === 'error') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#EA4335"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                // Show close button on error
                document.getElementById('paymentStatusFooter').style.display = 'flex';
            } else if (type === 'info') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#1a73e8"/><path d="M12 16V12M12 8H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
            
            document.getElementById('paymentStatusIcon').innerHTML = icon;
            
            const modal = new bootstrap.Modal(document.getElementById('paymentStatusModal'));
            modal.show();
        }
        
        function updatePaymentStatusModal(title, message, type) {
            document.getElementById('paymentStatusTitle').textContent = title;
            document.getElementById('paymentStatusMessage').innerHTML = message;
            
            let icon = '';
            if (type === 'loading') {
                icon = '<svg class="loading-spinner" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
                // Hide close button during loading
                document.getElementById('paymentStatusFooter').style.display = 'none';
            } else if (type === 'success') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34A853"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                // Show close button on success
                document.getElementById('paymentStatusFooter').style.display = 'flex';
            } else if (type === 'error') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#EA4335"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                // Show close button on error
                document.getElementById('paymentStatusFooter').style.display = 'flex';
            } else if (type === 'info') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#1a73e8"/><path d="M12 16V12M12 8H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
            
            document.getElementById('paymentStatusIcon').innerHTML = icon;
        }
        
        function openWaiverModal(fineId) {
            document.getElementById('waiverFineId').value = fineId;
            const modal = new bootstrap.Modal(document.getElementById('waiverModal'));
            modal.show();
        }
    </script>
</body>
</html>
