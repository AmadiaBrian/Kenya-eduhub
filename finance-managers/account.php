<?php
// Finance Manager Account Page
// Authentication is handled by index.php router
$finance_manager_id = $_SESSION['finance_manager_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';
$success_message = '';
$errors = [];
$destination_labels = [
    'phone' => 'Phone Number',
    'till' => 'Till Number',
    'paybill' => 'Paybill',
    'bank' => 'Bank Account',
    'other' => 'Other Source'
];

if (isset($_GET['success'])) {
    $success_message = trim((string) $_GET['success']);
}

if (isset($_GET['error'])) {
    $errors[] = trim((string) $_GET['error']);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Get school phone number
$school_phone = '';
try {
    $stmt = $pdo->prepare("SELECT phone FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school_data = $stmt->fetch();
    if ($school_data) {
        $school_phone = $school_data['phone'] ?? '';
    }
} catch (PDOException $e) {
    error_log("Failed to fetch school phone: " . $e->getMessage());
}

// Get school account balance
$school_balance = 0;
try {
    $stmt = $pdo->prepare("SELECT balance, created_at, updated_at FROM school_balances WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $balance_data = $stmt->fetch();
    
    if ($balance_data) {
        $school_balance = $balance_data['balance'];
        $created_at = $balance_data['created_at'];
        $updated_at = $balance_data['updated_at'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch school balance: " . $e->getMessage());
}

$pending_withdrawals = 0;
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM school_withdrawals WHERE school_id = ? AND status = 'pending'");
    $stmt->execute([$school_id]);
    $pending_withdrawals = (float) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Failed to fetch pending withdrawals: " . $e->getMessage());
}
$available_to_withdraw = max(0, (float) $school_balance - $pending_withdrawals);

// Get recent fee payments that contributed to balance
$recent_payments = [];
try {
    $stmt = $pdo->prepare("SELECT fp.id, fp.receipt_number, fp.amount, fp.payment_date, fp.payment_method, fp.status, fp.term, fp.year, fp.fee_type, s.first_name, s.last_name, s.admission_number 
                          FROM fee_payments fp 
                          JOIN students s ON fp.student_id = s.id 
                          WHERE s.school_id = ? AND fp.status = 'completed'
                          ORDER BY fp.payment_date DESC LIMIT 20");
    $stmt->execute([$school_id]);
    $recent_payments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch recent payments: " . $e->getMessage());
}

// Get recent withdrawals
$recent_withdrawals = [];
try {
    $stmt = $pdo->prepare("SELECT amount, destination_type, destination_name, destination_account, destination_extra, status, reference_number, result_desc, mpesa_receipt_number, created_at, success_at
                          FROM school_withdrawals
                          WHERE school_id = ?
                          ORDER BY created_at DESC LIMIT 15");
    $stmt->execute([$school_id]);
    $recent_withdrawals = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch recent withdrawals: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Balance - <?php echo htmlspecialchars($finance_manager_name); ?></title>
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
        
        /* Balance Card */
        .balance-card {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .balance-amount {
            font-size: 48px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .balance-info {
            font-size: 12px;
            opacity: 0.8;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            color: #000;
            background: #e8eaed;
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
            background: #fff8e1;
            color: #b06000;
            border: 1px solid #b06000;
        }

        .badge-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #c5221f;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 500;
            color: #202124;
        }

        .form-control,
        .form-select {
            border: 1px solid #dadce0;
            border-radius: 6px;
            font-size: 14px;
            padding: 10px 12px;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 6px;
            font-weight: 500;
            padding: 10px 18px;
        }

        .btn-primary:hover {
            background: #e85f2f;
            border-color: #e85f2f;
        }

        .withdrawal-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
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
            
            .balance-amount {
                font-size: 36px;
            }

            .form-grid {
                grid-template-columns: 1fr;
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
            <a class="nav-link" href="reports">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a class="nav-link active" href="account">
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
        <h1 class="page-title">School Account Balance</h1>
        
        <!-- Balance Card -->
        <div class="balance-card">
            <div class="balance-label">Current Balance</div>
            <div class="balance-amount">KES <?php echo number_format($school_balance, 2); ?></div>
            <div class="balance-info">
                <?php if (isset($updated_at)): ?>
                    Last updated: <?php echo date('M d, Y H:i', strtotime($updated_at)); ?>
                <?php else: ?>
                    Account created: <?php echo isset($created_at) ? date('M d, Y H:i', strtotime($created_at)) : 'N/A'; ?>
                <?php endif; ?>
            </div>
            <div class="balance-info" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.3);">
                <strong>Note:</strong> This balance only includes payments received through our system (M-Pesa). Cash payments and other payment methods are not reflected in this balance.
            </div>
            <?php if ($pending_withdrawals > 0): ?>
                <div class="balance-info" style="margin-top: 8px;">
                    Pending withdrawals: KES <?php echo number_format($pending_withdrawals, 2); ?>. Withdrawable now: KES <?php echo number_format($available_to_withdraw, 2); ?>.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Withdraw Funds -->
        <div class="card">
            <h2 class="card-title">Withdraw School Money</h2>
            <form id="withdrawalForm">
                <input type="hidden" name="action" value="withdraw">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="amount">Amount (KES)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" step="1" max="<?php echo htmlspecialchars((string) $available_to_withdraw); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="destination_type">Send To</label>
                        <select class="form-select" id="destination_type" name="destination_type" required onchange="updateDestinationFields()">
                            <option value="phone" selected>Phone Number</option>
                            <option value="till">Till Number</option>
                            <option value="paybill">Paybill</option>
                            <option value="bank">Bank Account</option>
                            <option value="other">Other Source</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="destination_name" id="destination_name_label">Recipient Name</label>
                        <input type="text" class="form-control" id="destination_name" name="destination_name" placeholder="Recipient name">
                    </div>
                    <div>
                        <label class="form-label" for="destination_account" id="destination_account_label">Phone Number</label>
                        <input type="text" class="form-control" id="destination_account" name="destination_account" placeholder="0712345678 or 254712345678" required>
                        <small class="text-muted" id="phone_hint" style="display: none;">Using school phone: <?php echo htmlspecialchars($school_phone); ?></small>
                    </div>
                    <div class="full-width">
                        <label class="form-label" for="destination_extra" id="destination_extra_label">Extra Details</label>
                        <input type="text" class="form-control" id="destination_extra" name="destination_extra" placeholder="Optional extra instruction">
                    </div>
                    <div class="full-width">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional withdrawal note"></textarea>
                    </div>
                    <div class="full-width withdrawal-actions">
                        <button type="submit" class="btn btn-primary" <?php echo $available_to_withdraw < 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i> Withdraw Money
                        </button>
                        <span class="text-muted">Available: KES <?php echo number_format($available_to_withdraw, 2); ?></span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Recent Withdrawals -->
        <div class="card">
            <h2 class="card-title">Recent Withdrawals</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Destination</th>
                            <th>Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_withdrawals)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No withdrawals found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($withdrawal['reference_number']); ?></td>
                                    <td><?php echo htmlspecialchars($destination_labels[$withdrawal['destination_type']] ?? ucfirst($withdrawal['destination_type'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($withdrawal['destination_account']); ?></strong>
                                        <?php if (!empty($withdrawal['destination_name'])): ?>
                                            <br><span class="text-muted"><?php echo htmlspecialchars($withdrawal['destination_name']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($withdrawal['destination_extra'])): ?>
                                            <br><span class="text-muted"><?php echo htmlspecialchars($withdrawal['destination_extra']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>KES <?php echo number_format($withdrawal['amount'], 2); ?></strong></td>
                                    <?php
                                        $status_badge = $withdrawal['status'] === 'success' ? 'badge-success' : ($withdrawal['status'] === 'failed' ? 'badge-danger' : 'badge-warning');
                                    ?>
                                    <td>
                                        <span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars(ucfirst($withdrawal['status'])); ?></span>
                                        <?php if (!empty($withdrawal['mpesa_receipt_number'])): ?>
                                            <br><span class="text-muted"><?php echo htmlspecialchars($withdrawal['mpesa_receipt_number']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($withdrawal['result_desc']) && $withdrawal['status'] !== 'success'): ?>
                                            <br><span class="text-muted"><?php echo htmlspecialchars($withdrawal['result_desc']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($withdrawal['success_at'] ?: $withdrawal['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Withdrawal Modal Functions - Google Console Dashboard Style
        function showWithdrawalModal(title, message, type = 'info') {
            const modal = document.createElement('div');
            modal.id = 'withdrawalModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: #ffffff;
                padding: 24px;
                border-radius: 8px;
                max-width: 448px;
                width: 90%;
                text-align: left;
                box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
                animation: modalSlideIn 0.3s ease-out;
            `;
            
            let icon = '';
            let iconColor = '';
            let headerColor = '';
            
            if (type === 'success') {
                icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34A853"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                iconColor = '#34A853';
                headerColor = '#202124';
            } else if (type === 'error') {
                icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#EA4335"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                iconColor = '#EA4335';
                headerColor = '#202124';
            } else if (type === 'loading') {
                icon = '<svg class="loading-spinner" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
                iconColor = '#1a73e8';
                headerColor = '#202124';
            } else {
                icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#1a73e8"/><path d="M12 16V12M12 8H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                iconColor = '#1a73e8';
                headerColor = '#202124';
            }
            
            modalContent.innerHTML = `
                <div style="display: flex; align-items: flex-start; margin-bottom: 16px;">
                    <div style="margin-right: 16px; flex-shrink: 0;">${icon}</div>
                    <div style="flex: 1;">
                        <h2 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 500; color: ${headerColor}; line-height: 28px;">${title}</h2>
                        <p style="margin: 0; font-size: 14px; color: #5f6368; line-height: 20px;">${message}</p>
                    </div>
                </div>
                ${type !== 'loading' ? `
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0;">
                        <button onclick="closeWithdrawalModal()" style="padding: 8px 16px; background: #1a73e8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; transition: background 0.2s;">Close</button>
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

        function closeWithdrawalModal() {
            const modal = document.getElementById('withdrawalModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.2s ease-out';
                setTimeout(() => modal.remove(), 200);
            }
        }

        function updateWithdrawalModal(title, message, type = 'info') {
            const modal = document.getElementById('withdrawalModal');
            if (modal) {
                const modalContent = modal.querySelector('div');
                
                let icon = '';
                let iconColor = '';
                let headerColor = '';
                
                if (type === 'success') {
                    icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34A853"/><path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    iconColor = '#34A853';
                    headerColor = '#202124';
                } else if (type === 'error') {
                    icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#EA4335"/><path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    iconColor = '#EA4335';
                    headerColor = '#202124';
                } else if (type === 'loading') {
                    icon = '<svg class="loading-spinner" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
                    iconColor = '#1a73e8';
                    headerColor = '#202124';
                } else {
                    icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#1a73e8"/><path d="M12 16V12M12 8H12.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    iconColor = '#1a73e8';
                    headerColor = '#202124';
                }
                
                modalContent.innerHTML = `
                    <div style="display: flex; align-items: flex-start; margin-bottom: 16px;">
                        <div style="margin-right: 16px; flex-shrink: 0;">${icon}</div>
                        <div style="flex: 1;">
                            <h2 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 500; color: ${headerColor}; line-height: 28px;">${title}</h2>
                            <p style="margin: 0; font-size: 14px; color: #5f6368; line-height: 20px;">${message}</p>
                        </div>
                    </div>
                    ${type !== 'loading' ? `
                        <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0;">
                            <button onclick="closeWithdrawalModal()" style="padding: 8px 16px; background: #1a73e8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; transition: background 0.2s;">Close</button>
                        </div>
                    ` : ''}
                `;
            }
        }

        // Check withdrawal status function
        function checkWithdrawalStatus(withdrawalId) {
            let attempts = 0;
            const maxAttempts = 20; // Check for up to 2 minutes (every 6 seconds)
            
            console.log('Starting withdrawal status check for:', withdrawalId);
            
            // Update modal to show checking status
            updateWithdrawalModal('Processing Withdrawal', 'Your withdrawal request is being processed by M-Pesa.<br><br><small>We are checking for your withdrawal status...</small>', 'loading');
            
            const checkInterval = setInterval(() => {
                attempts++;
                console.log(`Withdrawal status check attempt ${attempts}/${maxAttempts}`);
                
                const formData = new FormData();
                formData.append('withdrawal_id', withdrawalId);
                
                fetch('api/check_b2c_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Withdrawal status response:', data);
                    
                    if (data.found) {
                        clearInterval(checkInterval);
                        
                        if (data.status === 'success') {
                            console.log('Withdrawal successful:', data);
                            updateWithdrawalModal('Withdrawal Successful!', 'Your withdrawal has been processed successfully.<br><br><strong>Receipt:</strong> ' + data.mpesa_receipt + '<br><strong>Amount:</strong> KES ' + data.amount, 'success');
                            // Refresh page after 3 seconds to show updated balance
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        } else if (data.status === 'failed') {
                            console.error('Withdrawal failed:', data);
                            updateWithdrawalModal('Withdrawal Failed', data.error_message, 'error');
                        } else {
                            console.log('Withdrawal still pending:', data);
                            // Continue checking - modal already shows loading state
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        console.log('Withdrawal status check timed out');
                        updateWithdrawalModal('Withdrawal Status Timeout', 'We could not confirm your withdrawal status within the expected time. Please check your withdrawal history or try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error checking withdrawal status:', error);
                });
            }, 6000); // Check every 6 seconds
        }

        // Phone validation function
        function validateMpesaPhone(phone) {
            // Remove all non-numeric characters
            const cleanPhone = phone.replace(/[^0-9]/g, '');
            
            // Check if it's a valid Kenyan phone number
            if (cleanPhone.length === 9 && /^[17]/.test(cleanPhone)) {
                return '254' + cleanPhone;
            }
            if (cleanPhone.length === 10 && cleanPhone[0] === '0' && /^0[17]/.test(cleanPhone)) {
                return '254' + cleanPhone.substring(1);
            }
            if (cleanPhone.length === 12 && cleanPhone.startsWith('254') && /^254[17]/.test(cleanPhone)) {
                return cleanPhone;
            }
            
            return null;
        }

        // Withdrawal Form Handler
        document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const destinationType = document.getElementById('destination_type').value;
            const destinationAccount = document.getElementById('destination_account').value;
            const destinationName = document.getElementById('destination_name').value;
            const amount = document.getElementById('amount').value;
            
            console.log('=== B2C Withdrawal Request Started ===');
            console.log('Destination Type:', destinationType);
            console.log('Destination Account:', destinationAccount);
            console.log('Destination Name:', destinationName);
            console.log('Amount:', amount);
            
            // Validate phone number if phone withdrawal
            if (destinationType === 'phone') {
                const normalizedPhone = validateMpesaPhone(destinationAccount);
                if (!normalizedPhone) {
                    console.error('Phone validation failed for:', destinationAccount);
                    updateWithdrawalModal('Invalid Phone Number', 'Please enter a valid Kenyan phone number (e.g., 07XXXXXXXX or 254XXXXXXXXX).', 'error');
                    return;
                }
                // Update with normalized format
                document.getElementById('destination_account').value = normalizedPhone;
                console.log('Phone normalized to:', normalizedPhone);
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Show loading modal
            showWithdrawalModal('Initiating Withdrawal', 'Please wait while we initiate your withdrawal request...', 'loading');
            
            // Prepare form data
            const formData = new FormData(form);
            console.log('Form data prepared, sending to server...');
            
            // Send AJAX request
            const currentPath = window.location.pathname;
            const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
            const apiUrl = basePath + '/b2c/b2c_process.php';
            
            console.log('Current path:', currentPath);
            console.log('Base path:', basePath);
            console.log('API URL:', apiUrl);
            
            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                console.log('Server response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    console.error('Response not OK:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('=== B2C Server Response ===');
                console.log('Full response:', JSON.stringify(data, null, 2));
                console.log('Success:', data.success);
                console.log('Message:', data.message);
                console.log('Reference Number:', data.reference_number);
                console.log('Withdrawal ID:', data.withdrawal_id);
                
                if (data.success) {
                    // Show success message with withdrawal ID
                    updateWithdrawalModal('Withdrawal Initiated', data.message + '<br><br><small>Reference: ' + data.reference_number + '</small>', 'info');
                    console.log('Withdrawal ID:', data.withdrawal_id);
                    
                    // Start checking withdrawal status
                    if (data.withdrawal_id) {
                        checkWithdrawalStatus(data.withdrawal_id);
                    }
                    
                    form.reset();
                } else {
                    console.error('=== B2C Withdrawal Failed ===');
                    console.error('Error message:', data.message);
                    updateWithdrawalModal('Withdrawal Failed', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('=== B2C Network Error ===');
                console.error('Error name:', error.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                updateWithdrawalModal('Network Error', 'Failed to connect to withdrawal server: ' + error.message + '. Please check your internet connection and try again.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Withdraw Money';
            });
        });

        function updateDestinationFields() {
            const type = document.getElementById('destination_type').value;
            const nameLabel = document.getElementById('destination_name_label');
            const accountLabel = document.getElementById('destination_account_label');
            const extraLabel = document.getElementById('destination_extra_label');
            const nameInput = document.getElementById('destination_name');
            const accountInput = document.getElementById('destination_account');
            const extraInput = document.getElementById('destination_extra');
            const phoneHint = document.getElementById('phone_hint');
            const schoolPhone = '<?php echo htmlspecialchars($school_phone); ?>';

            const labels = {
                phone: ['Recipient Name', 'Phone Number', 'Extra Details'],
                till: ['Business Name', 'Till Number', 'Extra Details'],
                paybill: ['Business Name', 'Paybill Number', 'Account Number / Name'],
                bank: ['Bank Name', 'Account Number', 'Branch / Account Name'],
                other: ['Recipient / Source Name', 'Destination Details', 'Extra Details']
            };

            const placeholders = {
                phone: ['Recipient name', '0712345678 or 254712345678', 'Optional extra instruction'],
                till: ['Business name', 'Till number', 'Optional extra instruction'],
                paybill: ['Business name', 'Paybill number', 'Account number or name'],
                bank: ['Bank name', 'Bank account number', 'Branch, account name, or bank code'],
                other: ['Source or recipient name', 'Where the money should be sent', 'Any extra instruction']
            };

            const selectedLabels = labels[type] || ['Recipient / Institution Name', 'Destination Number / Account', 'Extra Details'];
            const selectedPlaceholders = placeholders[type] || ['Name, business, bank, or institution', 'Phone, till, paybill, account, or source', 'Account name, branch, reason, or any extra instruction'];

            nameLabel.textContent = selectedLabels[0];
            accountLabel.textContent = selectedLabels[1];
            extraLabel.textContent = selectedLabels[2];
            nameInput.placeholder = selectedPlaceholders[0];
            accountInput.placeholder = selectedPlaceholders[1];
            extraInput.placeholder = selectedPlaceholders[2];

            // If phone type, use school phone number and school name
            if (type === 'phone' && schoolPhone) {
                accountInput.value = schoolPhone;
                accountInput.readOnly = false; // Allow manual correction
                phoneHint.style.display = 'block';
                phoneHint.textContent = 'School phone: ' + schoolPhone + ' | You can edit this if needed';
                // Auto-fill recipient name with school name
                nameInput.value = '<?php echo htmlspecialchars($school_name); ?>';
                nameInput.readOnly = false;
            } else {
                accountInput.value = '';
                accountInput.readOnly = false;
                phoneHint.style.display = 'none';
                nameInput.value = '';
                nameInput.readOnly = false;
            }
        }

        // Initialize destination fields on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDestinationFields();
        });
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
