<?php
require_once '../config.php';

// Authentication check
if (!isset($_SESSION['librarian_id'])) {
    header('Location: login');
    exit;
}

$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Get book ID from URL
$book_id = $_GET['id'] ?? '';

if (empty($book_id)) {
    header('Location: books');
    exit;
}

// Get book details
$book = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND school_id = ?");
    $stmt->execute([$book_id, $school_id]);
    $book = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch book: " . $e->getMessage());
}

if (!$book) {
    header('Location: books');
    exit;
}

// Get borrowing history for this book
$borrowing_history = [];
try {
    $stmt = $pdo->prepare("SELECT bb.*, 
              CASE 
                  WHEN bb.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  WHEN bb.borrower_type = 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
              END as user_name,
              CASE 
                  WHEN bb.borrower_type = 'student' THEN s.admission_number
                  WHEN bb.borrower_type = 'teacher' THEN t.email
              END as user_identifier
              FROM book_borrowings bb
              LEFT JOIN students s ON bb.borrower_type = 'student' AND bb.borrower_id = s.id
              LEFT JOIN teachers t ON bb.borrower_type = 'teacher' AND bb.borrower_id = t.id
              WHERE bb.book_id = ?
              ORDER BY bb.borrow_date DESC");
    $stmt->execute([$book_id]);
    $borrowing_history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch borrowing history: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Book Details - <?php echo htmlspecialchars($book['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --bg-color: #ffffff;
            --header-height: 64px;
            --sidebar-width: 256px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header */
        .header {
            position: fixed;
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

        .user-name {
            font-size: 14px;
            color: #202124;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: transparent;
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
            box-shadow: none;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }

        .book-cover {
            max-width: 300px;
            max-height: 450px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .detail-label {
            font-weight: 500;
            color: #5f6368;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .detail-value {
            color: #202124;
            font-size: 16px;
            margin-bottom: 16px;
        }

        .table {
            background: transparent;
        }

        .table th {
            font-weight: 500;
            color: #5f6368;
            border-bottom: 1px solid #e8eaed;
        }

        .btn {
            border-radius: 25px;
            padding: 8px 24px;
            font-weight: 500;
            border: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background: #f1f3f4;
            color: #5f6368;
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
            <span class="user-name"><?php echo htmlspecialchars($librarian_name); ?></span>
            <div class="user-avatar">
                <?php echo strtoupper(substr($librarian_name, 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Library</div>
            <button class="nav-link" onclick="location.href='dashboard'">
                <i class="fas fa-home"></i> Dashboard
            </button>
            <button class="nav-link" onclick="location.href='books'">
                <i class="fas fa-book"></i> Books
            </button>
            <button class="nav-link" onclick="location.href='categories'">
                <i class="fas fa-tags"></i> Categories
            </button>
            <button class="nav-link" onclick="location.href='borrow'">
                <i class="fas fa-hand-holding"></i> Borrow
            </button>
            <button class="nav-link" onclick="location.href='return'">
                <i class="fas fa-undo"></i> Return
            </button>
            <button class="nav-link" onclick="location.href='reservations'">
                <i class="fas fa-bookmark"></i> Reservations
            </button>
            <button class="nav-link" onclick="location.href='fines'">
                <i class="fas fa-money-bill"></i> Fines
            </button>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Reports</div>
            <button class="nav-link" onclick="location.href='reports'">
                <i class="fas fa-chart-bar"></i> Reports
            </button>
            <button class="nav-link" onclick="location.href='import_export'">
                <i class="fas fa-exchange-alt"></i> Import/Export
            </button>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <button class="nav-link" onclick="location.href='profile'">
                <i class="fas fa-user"></i> Profile
            </button>
            <button class="nav-link" onclick="location.href='logout'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book"></i> Book Details</h2>
            <a href="books" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card text-center">
                    <?php if ($book['cover_image']): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="book-cover" alt="Book Cover">
                    <?php else: ?>
                        <div class="book-cover d-flex align-items-center justify-content-center bg-light">
                            <span class="text-muted">No Cover Image</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <h3 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Author</div>
                            <div class="detail-value"><?php echo htmlspecialchars($book['author']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">ISBN</div>
                            <div class="detail-value"><?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Publisher</div>
                            <div class="detail-value"><?php echo htmlspecialchars($book['publisher'] ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Publication Year</div>
                            <div class="detail-value"><?php echo htmlspecialchars($book['publication_year'] ?? '-'); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Category</div>
                            <div class="detail-value"><?php echo htmlspecialchars($book['category'] ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Condition</div>
                            <div class="detail-value"><?php echo ucfirst($book['condition'] ?? 'new'); ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-label">Total Copies</div>
                            <div class="detail-value"><?php echo $book['total_copies']; ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Available Copies</div>
                            <div class="detail-value"><?php echo $book['available_copies']; ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Price</div>
                            <div class="detail-value"><?php echo $book['book_price'] > 0 ? number_format($book['book_price'], 2) : '-'; ?></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="badge bg-<?php echo $book['status'] === 'available' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($book['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Added Date</div>
                            <div class="detail-value"><?php echo date('M d, Y', strtotime($book['created_at'] ?? 'now')); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($book['description']): ?>
                        <div>
                            <div class="detail-label">Description</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($book['description'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="books" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button class="btn btn-primary" onclick="location.href='books?edit=<?php echo $book['id']; ?>'">
                            <i class="fas fa-edit"></i> Edit Book
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($borrowing_history)): ?>
            <div class="card">
                <h4 class="card-title"><i class="fas fa-history"></i> Borrowing History</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Identifier</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowing_history as $history): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($history['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($history['user_identifier'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($history['borrow_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($history['due_date'])); ?></td>
                                    <td><?php echo $history['return_date'] ? date('M d, Y', strtotime($history['return_date'])) : '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $history['status'] === 'returned' ? 'success' : 
                                                ($history['status'] === 'overdue' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($history['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    </script>
</body>
</html>
