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
    $section = trim($_POST['section'] ?? '');
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    $condition = trim($_POST['condition'] ?? 'new');
    $book_price = (float)($_POST['book_price'] ?? 0);
    $cover_image = '';
    
    // Handle file upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['cover_image']['type'], $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        } elseif ($_FILES['cover_image']['size'] > $max_size) {
            $errors[] = 'File size exceeds 5MB limit.';
        } else {
            $upload_dir = __DIR__ . '/uploads/book_covers/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('book_', true) . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $file_path)) {
                $cover_image = 'uploads/book_covers/' . $file_name;
            } else {
                $errors[] = 'Failed to upload cover image.';
            }
        }
    }
    
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($author)) $errors[] = 'Author is required';
    if (empty($total_copies) || $total_copies < 1) $errors[] = 'Total copies must be at least 1';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO books (school_id, isbn, title, author, publisher, publication_year, category, total_copies, available_copies, description, cover_image, book_price, section, shelf_location, `condition`, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([$school_id, $isbn, $title, $author, $publisher, $publication_year, $category, $total_copies, $total_copies, $description, $cover_image, $book_price, $section, $shelf_location, $condition]);
            
            // Log the action
            $book_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'added', ?, 'librarian', ?)");
            $stmt->execute([$book_id, $school_id, $librarian_id, "Added book: $title by $author"]);
            
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
        // Get book details before deletion for logging
        $stmt = $pdo->prepare("SELECT title, author FROM books WHERE id = ? AND school_id = ?");
        $stmt->execute([$book_id, $school_id]);
        $book = $stmt->fetch();
        
        if ($book) {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ? AND school_id = ?");
            $stmt->execute([$book_id, $school_id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'deleted', ?, 'librarian', ?)");
            $stmt->execute([$book_id, $school_id, $librarian_id, "Deleted book: {$book['title']} by {$book['author']}"]);
            
            $success = 'Book deleted successfully!';
        } else {
            $errors[] = 'Book not found';
        }
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

// Handle book edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_book'])) {
    $book_id = $_POST['book_id'] ?? '';
    $isbn = trim($_POST['isbn'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_year = trim($_POST['publication_year'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total_copies = (int)($_POST['total_copies'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    $condition = trim($_POST['condition'] ?? 'new');
    $book_price = (float)($_POST['book_price'] ?? 0);
    $cover_image = $_POST['existing_cover_image'] ?? '';
    
    $errors = [];
    
    // Handle file upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['cover_image']['type'], $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        } elseif ($_FILES['cover_image']['size'] > $max_size) {
            $errors[] = 'File size exceeds 5MB limit.';
        } else {
            $upload_dir = __DIR__ . '/uploads/book_covers/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('book_', true) . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $file_path)) {
                // Delete old cover image if exists
                if ($cover_image && file_exists(__DIR__ . '/' . $cover_image)) {
                    unlink(__DIR__ . '/' . $cover_image);
                }
                $cover_image = 'uploads/book_covers/' . $file_name;
            } else {
                $errors[] = 'Failed to upload cover image.';
            }
        }
    }
    
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($author)) $errors[] = 'Author is required';
    if (empty($total_copies) || $total_copies < 1) $errors[] = 'Total copies must be at least 1';
    
    if (empty($errors)) {
        try {
            // Get current available copies to calculate new available
            $stmt = $pdo->prepare("SELECT total_copies, available_copies FROM books WHERE id = ? AND school_id = ?");
            $stmt->execute([$book_id, $school_id]);
            $current_book = $stmt->fetch();
            
            if ($current_book) {
                $copies_diff = $total_copies - $current_book['total_copies'];
                $new_available = max(0, $current_book['available_copies'] + $copies_diff);
                
                $stmt = $pdo->prepare("UPDATE books SET isbn = ?, title = ?, author = ?, publisher = ?, publication_year = ?, category = ?, total_copies = ?, available_copies = ?, description = ?, cover_image = ?, book_price = ?, section = ?, shelf_location = ?, `condition` = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$isbn, $title, $author, $publisher, $publication_year, $category, $total_copies, $new_available, $description, $cover_image, $book_price, $section, $shelf_location, $condition, $book_id, $school_id]);
                
                // Log the action
                $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'edited', ?, 'librarian', ?)");
                $stmt->execute([$book_id, $school_id, $librarian_id, "Edited book: $title by $author"]);
                
                $success = 'Book updated successfully!';
            } else {
                $errors[] = 'Book not found';
            }
        } catch (PDOException $e) {
            error_log("Failed to update book: " . $e->getMessage());
            $errors[] = 'Failed to update book. Please try again.';
        }
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

// Get categories from database
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
        
        /* Modern File Upload Styles */
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #e8eaed;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .file-upload-wrapper:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }
        
        .file-upload-wrapper.dragover {
            border-color: var(--primary-color);
            background: #e8f0fe;
            transform: scale(1.02);
        }
        
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-content {
            pointer-events: none;
        }
        
        .file-upload-icon {
            font-size: 48px;
            color: #9aa0a6;
            margin-bottom: 16px;
        }
        
        .file-upload-text {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin: 0 0 8px 0;
        }
        
        .file-upload-hint {
            font-size: 13px;
            color: #5f6368;
            margin: 0;
        }
        
        .file-upload-preview {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 12px;
        }
        
        .file-preview-icon {
            font-size: 32px;
            color: var(--primary-color);
        }
        
        .file-preview-name {
            font-size: 14px;
            color: #202124;
            font-weight: 500;
        }
        
        .file-remove-btn {
            background: #fce8e6;
            color: #c5221f;
            border: none;
            border-radius: 25px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .file-remove-btn:hover {
            background: #fad2cf;
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
            <form method="POST" id="addBookForm" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ISBN (Book Number)</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" class="form-control" name="isbn" id="isbnInput">
                            <button type="button" class="btn btn-secondary" onclick="lookupISBN()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <small class="text-muted">Click search to auto-fill book details</small>
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
                        <select class="form-control" name="category">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Total Copies *</label>
                        <input type="number" class="form-control" name="total_copies" required min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Book Price</label>
                        <input type="number" class="form-control" name="book_price" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Section</label>
                        <input type="text" class="form-control" name="section" placeholder="e.g., Fiction, Science, History">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Shelf Location</label>
                        <input type="text" class="form-control" name="shelf_location" placeholder="e.g., A-1, B-3, C-5">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Condition</label>
                        <select class="form-control" name="condition">
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cover Image</label>
                    <div class="file-upload-wrapper" id="coverDropZone">
                        <input type="file" class="form-control" name="cover_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="coverFileInput">
                        <div class="file-upload-content" id="coverUploadContent">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <p class="file-upload-text">Drag and drop cover image here or click to browse</p>
                            <p class="file-upload-hint">Supported formats: JPG, PNG, GIF, WEBP (Max size: 5MB)</p>
                        </div>
                        <div class="file-upload-preview" id="coverPreview" style="display: none;">
                            <img id="coverPreviewImg" src="" alt="Cover Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                            <button type="button" class="file-remove-btn" onclick="removeCoverImage()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
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
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category_name']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="yearFilter" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="">All Years</option>
                </select>
                <select id="copiesFilter" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="">All Availability</option>
                    <option value="available_only">Available Only</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-times"></i> Reset
                </button>
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
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No books found</td>
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
                                    <td><?php echo $book['book_price'] > 0 ? number_format($book['book_price'], 2) : '-'; ?></td>
                                    <td>
                                        <span style="color: <?php echo $book['status'] === 'available' ? '#137333' : '#c5221f'; ?>; font-weight: 500;">
                                            <?php echo ucfirst($book['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="book_details?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button class="btn btn-sm btn-success" onclick="generateQR(<?php echo $book['id']; ?>)">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
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
    
    <!-- Book Details Modal - Google Material Design Style -->
    <div class="modal fade" id="bookDetailsModal" tabindex="-1" aria-labelledby="bookDetailsModalLabel" aria-hidden="true" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" id="bookDetailsModalLabel" style="font-size: 22px; font-weight: 400; color: #202124;">Book Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <div class="row">
                        <div class="col-md-4">
                            <div id="bookCoverDisplay" style="width: 100%; height: 300px; background: #f0f0f0;border: 1px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <span style="color: #999;">No Cover</span>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h4 id="detailTitle" style="margin-bottom: 16px;"></h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%;">ISBN</th>
                                    <td id="detailIsbn"></td>
                                </tr>
                                <tr>
                                    <th>Author</th>
                                    <td id="detailAuthor"></td>
                                </tr>
                                <tr>
                                    <th>Publisher</th>
                                    <td id="detailPublisher"></td>
                                </tr>
                                <tr>
                                    <th>Publication Year</th>
                                    <td id="detailYear"></td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td id="detailCategory"></td>
                                </tr>
                                <tr>
                                    <th>Total Copies</th>
                                    <td id="detailTotalCopies"></td>
                                </tr>
                                <tr>
                                    <th>Available Copies</th>
                                    <td id="detailAvailableCopies"></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="detailStatus"></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td id="detailDescription"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Close</button>
                    <button type="button" class="btn btn-primary" id="editFromDetailsBtn" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Edit Book</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Book Modal - Google Material Design -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog" style="max-width: 700px;">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" id="editBookModalLabel" style="font-size: 22px; font-weight: 400; color: #202124;">Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form method="POST" id="editBookForm" enctype="multipart/form-data">
                        <input type="hidden" name="book_id" id="edit_book_id">
                        <input type="hidden" name="existing_cover_image" id="edit_existing_cover_image">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Title *</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Author *</label>
                                <input type="text" class="form-control" name="author" id="edit_author" required style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">ISBN</label>
                                <input type="text" class="form-control" name="isbn" id="edit_isbn" style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Publisher</label>
                                <input type="text" class="form-control" name="publisher" id="edit_publisher" style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Year</label>
                                <input type="number" class="form-control" name="publication_year" id="edit_publication_year" min="1900" max="2099" style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Category</label>
                                <select class="form-control" name="category" id="edit_category" style="border-radius: 8px; border: 1px solid #dadce0;">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category_name']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Total Copies *</label>
                                <input type="number" class="form-control" name="total_copies" id="edit_total_copies" required min="1" style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Book Price</label>
                                <input type="number" class="form-control" name="book_price" id="edit_book_price" step="0.01" placeholder="0.00" style="border-radius: 8px; border: 1px solid #dadce0;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Condition</label>
                                <select class="form-control" name="condition" id="edit_condition" style="border-radius: 8px; border: 1px solid #dadce0;">
                                    <option value="new">New</option>
                                    <option value="good">Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2" style="border-radius: 8px; border: 1px solid #dadce0;"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Cover Image</label>
                            <input type="file" class="form-control" name="cover_image" id="edit_cover_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="border-radius: 8px; border: 1px solid #dadce0;">
                            <small class="text-muted" style="font-size: 12px;">Leave blank to keep existing image</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="submit" form="editBookForm" name="edit_book" class="btn btn-primary" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Update Book</button>
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
        
        function editBook(book) {
            document.getElementById('edit_book_id').value = book.id;
            document.getElementById('edit_isbn').value = book.isbn || '';
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_publisher').value = book.publisher || '';
            document.getElementById('edit_publication_year').value = book.publication_year || '';
            document.getElementById('edit_category').value = book.category || '';
            document.getElementById('edit_total_copies').value = book.total_copies;
            document.getElementById('edit_book_price').value = book.book_price || '';
            document.getElementById('edit_description').value = book.description || '';
            document.getElementById('edit_condition').value = book.condition || 'new';
            document.getElementById('edit_existing_cover_image').value = book.cover_image || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editBookModal'));
            modal.show();
        }
        
        // Cover Image Upload Functionality
        const coverDropZone = document.getElementById('coverDropZone');
        const coverFileInput = document.getElementById('coverFileInput');
        const coverUploadContent = document.getElementById('coverUploadContent');
        const coverPreview = document.getElementById('coverPreview');
        const coverPreviewImg = document.getElementById('coverPreviewImg');
        
        if (coverDropZone && coverFileInput) {
            coverFileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    showCoverPreview(file);
                }
            });
            
            coverDropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                coverDropZone.classList.add('dragover');
            });
            
            coverDropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                coverDropZone.classList.remove('dragover');
            });
            
            coverDropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                coverDropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    coverFileInput.files = files;
                    showCoverPreview(files[0]);
                }
            });
        }
        
        function showCoverPreview(file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    coverPreviewImg.src = e.target.result;
                    coverUploadContent.style.display = 'none';
                    coverPreview.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeCoverImage() {
            coverFileInput.value = '';
            coverUploadContent.style.display = 'block';
            coverPreview.style.display = 'none';
            coverPreviewImg.src = '';
        }
        
        // Edit Cover Image Upload Functionality
        const editCoverDropZone = document.getElementById('editCoverDropZone');
        const editCoverFileInput = document.getElementById('edit_cover_image');
        const editCoverUploadContent = document.getElementById('editCoverUploadContent');
        const editCoverPreview = document.getElementById('editCoverPreview');
        const editCoverPreviewImg = document.getElementById('editCoverPreviewImg');
        
        if (editCoverDropZone && editCoverFileInput) {
            editCoverFileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    showEditCoverPreview(file);
                }
            });
            
            editCoverDropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                editCoverDropZone.classList.add('dragover');
            });
            
            editCoverDropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                editCoverDropZone.classList.remove('dragover');
            });
            
            editCoverDropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                editCoverDropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    editCoverFileInput.files = files;
                    showEditCoverPreview(files[0]);
                }
            });
        }
        
        function showEditCoverPreview(file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    editCoverPreviewImg.src = e.target.result;
                    editCoverUploadContent.style.display = 'none';
                    editCoverPreview.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeEditCoverImage() {
            editCoverFileInput.value = '';
            editCoverUploadContent.style.display = 'block';
            editCoverPreview.style.display = 'none';
            editCoverPreviewImg.src = '';
        }
        
        function resetEditCoverUpload() {
            if (editCoverFileInput) {
                editCoverFileInput.value = '';
                editCoverUploadContent.style.display = 'block';
                editCoverPreview.style.display = 'none';
                editCoverPreviewImg.src = '';
            }
        }
        
        function viewBookDetails(book) {
            document.getElementById('detailTitle').textContent = book.title;
            document.getElementById('detailIsbn').textContent = book.isbn || '-';
            document.getElementById('detailAuthor').textContent = book.author;
            document.getElementById('detailPublisher').textContent = book.publisher || '-';
            document.getElementById('detailYear').textContent = book.publication_year || '-';
            document.getElementById('detailCategory').textContent = book.category || '-';
            document.getElementById('detailTotalCopies').textContent = book.total_copies;
            document.getElementById('detailAvailableCopies').textContent = book.available_copies;
            document.getElementById('detailStatus').textContent = book.status;
            document.getElementById('detailDescription').textContent = book.description || '-';
            
            // Show cover image
            const coverDisplay = document.getElementById('bookCoverDisplay');
            if (book.cover_image) {
                coverDisplay.innerHTML = '<img src="' + book.cover_image + '" style="width: 100%; height: 100%; object-fit: contain;">';
            } else {
                coverDisplay.innerHTML = '<span style="color: #999;">No Cover</span>';
            }
            
            // Set up edit button
            document.getElementById('editFromDetailsBtn').onclick = function() {
                bootstrap.Modal.getInstance(document.getElementById('bookDetailsModal')).hide();
                editBook(book);
            };
            
            const modal = new bootstrap.Modal(document.getElementById('bookDetailsModal'));
            modal.show();
        }
        
        function lookupISBN() {
            const isbn = document.getElementById('isbnInput').value.trim();
            
            if (!isbn) {
                alert('Please enter an ISBN number');
                return;
            }
            
            const searchBtn = event.target.closest('button');
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('api/lookup_isbn.php?isbn=' + encodeURIComponent(isbn))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const bookData = data.data;
                        
                        // Fill form fields
                        if (bookData.title) {
                            document.querySelector('input[name="title"]').value = bookData.title;
                        }
                        if (bookData.authors && bookData.authors.length > 0) {
                            document.querySelector('input[name="author"]').value = bookData.authors.join(', ');
                        }
                        if (bookData.publisher) {
                            document.querySelector('input[name="publisher"]').value = bookData.publisher;
                        }
                        if (bookData.publish_year) {
                            document.querySelector('input[name="publication_year"]').value = bookData.publish_year;
                        }
                        if (bookData.description) {
                            document.querySelector('textarea[name="description"]').value = bookData.description;
                        }
                        
                        alert('Book details found and filled!');
                    } else {
                        alert(data.message || 'No book found with this ISBN');
                    }
                })
                .catch(error => {
                    console.error('ISBN lookup error:', error);
                    alert('Failed to lookup ISBN. Please try again.');
                })
                .finally(() => {
                    searchBtn.disabled = false;
                    searchBtn.innerHTML = '<i class="fas fa-search"></i>';
                });
        }
        
        function generateQR(bookId) {
            window.open('generate_qr.php?book_id=' + bookId, '_blank', 'width=600,height=700');
        }
        
        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const yearFilter = document.getElementById('yearFilter');
            const copiesFilter = document.getElementById('copiesFilter');
            const table = document.querySelector('.table');
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            // Populate categories
            const categories = new Set();
            rows.forEach(row => {
                const categoryCell = row.querySelector('td:nth-child(5)');
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
            
            // Populate years from publication year data
            const years = new Set();
            rows.forEach(row => {
                // Get publication year from the data attribute or parse from row
                const titleCell = row.querySelector('td:nth-child(2)');
                if (titleCell && titleCell.dataset.year) {
                    years.add(titleCell.dataset.year);
                }
            });
            
            // If no years from data, add common years
            if (years.size === 0) {
                const currentYear = new Date().getFullYear();
                for (let year = currentYear; year >= currentYear - 20; year--) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearFilter.appendChild(option);
                }
            } else {
                years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearFilter.appendChild(option);
                });
            }
            
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const categoryValue = categoryFilter.value;
                const yearValue = yearFilter.value;
                const copiesValue = copiesFilter.value;
                
                rows.forEach(row => {
                    const coverCell = row.querySelector('td:nth-child(1)');
                    const titleCell = row.querySelector('td:nth-child(2)');
                    const authorCell = row.querySelector('td:nth-child(3)');
                    const isbnCell = row.querySelector('td:nth-child(4)');
                    const categoryCell = row.querySelector('td:nth-child(5)');
                    const totalCopiesCell = row.querySelector('td:nth-child(6)');
                    const availableCopiesCell = row.querySelector('td:nth-child(7)');
                    const statusCell = row.querySelector('td:nth-child(8)');
                    
                    const title = titleCell.textContent.toLowerCase();
                    const author = authorCell.textContent.toLowerCase();
                    const isbn = isbnCell.textContent.toLowerCase();
                    const category = categoryCell.textContent.trim();
                    const status = statusCell.textContent.toLowerCase().trim();
                    const totalCopies = parseInt(totalCopiesCell.textContent);
                    const availableCopies = parseInt(availableCopiesCell.textContent);
                    
                    const matchesSearch = title.includes(searchTerm) || 
                                         author.includes(searchTerm) || 
                                         isbn.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;
                    const matchesCategory = !categoryValue || category === categoryValue;
                    const matchesYear = !yearValue; // Would need year data from server
                    const matchesCopies = !copiesValue || 
                                       (copiesValue === 'available_only' && availableCopies > 0) ||
                                       (copiesValue === 'out_of_stock' && availableCopies === 0);
                    
                    if (matchesSearch && matchesStatus && matchesCategory && matchesYear && matchesCopies) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            function resetFilters() {
                searchInput.value = '';
                statusFilter.value = '';
                categoryFilter.value = '';
                yearFilter.value = '';
                copiesFilter.value = '';
                filterTable();
            }
            
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
            categoryFilter.addEventListener('change', filterTable);
            yearFilter.addEventListener('change', filterTable);
            copiesFilter.addEventListener('change', filterTable);
            
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
