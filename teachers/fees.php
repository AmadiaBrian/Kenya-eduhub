<?php
// Teacher Fees Page
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$stream_id = $_SESSION['stream_id'] ?? null;
$class_name = $_SESSION['class_name'] ?? '';
$stream_name = $_SESSION['stream_name'] ?? '';

$selected_student_id = $_GET['student_id'] ?? null;

// Get teacher details
try {
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher details: " . $e->getMessage());
    $teacher = null;
}

// Get students in teacher's class
$students = [];
if ($class_id) {
    try {
        $query = "SELECT s.*, c.class_name, st.stream_name 
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams st ON s.stream_id = st.id
                  WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'";
        $params = [$school_id, $class_id];
        
        if ($stream_id) {
            $query .= " AND s.stream_id = ?";
            $params[] = $stream_id;
        }
        
        $query .= " ORDER BY s.first_name, s.last_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch students: " . $e->getMessage());
    }
}

// Get all students in teacher's class/stream with fee balances by fee type
$stream_fee_summary = [];
if ($class_id) {
    try {
        $current_year = date('Y');
        $terms = ['Term 1', 'Term 2', 'Term 3'];
        
        // Get all students with their fee structures and payments
        $query = "SELECT s.id, s.admission_number, s.first_name, s.last_name, s.stream_id, st.stream_name,
                 fs.id as fee_structure_id, fs.fee_type, fs.term, fs.year, fs.amount as fee_amount,
                 COALESCE(SUM(fp.amount), 0) as paid_amount
                 FROM students s
                 LEFT JOIN classes c ON s.class_id = c.id
                 LEFT JOIN streams st ON s.stream_id = st.id
                 LEFT JOIN fee_structure fs ON c.id = fs.class_id AND fs.year = ?
                 LEFT JOIN fee_payments fp ON s.id = fp.student_id AND fs.term = fp.term AND fs.year = fp.year AND fp.status = 'completed' 
                     AND (fp.fee_type = fs.fee_type OR (fp.fee_type IS NULL AND fs.fee_type = 'Tuition'))
                 WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'";
        $params = [$current_year, $school_id, $class_id];
        
        if ($stream_id) {
            $query .= " AND s.stream_id = ?";
            $params[] = $stream_id;
        }
        
        $query .= " GROUP BY s.id, s.admission_number, s.first_name, s.last_name, s.stream_id, st.stream_name, 
                   fs.id, fs.fee_type, fs.term, fs.year, fs.amount
                   ORDER BY s.first_name, s.last_name, fs.fee_type, fs.term";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $all_student_fees = $stmt->fetchAll();
        
        // Group by fee type
        $stream_fee_summary = [];
        foreach ($all_student_fees as $row) {
            $fee_type = $row['fee_type'] ?? 'Tuition';
            $student_id = $row['id'];
            
            if (!isset($stream_fee_summary[$fee_type])) {
                $stream_fee_summary[$fee_type] = [];
            }
            
            if (!isset($stream_fee_summary[$fee_type][$student_id])) {
                $stream_fee_summary[$fee_type][$student_id] = [
                    'admission_number' => $row['admission_number'],
                    'student_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'stream_name' => $row['stream_name'],
                    'fees' => [],
                    'total_fee' => 0,
                    'total_paid' => 0,
                    'total_balance' => 0
                ];
            }
            
            $balance = $row['fee_amount'] - $row['paid_amount'];
            
            $stream_fee_summary[$fee_type][$student_id]['fees'][] = [
                'term' => $row['term'],
                'year' => $row['year'],
                'fee_amount' => $row['fee_amount'],
                'paid_amount' => $row['paid_amount'],
                'balance' => $balance
            ];
            
            $stream_fee_summary[$fee_type][$student_id]['total_fee'] += $row['fee_amount'];
            $stream_fee_summary[$fee_type][$student_id]['total_paid'] += $row['paid_amount'];
            $stream_fee_summary[$fee_type][$student_id]['total_balance'] += $balance;
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch stream fee summary: " . $e->getMessage());
    }
}

// Get fee data for selected student
$fee_data = [];
if ($selected_student_id) {
    try {
        // Verify student belongs to teacher's class/stream
        $query = "SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ? AND s.school_id = ? AND s.class_id = ?";
        $params = [$selected_student_id, $school_id, $class_id];
        
        if ($stream_id) {
            $query .= " AND s.stream_id = ?";
            $params[] = $stream_id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $selected_student = $stmt->fetch();
        
        if ($selected_student) {
            $student_class_id = $selected_student['class_id'];
            $current_year = date('Y');
            $terms = ['Term 1', 'Term 2', 'Term 3'];
            
            // Get fee structure for all terms in current year (all fee types)
            $fee_structures = [];
            if ($student_class_id) {
                $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
                $stmt->execute([$school_id, $student_class_id, $current_year]);
                $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Get payment history for current year (only completed payments)
            $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
            $stmt->execute([$selected_student_id, $current_year]);
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
                'student' => $selected_student,
                'fee_structure_status' => $fee_structure_status
            ];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch fee data: " . $e->getMessage());
        $fee_data = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payments - <?php echo htmlspecialchars($teacher_name); ?></title>
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
                width: 100%;
            }
            
            .table {
                min-width: 100%;
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
                <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
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
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
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
            View fee payment history and balance for students in your class
        </p>
        
        <?php if ($class_id): ?>
            <div class="card">
                <h2 class="card-title">Select Student</h2>
                <p class="text-muted" style="margin-bottom: 16px;">
                    Viewing students in: 
                    <strong><?php echo htmlspecialchars($class_name); ?></strong>
                    <?php if ($stream_id): ?>
                        - <strong><?php echo htmlspecialchars($stream_name); ?> Stream</strong>
                    <?php else: ?>
                        - All Streams
                    <?php endif; ?>
                </p>
                <div class="filter-group">
                    <label for="student_search">Search Student (Name or Admission No)</label>
                    <input type="text" class="form-control" id="student_search" placeholder="Type to search..." onkeyup="filterStudents()">
                </div>
                <div class="filter-group">
                    <label for="student_id">Student</label>
                    <select class="form-control" id="student_id" name="student_id" onchange="window.location.href='fees?student_id='+this.value">
                        <option value="">Select a student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" 
                                    data-name="<?php echo strtolower(htmlspecialchars($student['first_name'] . ' ' . $student['last_name'])); ?>"
                                    data-adm="<?php echo strtolower(htmlspecialchars($student['admission_number'])); ?>"
                                    <?php echo $selected_student_id == $student['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <a href="parents" class="quick-access-item">
                        <div class="quick-access-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="quick-access-label">Parents</div>
                    </a>
                    <a href="profile" class="quick-access-item">
                        <div class="quick-access-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="quick-access-label">Profile</div>
                    </a>
                </div>
            </div>
            
            <!-- Stream Fee Summary -->
            <?php if (!empty($stream_fee_summary)): ?>
                <div class="card" id="streamFeeSummary">
                    <h2 class="card-title">Stream Fee Summary - <?php echo date('Y'); ?></h2>
                    <p class="text-muted" style="margin-bottom: 16px;">
                        Fee balances for all students in your stream, grouped by fee type
                    </p>
                    
                    <?php foreach ($stream_fee_summary as $fee_type => $students): ?>
                        <div style="margin-bottom: 32px;" data-fee-type="<?php echo htmlspecialchars($fee_type); ?>">
                            <h3 style="font-size: 16px; font-weight: 600; color: #202124; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #FF6B35;">
                                <?php echo htmlspecialchars($fee_type); ?> Fees
                            </h3>
                            <?php if (empty($students)): ?>
                                <p class="text-muted">No students with <?php echo htmlspecialchars($fee_type); ?> fees.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Admission No</th>
                                                <th>Student Name</th>
                                                <th>Stream</th>
                                                <th>Total Fees</th>
                                                <th>Total Paid</th>
                                                <th>Total Balance</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr data-admission="<?php echo strtolower(htmlspecialchars($student['admission_number'])); ?>" data-name="<?php echo strtolower(htmlspecialchars($student['student_name'])); ?>">
                                                    <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['stream_name'] ?? '-'); ?></td>
                                                    <td>KES <?php echo number_format($student['total_fee']); ?></td>
                                                    <td>KES <?php echo number_format($student['total_paid']); ?></td>
                                                    <td><strong>KES <?php echo number_format($student['total_balance']); ?></strong></td>
                                                    <td>
                                                        <?php if ($student['total_balance'] <= 0): ?>
                                                            <span style="color: #137333; font-weight: 500;">Paid</span>
                                                        <?php elseif ($student['total_paid'] > 0): ?>
                                                            <span style="color: #f9ab00; font-weight: 500;">Partial</span>
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
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($selected_student_id && !empty($fee_data)): ?>
                <div class="card">
                    <h2 class="card-title">Fee Summary - <?php echo htmlspecialchars($fee_data['student']['first_name'] . ' ' . $fee_data['student']['last_name']); ?> (<?php echo htmlspecialchars($fee_data['current_year']); ?>)</h2>
                    
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
                                                    Paid
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <p class="text-muted">Please select a student to view their fee payment history.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">You are not assigned to any class. Please contact the school administrator.</p>
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

        function filterStudents() {
            const searchInput = document.getElementById('student_search').value.toLowerCase();
            const select = document.getElementById('student_id');
            const options = select.getElementsByTagName('option');
            
            // Store the first option (Select a student)
            const firstOption = options[0].cloneNode(true);
            
            // Clear the select
            select.innerHTML = '';
            
            // Add back the first option
            select.appendChild(firstOption);
            
            // Add only matching options
            for (let i = 1; i < options.length; i++) {
                const option = options[i];
                const name = option.getAttribute('data-name') || '';
                const adm = option.getAttribute('data-adm') || '';
                
                if (name.includes(searchInput) || adm.includes(searchInput)) {
                    select.appendChild(option.cloneNode(true));
                }
            }
            
            // Restore selected value if it still exists
            const selectedValue = '<?php echo $selected_student_id ?? ''; ?>';
            if (selectedValue) {
                select.value = selectedValue;
            }
            
            // Filter stream summary rows based on search
            const streamSummary = document.getElementById('streamFeeSummary');
            if (streamSummary) {
                const rows = streamSummary.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowAdm = row.getAttribute('data-admission') || '';
                    const rowName = row.getAttribute('data-name') || '';
                    
                    if (searchInput === '' || rowAdm.includes(searchInput) || rowName.includes(searchInput)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Hide empty fee type sections
                const feeTypeSections = streamSummary.querySelectorAll('[data-fee-type]');
                feeTypeSections.forEach(section => {
                    const visibleRows = section.querySelectorAll('tbody tr[style=""]');
                    const hiddenRows = section.querySelectorAll('tbody tr[style="display: none;"]');
                    const totalRows = section.querySelectorAll('tbody tr');
                    
                    if (totalRows.length > 0 && totalRows.length === hiddenRows.length) {
                        section.style.display = 'none';
                    } else {
                        section.style.display = 'block';
                    }
                });
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
