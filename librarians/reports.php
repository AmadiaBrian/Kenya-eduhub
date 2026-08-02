<?php
// Librarian Reports Page
// Authentication is handled by index.php router
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_borrower_type = $_GET['borrower_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Get borrowing history
$borrowing_history = [];
try {
    $query = "SELECT bb.*, b.title, b.author,
              CASE 
                  WHEN bb.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  WHEN bb.borrower_type = 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
              END as borrower_name,
              CASE 
                  WHEN bb.borrower_type = 'student' THEN s.admission_number
                  WHEN bb.borrower_type = 'teacher' THEN t.email
              END as borrower_identifier
              FROM book_borrowings bb
              JOIN books b ON bb.book_id = b.id
              LEFT JOIN students s ON bb.borrower_type = 'student' AND bb.borrower_id = s.id
              LEFT JOIN teachers t ON bb.borrower_type = 'teacher' AND bb.borrower_id = t.id
              WHERE b.school_id = ?";
    $params = [$school_id];
    
    if ($filter_status) {
        $query .= " AND bb.status = ?";
        $params[] = $filter_status;
    }
    
    if ($filter_borrower_type) {
        $query .= " AND bb.borrower_type = ?";
        $params[] = $filter_borrower_type;
    }
    
    if ($filter_date_from) {
        $query .= " AND bb.borrow_date >= ?";
        $params[] = $filter_date_from;
    }
    
    if ($filter_date_to) {
        $query .= " AND bb.borrow_date <= ?";
        $params[] = $filter_date_to;
    }
    
    $query .= " ORDER BY bb.borrow_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $borrowing_history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch borrowing history: " . $e->getMessage());
}

// Get statistics
$stats = [];
try {
    // Total borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_borrowings'] = $stmt->fetch()['total'] ?? 0;
    
    // Currently borrowed
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'borrowed'");
    $stmt->execute([$school_id]);
    $stats['currently_borrowed'] = $stmt->fetch()['total'] ?? 0;
    
    // Returned
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'returned'");
    $stmt->execute([$school_id]);
    $stats['returned'] = $stmt->fetch()['total'] ?? 0;
    
    // Overdue
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'borrowed' AND bb.due_date < CURDATE()");
    $stmt->execute([$school_id]);
    $stats['overdue'] = $stmt->fetch()['total'] ?? 0;
    
    // Student borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.borrower_type = 'student'");
    $stmt->execute([$school_id]);
    $stats['student_borrowings'] = $stmt->fetch()['total'] ?? 0;
    
    // Teacher borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.borrower_type = 'teacher'");
    $stmt->execute([$school_id]);
    $stats['teacher_borrowings'] = $stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    error_log("Failed to fetch statistics: " . $e->getMessage());
}

// Get popular books
$popular_books = [];
try {
    $stmt = $pdo->prepare("SELECT b.id, b.title, b.author, COUNT(bb.id) as borrow_count
                            FROM books b
                            LEFT JOIN book_borrowings bb ON b.id = bb.book_id
                            WHERE b.school_id = ?
                            GROUP BY b.id, b.title, b.author
                            ORDER BY borrow_count DESC
                            LIMIT 10");
    $stmt->execute([$school_id]);
    $popular_books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch popular books: " . $e->getMessage());
}

// Get inventory statistics
$inventory_stats = [];
try {
    // Books by category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM books WHERE school_id = ? GROUP BY category ORDER BY count DESC");
    $stmt->execute([$school_id]);
    $inventory_stats['by_category'] = $stmt->fetchAll();
    
    // Books by condition
    $stmt = $pdo->prepare("SELECT `condition`, COUNT(*) as count FROM books WHERE school_id = ? GROUP BY `condition` ORDER BY count DESC");
    $stmt->execute([$school_id]);
    $inventory_stats['by_condition'] = $stmt->fetchAll();
    
    // Books by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM books WHERE school_id = ? GROUP BY status ORDER BY count DESC");
    $stmt->execute([$school_id]);
    $inventory_stats['by_status'] = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch inventory statistics: " . $e->getMessage());
}

// Get categories for filtering
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM book_categories WHERE status = 'active' ORDER BY category_name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch categories: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Reports - <?php echo htmlspecialchars($librarian_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .header-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 25px;
            color: #5f6368;
            transition: background 0.2s;
        }
        
        .header-btn:hover {
            background: #f1f3f4;
        }
        
        .header-btn i {
            font-size: 20px;
            color: var(--primary-color);
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
            text-align: center;
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
            align-items: center;
            justify-content: center;
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
            background: var(--primary-color);
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
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-value {
                font-size: 20px;
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
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-right span {
                font-size: 12px;
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .logo {
                font-size: 18px;
            }
            
            .row {
                flex-direction: column;
            }
            
            .col-md-3,
            .col-md-2 {
                width: 100%;
                margin-bottom: 12px;
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
            <a href="borrow" class="nav-link">
                <i class="fas fa-hand-holding"></i>
                <span>Borrow Book</span>
            </a>
            <a href="return" class="nav-link">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
            <a href="reports" class="nav-link active">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
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
        <h1 style="margin-bottom: 24px;">Library Reports</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_borrowings']; ?></div>
                <div class="stat-label">Total Borrowings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['currently_borrowed']; ?></div>
                <div class="stat-label">Currently Borrowed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['returned']; ?></div>
                <div class="stat-label">Returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['overdue']; ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['student_borrowings']; ?></div>
                <div class="stat-label">Student Borrowings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['teacher_borrowings']; ?></div>
                <div class="stat-label">Teacher Borrowings</div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row" style="margin-bottom: 24px;">
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Circulation Statistics</h2>
                    <canvas id="circulationChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Borrower Distribution</h2>
                    <canvas id="borrowerChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="row" style="margin-bottom: 24px;">
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Books by Category</h2>
                    <canvas id="categoryChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Books by Condition</h2>
                    <canvas id="conditionChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Popular Books -->
        <div class="card">
            <h2 class="card-title">Popular Books</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Borrow Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($popular_books)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No borrowing data available</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($popular_books as $book): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo $book['borrow_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Inventory Reports -->
        <div class="card">
            <h2 class="card-title">Inventory Reports</h2>
            
            <div class="row">
                <div class="col-md-4">
                    <h3 style="font-size: 16px; margin-bottom: 12px;">Books by Category</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventory_stats['by_category'])): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inventory_stats['by_category'] as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['category'] ?? 'Uncategorized'); ?></td>
                                            <td><?php echo $stat['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <h3 style="font-size: 16px; margin-bottom: 12px;">Books by Condition</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Condition</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventory_stats['by_condition'])): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inventory_stats['by_condition'] as $stat): ?>
                                        <tr>
                                            <td><?php echo ucfirst($stat['condition']); ?></td>
                                            <td><?php echo $stat['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <h3 style="font-size: 16px; margin-bottom: 12px;">Books by Status</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventory_stats['by_status'])): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inventory_stats['by_status'] as $stat): ?>
                                        <tr>
                                            <td><?php echo ucfirst($stat['status']); ?></td>
                                            <td><?php echo $stat['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Borrowing History -->
        <div class="card">
            <h2 class="card-title">Borrowing History</h2>
            
            <!-- Search -->
            <div class="search-section" style="margin-bottom: 20px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by book, borrower..." style="width: 100%; max-width: 400px;">
            </div>
            
            <!-- Filters -->
            <form method="GET" style="margin-bottom: 20px;">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status">
                            <option value="">All</option>
                            <option value="borrowed" <?php echo $filter_status === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                            <option value="returned" <?php echo $filter_status === 'returned' ? 'selected' : ''; ?>>Returned</option>
                            <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Borrower Type</label>
                        <select class="form-control" name="borrower_type">
                            <option value="">All</option>
                            <option value="student" <?php echo $filter_borrower_type === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="teacher" <?php echo $filter_borrower_type === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <button type="button" class="btn btn-secondary" onclick="exportToCSV()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrower</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($borrowing_history)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No borrowing records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($borrowing_history as $record): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($record['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($record['author']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($record['borrower_name']); ?>
                                        <br>
                                        <small style="color: #5f6368;"><?php echo htmlspecialchars($record['borrower_identifier']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                                    <td><?php echo $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($record['status'] === 'borrowed'): ?>
                                            <?php if ($record['due_date'] < date('Y-m-d')): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Borrowed</span>
                                            <?php endif; ?>
                                        <?php elseif ($record['status'] === 'overdue'): ?>
                                            <span class="badge badge-danger">Returned Late</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Returned</span>
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
        
        // Search Functionality for Borrowing History
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                const table = document.querySelectorAll('.table')[2]; // Third table is borrowing history
                if (table) {
                    const tbody = table.querySelector('tbody');
                    const rows = tbody.querySelectorAll('tr');
                    
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        
                        rows.forEach(row => {
                            const book = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                            const author = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                            const borrower = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                            
                            const matchesSearch = book.includes(searchTerm) || 
                                                 author.includes(searchTerm) || 
                                                 borrower.includes(searchTerm);
                            
                            if (matchesSearch) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });
                }
            }
        });
        
        // Export to CSV Functionality
        function exportToCSV() {
            const table = document.querySelectorAll('.table')[2]; // Third table is borrowing history
            if (!table) return;
            
            const rows = table.querySelectorAll('tr');
            let csvContent = [];
            
            // Add headers
            const headers = [];
            table.querySelectorAll('th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            csvContent.push(headers.join(','));
            
            // Add data rows
            table.querySelectorAll('tbody tr').forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach(td => {
                    rowData.push(td.textContent.trim().replace(/,/g, ' '));
                });
                csvContent.push(rowData.join(','));
            });
            
            // Create and download CSV
            const csvString = csvContent.join('\n');
            const blob = new Blob([csvString], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'library_reports_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Circulation Statistics Chart
            const circulationCtx = document.getElementById('circulationChart');
            if (circulationCtx) {
                new Chart(circulationCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Total Borrowings', 'Currently Borrowed', 'Returned', 'Overdue'],
                        datasets: [{
                            label: 'Count',
                            data: [
                                <?php echo $stats['total_borrowings']; ?>,
                                <?php echo $stats['currently_borrowed']; ?>,
                                <?php echo $stats['returned']; ?>,
                                <?php echo $stats['overdue']; ?>
                            ],
                            backgroundColor: [
                                '#FF6B35',
                                '#008000',
                                '#137333',
                                '#c5221f'
                            ],
                            borderColor: [
                                '#FF6B35',
                                '#008000',
                                '#137333',
                                '#c5221f'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Borrower Distribution Chart
            const borrowerCtx = document.getElementById('borrowerChart');
            if (borrowerCtx) {
                new Chart(borrowerCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Student Borrowings', 'Teacher Borrowings'],
                        datasets: [{
                            data: [
                                <?php echo $stats['student_borrowings']; ?>,
                                <?php echo $stats['teacher_borrowings']; ?>
                            ],
                            backgroundColor: ['#FF6B35', '#008000'],
                            borderColor: ['#FF6B35', '#008000'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Books by Category Chart
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx && <?php echo count($inventory_stats['by_category']); ?> > 0) {
                const categoryLabels = <?php echo json_encode(array_column($inventory_stats['by_category'], 'category') ?? ['Uncategorized']); ?>;
                const categoryData = <?php echo json_encode(array_column($inventory_stats['by_category'], 'count')); ?>;
                
                new Chart(categoryCtx, {
                    type: 'pie',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categoryData,
                            backgroundColor: [
                                '#FF6B35', '#008000', '#137333', '#c5221f', '#FFD700',
                                '#4285F4', '#EA4335', '#FBBC05', '#34A853', '#FF6D01'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Books by Condition Chart
            const conditionCtx = document.getElementById('conditionChart');
            if (conditionCtx && <?php echo count($inventory_stats['by_condition']); ?> > 0) {
                const conditionLabels = <?php echo json_encode(array_map('ucfirst', array_column($inventory_stats['by_condition'], 'condition'))); ?>;
                const conditionData = <?php echo json_encode(array_column($inventory_stats['by_condition'], 'count')); ?>;
                
                new Chart(conditionCtx, {
                    type: 'bar',
                    data: {
                        labels: conditionLabels,
                        datasets: [{
                            label: 'Count',
                            data: conditionData,
                            backgroundColor: [
                                '#137333', // new
                                '#008000', // good
                                '#FFD700', // fair
                                '#FF6B35', // poor
                                '#c5221f'  // damaged
                            ],
                            borderColor: [
                                '#137333', '#008000', '#FFD700', '#FF6B35', '#c5221f'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
