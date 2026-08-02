<?php
// Book Categories Management Page
// Authentication is handled by index.php router
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    if (empty($category_name)) $errors[] = 'Category name is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO book_categories (school_id, category_name, description, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$school_id, $category_name, $description]);
            $success = 'Category added successfully!';
        } catch (PDOException $e) {
            error_log("Failed to add category: " . $e->getMessage());
            $errors[] = 'Failed to add category. Please try again.';
        }
    }
}

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'] ?? '';
    
    try {
        // Check if category is being used by any books
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category = (SELECT category_name FROM book_categories WHERE id = ?) AND school_id = ?");
        $stmt->execute([$category_id, $school_id]);
        $book_count = $stmt->fetchColumn();
        
        if ($book_count > 0) {
            $errors[] = 'Cannot delete category - it is being used by ' . $book_count . ' book(s)';
        } else {
            $stmt = $pdo->prepare("DELETE FROM book_categories WHERE id = ? AND school_id = ?");
            $stmt->execute([$category_id, $school_id]);
            $success = 'Category deleted successfully!';
        }
    } catch (PDOException $e) {
        error_log("Failed to delete category: " . $e->getMessage());
        $errors[] = 'Failed to delete category. Please try again.';
    }
}

// Handle category status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $category_id = $_POST['category_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE book_categories SET status = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$status, $category_id, $school_id]);
        $success = 'Category status updated successfully!';
    } catch (PDOException $e) {
        error_log("Failed to update category status: " . $e->getMessage());
        $errors[] = 'Failed to update status. Please try again.';
    }
}

// Get categories
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM book_categories WHERE school_id = ? ORDER BY category_name ASC");
    $stmt->execute([$school_id]);
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
    <title>Book Categories - <?php echo htmlspecialchars($librarian_name); ?></title>
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
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
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
            <a href="categories" class="nav-link active">
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
            <a href="reservations" class="nav-link">
                <i class="fas fa-bookmark"></i>
                <span>Reservations</span>
            </a>
            <a href="fines" class="nav-link">
                <i class="fas fa-money-bill-wave"></i>
                <span>Fines</span>
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
        
        <!-- Add Category -->
        <div class="card">
            <h2 class="card-title">Add New Category</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description">
                    </div>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Add Category
                </button>
            </form>
        </div>
        
        <!-- Categories List -->
        <div class="card">
            <h2 class="card-title">Book Categories</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No categories found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
                                    <td>
                                        <span style="color: <?php echo $category['status'] === 'active' ? '#137333' : '#c5221f'; ?>; font-weight: 500;">
                                            <?php echo ucfirst($category['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $category['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-secondary">
                                                <?php echo $category['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
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
    </script>
</body>
</html>
