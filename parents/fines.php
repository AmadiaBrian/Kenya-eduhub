<?php
// Parent Fines Page
// Authentication is handled by index.php router
$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Get parent's phone number
$parent_phone = '';
try {
    $stmt = $pdo->prepare("SELECT phone FROM parents WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    if ($parent) {
        $parent_phone = $parent['phone'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch parent phone: " . $e->getMessage());
}


// Get parent's children
$children = [];
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
} catch (PDOException $e) {
    error_log("Failed to fetch children: " . $e->getMessage());
}

// Get fines for parent's children
$fines = [];
try {
    if (!empty($children)) {
        $child_ids = array_column($children, 'id');
        $placeholders = str_repeat('?,', count($child_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT lf.*, b.title, b.author,
                  CASE 
                      WHEN lf.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  END as user_name,
                  CASE 
                      WHEN lf.user_type = 'student' THEN s.admission_number
                  END as user_identifier,
                  s.id as student_id
                  FROM library_fines lf
                  JOIN books b ON lf.book_id = b.id
                  JOIN students s ON lf.user_id = s.id AND lf.user_type = 'student'
                  WHERE lf.user_id IN ($placeholders) AND lf.user_type = 'student'
                  ORDER BY lf.issue_date DESC");
        $stmt->execute($child_ids);
        $fines = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch fines: " . $e->getMessage());
}

// Calculate fine statistics
$fine_stats = [
    'total_fines' => count($fines),
    'total_amount' => array_sum(array_column($fines, 'amount')),
    'total_paid' => array_sum(array_column($fines, 'amount_paid')),
    'unpaid_amount' => 0,
    'paid_count' => 0,
    'unpaid_count' => 0,
    'partial_count' => 0
];

foreach ($fines as $fine) {
    $fine_stats['unpaid_amount'] += ($fine['amount'] - $fine['amount_paid']);
    if ($fine['status'] === 'paid') {
        $fine_stats['paid_count']++;
    } elseif ($fine['status'] === 'unpaid') {
        $fine_stats['unpaid_count']++;
    } elseif ($fine['status'] === 'partial') {
        $fine_stats['partial_count']++;
    }
}

// Get overdue books for parent's children (not yet returned but past due date)
$overdue_books = [];
try {
    if (!empty($children)) {
        $child_ids = array_column($children, 'id');
        $placeholders = str_repeat('?,', count($child_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.isbn,
                  CASE 
                      WHEN bb.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  END as student_name,
                  CASE 
                      WHEN bb.borrower_type = 'student' THEN s.admission_number
                  END as admission_number,
                  DATEDIFF(CURDATE(), bb.due_date) as days_overdue
                  FROM book_borrowings bb
                  JOIN books b ON bb.book_id = b.id
                  JOIN students s ON bb.borrower_id = s.id AND bb.borrower_type = 'student'
                  WHERE bb.borrower_id IN ($placeholders) AND bb.borrower_type = 'student'
                  AND bb.status = 'borrowed'
                  AND bb.due_date < CURDATE()
                  ORDER BY bb.due_date ASC");
        $stmt->execute($child_ids);
        $overdue_books = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch overdue books: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines - <?php echo htmlspecialchars($parent_name); ?></title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: transparent;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #5f6368;
            font-weight: 500;
        }

        .stat-sublabel {
            font-size: 12px;
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
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #5f6368;
            font-size: 14px;
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
        
        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
            border: 1px solid #d2e3fc;
        }

        .alert-warning {
            background: #fef7e0;
            color: #b06000;
            border: 1px solid #fde8b0;
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
            <a href="children" class="nav-link">
                <i class="fas fa-child"></i>
                <span>My Children</span>
            </a>
            <a href="fees" class="nav-link">
                <i class="fas fa-money-bill-wave"></i>
                <span>School Fees</span>
            </a>
            <a href="fines" class="nav-link active">
                <i class="fas fa-book"></i>
                <span>Library Fines</span>
            </a>
            <a href="performance" class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Performance</span>
            </a>
            <a href="results" class="nav-link">
                <i class="fas fa-award"></i>
                <span>Results</span>
            </a>
            <a href="assignments" class="nav-link">
                <i class="fas fa-tasks"></i>
                <span>Assignments</span>
            </a>
            <a href="attendance" class="nav-link">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
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
        <h1 style="margin-bottom: 24px;">Library Fines</h1>
        
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
        
        <?php if (empty($children)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No children linked to your account. Please contact the school administration.
            </div>
        <?php else: ?>
            <!-- Overdue Books Warning -->
            <?php if (!empty($overdue_books)): ?>
                <div class="alert alert-warning" style="border-left: 4px solid #fbbc04;">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 20px; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <strong style="font-size: 16px; display: block; margin-bottom: 8px;">Overdue Books Alert</strong>
                            <p style="margin: 0 0 12px 0; font-size: 14px; line-height: 1.5;">
                                Your children have <?php echo count($overdue_books); ?> overdue book(s). These books should be returned immediately to avoid potential fines. If returned in poor condition or lost, additional charges may apply.
                            </p>
                            <div class="table-responsive" style="margin-top: 12px;">
                                <table class="table" style="font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Student</th>
                                            <th>Due Date</th>
                                            <th>Days Overdue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($overdue_books as $overdue): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($overdue['title']); ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($overdue['student_name']); ?>
                                                    <br>
                                                    <small style="color: #5f6368;"><?php echo htmlspecialchars($overdue['admission_number']); ?></small>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($overdue['due_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-danger" style="font-size: 11px;">
                                                        <?php echo $overdue['days_overdue']; ?> days
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p style="margin: 12px 0 0 0; font-size: 13px; color: #5f6368; font-style: italic;">
                                <i class="fas fa-lightbulb"></i> Please advise your child(ren) to return these books to the library as soon as possible.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <!-- Fine Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $fine_stats['total_fines']; ?></div>
                    <div class="stat-label">Total Fines</div>
                    <div class="stat-sublabel">For your children</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($fine_stats['total_amount'], 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                    <div class="stat-sublabel">Currency units</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($fine_stats['total_paid'], 2); ?></div>
                    <div class="stat-label">Amount Paid</div>
                    <div class="stat-sublabel">Already settled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($fine_stats['unpaid_amount'], 2); ?></div>
                    <div class="stat-label">Outstanding</div>
                    <div class="stat-sublabel">Remaining balance</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $fine_stats['unpaid_count']; ?></div>
                    <div class="stat-label">Unpaid Fines</div>
                    <div class="stat-sublabel">Pending payment</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $fine_stats['paid_count']; ?></div>
                    <div class="stat-label">Paid Fines</div>
                    <div class="stat-sublabel">Completed</div>
                </div>
            </div>
            
            <!-- Fines List -->
            <div class="card">
                <h2 class="card-title">Fines for Your Children</h2>
                <?php if (empty($fines)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-check-circle"></i> No fines found for your children. Keep up the good work!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Student</th>
                                    <th>Admission No</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Remaining</th>
                                    <th>Status</th>
                                    <th>Issue Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fines as $fine): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($fine['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($fine['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($fine['user_identifier']); ?></td>
                                        <td><?php echo number_format($fine['amount'], 2); ?></td>
                                        <td><?php echo number_format($fine['amount_paid'], 2); ?></td>
                                        <td><?php echo number_format($fine['amount'] - $fine['amount_paid'], 2); ?></td>
                                        <td>
                                            <?php if ($fine['status'] === 'unpaid'): ?>
                                                <span class="badge badge-danger">Unpaid</span>
                                            <?php elseif ($fine['status'] === 'partial'): ?>
                                                <span class="badge badge-warning">Partial</span>
                                            <?php elseif ($fine['status'] === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php elseif ($fine['status'] === 'waived'): ?>
                                                <span class="badge badge-info">Waived</span>
                                            <?php elseif ($fine['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge"><?php echo ucfirst($fine['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($fine['issue_date'])); ?></td>
                                        <td>
                                            <?php if ($fine['status'] === 'unpaid' || $fine['status'] === 'partial' || $fine['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-primary" onclick="openPaymentModal(<?php echo $fine['id']; ?>, <?php echo $fine['amount'] - $fine['amount_paid']; ?>)">
                                                    <i class="fas fa-money-bill"></i> Pay
                                                </button>
                                            <?php else: ?>
                                                <span style="color: #5f6368;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Payment Modal - Google Material Design Style -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-body" style="padding: 32px;">
                    <h3 style="font-size: 22px; font-weight: 400; color: #202124; margin: 0 0 24px 0; line-height: 28px;">Pay Fine</h3>
                    <form id="finePaymentForm">
                        <input type="hidden" name="fine_id" id="paymentFineId">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">Payment Method</label>
                            <select class="form-control" name="payment_method" id="paymentMethod" required style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit;">
                                <option value="mpesa">MPESA</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 16px;" id="mpesaFields">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">MPESA Phone Number</label>
                            <input type="text" class="form-control" name="phone" id="mpesaPhone" value="<?php echo htmlspecialchars($parent_phone); ?>" style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit;">
                        </div>
                        <div style="margin-bottom: 16px;" id="amountField">
                            <label style="display: block; font-size: 14px; color: #5f6368; margin-bottom: 8px; font-weight: 500;">Payment Amount *</label>
                            <input type="number" class="form-control" name="amount" id="paymentAmount" step="0.01" min="0" style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #dadce0; border-radius: 8px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: inherit;">
                        </div>
                        <button type="submit" class="btn btn-primary" id="submitPaymentBtn" style="background: #FF6B35; color: white; border: none; padding: 10px 24px; border-radius: 25px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase; width: 100%;">
                            Pay Now
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
        
        function openPaymentModal(fineId, remainingAmount) {
            document.getElementById('paymentFineId').value = fineId;
            document.getElementById('paymentAmount').value = remainingAmount;
            document.getElementById('paymentAmount').max = remainingAmount;
            
            // Reset message
            document.getElementById('paymentMessage').style.display = 'none';
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }
        
        // Handle payment form submission
        document.getElementById('finePaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fineId = document.getElementById('paymentFineId').value;
            const amount = document.getElementById('paymentAmount').value;
            const phone = document.getElementById('mpesaPhone').value;
            const submitBtn = document.getElementById('submitPaymentBtn');
            
            // MPESA payment
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            
            const formData = new FormData();
            formData.append('fine_id', fineId);
            formData.append('amount', amount);
            formData.append('phone', phone);
            
            fetch('api/mpesa_fine_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('MPESA Response status:', response.status);
                console.log('MPESA Response ok:', response.ok);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('MPESA Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('MPESA Payment Response:', data);
                    
                    if (data.success) {
                        // Start checking payment status (this will show the status modal)
                        if (data.CheckoutRequestID) {
                            checkFinePaymentStatus(data.CheckoutRequestID);
                        }
                        
                        // Close payment modal after status modal is shown
                        setTimeout(() => {
                            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                            if (paymentModal) {
                                paymentModal.hide();
                            }
                        }, 100);
                    } else {
                        showPaymentStatusModal('Payment Failed', data.error, 'error');
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).show();
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showPaymentStatusModal('Network Error', 'Invalid response from server. Please try again.', 'error');
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).show();
                }
            })
            .catch(error => {
                console.error('MPESA Payment Network Error:', error);
                showPaymentStatusModal('Network Error', 'Failed to connect to payment server: ' + error.message, 'error');
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).show();
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Pay Now';
            });
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
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#34a853"/><path d="M8 12l3 3 5-6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                // Show close button on success
                document.getElementById('paymentStatusFooter').style.display = 'flex';
            } else if (type === 'error') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#ea4335"/><path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                // Show close button on error
                document.getElementById('paymentStatusFooter').style.display = 'flex';
            } else if (type === 'info') {
                icon = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#4285f4"/><path d="M12 16v-4M12 8v4" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>';
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
    </script>
</body>
</html>
