<?php
// Book Management Page
// Authentication is handled by index.php router
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Handle book addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $isbn = trim($_POST['isbn'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_year = trim($_POST['publication_year'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total_copies = (int)($_POST['total_copies'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($author)) $errors[] = 'Author is required';
    if (empty($total_copies) || $total_copies < 1) $errors[] = 'Total copies must be at least 1';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO books (school_id, isbn, title, author, publisher, publication_year, category, total_copies, available_copies, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([$school_id, $isbn, $title, $author, $publisher, $publication_year, $category, $total_copies, $total_copies, $description]);
            $success = 'Book added successfully!';
        } catch (PDOException $e) {
            error_log("Failed to add book: " . $e->getMessage());
            $errors[] = 'Failed to add book. Please try again.';
        }
    }
}

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ? AND school_id = ?");
        $stmt->execute([$book_id, $school_id]);
        $success = 'Book deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete book: " . $e->getMessage());
        $errors[] = 'Failed to delete book. Please try again.';
    }
}

// Handle book status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $book_id = $_POST['book_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE books SET status = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$status, $book_id, $school_id]);
        $success = 'Book status updated successfully!';
    } catch (PDOException $e) {
        error_log("Failed to update book status: " . $e->getMessage());
        $errors[] = 'Failed to update status. Please try again.';
    }
}

// Get books
$books = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE school_id = ? ORDER BY title ASC");
    $stmt->execute([$school_id]);
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch books: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - <?php echo htmlspecialchars($librarian_name); ?></title>
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
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a4e54;
        }
        
        .btn-danger {
            background: #c5221f;
            color: white;
        }
        
        .btn-danger:hover {
            background: #a91c1a;
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
            
            .form-control {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .form-label {
                font-size: 12px;
            }
            
            .card {
                text-align: left;
            }
            
            .card form {
                text-align: left;
            }
            
            #mobileAddBookBtn {
                display: block !important;
            }
            
            #addBookCard {
                display: none;
            }
            
            #addBookCard.show {
                display: block;
            }
            
            #closeFormBtn {
                display: inline-block !important;
            }
            
            #hideFormBtn {
                display: inline-block !important;
            }
            
            .card form > div:last-of-type {
                justify-content: space-between;
            }
            
            .card form > div:last-of-type .btn {
                padding: 14px 24px;
                font-size: 16px;
                flex: 1;
                max-width: 48%;
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
            
            .row {
                flex-direction: column;
            }
            
            .col-md-4,
            .col-md-3 {
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
            <a href="books" class="nav-link active">
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
        
        <!-- Mobile Add Book Button -->
        <button id="mobileAddBookBtn" class="btn btn-primary" style="display: none; width: 100%; margin-bottom: 20px;">
            <i class="fas fa-plus me-2"></i> Add New Book
        </button>
        
        <!-- Add Book -->
        <div class="card" id="addBookCard">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 class="card-title" style="margin-bottom: 0;">Add New Book</h2>
                <button id="closeFormBtn" class="btn btn-sm btn-secondary" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="addBookForm">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ISBN (Book Number)</label>
                        <input type="text" class="form-control" name="isbn">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Author *</label>
                        <input type="text" class="form-control" name="author" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Publisher</label>
                        <input type="text" class="form-control" name="publisher">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Publication Year</label>
                        <input type="number" class="form-control" name="publication_year" min="1900" max="2099">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" name="category">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Total Copies *</label>
                        <input type="number" class="form-control" name="total_copies" required min="1">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="submit" name="add_book" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Add Book
                    </button>
                    <button type="button" id="hideFormBtn" class="btn btn-secondary" style="display: none;">
                        <i class="fas fa-times me-2"></i> Hide
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Books List -->
        <div class="card">
            <h2 class="card-title">Books Inventory</h2>
            <div class="search-filter-section" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by title, author, ISBN..." style="flex: 1; min-width: 200px;">
                <select id="statusFilter" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
                <select id="categoryFilter" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Total</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No books found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($book['category'] ?? '-'); ?></td>
                                    <td><?php echo $book['total_copies']; ?></td>
                                    <td><?php echo $book['available_copies']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $book['status'] === 'available' ? '#137333' : '#c5221f'; ?>; font-weight: 500;">
                                            <?php echo ucfirst($book['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $book['status'] === 'available' ? 'unavailable' : 'available'; ?>">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-secondary">
                                                <?php echo $book['status'] === 'available' ? 'Disable' : 'Enable'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="delete_book" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
        
        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const table = document.querySelector('.table');
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            // Populate categories
            const categories = new Set();
            rows.forEach(row => {
                const categoryCell = row.querySelector('td:nth-child(4)');
                if (categoryCell) {
                    categories.add(categoryCell.textContent.trim());
                }
            });
            
            categories.forEach(category => {
                if (category && category !== '-') {
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    categoryFilter.appendChild(option);
                }
            });
            
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const categoryValue = categoryFilter.value;
                
                rows.forEach(row => {
                    const title = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                    const author = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const isbn = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const category = row.querySelector('td:nth-child(4)').textContent.trim();
                    const status = row.querySelector('td:nth-child(7)').textContent.toLowerCase().trim();
                    
                    const matchesSearch = title.includes(searchTerm) || 
                                         author.includes(searchTerm) || 
                                         isbn.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;
                    const matchesCategory = !categoryValue || category === categoryValue;
                    
                    if (matchesSearch && matchesStatus && matchesCategory) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
            categoryFilter.addEventListener('change', filterTable);
            
            // Mobile Add Book Form Toggle
            const mobileAddBookBtn = document.getElementById('mobileAddBookBtn');
            const addBookCard = document.getElementById('addBookCard');
            const closeFormBtn = document.getElementById('closeFormBtn');
            const hideFormBtn = document.getElementById('hideFormBtn');
            
            if (mobileAddBookBtn && addBookCard && closeFormBtn && hideFormBtn) {
                mobileAddBookBtn.addEventListener('click', function() {
                    addBookCard.classList.add('show');
                    mobileAddBookBtn.style.display = 'none';
                    addBookCard.scrollIntoView({ behavior: 'smooth' });
                });
                
                closeFormBtn.addEventListener('click', function() {
                    addBookCard.classList.remove('show');
                    mobileAddBookBtn.style.display = 'block';
                });
                
                hideFormBtn.addEventListener('click', function() {
                    addBookCard.classList.remove('show');
                    mobileAddBookBtn.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>
