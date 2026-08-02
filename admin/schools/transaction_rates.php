<?php
/**
 * Transaction Rates Management
 * Admin interface to manage transaction fees and rates
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/security_lite.php';

// Output CSRF token variable for use in HTML
$csrf_token = generateCSRFLite();

// Authentication is handled by the admin router (admin/index.php)
// No need to check here since this page is loaded through the router

// Get all transaction rates
$stmt = $pdo->query("SELECT * FROM transaction_rates ORDER BY transaction_type");
$rates = $stmt->fetchAll();

// Get total revenue collected
$stmt = $pdo->query("SELECT 
    COALESCE(SUM(amount), 0) as total_revenue,
    COUNT(*) as total_transactions,
    DATE(created_at) as date
    FROM system_revenue 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 30");
$revenue_data = $stmt->fetchAll();

// Get recent fee collections
$stmt = $pdo->query("SELECT tf.*, s.school_name 
    FROM transaction_fees tf
    LEFT JOIN schools s ON tf.school_id = s.id
    ORDER BY tf.created_at DESC
    LIMIT 20");
$recent_fees = $stmt->fetchAll();

// Get total revenue by transaction type
$stmt = $pdo->query("SELECT 
    transaction_type,
    SUM(fee_amount) as total_fees,
    COUNT(*) as transaction_count
    FROM transaction_fees
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY transaction_type");
$fees_by_type = $stmt->fetchAll();

// Get user info for header
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Rates - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
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
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
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
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
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
            font-size: 14px;
        }
        
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
                padding-top: calc(var(--header-height) + 16px);
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: transparent;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e8eaed;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: 400;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid #e8eaed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
        }
        
        .btn {
            padding: 10px 24px;
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
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
        }
        
        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        td {
            padding: 12px 15px;
            font-size: 13px;
            border: 1px solid #000;
            color: #000;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .status-inactive {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-percentage {
            background: #e8f0fe;
            color: #1a73e8;
        }
        
        .badge-fixed {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            position: relative;
            border: 1px solid #e8eaed;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #5f6368;
        }
        
        .close:hover {
            color: #202124;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #5f6368;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e8eaed;
            border-radius: 4px;
            background: white;
            color: #202124;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
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
                <a class="nav-link" href="../dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link active" href="../transaction-rates">
                    <i class="fas fa-percentage"></i> Transaction Rates
                </a>
                <a class="nav-link" href="../schools">
                    <i class="fas fa-school"></i> Schools
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Management <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../users">
                    <i class="fas fa-users"></i> Users
                </a>
                <a class="nav-link" href="../resources">
                    <i class="fas fa-book"></i> Resources
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Reports <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a class="nav-link" href="../logs">
                    <i class="fas fa-file-alt"></i> Logs
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Settings <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a class="nav-link" href="../logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Transaction Rates Management</h1>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>KES <?php echo number_format(array_sum(array_column($revenue_data, 'total_revenue')), 2); ?></h3>
                <p>Total Revenue (30 Days)</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($recent_fees); ?></h3>
                <p>Total Transactions</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($rates, fn($r) => $r['is_active'])); ?></h3>
                <p>Active Rates</p>
            </div>
            <div class="stat-card">
                <h3>KES <?php echo count($recent_fees) > 0 ? number_format(array_sum(array_column($recent_fees, 'fee_amount')) / count($recent_fees), 2) : '0.00'; ?></h3>
                <p>Average Fee per Transaction</p>
            </div>
        </div>
        
        <!-- Transaction Rates Table -->
        <div class="card">
            <div class="card-header">
                <h2>Transaction Rates</h2>
                <div>
                    <button class="btn btn-secondary" onclick="downloadTemplate()">Download Template</button>
                    <button class="btn btn-secondary" onclick="openBulkUploadModal()">Bulk Upload CSV</button>
                    <button class="btn btn-primary" onclick="openModal()">Add New Rate</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Type</th>
                            <th>Rate Type</th>
                            <th>Rate Value</th>
                            <th>Min Fee</th>
                            <th>Max Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rates as $rate): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rate['transaction_type']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $rate['rate_type']; ?>">
                                    <?php echo ucfirst($rate['rate_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($rate['rate_type'] == 'percentage') {
                                    echo number_format($rate['rate_value'], 4) . '%';
                                } else {
                                    echo 'KES ' . number_format($rate['rate_value'], 2);
                                }
                                ?>
                            </td>
                            <td>KES <?php echo number_format($rate['min_fee'], 2); ?></td>
                            <td><?php echo $rate['max_fee'] ? 'KES ' . number_format($rate['max_fee'], 2) : 'N/A'; ?></td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="editRate(<?php echo $rate['id']; ?>)">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Fee Collections -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h2>Recent Fee Collections</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>School</th>
                            <th>Transaction Type</th>
                            <th>Transaction Amount</th>
                            <th>Fee Amount</th>
                            <th>Rate Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_fees as $fee): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($fee['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($fee['school_name'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($fee['transaction_type']); ?></td>
                            <td>KES <?php echo number_format($fee['transaction_amount'], 2); ?></td>
                            <td style="color: #137333; font-weight: 600;">KES <?php echo number_format($fee['fee_amount'], 2); ?></td>
                            <td>
                                <?php 
                                if ($fee['rate_type'] == 'percentage') {
                                    echo number_format($fee['fee_rate'], 4) . '%';
                                } else {
                                    echo 'KES ' . number_format($fee['fee_rate'], 2);
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Revenue by Transaction Type -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h2>Revenue by Transaction Type (30 Days)</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Type</th>
                            <th>Total Fees Collected</th>
                            <th>Transaction Count</th>
                            <th>Average Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fees_by_type as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['transaction_type']); ?></td>
                            <td style="color: #137333; font-weight: 600;">KES <?php echo number_format($type['total_fees'], 2); ?></td>
                            <td><?php echo $type['transaction_count']; ?></td>
                            <td>KES <?php echo number_format($type['total_fees'] / $type['transaction_count'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Bulk Upload Modal -->
    <div id="bulkUploadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBulkUploadModal()">&times;</span>
            <h2>Bulk Upload Transaction Rates</h2>
            <div class="form-group">
                <label for="csvFile">Upload CSV File</label>
                <input type="file" id="csvFile" accept=".csv" required>
                <small style="color: #5f6368;">Download the template first and fill it with your transaction rates data.</small>
            </div>
            <button type="button" class="btn btn-primary" onclick="uploadCSV()" style="width: 100%;">Upload CSV</button>
            <div id="uploadResult" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <!-- Add/Edit Rate Modal -->
    <div id="rateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Transaction Rate</h2>
            <form id="rateForm">
                <input type="hidden" id="rateId" name="rate_id">
                
                <div class="form-group">
                    <label for="transactionType">Transaction Type</label>
                    <select id="transactionType" name="transaction_type" required>
                        <option value="">Select Type</option>
                        <option value="mpesa_stk_push">M-Pesa STK Push (Deposits)</option>
                        <option value="mpesa_b2c_withdrawal">M-Pesa B2C Withdrawal (School Withdrawals)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="rateType">Rate Type</label>
                    <select id="rateType" name="rate_type" required onchange="toggleRateFields()">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="rateValue">Rate Value</label>
                    <input type="number" id="rateValue" name="rate_value" step="0.0001" min="0" required>
                    <small id="rateValueHint" style="color: #5f6368;">Enter percentage (e.g., 1.5 for 1.5%)</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="minFee">Minimum Fee (KES)</label>
                        <input type="number" id="minFee" name="min_fee" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="maxFee">Maximum Fee (KES)</label>
                        <input type="number" id="maxFee" name="max_fee" step="0.01" min="0" placeholder="Optional">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Rate</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }

        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
        
        function openModal() {
            document.getElementById('rateModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add Transaction Rate';
            document.getElementById('rateForm').reset();
            document.getElementById('rateId').value = '';
        }
        
        function closeModal() {
            document.getElementById('rateModal').style.display = 'none';
        }
        
        function toggleRateFields() {
            const rateType = document.getElementById('rateType').value;
            const hint = document.getElementById('rateValueHint');
            
            if (rateType === 'percentage') {
                hint.textContent = 'Enter percentage (e.g., 1.5 for 1.5%)';
            } else {
                hint.textContent = 'Enter fixed amount in KES';
            }
        }
        
        function editRate(id) {
            fetch('/api/get_transaction_rate.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('rateModal').style.display = 'block';
                    document.getElementById('modalTitle').textContent = 'Edit Transaction Rate';
                    document.getElementById('rateId').value = data.id;
                    document.getElementById('transactionType').value = data.transaction_type;
                    document.getElementById('rateType').value = data.rate_type;
                    document.getElementById('rateValue').value = data.rate_value;
                    document.getElementById('minFee').value = data.min_fee;
                    document.getElementById('maxFee').value = data.max_fee || '';
                    document.getElementById('description').value = data.description || '';
                    toggleRateFields();
                });
        }
        
        document.getElementById('rateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('/api/save_transaction_rate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        });
        
        window.onclick = function(event) {
            const modal = document.getElementById('rateModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        function openBulkUploadModal() {
            document.getElementById('bulkUploadModal').style.display = 'block';
            document.getElementById('uploadResult').innerHTML = '';
            document.getElementById('csvFile').value = '';
        }
        
        function closeBulkUploadModal() {
            document.getElementById('bulkUploadModal').style.display = 'none';
        }
        
        function downloadTemplate() {
            const csvContent = 'transaction_type,rate_type,rate_value,min_fee,max_fee,description\n' +
                'mpesa_stk_push,percentage,1.5000,1.00,50.00,M-Pesa STK Push fee - 1.5% of deposit amount\n' +
                'mpesa_b2c_withdrawal,fixed,15.00,15.00,15.00,M-Pesa B2C withdrawal fee - fixed KES 15';
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'transaction_rates_template.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function uploadCSV() {
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a CSV file');
                return;
            }
            
            const formData = new FormData();
            formData.append('csv_file', file);
            
            document.getElementById('uploadResult').innerHTML = '<p style="color: #5f6368;">Uploading...</p>';
            
            fetch('/api/save_transaction_rate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('uploadResult').innerHTML = 
                        '<p style="color: #137333;">✅ ' + result.message + '</p>';
                    setTimeout(() => {
                        closeBulkUploadModal();
                        location.reload();
                    }, 2000);
                } else {
                    document.getElementById('uploadResult').innerHTML = 
                        '<p style="color: #c5221f;">❌ Error: ' + result.message + '</p>';
                }
            })
            .catch(error => {
                document.getElementById('uploadResult').innerHTML = 
                    '<p style="color: #c5221f;">❌ Error: ' + error.message + '</p>';
            });
        }
    </script>
</body>
</html>
