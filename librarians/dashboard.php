<?php
// Librarian Dashboard
// Authentication is handled by index.php router
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Get statistics
$stats = [];
try {
    // Total books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(available_copies) as available, SUM(total_copies) as total_copies FROM books WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $book_stats = $stmt->fetch();
    $stats['total_books'] = $book_stats['total'] ?? 0;
    $stats['available_books'] = $book_stats['available'] ?? 0;
    $stats['total_copies'] = $book_stats['total_copies'] ?? 0;

    // Calculate availability percentage
    $stats['availability_percentage'] = $stats['total_copies'] > 0 ? round(($stats['available_books'] / $stats['total_copies']) * 100, 1) : 0;
    
    // Currently borrowed books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'borrowed'");
    $stmt->execute([$school_id]);
    $stats['borrowed_books'] = $stmt->fetch()['total'] ?? 0;

    // Overdue books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'borrowed' AND bb.due_date < CURDATE()");
    $stmt->execute([$school_id]);
    $stats['overdue_books'] = $stmt->fetch()['total'] ?? 0;

    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$school_id]);
    $stats['total_students'] = $stmt->fetch()['total'] ?? 0;

    // Total teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM teachers WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $stats['total_teachers'] = $stmt->fetch()['total'] ?? 0;

    // Books by category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM books WHERE school_id = ? AND category IS NOT NULL AND category != '' GROUP BY category ORDER BY count DESC LIMIT 5");
    $stmt->execute([$school_id]);
    $stats['top_categories'] = $stmt->fetchAll();

    // Most borrowed books
    $stmt = $pdo->prepare("SELECT b.title, b.author, COUNT(bb.id) as borrow_count FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? GROUP BY b.id ORDER BY borrow_count DESC LIMIT 5");
    $stmt->execute([$school_id]);
    $stats['most_borrowed'] = $stmt->fetchAll();

    // Books by condition
    $stmt = $pdo->prepare("SELECT `condition`, COUNT(*) as count FROM books WHERE school_id = ? GROUP BY `condition`");
    $stmt->execute([$school_id]);
    $stats['by_condition'] = $stmt->fetchAll();

    // Recent returns (last 7 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'returned' AND bb.return_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$school_id]);
    $stats['recent_returns'] = $stmt->fetch()['total'] ?? 0;

    // Active reservations
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_reservations br JOIN books b ON br.book_id = b.id WHERE b.school_id = ? AND br.status = 'pending'");
    $stmt->execute([$school_id]);
    $stats['active_reservations'] = $stmt->fetch()['total'] ?? 0;

    // Library utilization percentage
    $stats['utilization_percentage'] = $stats['total_copies'] > 0 ? round((($stats['total_copies'] - $stats['available_books']) / $stats['total_copies']) * 100, 1) : 0;

    // Average borrow time (days)
    $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(IFNULL(bb.return_date, CURDATE()), bb.borrow_date)) as avg_days FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'returned'");
    $stmt->execute([$school_id]);
    $avg_borrow = $stmt->fetch();
    $stats['avg_borrow_days'] = round($avg_borrow['avg_days'] ?? 0, 1);

    // Today's activity
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND DATE(bb.borrow_date) = CURDATE()");
    $stmt->execute([$school_id]);
    $stats['today_borrows'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE b.school_id = ? AND bb.status = 'returned' AND DATE(bb.return_date) = CURDATE()");
    $stmt->execute([$school_id]);
    $stats['today_returns'] = $stmt->fetch()['total'] ?? 0;

    // Low stock books (less than 3 copies available)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE school_id = ? AND available_copies < 3 AND total_copies > 0");
    $stmt->execute([$school_id]);
    $stats['low_stock_count'] = $stmt->fetch()['total'] ?? 0;

    // Get recent activity for timeline
    $stmt = $pdo->prepare("SELECT bh.*, b.title, 
                            CASE 
                                WHEN bh.user_type = 'librarian' THEN CONCAT('Librarian')
                                WHEN bh.user_type = 'student' THEN (SELECT CONCAT(s.first_name, ' ', s.last_name) FROM students s WHERE s.id = bh.user_id)
                                WHEN bh.user_type = 'teacher' THEN (SELECT CONCAT(t.first_name, ' ', t.last_name) FROM teachers t WHERE t.id = bh.user_id)
                            END as user_name
                            FROM book_history bh
                            JOIN books b ON bh.book_id = b.id
                            WHERE b.school_id = ?
                            ORDER BY bh.created_at DESC
                            LIMIT 10");
    $stmt->execute([$school_id]);
    $recent_activity = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch statistics: " . $e->getMessage());
    $stats = [
        'total_books' => 0,
        'available_books' => 0,
        'total_copies' => 0,
        'borrowed_books' => 0,
        'overdue_books' => 0,
        'total_students' => 0,
        'total_teachers' => 0,
        'top_categories' => [],
        'most_borrowed' => [],
        'by_condition' => [],
        'recent_returns' => 0,
        'active_reservations' => 0,
        'availability_percentage' => 0,
        'utilization_percentage' => 0,
        'avg_borrow_days' => 0,
        'today_borrows' => 0,
        'today_returns' => 0,
        'low_stock_count' => 0
    ];
    $recent_activity = [];
}

// Get recent borrowings
$recent_borrowings = [];
try {
    $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author,
                            CASE 
                                WHEN bb.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                                WHEN bb.borrower_type = 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
                            END as borrower_name
                            FROM book_borrowings bb
                            JOIN books b ON bb.book_id = b.id
                            LEFT JOIN students s ON bb.borrower_type = 'student' AND bb.borrower_id = s.id
                            LEFT JOIN teachers t ON bb.borrower_type = 'teacher' AND bb.borrower_id = t.id
                            WHERE b.school_id = ?
                            ORDER BY bb.borrow_date DESC
                            LIMIT 10");
    $stmt->execute([$school_id]);
    $recent_borrowings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch recent borrowings: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Dashboard - <?php echo htmlspecialchars($librarian_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--bg-color);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 8px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0;
            background: transparent;
            border: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 12px;
        }

        .stat-icon i {
            border: none !important;
            box-shadow: none !important;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #5f6368;
        }

        .stat-sublabel {
            font-size: 12px;
            color: #9aa0a6;
            margin-top: 4px;
        }

        /* Alerts Section */
        .alerts-section {
            margin-bottom: 24px;
        }

        .alerts-section .alert {
            margin-bottom: 12px;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 20px;
            background: var(--bg-color);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 8px;
            text-decoration: none;
            color: #202124;
            transition: all 0.2s;
        }

        .quick-action-btn:hover {
            background: #e8eaed;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .quick-action-btn i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .quick-action-btn span {
            font-size: 14px;
            font-weight: 500;
        }

        /* Activity Timeline */
        .activity-timeline {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #e8eaed;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8f0fe;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #1967d2;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }

        .activity-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 4px;
        }

        .activity-details {
            font-size: 12px;
            color: #9aa0a6;
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
            text-decoration: none;
            display: inline-block;
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

        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
            
            .stat-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            
            .stat-value {
                font-size: 24px;
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
            <a href="dashboard" class="nav-link active">
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
            <a href="import_export" class="nav-link">
                <i class="fas fa-exchange-alt"></i>
                <span>Import/Export</span>
            </a>
            <a href="borrow" class="nav-link">
                <i class="fas fa-hand-holding"></i>
                <span>Borrow Book</span>
            </a>
            <a href="return" class="nav-link">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
            <a href="reports" class="nav-link">
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
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($librarian_name); ?></p>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_books']; ?></div>
                <div class="stat-label">Total Books</div>
                <div class="stat-sublabel"><?php echo $stats['total_copies']; ?> total copies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['available_books']; ?></div>
                <div class="stat-label">Available</div>
                <div class="stat-sublabel"><?php echo $stats['availability_percentage']; ?>% availability</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="stat-value"><?php echo $stats['borrowed_books']; ?></div>
                <div class="stat-label">Borrowed</div>
                <div class="stat-sublabel">Currently out</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['overdue_books']; ?></div>
                <div class="stat-label">Overdue</div>
                <div class="stat-sublabel">Need attention</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clone"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_copies']; ?></div>
                <div class="stat-label">Total Copies</div>
                <div class="stat-sublabel">Across all books</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="stat-value"><?php echo $stats['recent_returns']; ?></div>
                <div class="stat-label">Recent Returns</div>
                <div class="stat-sublabel">Last 7 days</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bookmark"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active_reservations']; ?></div>
                <div class="stat-label">Reservations</div>
                <div class="stat-sublabel">Pending requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Students</div>
                <div class="stat-sublabel">Active users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
                <div class="stat-label">Teachers</div>
                <div class="stat-sublabel">Staff members</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo $stats['utilization_percentage']; ?>%</div>
                <div class="stat-label">Utilization</div>
                <div class="stat-sublabel">Books in use</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['avg_borrow_days']; ?>d</div>
                <div class="stat-label">Avg Borrow</div>
                <div class="stat-sublabel">Days per book</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value"><?php echo $stats['today_borrows']; ?></div>
                <div class="stat-label">Today Borrows</div>
                <div class="stat-sublabel"><?php echo $stats['today_returns']; ?> returns</div>
            </div>
        </div>

        <!-- Alerts Section -->
        <?php if ($stats['overdue_books'] > 0 || $stats['low_stock_count'] > 0 || $stats['active_reservations'] > 0): ?>
        <div class="alerts-section">
            <?php if ($stats['overdue_books'] > 0): ?>
            <div class="alert alert-danger" style="display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong><?php echo $stats['overdue_books']; ?> Overdue Books</strong><br>
                    <small>Some books need immediate attention</small>
                </div>
                <a href="return" class="btn btn-sm btn-primary" style="margin-left: auto;">View Overdue</a>
            </div>
            <?php endif; ?>
            
            <?php if ($stats['low_stock_count'] > 0): ?>
            <div class="alert alert-warning" style="display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-box"></i>
                <div>
                    <strong><?php echo $stats['low_stock_count']; ?> Low Stock Books</strong><br>
                    <small>Consider ordering more copies</small>
                </div>
                <a href="books" class="btn btn-sm btn-primary" style="margin-left: auto;">Manage Stock</a>
            </div>
            <?php endif; ?>
            
            <?php if ($stats['active_reservations'] > 0): ?>
            <div class="alert alert-info" style="display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-bookmark"></i>
                <div>
                    <strong><?php echo $stats['active_reservations']; ?> Pending Reservations</strong><br>
                    <small>Students waiting for books</small>
                </div>
                <a href="reservations" class="btn btn-sm btn-primary" style="margin-left: auto;">View Reservations</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card">
            <h2 class="card-title">Quick Actions</h2>
            <div class="quick-actions-grid">
                <a href="borrow" class="quick-action-btn">
                    <i class="fas fa-hand-holding"></i>
                    <span>Borrow Book</span>
                </a>
                <a href="return" class="quick-action-btn">
                    <i class="fas fa-undo"></i>
                    <span>Return Book</span>
                </a>
                <a href="books" class="quick-action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add Book</span>
                </a>
                <a href="books" class="quick-action-btn">
                    <i class="fas fa-search"></i>
                    <span>Search Books</span>
                </a>
                <a href="reservations" class="quick-action-btn">
                    <i class="fas fa-bookmark"></i>
                    <span>Reservations</span>
                </a>
                <a href="reports" class="quick-action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row" style="margin-bottom: 24px;">
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Books by Category</h2>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Books by Condition</h2>
                    <div class="chart-container">
                        <canvas id="conditionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-bottom: 24px;">
            <div class="col-md-12">
                <div class="card">
                    <h2 class="card-title">Most Borrowed Books</h2>
                    <div class="chart-container">
                        <canvas id="borrowedChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Categories & Most Borrowed -->
        <div class="row" style="margin-bottom: 24px;">
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Top Categories</h2>
                    <?php if (empty($stats['top_categories'])): ?>
                        <p class="text-muted">No categories data available</p>
                    <?php else: ?>
                        <div style="text-align: left;">
                            <?php foreach ($stats['top_categories'] as $category): ?>
                                <div style="margin-bottom: 12px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <span><?php echo htmlspecialchars($category['category']); ?></span>
                                        <span><?php echo $category['count']; ?> books</span>
                                    </div>
                                    <div style="background: #e0e0e0; height: 8px; border-radius: 4px;">
                                        <div style="background: var(--primary-color); height: 100%; border-radius: 4px; width: <?php echo ($category['count'] / $stats['total_books']) * 100; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <h2 class="card-title">Most Borrowed Books</h2>
                    <?php if (empty($stats['most_borrowed'])): ?>
                        <p class="text-muted">No borrowing data available</p>
                    <?php else: ?>
                        <div style="text-align: left;">
                            <?php foreach ($stats['most_borrowed'] as $book): ?>
                                <div style="margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($book['author']); ?></div>
                                    <div style="font-size: 12px; color: var(--primary-color);"><?php echo $book['borrow_count']; ?> times borrowed</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Books by Condition -->
        <div class="card">
            <h2 class="card-title">Books by Condition</h2>
            <?php if (empty($stats['by_condition'])): ?>
                <p class="text-muted">No condition data available</p>
            <?php else: ?>
                <div style="display: flex; gap: 16px; flex-wrap: wrap; justify-content: center;">
                    <?php foreach ($stats['by_condition'] as $condition): ?>
                        <div style="padding: 16px; background: #f8f9fa; border-radius: 8px; min-width: 120px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 500; color: var(--primary-color);"><?php echo $condition['count']; ?></div>
                            <div style="font-size: 12px; color: #666; text-transform: capitalize;"><?php echo htmlspecialchars($condition['condition']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Borrowings -->
        <div class="card">
            <h2 class="card-title">Recent Borrowings</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrower</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_borrowings)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No recent borrowings</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_borrowings as $borrowing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($borrowing['title']); ?></td>
                                    <td><?php echo htmlspecialchars($borrowing['author']); ?></td>
                                    <td><?php echo htmlspecialchars($borrowing['borrower_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($borrowing['due_date'])); ?></td>
                                    <td>
                                        <?php if ($borrowing['status'] === 'borrowed'): ?>
                                            <?php if ($borrowing['due_date'] < date('Y-m-d')): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Borrowed</span>
                                            <?php endif; ?>
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

        <!-- Activity Timeline -->
        <div class="card">
            <h2 class="card-title">Recent Activity</h2>
            <?php if (empty($recent_activity)): ?>
                <p class="text-muted">No recent activity</p>
            <?php else: ?>
                <div class="activity-timeline">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php
                                $icon = 'fa-info-circle';
                                if ($activity['action'] === 'borrowed') $icon = 'fa-hand-holding';
                                elseif ($activity['action'] === 'returned') $icon = 'fa-undo';
                                elseif ($activity['action'] === 'added') $icon = 'fa-plus';
                                elseif ($activity['action'] === 'deleted') $icon = 'fa-trash';
                                elseif ($activity['action'] === 'fine_issued') $icon = 'fa-money-bill';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo ucfirst(htmlspecialchars($activity['action'])); ?>: <?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-meta">
                                    <span class="activity-user"><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></span>
                                    <span class="activity-time"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></span>
                                </div>
                                <?php if (!empty($activity['details'])): ?>
                                    <div class="activity-details"><?php echo preg_replace('/\s+for\s+\w+\s+ID:\s+\d+/i', '', htmlspecialchars($activity['details'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Chart.js loaded:', typeof Chart !== 'undefined');

            // Category Chart
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx) {
                const categoryData = <?php echo json_encode($stats['top_categories']); ?>;
                console.log('Category data:', categoryData);

                if (categoryData && categoryData.length > 0) {
                    const categoryLabels = categoryData.map(c => c.category);
                    const categoryCounts = categoryData.map(c => c.count);

                    new Chart(categoryCtx, {
                        type: 'bar',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                label: 'Books',
                                data: categoryCounts,
                                backgroundColor: 'rgba(255, 107, 53, 0.7)',
                                borderColor: 'rgba(255, 107, 53, 1)',
                                borderWidth: 1,
                                borderRadius: 4
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
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                } else {
                    categoryCtx.parentElement.innerHTML = '<p class="text-muted">No category data available</p>';
                }
            }

            // Condition Chart
            const conditionCtx = document.getElementById('conditionChart');
            if (conditionCtx) {
                const conditionData = <?php echo json_encode($stats['by_condition']); ?>;
                console.log('Condition data:', conditionData);

                if (conditionData && conditionData.length > 0) {
                    const conditionLabels = conditionData.map(c => c.condition);
                    const conditionCounts = conditionData.map(c => c.count);

                    const conditionColors = ['#FF6B35', '#5f6368', '#e8eaed', '#1e8e3e', '#fbbc04'];

                    new Chart(conditionCtx, {
                        type: 'doughnut',
                        data: {
                            labels: conditionLabels,
                            datasets: [{
                                data: conditionCounts,
                                backgroundColor: conditionColors.slice(0, conditionCounts.length),
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                } else {
                    conditionCtx.parentElement.innerHTML = '<p class="text-muted">No condition data available</p>';
                }
            }

            // Most Borrowed Books Chart
            const borrowedCtx = document.getElementById('borrowedChart');
            if (borrowedCtx) {
                const borrowedData = <?php echo json_encode($stats['most_borrowed']); ?>;
                console.log('Borrowed data:', borrowedData);

                if (borrowedData && borrowedData.length > 0) {
                    const borrowedLabels = borrowedData.map(b => b.title.substring(0, 30) + (b.title.length > 30 ? '...' : ''));
                    const borrowedCounts = borrowedData.map(b => b.borrow_count);

                    new Chart(borrowedCtx, {
                        type: 'bar',
                        data: {
                            labels: borrowedLabels,
                            datasets: [{
                                label: 'Times Borrowed',
                                data: borrowedCounts,
                                backgroundColor: 'rgba(30, 142, 62, 0.7)',
                                borderColor: 'rgba(30, 142, 62, 1)',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                y: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                } else {
                    borrowedCtx.parentElement.innerHTML = '<p class="text-muted">No borrowed books data available</p>';
                }
            }
        });
    </script>
</body>
</html>
