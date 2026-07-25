<?php
// Parent Fees Page
// Authentication is handled by index.php router
$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get active term from calendar status
$active_term = $calendar_status['current_term']['term_name'] ?? null;

$selected_child_id = $_GET['child_id'] ?? null;

// Get children of this parent
try {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Set default child if none selected
    if (!$selected_child_id && !empty($children)) {
        $selected_child_id = $children[0]['id'];
    }
    
    // Get fee data for selected child
    $fee_data = [];
    $reminder_history = [];
    if ($selected_child_id) {
        $stmt = $pdo->prepare("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
        $stmt->execute([$selected_child_id]);
        $selected_child = $stmt->fetch();
        
        if ($selected_child) {
            $class_id = $selected_child['class_id'];
            $current_year = date('Y');
            
            // Get terms from database for current year
            $terms = [];
            try {
                $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
                $stmt->execute([$school_id, $current_year]);
                $term_records = $stmt->fetchAll();
                foreach ($term_records as $term) {
                    $terms[] = $term['term_name'];
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch terms: " . $e->getMessage());
                // Fallback to default terms if query fails
                $terms = ['Term 1', 'Term 2', 'Term 3'];
            }
            
            if (empty($terms)) {
                $terms = ['Term 1', 'Term 2', 'Term 3'];
            }
            
            // Get fee structure for all terms in current year (all fee types)
            $fee_structures = [];
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
                $stmt->execute([$school_id, $class_id, $current_year]);
                $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Get payment history for current year (only completed payments)
            $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
            $stmt->execute([$selected_child_id, $current_year]);
            $payments = $stmt->fetchAll();
            
            // Calculate term-wise balances (only Tuition fees)
            $term_balances = [];
            $year_total_fees = 0;
            $year_total_paid = 0;
            
            foreach ($terms as $term) {
                $term_fee = 0;
                $term_paid = 0;
                
                // Get fee structure for this term (only Tuition)
                foreach ($fee_structures as $fs) {
                    if ($fs['term'] === $term && $fs['fee_type'] === 'Tuition') {
                        $term_fee = $fs['amount'];
                        break;
                    }
                }
                
                // Get payments for this term (only Tuition)
                foreach ($payments as $payment) {
                    if ($payment['term'] === $term && ($payment['fee_type'] === 'Tuition' || $payment['fee_type'] === null)) {
                        $term_paid += $payment['amount'];
                    }
                }
                
                $term_balance = $term_fee - $term_paid;
                
                $term_balances[$term] = [
                    'fees' => $term_fee,
                    'paid' => $term_paid,
                    'balance' => $term_balance
                ];
                
                $year_total_fees += $term_fee;
                $year_total_paid += $term_paid;
            }
            
            $year_balance = $year_total_fees - $year_total_paid;
            
            // Calculate fee structure payment status for all fee types
            $fee_structure_status = [];
            foreach ($fee_structures as $fs) {
                $fs_id = $fs['id'];
                $fs_term = $fs['term'];
                $fs_fee_type = $fs['fee_type'];
                $fs_amount = $fs['amount'];
                
                // Get payments for this specific fee structure
                $paid_amount = 0;
                foreach ($payments as $payment) {
                    if ($payment['term'] === $fs_term && ($payment['fee_type'] === $fs_fee_type || ($payment['fee_type'] === null && $fs_fee_type === 'Tuition'))) {
                        $paid_amount += $payment['amount'];
                    }
                }
                
                $fee_structure_status[] = [
                    'id' => $fs_id,
                    'fee_type' => $fs_fee_type,
                    'term' => $fs_term,
                    'year' => $fs['year'],
                    'amount' => $fs_amount,
                    'paid' => $paid_amount,
                    'balance' => $fs_amount - $paid_amount,
                    'status' => $paid_amount >= $fs_amount ? 'Paid' : 'Not Paid',
                    'description' => $fs['description'] ?? ''
                ];
            }
            
            $fee_data = [
                'fee_structures' => $fee_structures,
                'payments' => $payments,
                'term_balances' => $term_balances,
                'year_total_fees' => $year_total_fees,
                'year_total_paid' => $year_total_paid,
                'year_balance' => $year_balance,
                'current_year' => $current_year,
                'fee_structure_status' => $fee_structure_status
            ];
            
            // Get reminder history for this child
            $stmt = $pdo->prepare("
                SELECT rh.*, s.first_name, s.last_name 
                FROM reminder_history rh
                JOIN students s ON rh.student_id = s.id
                WHERE rh.student_id = ? AND rh.school_id = ?
                ORDER BY rh.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$selected_child_id, $school_id]);
            $reminder_history = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log("Failed to fetch fee data: " . $e->getMessage());
    $children = [];
    $fee_data = [];
    $reminder_history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payments - <?php echo htmlspecialchars($parent_name); ?></title>
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
        
        /* Fee Summary */
        .fee-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .fee-summary-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .fee-summary-label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .fee-summary-value {
            font-size: 24px;
            font-weight: 500;
            color: #202124;
        }
        
        .fee-summary-value.due {
            color: #c5221f;
        }
        
        .fee-summary-value.paid {
            color: #137333;
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
        
        /* Filter */
        .filter-group {
            margin-bottom: 16px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        /* Table */
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
            
            .fee-summary {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                overflow-x: auto;
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
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="children">
                <i class="fas fa-child"></i> My Children
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="assignments">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a class="nav-link" href="fines">
                <i class="fas fa-book"></i> Library Fines
            </a>
            <a class="nav-link active" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
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
        <h1 class="page-title">Fee Payments</h1>
        <p class="page-subtitle">
            View fee payment history and balance
        </p>
        
        <!-- Calendar Status -->
        <div style="margin-bottom: 24px;">
            <?php if ($calendar_status['is_holiday']): ?>
                <div style="background: #fce8e6; border: 1px solid #c5221f; padding: 16px; border-radius: 8px;">
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
                <div style="background: #fef7e0; border: 1px solid #f9ab00; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-info-circle" style="color: #f9ab00; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #b06000;">School is on Break</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">No active term is currently set.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #e6f4ea; border: 1px solid #137333; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-check-circle" style="color: #137333; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #137333;">School is In Session</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                                <?php if ($calendar_status['current_term']): ?>
                                    Active Term: <?php echo htmlspecialchars($calendar_status['current_term']['term_name']); ?> 
                                    (<?php echo date('M j, Y', strtotime($calendar_status['current_term']['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($calendar_status['current_term']['end_date'])); ?>)
                                <?php else: ?>
                                    Year: <?php echo $calendar_status['current_year']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2 class="card-title">Select Child</h2>
            <div class="filter-group">
                <label for="child_id">Child</label>
                <select class="form-control" id="child_id" name="child_id" onchange="window.location.href='fees?child_id='+this.value">
                    <option value="">Select a child</option>
                    <?php foreach ($children as $child): ?>
                        <option value="<?php echo $child['id']; ?>" <?php echo $selected_child_id == $child['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selected_child_id): ?>
            <div class="filter-group" style="margin-top: 15px;">
                <button class="btn" onclick="generateFeeStatement(<?php echo $selected_child_id; ?>)" style="background-color: #ff6600; color: white; border: none; width: 100%; margin-bottom: 10px;">
                    <i class="fas fa-file-invoice"></i> Generate Fee Statement
                </button>
                <button class="btn" onclick="generateFeeStructure(<?php echo $selected_child_id; ?>)" style="background-color: #ff6600; color: white; border: none; width: 100%;">
                    <i class="fas fa-list-alt"></i> Fee Structure Document
                </button>
            </div>
            <?php endif; ?>
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
                <a href="performance" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-access-label">Performance</div>
                </a>
                <a href="attendance" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="quick-access-label">Attendance</div>
                </a>
                <a href="children" class="quick-access-item">
                    <div class="quick-access-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="quick-access-label">My Children</div>
                </a>
                <a href="profile" class="quick-access-item">
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
        
        <?php if ($selected_child_id && !empty($fee_data)): ?>
            <!-- Payment Form -->
            <div class="card">
                <h2 class="card-title">Pay Fees via M-Pesa</h2>
                <form id="mpesaPaymentForm">
                    <div class="filter-group">
                        <label for="payment_fee_type">Fee Type</label>
                        <select class="form-control" id="payment_fee_type" name="fee_type" onchange="updatePaymentAmount()">
                            <option value="Tuition">Tuition</option>
                            <?php foreach($fee_data['fee_structures'] as $fs): ?>
                                <?php if($fs['fee_type'] !== 'Tuition'): ?>
                                    <option value="<?php echo htmlspecialchars($fs['fee_type']); ?>"><?php echo htmlspecialchars($fs['fee_type']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="payment_term">Term</label>
                        <select class="form-control" id="payment_term" name="term" onchange="updatePaymentAmount()">
                            <?php foreach($terms as $term): ?>
                                <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $active_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="payment_year">Year</label>
                        <select class="form-control" id="payment_year" name="year" onchange="updatePaymentAmount()">
                            <?php 
                            $current_year = date('Y');
                            for($y = $current_year; $y <= $current_year + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="payment_amount">Amount (KES)</label>
                        <input type="number" class="form-control" id="payment_amount" name="amount" min="1" required placeholder="Enter amount">
                        <small style="color: #5f6368; font-size: 11px;">Amount auto-filled based on balance. You can change it if needed.</small>
                    </div>
                    <div class="filter-group">
                        <label for="payment_phone">M-Pesa Phone Number</label>
                        <input type="tel" class="form-control" id="payment_phone" name="phone" required value="<?php echo htmlspecialchars($_SESSION['parent_phone'] ?? ''); ?>" placeholder="2547XXXXXXXX">
                        <small style="color: #5f6368; font-size: 11px;">For sandbox testing, use a registered test number (e.g., 254700000000)</small>
                    </div>
                    <input type="hidden" id="payment_student_id" name="student_id" value="<?php echo $selected_child_id; ?>">
                    <button type="submit" class="btn btn-primary" style="background: #FF6B35; border: none; padding: 12px 24px; border-radius: 25px; color: white; font-weight: 500; cursor: pointer; width: 100%;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg" alt="M-Pesa" style="height: 24px; width: auto; margin-right: 8px; vertical-align: middle;"> Pay with M-Pesa
                    </button>
                </form>
                <div id="paymentMessage" style="margin-top: 16px; display: none;"></div>
            </div>

            <div class="card">
                <h2 class="card-title">Fee Summary - <?php echo htmlspecialchars($fee_data['current_year']); ?></h2>
                
                <!-- Term-wise Balances -->
                <div class="fee-summary">
                    <?php foreach ($fee_data['term_balances'] as $term => $data): ?>
                    <div class="fee-summary-item">
                        <div class="fee-summary-label"><?php echo htmlspecialchars($term); ?> Balance</div>
                        <div class="fee-summary-value <?php echo $data['balance'] > 0 ? 'due' : 'paid'; ?>">
                            KES <?php echo number_format($data['balance']); ?>
                        </div>
                        <div class="fee-summary-label" style="font-size: 10px; margin-top: 4px;">
                            Paid: <?php echo number_format($data['paid']); ?> / <?php echo number_format($data['fees']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Year Total -->
                <div style="margin-top: 24px; padding: 16px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #FF6B35;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 14px; color: #5f6368; margin-bottom: 4px;">Year Total Balance</div>
                            <div style="font-size: 12px; color: #5f6368;">
                                Total Paid: KES <?php echo number_format($fee_data['year_total_paid']); ?> / 
                                Total Fees: KES <?php echo number_format($fee_data['year_total_fees']); ?>
                            </div>
                        </div>
                        <div style="font-size: 28px; font-weight: 500; color: <?php echo $fee_data['year_balance'] > 0 ? '#c5221f' : '#137333'; ?>;">
                            KES <?php echo number_format($fee_data['year_balance']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Fee Structure Status</h2>
                <?php if (empty($fee_data['fee_structure_status'])): ?>
                    <p class="text-muted">No fee structures found for this student.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fee Type</th>
                                    <th>Term</th>
                                    <th>Year</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fee_data['fee_structure_status'] as $status): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($status['fee_type']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($status['term']); ?></td>
                                        <td><?php echo htmlspecialchars($status['year']); ?></td>
                                        <td>KES <?php echo number_format($status['amount']); ?></td>
                                        <td>KES <?php echo number_format($status['paid']); ?></td>
                                        <td><strong>KES <?php echo number_format($status['balance']); ?></strong></td>
                                        <td>
                                            <?php if ($status['status'] === 'Paid'): ?>
                                                <span style="color: #137333; font-weight: 500;">Paid</span>
                                            <?php elseif ($status['paid'] > 0): ?>
                                                <span style="color: #f9ab00; font-weight: 500;">KES <?php echo number_format($status['paid']); ?> paid</span>
                                            <?php else: ?>
                                                <span style="color: #c5221f; font-weight: 500;">Not Paid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2 class="card-title">Payment History</h2>
                <?php if (empty($fee_data['payments'])): ?>
                    <p class="text-muted">No payment records found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fee_data['payments'] as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($payment['fee_type'] ?? 'Tuition'); ?></strong></td>
                                        <td>KES <?php echo number_format($payment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['receipt_number'] ?? '-'); ?></td>
                                        <td>
                                            <span style="color: #137333; font-weight: 500;">
                                                <?php echo htmlspecialchars($payment['status'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm" onclick="generateReceipt(<?php echo $payment['id']; ?>)" style="background-color: #ff6600; color: white; border: none;">
                                                <i class="fas fa-print"></i> Receipt
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Reminder History Card -->
            <?php if (!empty($reminder_history)): ?>
                <div class="card">
                    <h2 class="card-title">Payment Reminders</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Term</th>
                                    <th>Year</th>
                                    <th>Outstanding Amount</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reminder_history as $reminder): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($reminder['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($reminder['term']); ?></td>
                                        <td><?php echo htmlspecialchars($reminder['year']); ?></td>
                                        <td>KES <?php echo number_format($reminder['outstanding_amount'], 2); ?></td>
                                        <td>
                                            <?php if ($reminder['reminder_type'] === 'email'): ?>
                                                <span class="badge" style="background: #e8f0fe; color: #1a73e8;">
                                                    <i class="fas fa-envelope"></i> Email
                                                </span>
                                            <?php elseif ($reminder['reminder_type'] === 'letter'): ?>
                                                <span class="badge" style="background: #fce8e6; color: #c5221f;">
                                                    <i class="fas fa-file-alt"></i> Letter
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #f1f3f4; color: #5f6368;">
                                                    <i class="fas fa-phone"></i> Manual
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($reminder['status'] === 'sent'): ?>
                                                <span class="badge" style="background: #e6f4ea; color: #137333;">
                                                    <i class="fas fa-check"></i> Sent
                                                </span>
                                            <?php elseif ($reminder['status'] === 'failed'): ?>
                                                <span class="badge" style="background: #fce8e6; color: #c5221f;">
                                                    <i class="fas fa-times"></i> Failed
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #fef7e0; color: #b06000;">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">Please select a child to view their fee payment history.</p>
            </div>
        <?php endif; ?>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }

        async function generateReceipt(paymentId) {
            try {
                const formData = new FormData();
                formData.append('payment_id', paymentId);
                
                const response = await fetch('api/generate_receipt.php', {
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
                        link.href = '/Kenyaeduhub' + data.receipt_url;
                        link.download = data.receipt_url.split('/').pop();
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        // Open in new tab on desktop
                        window.open('/Kenyaeduhub' + data.receipt_url, '_blank');
                    }
                } else {
                    alert('Failed to generate receipt: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error generating receipt: ' + error.message);
            }
        }

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

        async function generateFeeStructure(studentId) {
            try {
                const formData = new FormData();
                formData.append('student_id', studentId);
                
                const response = await fetch('api/generate_fee_structure.php', {
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
                        link.href = '/Kenyaeduhub' + data.structure_url;
                        link.download = data.structure_filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        // Open in new tab on desktop
                        window.open('/Kenyaeduhub' + data.structure_url, '_blank');
                    }
                } else {
                    alert('Failed to generate fee structure: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error generating fee structure: ' + error.message);
            }
        }

        // Payment Modal Functions - Google Material Design Style
        function showPaymentModal(title, message, type = 'info') {
            const modal = document.createElement('div');
            modal.id = 'paymentModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.4);
                backdrop-filter: blur(2px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: #ffffff;
                padding: 32px;
                border-radius: 24px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);
                animation: modalSlideIn 0.3s ease-out;
                border: none;
            `;
            
            let icon = '';
            
            if (type === 'success') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34A853"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            } else if (type === 'error') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#EA4335"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            } else if (type === 'loading') {
                icon = '<svg class="loading-spinner" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
            } else {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#1a73e8"/><path d="M12 16V12M12 8H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
            
            modalContent.innerHTML = `
                <div style="margin-bottom: 20px;">${icon}</div>
                <h2 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 400; color: #202124; line-height: 28px;">${title}</h2>
                <p style="margin: 0; font-size: 14px; color: #5f6368; line-height: 20px;">${message}</p>
                ${type !== 'loading' ? `
                    <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 24px; border-top: none;">
                        <button onclick="closePaymentModal()" style="padding: 10px 24px; background: #1a73e8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; transition: background 0.2s;">Close</button>
                    </div>
                ` : ''}
            `;
            
            // Add animation keyframes
            const style = document.createElement('style');
            style.textContent = `
                @keyframes modalSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .loading-spinner {
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
        }

        function closePaymentModal() {
            const modal = document.getElementById('paymentModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.2s ease-out';
                setTimeout(() => modal.remove(), 200);
            }
        }

        function updatePaymentModal(title, message, type = 'info') {
            const modal = document.getElementById('paymentModal');
            if (modal) {
                const modalContent = modal.querySelector('div');
                
                let icon = '';
                
                if (type === 'success') {
                    icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34A853"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                } else if (type === 'error') {
                    icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#EA4335"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                } else if (type === 'loading') {
                    icon = '<svg class="loading-spinner" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
                } else {
                    icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#1a73e8"/><path d="M12 16V12M12 8H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                }
                
                modalContent.innerHTML = `
                    <div style="margin-bottom: 20px;">${icon}</div>
                    <h2 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 400; color: #202124; line-height: 28px;">${title}</h2>
                    <p style="margin: 0; font-size: 14px; color: #5f6368; line-height: 20px;">${message}</p>
                    ${type !== 'loading' ? `
                        <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 24px; border-top: none;">
                            <button onclick="closePaymentModal()" style="padding: 10px 24px; background: #1a73e8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; transition: background 0.2s;">Close</button>
                        </div>
                    ` : ''}
                `;
            }
        }

        // Update payment amount based on fee type, term, and year selection
        function updatePaymentAmount() {
            const feeType = document.getElementById('payment_fee_type').value;
            const term = document.getElementById('payment_term').value;
            const year = document.getElementById('payment_year').value;
            const amountField = document.getElementById('payment_amount');
            
            // Fee data from PHP
            const feeData = <?php echo json_encode($fee_data); ?>;
            
            // Find the balance for selected fee type, term, and year
            let balance = 0;
            
            if (feeData && feeData.fee_structure_status) {
                const feeItem = feeData.fee_structure_status.find(
                    item => item.fee_type === feeType && item.term === term && item.year == year
                );
                
                if (feeItem && feeItem.balance > 0) {
                    balance = feeItem.balance;
                }
            }
            
            // Pre-fill amount with balance (if > 0)
            if (balance > 0) {
                amountField.value = balance;
            } else {
                amountField.value = '';
            }
        }

        // Initialize amount on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePaymentAmount();
        });

        // M-Pesa Payment Form Handler
        document.getElementById('mpesaPaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const messageDiv = document.getElementById('paymentMessage');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Show loading modal
            showPaymentModal('Initiating Payment', 'Please wait while we initiate your M-Pesa payment request...', 'loading');
            
            // Prepare form data
            const formData = new FormData(form);
            
            // Send AJAX request
            fetch('api/mpesa_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('M-Pesa Payment Response:', data);
                
                if (data.success) {
                    // Show success message with CheckoutRequestID
                    updatePaymentModal('Payment Initiated', data.message + '<br><br><small>Transaction ID: ' + data.CheckoutRequestID + '</small>', 'info');
                    console.log('CheckoutRequestID:', data.CheckoutRequestID);
                    
                    // Start checking payment status
                    if (data.CheckoutRequestID) {
                        checkPaymentStatus(data.CheckoutRequestID);
                    }
                    
                    form.reset();
                } else {
                    console.error('M-Pesa Payment Error:', data);
                    updatePaymentModal('Payment Failed', data.error, 'error');
                }
            })
            .catch(error => {
                console.error('M-Pesa Payment Network Error:', error);
                updatePaymentModal('Network Error', 'Failed to connect to payment server. Please check your internet connection and try again.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-mobile-alt"></i> Pay with M-Pesa';
            });
        });

        // Check payment status function
        function checkPaymentStatus(checkoutRequestID) {
            let attempts = 0;
            const maxAttempts = 20; // Check for up to 2 minutes (every 6 seconds)
            
            console.log('Starting payment status check for:', checkoutRequestID);
            
            // Update modal to show checking status
            updatePaymentModal('Checking Payment Status', 'Please check your phone and enter your M-Pesa PIN to complete the payment.<br><br><small>We are checking for your payment status...</small>', 'loading');
            
            const checkInterval = setInterval(() => {
                attempts++;
                console.log(`Payment status check attempt ${attempts}/${maxAttempts}`);
                
                const formData = new FormData();
                formData.append('checkoutRequestID', checkoutRequestID);
                
                fetch('api/check_fee_payment_status.php', {
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
                            updatePaymentModal('Payment Successful!', 'Your payment has been processed successfully.<br><br><strong>Receipt:</strong> ' + data.mpesa_receipt + '<br><strong>Amount:</strong> KES ' + data.amount, 'success');
                            // Refresh page after 3 seconds to show updated payment
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        } else if (data.status === 'failed') {
                            console.error('Payment failed:', data);
                            updatePaymentModal('Payment Failed', data.error_message, 'error');
                        } else {
                            console.log('Payment still pending:', data);
                            // Continue checking - modal already shows loading state
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        console.log('Payment status check timed out');
                        updatePaymentModal('Payment Status Timeout', 'We could not confirm your payment status within the expected time. Please check your payment history or try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                });
            }, 6000); // Check every 6 seconds
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
