<?php
// Bulk Import/Export Books
// Authentication is handled by index.php router
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Handle CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="books_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['ISBN', 'Title', 'Author', 'Publisher', 'Publication Year', 'Category', 'Total Copies', 'Available Copies', 'Description', 'Section', 'Shelf Location', 'Condition', 'Status']);
    
    // Get books
    try {
        $stmt = $pdo->prepare("SELECT isbn, title, author, publisher, publication_year, category, total_copies, available_copies, description, section, shelf_location, condition, status FROM books WHERE school_id = ? ORDER BY title ASC");
        $stmt->execute([$school_id]);
        $books = $stmt->fetchAll();
        
        foreach ($books as $book) {
            fputcsv($output, [
                $book['isbn'] ?? '',
                $book['title'],
                $book['author'],
                $book['publisher'] ?? '',
                $book['publication_year'] ?? '',
                $book['category'] ?? '',
                $book['total_copies'],
                $book['available_copies'],
                $book['description'] ?? '',
                $book['section'] ?? '',
                $book['shelf_location'] ?? '',
                $book['condition'] ?? 'new',
                $book['status']
            ]);
        }
    } catch (PDOException $e) {
        error_log("Failed to export books: " . $e->getMessage());
    }
    
    fclose($output);
    exit;
}

// Handle CSV import
$errors = [];
$success_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        
        if (($handle = fopen($file, 'r')) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $isbn = trim($data[0] ?? '');
                $title = trim($data[1] ?? '');
                $author = trim($data[2] ?? '');
                $publisher = trim($data[3] ?? '');
                $publication_year = trim($data[4] ?? '');
                $category = trim($data[5] ?? '');
                $total_copies = (int)($data[6] ?? 0);
                $description = trim($data[8] ?? '');
                $section = trim($data[9] ?? '');
                $shelf_location = trim($data[10] ?? '');
                $condition = trim($data[11] ?? 'new');
                
                // Validate required fields
                if (empty($title) || empty($author) || empty($total_copies)) {
                    continue;
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO books (school_id, isbn, title, author, publisher, publication_year, category, total_copies, available_copies, description, section, shelf_location, condition, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
                    $stmt->execute([$school_id, $isbn, $title, $author, $publisher, $publication_year, $category, $total_copies, $total_copies, $description, $section, $shelf_location, $condition]);
                    
                    // Log the action
                    $book_id = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'added', ?, 'librarian', ?)");
                    $stmt->execute([$book_id, $school_id, $librarian_id, "Imported book: $title by $author"]);
                    
                    $success_count++;
                } catch (PDOException $e) {
                    error_log("Failed to import book: " . $e->getMessage());
                    $errors[] = "Failed to import: $title by $author";
                }
            }
            
            fclose($handle);
            
            if ($success_count > 0) {
                $success = "Successfully imported $success_count books!";
            }
        } else {
            $errors[] = 'Failed to open CSV file';
        }
    } else {
        $errors[] = 'Please select a CSV file to import';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Import/Export Books - <?php echo htmlspecialchars($librarian_name); ?></title>
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
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease, margin-left 0.3s ease;
            z-index: 999;
        }
        
        .sidebar.collapsed {
            transform: translateX(-256px);
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
            padding-bottom: 80px;
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
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
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
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 25px;
            font-size: 14px;
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
        
        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
            border: 1px solid #d2e3fc;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                z-index: 9999;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-bottom: 80px;
            }
            
            .header {
                padding: 0 16px;
            }
            
            .logo {
                font-size: 14px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
            }
            
            .upload-section {
                flex-direction: column;
                gap: 12px;
            }
            
            .upload-area {
                padding: 20px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                font-size: 11px;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
            }
            
            .file-list {
                grid-template-columns: 1fr;
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
            <a href="categories" class="nav-link">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
            <a href="import_export" class="nav-link active">
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
        <h1 class="page-title">Import/Export Books</h1>
        
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
        
        <!-- Export Section -->
        <div class="card">
            <h2 class="card-title">Export Books to CSV</h2>
            <p class="text-muted">Export all books to a CSV file for backup or editing.</p>
            <a href="import_export?export=1" class="btn btn-primary">
                <i class="fas fa-download me-2"></i> Export Books
            </a>
        </div>
        
        <!-- Import Section -->
        <div class="card">
            <h2 class="card-title">Import Books from CSV</h2>
            <p class="text-muted">Import books from a CSV file. The CSV should have the following columns: ISBN, Title, Author, Publisher, Publication Year, Category, Total Copies, Available Copies, Description, Section, Shelf Location, Condition, Status</p>
            
            <div class="alert alert-info">
                <strong>CSV Format Example:</strong><br>
                ISBN, Title, Author, Publisher, Publication Year, Category, Total Copies, Available Copies, Description, Section, Shelf Location, Condition, Status
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="importForm">
                <div class="mb-3">
                    <label class="form-label">Select CSV File</label>
                    <div class="file-upload-wrapper" id="dropZone">
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required id="csvFileInput">
                        <div class="file-upload-content" id="fileUploadContent">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <p class="file-upload-text">Drag and drop CSV file here or click to browse</p>
                            <p class="file-upload-hint">Supported format: .csv (Max size: 10MB)</p>
                        </div>
                        <div class="file-upload-preview" id="filePreview" style="display: none;">
                            <i class="fas fa-file-csv file-preview-icon"></i>
                            <span class="file-preview-name" id="fileName"></span>
                            <button type="button" class="file-remove-btn" onclick="removeFile()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="submit" name="import" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Import Books
                </button>
            </form>
        </div>
        
        <!-- Download Template -->
        <div class="card">
            <h2 class="card-title">Download CSV Template</h2>
            <p class="text-muted">Download a blank CSV template to fill in your book data.</p>
            <button class="btn btn-secondary" onclick="downloadTemplate()">
                <i class="fas fa-file-csv me-2"></i> Download Template
            </button>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: #f8f9fa; border-top: 1px solid #e8eaed; padding: 20px 0; z-index: 1000;">
        <div style="text-align: center;">
            <p style="margin: 0; color: #5f6368; font-size: 14px;">
                <span style="color: #FF6B35;">&copy; 2026</span>
                <span style="color: #FF6B35;">Kenya</span>
                <span style="color: #008000;">EduHub</span>
                <span style="color: #5f6368;">. All rights reserved.</span>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                // Mobile: toggle the 'show' class
                sidebar.classList.toggle('show');
            } else {
                // Desktop: toggle the 'collapsed' and 'expanded' classes
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
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
        
        function downloadTemplate() {
            const csvContent = "ISBN,Title,Author,Publisher,Publication Year,Category,Total Copies,Available Copies,Description,Section,Shelf Location,Condition,Status\n";
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'books_template.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // File Upload Functionality
        const dropZone = document.getElementById('dropZone');
        const csvFileInput = document.getElementById('csvFileInput');
        const fileUploadContent = document.getElementById('fileUploadContent');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        
        // Handle file selection
        csvFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                showFilePreview(file);
            }
        });
        
        // Handle drag and drop events
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                csvFileInput.files = files;
                showFilePreview(files[0]);
            }
        });
        
        function showFilePreview(file) {
            fileUploadContent.style.display = 'none';
            filePreview.style.display = 'flex';
            fileName.textContent = file.name;
        }
        
        function removeFile() {
            csvFileInput.value = '';
            fileUploadContent.style.display = 'block';
            filePreview.style.display = 'none';
        }
    </script>
</body>
</html>
