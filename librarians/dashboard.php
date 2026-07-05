<?php
// Librarian Dashboard
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['librarian_id'])) {
    header('Location: index.php');
    exit;
}

$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Get statistics
$stats = [];
try {
    // Total books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(available_copies) as available FROM books WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $book_stats = $stmt->fetch();
    $stats['total_books'] = $book_stats['total'] ?? 0;
    $stats['available_books'] = $book_stats['available'] ?? 0;
    
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
} catch (PDOException $e) {
    error_log("Failed to fetch statistics: " . $e->getMessage());
    $stats = [
        'total_books' => 0,
        'available_books' => 0,
        'borrowed_books' => 0,
        'overdue_books' => 0,
        'total_students' => 0,
        'total_teachers' => 0
    ];
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
    <title>Dashboard - <?php echo htmlspecialchars($librarian_name); ?></title>
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
        
        .header-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
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
            background: transparent;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 12px;
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
            border-radius: 4px;
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
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="books.php" class="nav-link">
                <i class="fas fa-book"></i>
                <span>Books</span>
            </a>
            <a href="borrow.php" class="nav-link">
                <i class="fas fa-hand-holding"></i>
                <span>Borrow Book</span>
            </a>
            <a href="return.php" class="nav-link">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="profile.php" class="nav-link">
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
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['available_books']; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="stat-value"><?php echo $stats['borrowed_books']; ?></div>
                <div class="stat-label">Borrowed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['overdue_books']; ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
                <div class="stat-label">Teachers</div>
            </div>
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
                    window.location.href = 'index.php';
                } else {
                    alert('Logout failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'index.php';
            });
        }
    </script>
</body>
</html>
