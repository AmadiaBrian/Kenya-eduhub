<?php
// Book Returning Page
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

// Handle book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $borrowing_id = $_POST['borrowing_id'] ?? '';
    $return_date = $_POST['return_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    if (empty($borrowing_id)) $errors[] = 'Borrowing record is required';
    if (empty($return_date)) $errors[] = 'Return date is required';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Get borrowing details
            $stmt = $pdo->prepare("SELECT bb.*, b.available_copies FROM book_borrowings bb JOIN books b ON bb.book_id = b.id WHERE bb.id = ? AND bb.status = 'borrowed'");
            $stmt->execute([$borrowing_id]);
            $borrowing = $stmt->fetch();
            
            if (!$borrowing) {
                $errors[] = 'Borrowing record not found or already returned';
                $pdo->rollBack();
            } else {
                // Update borrowing record
                $status = $borrowing['due_date'] < $return_date ? 'overdue' : 'returned';
                $stmt = $pdo->prepare("UPDATE book_borrowings SET return_date = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->execute([$return_date, $status, $notes, $borrowing_id]);
                
                // Update available copies
                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                $stmt->execute([$borrowing['book_id']]);
                
                $pdo->commit();
                $success = 'Book returned successfully!';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Failed to return book: " . $e->getMessage());
            $errors[] = 'Failed to return book. Please try again.';
        }
    }
}

// Get current borrowings
$current_borrowings = [];
try {
    $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.id as book_id,
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
                            WHERE b.school_id = ? AND bb.status = 'borrowed'
                            ORDER BY bb.due_date ASC");
    $stmt->execute([$school_id]);
    $current_borrowings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch current borrowings: " . $e->getMessage());
}

// Get recent returns
$recent_returns = [];
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
                            WHERE b.school_id = ? AND bb.status IN ('returned', 'overdue')
                            ORDER BY bb.return_date DESC
                            LIMIT 10");
    $stmt->execute([$school_id]);
    $recent_returns = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch recent returns: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - <?php echo htmlspecialchars($librarian_name); ?></title>
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
            border-radius: 8px;
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
        
        /* Status badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-warning {
            background: #fef7e0;
            color: #b06000;
        }
        
        .badge-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-success {
            background: #e6f4ea;
            color: #137333;
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
            <a href="dashboard.php" class="nav-link">
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
            <a href="return.php" class="nav-link active">
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
        
        <!-- Current Borrowings -->
        <div class="card">
            <h2 class="card-title">Books to Return</h2>
            <div class="search-filter-section" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by book, borrower..." style="flex: 1; min-width: 200px;">
                <select id="statusFilter" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="">All Status</option>
                    <option value="overdue">Overdue</option>
                    <option value="borrowed">Borrowed</option>
                </select>
            </div>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($current_borrowings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No books pending return</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($current_borrowings as $borrowing): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($borrowing['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($borrowing['author']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($borrowing['borrower_name']); ?>
                                        <br>
                                        <small style="color: #5f6368;"><?php echo htmlspecialchars($borrowing['borrower_identifier']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($borrowing['due_date'])); ?></td>
                                    <td>
                                        <?php if ($borrowing['due_date'] < date('Y-m-d')): ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Borrowed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showReturnModal(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>')">
                                            Return
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Returns -->
        <div class="card">
            <h2 class="card-title">Recent Returns</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrower</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_returns)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No recent returns</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_returns as $return): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($return['title']); ?></td>
                                    <td><?php echo htmlspecialchars($return['author']); ?></td>
                                    <td><?php echo htmlspecialchars($return['borrower_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($return['return_date'])); ?></td>
                                    <td>
                                        <?php if ($return['status'] === 'overdue'): ?>
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
    
    <!-- Return Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Return Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="borrowing_id" id="borrowingId">
                        <div class="mb-3">
                            <label class="form-label">Book</label>
                            <input type="text" class="form-control" id="bookTitle" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Return Date</label>
                            <input type="date" class="form-control" name="return_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                        <button type="submit" name="return_book" class="btn btn-primary">
                            <i class="fas fa-undo me-2"></i> Return Book
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showReturnModal(borrowingId, bookTitle) {
            document.getElementById('borrowingId').value = borrowingId;
            document.getElementById('bookTitle').value = bookTitle;
            const modal = new bootstrap.Modal(document.getElementById('returnModal'));
            modal.show();
        }
        
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
        
        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const table = document.querySelector('.table');
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                
                rows.forEach(row => {
                    const book = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                    const author = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const borrower = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const statusCell = row.querySelector('td:nth-child(6)');
                    const status = statusCell ? statusCell.textContent.toLowerCase().trim() : '';
                    
                    const matchesSearch = book.includes(searchTerm) || 
                                         author.includes(searchTerm) || 
                                         borrower.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;
                    
                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
        });
    </script>
</body>
</html>
