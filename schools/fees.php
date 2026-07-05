<?php
// Fees Management Page
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['school_id'])) {
    header('Location: index.php');
    exit;
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

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
    <title>Fees - <?php echo htmlspecialchars($school_name); ?></title>
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
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="students.php">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers.php">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes.php">
                <i class="fas fa-chalkboard"></i> Classes
            </a>
            <a class="nav-link" href="streams.php">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link" href="subjects.php">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="parents.php">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="performance.php">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance.php">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link active" href="fees.php">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Fees Management</h1>
        
        <!-- Quick Access -->
        <div class="card">
            <h2 class="card-title">Quick Access</h2>
            <div class="quick-access-grid">
                <a href="dashboard.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="quick-access-label">Dashboard</div>
                </a>
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
                <a href="classes.php" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="quick-access-label">Classes</div>
                </a>
            </div>
        </div>
        
        <!-- Fee Structure Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Fee Structure</span>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeStructureModal">
                    <i class="fas fa-plus me-2"></i> Add Fee Structure
                </button>
            </div>
            <div class="card-body">
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Fee Payments</span>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="fas fa-plus me-2"></i> Record Payment
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Term</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="paymentsTable">
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Fee Balances Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Student Fee Balances</span>
                <div class="d-flex gap-2">
                    <select class="form-control form-control-sm" id="balanceTerm" style="width: 150px;">
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                    <select class="form-control form-control-sm" id="balanceYear" style="width: 100px;">
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                    </select>
                    <button class="btn btn-sm btn-primary" onclick="loadBalances()">
                        <i class="fas fa-search"></i> View
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Term</th>
                                <th>Year</th>
                                <th>Fee Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="balancesTable">
                            <tr><td colspan="9" class="text-center">Select term and year to view balances</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Fee Structure Modal -->
    <div class="modal fade" id="addFeeStructureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feeStructureModalTitle">Add Fee Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addFeeStructureForm">
                        <input type="hidden" id="feeStructureId">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-control" id="feeClassId" required>
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Term</label>
                                <select class="form-control" id="feeTerm" required>
                                    <option value="">Select Term</option>
                                    <option value="Term 1">Term 1</option>
                                    <option value="Term 2">Term 2</option>
                                    <option value="Term 3">Term 3</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fee Type</label>
                                <input type="text" class="form-control" id="feeType" value="Tuition" required placeholder="e.g., Tuition, Remedial, Exam Fees">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" id="feeYear" value="2026" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount (KES)</label>
                                <input type="number" class="form-control" id="feeAmount" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="feeDescription" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveFeeStructureBtn" onclick="saveFeeStructure()">Add Fee Structure</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Fee Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                                <small class="text-muted">
                                    <?php if (!empty($admission_prefix)): ?>
                                        Your school's admission prefix is: <strong><?php echo htmlspecialchars($admission_prefix); ?></strong>. Enter only the number part (e.g., 7280)
                                    <?php else: ?>
                                        Enter the student's admission number
                                    <?php endif; ?>
                                </small>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="addPayment()">Record Payment</button>
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
                    const tbody = document.getElementById('feeStructureTable');
                    tbody.innerHTML = data.data.map(fee => `
                        <tr>
                            <td>${fee.class_name}</td>
                            <td>${fee.term}</td>
                            <td>${fee.year}</td>
                            <td>KES ${fee.amount.toLocaleString()}</td>
                            <td>${fee.description || '-'}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editFeeStructure(${fee.id}, '${fee.class_id}', '${fee.term}', ${fee.year}, '${fee.fee_type}', ${fee.amount}, '${fee.description || ''}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteFeeStructure(${fee.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading fee structures:', error);
            }
        }
        
        // Load payments
        async function loadPayments() {
            try {
                const response = await fetch('api/fees.php?type=payments');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('paymentsTable');
                    tbody.innerHTML = data.data.map(payment => `
                        <tr>
                            <td>${payment.receipt_number}</td>
                            <td>${payment.student_name}</td>
                            <td>KES ${payment.amount.toLocaleString()}</td>
                            <td>${payment.payment_date}</td>
                            <td>${payment.payment_method}</td>
                            <td>${payment.term}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="deletePayment(${payment.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading payments:', error);
            }
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
        
        // Load balances
        async function loadBalances() {
            const term = document.getElementById('balanceTerm').value;
            const year = document.getElementById('balanceYear').value;
            
            try {
                const response = await fetch(`api/fees.php?type=balances&term=${term}&year=${year}`);
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('balancesTable');
                    tbody.innerHTML = data.data.map(balance => `
                        <tr>
                            <td>${balance.admission_number}</td>
                            <td>${balance.student_name}</td>
                            <td>${balance.class_name || '-'}</td>
                            <td>${balance.term}</td>
                            <td>${balance.year}</td>
                            <td>KES ${balance.fee_amount.toLocaleString()}</td>
                            <td>KES ${balance.paid_amount.toLocaleString()}</td>
                            <td>KES ${balance.balance.toLocaleString()}</td>
                            <td>
                                <span class="badge ${balance.balance <= 0 ? 'bg-success' : 'bg-warning'}">
                                    ${balance.balance <= 0 ? 'Paid' : 'Balance Due'}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading balances:', error);
            }
        }
        
        // Initialize
        loadClassesDropdown();
        loadFeeStructures();
        loadPayments();
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
