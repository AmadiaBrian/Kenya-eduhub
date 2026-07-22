<?php
// Finance Manager Dashboard
// Authentication is handled by index.php router
$finance_manager_id = $_SESSION['finance_manager_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get statistics
$stats = [];
try {
    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$school_id]);
    $stats['total_students'] = $stmt->fetch()['total'];
    
    // Total fee collections this year (only completed payments)
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fee_payments fp JOIN students s ON fp.student_id = s.id WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed'");
    $stmt->execute([$school_id, $current_year]);
    $stats['fee_collections'] = $stmt->fetch()['total'] ?? 0;
    
    // Outstanding fee balance (only Tuition fees, only completed payments)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(fs.amount), 0) as total_fees
                          FROM fee_structure fs
                          WHERE fs.school_id = ? AND fs.year = ? AND fs.fee_type = 'Tuition'");
    $stmt->execute([$school_id, $current_year]);
    $total_fees = $stmt->fetch()['total_fees'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(fp.amount), 0) as total_paid
                          FROM fee_payments fp
                          JOIN students s ON fp.student_id = s.id
                          WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)");
    $stmt->execute([$school_id, $current_year]);
    $total_paid = $stmt->fetch()['total_paid'] ?? 0;
    
    $stats['outstanding_balance'] = $total_fees - $total_paid;
    
    // Payment method breakdown
    $stmt = $pdo->prepare("SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                          FROM fee_payments fp
                          JOIN students s ON fp.student_id = s.id
                          WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed'
                          GROUP BY payment_method");
    $stmt->execute([$school_id, $current_year]);
    $payment_methods = $stmt->fetchAll();
    
    // Monthly revenue data
    $stmt = $pdo->prepare("SELECT MONTH(payment_date) as month, SUM(amount) as total
                          FROM fee_payments fp
                          JOIN students s ON fp.student_id = s.id
                          WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed'
                          GROUP BY MONTH(payment_date)
                          ORDER BY month");
    $stmt->execute([$school_id, $current_year]);
    $monthly_revenue = $stmt->fetchAll();
    
    // Term-wise collection data
    $stmt = $pdo->prepare("SELECT term, SUM(amount) as total
                          FROM fee_payments fp
                          JOIN students s ON fp.student_id = s.id
                          WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed'
                          GROUP BY term
                          ORDER BY FIELD(term, 'Term 1', 'Term 2', 'Term 3')");
    $stmt->execute([$school_id, $current_year]);
    $term_collections = $stmt->fetchAll();
    
    // Recent payments (only successful/completed)
    $stmt = $pdo->prepare("SELECT fp.id, fp.receipt_number, fp.amount, fp.payment_date, fp.payment_method, fp.term, fp.year, fp.fee_type, s.first_name, s.last_name, s.admission_number 
                          FROM fee_payments fp 
                          JOIN students s ON fp.student_id = s.id 
                          WHERE s.school_id = ? AND fp.status = 'completed'
                          ORDER BY fp.payment_date DESC LIMIT 10");
    $stmt->execute([$school_id]);
    $recent_payments = $stmt->fetchAll();
    
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
        $params = [$school_id, $current_year];
        
        $query .= " GROUP BY fs.id, fs.fee_type, fs.term, fs.year, fs.amount, fs.description, c.class_name
                  ORDER BY fs.fee_type, fs.term, c.class_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $non_tuition_fees = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch non-tuition fees: " . $e->getMessage());
    }
} catch (PDOException $e) {
    error_log("Failed to fetch dashboard stats: " . $e->getMessage());
    $stats['total_students'] = 0;
    $stats['fee_collections'] = 0;
    $stats['outstanding_balance'] = 0;
    $payment_methods = [];
    $monthly_revenue = [];
    $term_collections = [];
    $recent_payments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($finance_manager_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            font-size: 28px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #FF6B35, #008000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
            font-weight: 400;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stat-card {
            background: var(--bg-color);
            padding: 28px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-icon {
            width: 72px;
            height: 72px;
            color: #FF6B35;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 20px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #FF6B35;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #000;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Cards */
        .card {
            background: var(--bg-color);
            padding: 28px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            padding-bottom: 32px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 20px;
            position: relative;
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
            color: #000;
            background: #e8eaed;
            border: 1px solid #000;
        }
        
        .badge-success {
            background: #e8f5e9;
            color: #137333;
            border: 1px solid #137333;
        }
        
        .badge-info {
            background: #e8f0fe;
            color: #1967d2;
            border: 1px solid #1967d2;
        }
        
        .badge-warning {
            background: #fff3e0;
            color: #b06000;
            border: 1px solid #b06000;
        }
        
        /* Chart Containers */
        .chart-container {
            background: var(--bg-color);
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        
        .chart-container:hover {
            transform: translateY(-2px);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 20px;
            position: relative;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #e0e0e0;
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
                padding: 16px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-icon {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .chart-container {
                padding: 16px;
            }
            
            .card {
                padding: 20px;
            }
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }
            
            .table {
                min-width: 100%;
                font-size: 12px;
            }
            
            .table th, .table td {
                padding: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .page-title {
                font-size: 18px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .chart-title {
                font-size: 16px;
            }
            
            .card-title {
                font-size: 18px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card, .card, .chart-container {
            animation: fadeIn 0.5s ease-out;
        }
        
        .stat-card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .stat-card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .stat-card:nth-child(3) {
            animation-delay: 0.3s;
        }
        
        .chart-container:nth-child(1) {
            animation-delay: 0.4s;
        }
        
        .chart-container:nth-child(2) {
            animation-delay: 0.5s;
        }
        
        .chart-container:nth-child(3) {
            animation-delay: 0.6s;
        }
        
        .chart-container:nth-child(4) {
            animation-delay: 0.7s;
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
            <a class="nav-link active" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="fees">
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
        <h1 class="page-title">Finance Dashboard</h1>
        <p class="page-subtitle">Real-time financial overview and analytics</p>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value">KES <?php echo number_format($stats['fee_collections'], 2); ?></div>
                <div class="stat-label">Fee Collections (<?php echo date('Y'); ?>)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-value">KES <?php echo number_format($stats['outstanding_balance'], 2); ?></div>
                <div class="stat-label">Outstanding Balance</div>
            </div>
        </div>
        
        <!-- Financial Charts -->
        <div class="chart-grid">
            <div class="chart-container">
                <h3 class="chart-title">Monthly Revenue Trend (<?php echo $current_year; ?>)</h3>
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
            <div class="chart-container">
                <h3 class="chart-title">Payment Method Distribution</h3>
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="chart-container">
                <h3 class="chart-title">Term-wise Fee Collection</h3>
                <canvas id="termCollectionChart"></canvas>
            </div>
            <div class="chart-container">
                <h3 class="chart-title">Collection Overview</h3>
                <canvas id="collectionOverviewChart"></canvas>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="card">
            <h2 class="card-title">Recent Fee Payments</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Admission No</th>
                            <th>Amount</th>
                            <th>Term</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_payments)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No recent payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['admission_number']); ?></td>
                                    <td><strong>KES <?php echo number_format($payment['amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['term']); ?></td>
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

        // Prepare chart data
        const monthlyRevenueData = <?php echo json_encode($monthly_revenue); ?>;
        const paymentMethodsData = <?php echo json_encode($payment_methods); ?>;
        const termCollectionsData = <?php echo json_encode($term_collections); ?>;
        const feeCollections = <?php echo $stats['fee_collections']; ?>;
        const outstandingBalance = <?php echo $stats['outstanding_balance']; ?>;
        const totalFees = feeCollections + outstandingBalance;

        // Monthly Revenue Chart
        const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyData = new Array(12).fill(0);
        monthlyRevenueData.forEach(item => {
            monthlyData[item.month - 1] = item.total;
        });

        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthNames,
                datasets: [{
                    label: 'Revenue (KES)',
                    data: monthlyData,
                    borderColor: '#FF6B35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Payment Method Distribution Chart
        const paymentCtx = document.getElementById('paymentMethodChart').getContext('2d');
        const paymentLabels = paymentMethodsData.map(item => item.payment_method);
        const paymentAmounts = paymentMethodsData.map(item => item.total);
        const paymentColors = ['#FF6B35', '#008000', '#1E88E5', '#FFC107', '#9C27B0'];

        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentAmounts,
                    backgroundColor: paymentColors.slice(0, paymentLabels.length),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': KES ' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Term-wise Collection Chart
        const termCtx = document.getElementById('termCollectionChart').getContext('2d');
        const termLabels = termCollectionsData.map(item => item.term);
        const termAmounts = termCollectionsData.map(item => item.total);

        new Chart(termCtx, {
            type: 'bar',
            data: {
                labels: termLabels,
                datasets: [{
                    label: 'Collection (KES)',
                    data: termAmounts,
                    backgroundColor: ['#FF6B35', '#008000', '#1E88E5'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Collection Overview Chart
        const overviewCtx = document.getElementById('collectionOverviewChart').getContext('2d');
        new Chart(overviewCtx, {
            type: 'pie',
            data: {
                labels: ['Collected', 'Outstanding'],
                datasets: [{
                    data: [feeCollections, outstandingBalance],
                    backgroundColor: ['#008000', '#FF6B35'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const percentage = ((context.raw / totalFees) * 100).toFixed(1);
                                return context.label + ': KES ' + context.raw.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
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
