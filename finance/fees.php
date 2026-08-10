<?php
// Finance Manager Fee Management Page
// Authentication is handled by index.php router
$finance_manager_id = $_SESSION['finance_manager_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

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

// Get classes
$classes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Handle fee structure creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fee_structure'])) {
    $class_id = $_POST['class_id'] ?? '';
    $term = $_POST['term'] ?? '';
    $year = $_POST['year'] ?? date('Y');
    $fee_type = $_POST['fee_type'] ?? 'Tuition';
    $amount = $_POST['amount'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $errors = [];
    if (empty($class_id)) $errors[] = 'Class is required';
    if (empty($term)) $errors[] = 'Term is required';
    if (empty($year)) $errors[] = 'Year is required';
    if (empty($amount) || !is_numeric($amount)) $errors[] = 'Valid amount is required';
    
    if (empty($errors)) {
        try {
            // Check if fee structure already exists
            $stmt = $pdo->prepare("SELECT id FROM fee_structure WHERE school_id = ? AND class_id = ? AND term = ? AND year = ? AND fee_type = ?");
            $stmt->execute([$school_id, $class_id, $term, $year, $fee_type]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE fee_structure SET amount = ?, description = ? WHERE id = ?");
                $stmt->execute([$amount, $description, $existing['id']]);
                $success = 'Fee structure updated successfully!';
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO fee_structure (school_id, class_id, term, year, fee_type, amount, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $class_id, $term, $year, $fee_type, $amount, $description]);
                $success = 'Fee structure created successfully!';
            }
        } catch (PDOException $e) {
            error_log("Failed to save fee structure: " . $e->getMessage());
            $errors[] = 'Failed to save fee structure. Please try again.';
        }
    }
}

// Handle fee payment recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $student_id = $_POST['student_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $term = $_POST['term'] ?? '';
    $year = $_POST['year'] ?? date('Y');
    $fee_type = $_POST['fee_type'] ?? 'Tuition';
    $transaction_id = $_POST['transaction_id'] ?? '';
    
    $errors = [];
    if (empty($student_id)) $errors[] = 'Student is required';
    if (empty($amount) || !is_numeric($amount)) $errors[] = 'Valid amount is required';
    if (empty($payment_method)) $errors[] = 'Payment method is required';
    if (empty($term)) $errors[] = 'Term is required';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate receipt number
            $receipt_number = 'REC' . date('YmdHis') . rand(1000, 9999);
            
            // Record payment
            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, payment_method, transaction_id, term, year, fee_type, receipt_number, status) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$student_id, $amount, $payment_method, $transaction_id, $term, $year, $fee_type, $receipt_number]);
            
            $pdo->commit();
            $success = 'Payment recorded successfully! Receipt: ' . $receipt_number;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Failed to record payment: " . $e->getMessage());
            $errors[] = 'Failed to record payment. Please try again.';
        }
    }
}

// Get students
$students = [];
try {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.school_id = ? AND s.status = 'active' ORDER BY s.first_name, s.last_name");
    $stmt->execute([$school_id]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch students: " . $e->getMessage());
}

// Get fee structures
$fee_structures = [];
try {
    $stmt = $pdo->prepare("SELECT fs.*, c.class_name FROM fee_structure fs JOIN classes c ON fs.class_id = c.id WHERE fs.school_id = ? ORDER BY fs.year DESC, fs.term, c.class_name");
    $stmt->execute([$school_id]);
    $fee_structures = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch fee structures: " . $e->getMessage());
}

// Get recent payments (only successful/completed)
$recent_payments = [];
try {
    $stmt = $pdo->prepare("SELECT fp.*, s.first_name, s.last_name, s.admission_number, c.class_name 
                          FROM fee_payments fp 
                          JOIN students s ON fp.student_id = s.id 
                          LEFT JOIN classes c ON s.class_id = c.id 
                          WHERE s.school_id = ? AND fp.status = 'completed'
                          ORDER BY fp.payment_date DESC LIMIT 20");
    $stmt->execute([$school_id]);
    $recent_payments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch recent payments: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Fee Management - <?php echo htmlspecialchars($finance_manager_name); ?></title>
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
            <a class="nav-link active" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice"></i> Invoices
            </a>
            <a class="nav-link" href="reports">
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
        <h1 class="page-title">Fee Management</h1>
        
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
        
        <!-- Fee Structure Card -->
        <div class="card">
            <h2 class="card-title">Fee Structure</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Fee Type</label>
                        <input type="text" class="form-control" name="fee_type" value="Tuition" required placeholder="e.g., Tuition, Remedial, Exam Fees">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Term</label>
                        <select class="form-control" name="term" required>
                            <?php foreach($terms as $term): ?>
                                <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $active_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Amount (KES)</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="save_fee_structure" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> Save Structure
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
            </form>
        </div>

        <!-- Fee Statement Generation Card -->
        <div class="card">
            <h2 class="card-title">Generate Fee Statements</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Generate Class Fee Statement</label>
                    <select class="form-control" id="class_statement_select" style="margin-bottom: 10px;">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn" onclick="generateClassFeeStatement()" style="background-color: #ff6600; color: white; border: none;">
                        <i class="fas fa-file-invoice me-2"></i> Generate Class Statement
                    </button>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Generate Student Fee Statement</label>
                    <select class="form-control" id="student_statement_select" style="margin-bottom: 10px;">
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['admission_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn" onclick="generateStudentFeeStatement()" style="background-color: #ff6600; color: white; border: none;">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Generate Student Statement
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Record Payment Card -->
        <div class="card">
            <h2 class="card-title">Record Payment</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Student</label>
                        <select class="form-control" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['admission_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Amount (KES)</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Fee Type</label>
                        <input type="text" class="form-control" name="fee_type" value="Tuition" required placeholder="e.g., Tuition, Remedial, Exam Fees">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" name="payment_method" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Term</label>
                        <select class="form-control" name="term" required>
                            <?php foreach($terms as $term): ?>
                                <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $active_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="record_payment" class="btn" style="background-color: #ff6600; color: white; border: none; padding: 8px 16px; display: inline-flex; align-items: center;">
                            <i class="fas fa-check me-2"></i> Record
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Transaction ID (Optional)</label>
                    <input type="text" class="form-control" name="transaction_id">
                </div>
            </form>
        </div>
        
        <!-- Fee Structures List -->
        <div class="card">
            <h2 class="card-title">Current Fee Structures</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Fee Type</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fee_structures)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No fee structures found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fee_structures as $fs): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fs['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($fs['fee_type'] ?? 'Tuition'); ?></td>
                                    <td><?php echo htmlspecialchars($fs['term']); ?></td>
                                    <td><?php echo htmlspecialchars($fs['year']); ?></td>
                                    <td><strong>KES <?php echo number_format($fs['amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($fs['description'] ?? '-'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="editFeeStructure(<?php echo $fs['id']; ?>, '<?php echo htmlspecialchars($fs['class_name']); ?>', '<?php echo htmlspecialchars($fs['fee_type'] ?? 'Tuition'); ?>', '<?php echo htmlspecialchars($fs['term']); ?>', '<?php echo $fs['year']; ?>', <?php echo $fs['amount']; ?>, '<?php echo htmlspecialchars($fs['description'] ?? ''); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="card">
            <h2 class="card-title">Recent Payments</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Amount</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No recent payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['class_name'] ?? '-'); ?></td>
                                    <td><strong>KES <?php echo number_format($payment['amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['year']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
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
        
        function editFeeStructure(id, className, feeType, term, year, amount, description) {
            // Find the class select option that matches the class name
            const classSelect = document.querySelector('select[name="class_id"]');
            for (let i = 0; i < classSelect.options.length; i++) {
                if (classSelect.options[i].text === className) {
                    classSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Set other form values
            document.querySelector('select[name="fee_type"]').value = feeType;
            document.querySelector('select[name="term"]').value = term;
            document.querySelector('input[name="year"]').value = year;
            document.querySelector('input[name="amount"]').value = amount;
            document.querySelector('textarea[name="description"]').value = description;
            
            // Scroll to the fee structure form
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        }

        async function generateClassFeeStatement() {
            const classId = document.getElementById('class_statement_select').value;
            if (!classId) {
                alert('Please select a class');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('class_id', classId);
                
                const response = await fetch('api/generate_class_fee_statement.php', {
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
                    alert('Failed to generate class fee statement: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error generating class fee statement: ' + error.message);
            }
        }

        async function generateStudentFeeStatement() {
            const studentId = document.getElementById('student_statement_select').value;
            if (!studentId) {
                alert('Please select a student');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('student_id', studentId);
                
                const response = await fetch('api/generate_student_fee_statement.php', {
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
                    alert('Failed to generate student fee statement: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error generating student fee statement: ' + error.message);
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
