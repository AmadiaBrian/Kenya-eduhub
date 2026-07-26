<?php
// Subjects Management Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Handle category operations
$success = '';
$error = '';

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_code = trim($_POST['category_code']);
    $is_compulsory = isset($_POST['is_compulsory']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO subject_categories (school_id, category_name, category_code, is_compulsory) VALUES (?, ?, ?, ?)");
        $stmt->execute([$school_id, $category_name, $category_code, $is_compulsory]);
        $success = "Category added successfully";
    } catch (PDOException $e) {
        error_log("Failed to add category: " . $e->getMessage());
        $error = "Failed to add category";
    }
}

// Handle category CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['category_file'])) {
    $file = $_FILES['category_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error';
    } else {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            $error = 'Please upload a CSV file. Download the template first.';
        } else {
            $handle = fopen($file['tmp_name'], 'r');

            if ($handle) {
                try {
                    $pdo->beginTransaction();

                    // Skip header row
                    fgetcsv($handle);

                    $row_count = 0;
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        if (count($data) >= 2) {
                            $category_name = trim($data[0]);
                            $category_code = trim($data[1]);
                            $is_compulsory = isset($data[2]) && strtolower(trim($data[2])) === 'yes' ? 1 : 0;

                            if (!empty($category_name) && !empty($category_code)) {
                                $stmt = $pdo->prepare("INSERT INTO subject_categories (school_id, category_name, category_code, is_compulsory) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$school_id, $category_name, $category_code, $is_compulsory]);
                                $row_count++;
                            }
                        }
                    }

                    fclose($handle);
                    $pdo->commit();

                    if ($row_count > 0) {
                        $success = "Successfully imported $row_count subject categories";
                    } else {
                        $error = 'No valid subject categories found in the file. Please check the format.';
                    }

                    // Refresh categories
                    $stmt = $pdo->prepare("SELECT * FROM subject_categories WHERE school_id = ? ORDER BY category_name");
                    $stmt->execute([$school_id]);
                    $categories = $stmt->fetchAll();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    fclose($handle);
                    error_log("Category import error: " . $e->getMessage());
                    $error = 'Failed to import subject categories: ' . $e->getMessage();
                }
            } else {
                $error = 'Failed to open uploaded file';
            }
        }
    }
}

// Handle delete category
if (isset($_GET['delete_category']) && isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM subject_categories WHERE id = ? AND school_id = ?");
        $stmt->execute([$category_id, $school_id]);

        if ($stmt->rowCount() > 0) {
            $success = "Category deleted successfully";
        } else {
            $error = "Failed to delete category";
        }
    } catch (PDOException $e) {
        error_log("Failed to delete category: " . $e->getMessage());
        $error = "Failed to delete category";
    }
}

// Handle edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);
    $category_code = trim($_POST['category_code']);
    $is_compulsory = isset($_POST['is_compulsory']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE subject_categories SET category_name = ?, category_code = ?, is_compulsory = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$category_name, $category_code, $is_compulsory, $category_id, $school_id]);

        if ($stmt->rowCount() > 0) {
            $success = "Category updated successfully";
        } else {
            $error = "Failed to update category";
        }
    } catch (PDOException $e) {
        error_log("Failed to update category: " . $e->getMessage());
        $error = "Failed to update category";
    }
}

// Handle category template download
if (isset($_GET['download_category_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subject_categories_template.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Category Name', 'Category Code', 'Compulsory (Yes/No)']);
    fputcsv($output, ['Compulsory', 'COMP', 'Yes']);
    fputcsv($output, ['Sciences', 'SCI', 'No']);
    fputcsv($output, ['Humanities', 'HUM', 'No']);
    fputcsv($output, ['Technical', 'TECH', 'No']);
    fclose($output);

    exit;
}

// Fetch categories
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM subject_categories WHERE school_id = ? ORDER BY category_name");
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
    <title>Subjects - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
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
        
        .school-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        .card {
            background: var(--bg-color);
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
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
        }
        
        .btn-danger {
            background: #d93025;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b92b20;
        }
        
        .table {
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            width: 100%;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #000;
        }

        .table th {
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px;
            font-weight: bold;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
            background: #f5f5f5;
        }

        .table td {
            padding: 10px;
            border: 1px solid #000;
            color: #000;
            font-size: 12px;
            vertical-align: middle;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }
            
            .header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                padding: 0 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                transform: none !important;
            }
            
            .logo span {
                font-size: 18px;
            }
            
            .menu-btn {
                padding: 8px;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .menu-btn:hover {
                background: rgba(0,0,0,0.04);
            }
            
            .sidebar {
                position: fixed !important;
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
            }
            
            .table {
                min-width: 600px;
                width: 100%;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
            }
            
            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #dadce0;
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            }
            
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e8eaed;
            }
            
            .card-header .btn {
                width: 100%;
            }
            
            .card {
                text-align: center;
            }
            
            .card-header {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 12px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
            .main-content {
                padding: 12px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 12px;
            }
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
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
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-chalkboard"></i> Classes
            </a>
            <a class="nav-link" href="streams">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link active" href="subjects">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="exam-types">
                <i class="fas fa-clipboard-list"></i> Exam Types
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-clipboard-list"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Subjects Management</h1>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Subject Categories Section -->
        <div class="card">
            <h2 class="card-title">Subject Categories</h2>
            <p class="text-muted mb-3">Organize subjects into categories (e.g., Compulsory, Sciences, Humanities) for proper aggregate grading calculations.</p>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Download Template</label>
                    <a href="subjects?download_category_template=1" class="btn btn-outline-primary w-100">
                        <i class="fas fa-download me-2"></i> Download CSV Template
                    </a>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload Completed Template</label>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" class="form-control" name="category_file" accept=".csv" required>
                        <small class="text-muted">Upload the completed CSV file with subject categories</small>
                        <button type="submit" name="upload_categories" class="btn btn-primary mt-2 w-100">
                            <i class="fas fa-upload me-2"></i> Upload Categories
                        </button>
                    </form>
                </div>
            </div>

            <hr class="my-4">

            <h3 class="card-title">Add Single Category</h3>
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="category_name" required placeholder="e.g., Compulsory, Sciences, Humanities">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category Code</label>
                        <input type="text" class="form-control" name="category_code" required placeholder="e.g., COMP, SCI, HUM">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_compulsory" id="isCompulsory">
                        <label class="form-check-label" for="isCompulsory">Mark as Compulsory Category</label>
                    </div>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Add Category
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">Current Categories</h2>

            <?php if (empty($categories)): ?>
                <p class="text-center text-muted">No categories defined yet</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Code</th>
                                <th>Compulsory</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['category_code']); ?></td>
                                    <td><?php echo $category['is_compulsory'] ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>', '<?php echo htmlspecialchars($category['category_code']); ?>', <?php echo $category['is_compulsory']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="subjects?delete_category=1&category_id=<?php echo $category['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this category?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Category Modal -->
        <div class="modal fade" id="editCategoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="edit_category" value="1">
                            <input type="hidden" name="category_id" id="editCategoryId">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" name="category_name" id="editCategoryName" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category Code</label>
                                <input type="text" class="form-control" name="category_code" id="editCategoryCode" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_compulsory" id="editIsCompulsory">
                                    <label class="form-check-label" for="editIsCompulsory">Mark as Compulsory Category</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Statistics -->
        <div id="subjectStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <?php
            $totalSubjects = 0;
            $activeSubjects = 0;
            $inactiveSubjects = 0;
            
            try {
                $stmt = $pdo->prepare("SELECT status FROM subjects WHERE school_id = ?");
                $stmt->execute([$school_id]);
                $subjects = $stmt->fetchAll();
                
                foreach ($subjects as $subject) {
                    $totalSubjects++;
                    if ($subject['status'] === 'active') $activeSubjects++;
                    if ($subject['status'] === 'inactive') $inactiveSubjects++;
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch subject stats: " . $e->getMessage());
            }
            ?>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalSubjects; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Subjects</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $activeSubjects; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Active</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $inactiveSubjects; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Inactive</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>All Subjects</span>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-2"></i> Add Subject
                    </button>
                    <button class="btn btn-success" onclick="exportSubjects()">
                        <i class="fas fa-download me-2"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchSubject" placeholder="Search by subject name or code..." onkeyup="filterSubjects()">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterSubjectStatus" onchange="filterSubjects()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterSubjectCode" onchange="filterSubjects()">
                            <option value="">All Codes</option>
                            <option value="has_code">Has Code</option>
                            <option value="no_code">No Code</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Subject Code</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subjectsTable">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Subject Modal - Google Material Design Style -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addSubjectForm">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Subject Name</label>
                            <input type="text" class="form-control" id="subjectName" required style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Subject Code (Optional)</label>
                            <input type="text" class="form-control" id="subjectCode" placeholder="e.g., MATH, ENG" style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Category</label>
                            <select class="form-control" id="subjectCategory" style="border-radius: 8px; border: 1px solid #dadce0;">
                                <option value="">Select Category (Optional)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?> (<?php echo htmlspecialchars($cat['category_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Status</label>
                            <select class="form-control" id="subjectStatus" style="border-radius: 8px; border: 1px solid #dadce0;">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addSubject()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Add Subject</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Subject Modal - Google Material Design Style -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="editSubjectForm">
                        <input type="hidden" id="editSubjectId">
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="editSubjectName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject Code (Optional)</label>
                            <input type="text" class="form-control" id="editSubjectCode">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" id="editSubjectCategory">
                                <option value="">Select Category (Optional)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?> (<?php echo htmlspecialchars($cat['category_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="editSubjectStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateSubject()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Update Subject</button>
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
            mainContent.classList.toggle('expanded');
        }
        
        // Load subjects
        async function loadSubjects() {
            try {
                const response = await fetch('api/subjects.php');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('subjectsTable');
                    tbody.innerHTML = data.data.map(subject => `
                        <tr>
                            <td>${subject.subject_name}</td>
                            <td>${subject.subject_code || '-'}</td>
                            <td>${subject.category_name || '-'}</td>
                            <td>
                                <span class="badge ${subject.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                    ${subject.status}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editSubject(${subject.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteSubject(${subject.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading subjects:', error);
            }
        }
        
        // Filter subjects
        function filterSubjects() {
            const search = document.getElementById('searchSubject').value.toLowerCase();
            const status = document.getElementById('filterSubjectStatus').value;
            const codeFilter = document.getElementById('filterSubjectCode').value;
            
            const rows = document.querySelectorAll('#subjectsTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const subjectName = cells[0].textContent.toLowerCase();
                const subjectCode = cells[1].textContent.toLowerCase();
                const rowStatus = cells[2].textContent.toLowerCase();
                
                const matchesSearch = subjectName.includes(search) || subjectCode.includes(search);
                const matchesStatus = !status || rowStatus.includes(status);
                
                let matchesCode = true;
                if (codeFilter === 'has_code') {
                    matchesCode = subjectCode !== '-';
                } else if (codeFilter === 'no_code') {
                    matchesCode = subjectCode === '-';
                }
                
                row.style.display = (matchesSearch && matchesStatus && matchesCode) ? '' : 'none';
            });
        }
        
        // Export subjects to CSV
        function exportSubjects() {
            const rows = document.querySelectorAll('#subjectsTable tr');
            let csvContent = 'Subject Name,Subject Code,Status\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const rowData = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent
                ].map(field => {
                    let text = String(field).trim();
                    text = text.replace(/"/g, '""');
                    if (text.includes(',') || text.includes('"')) {
                        text = `"${text}"`;
                    }
                    return text;
                });
                
                csvContent += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            const timestamp = new Date().toISOString().split('T')[0];
            link.setAttribute('href', url);
            link.setAttribute('download', `subjects_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Add subject
        async function addSubject() {
            const subjectData = {
                subject_name: document.getElementById('subjectName').value,
                subject_code: document.getElementById('subjectCode').value || null,
                category_id: document.getElementById('subjectCategory').value || null,
                status: document.getElementById('subjectStatus').value
            };

            try {
                const response = await fetch('api/subjects.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(subjectData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Subject added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addSubjectModal')).hide();
                    document.getElementById('addSubjectForm').reset();
                    loadSubjects();
                } else {
                    alert(data.error || 'Failed to add subject');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // Edit category
        function editCategory(id, name, code, isCompulsory) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editCategoryName').value = name;
            document.getElementById('editCategoryCode').value = code;
            document.getElementById('editIsCompulsory').checked = isCompulsory === 1;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        // Edit subject
        async function editSubject(id) {
            try {
                const response = await fetch('api/subjects.php');
                const data = await response.json();
                if (data.success) {
                    const subject = data.data.find(s => s.id === id);
                    if (subject) {
                        document.getElementById('editSubjectId').value = subject.id;
                        document.getElementById('editSubjectName').value = subject.subject_name;
                        document.getElementById('editSubjectCode').value = subject.subject_code || '';
                        document.getElementById('editSubjectCategory').value = subject.category_id || '';
                        document.getElementById('editSubjectStatus').value = subject.status;
                        new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading subject:', error);
            }
        }

        // Update subject
        async function updateSubject() {
            const subjectData = {
                subject_id: document.getElementById('editSubjectId').value,
                subject_name: document.getElementById('editSubjectName').value,
                subject_code: document.getElementById('editSubjectCode').value || null,
                category_id: document.getElementById('editSubjectCategory').value || null,
                status: document.getElementById('editSubjectStatus').value
            };

            try {
                const response = await fetch('api/subjects.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(subjectData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Subject updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editSubjectModal')).hide();
                    loadSubjects();
                } else {
                    alert(data.error || 'Failed to update subject');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        // Delete subject
        async function deleteSubject(id) {
            if (confirm('Are you sure you want to delete this subject?')) {
                try {
                    const response = await fetch(`api/subjects.php?id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Subject deleted successfully!');
                        loadSubjects();
                    } else {
                        alert(data.error || 'Failed to delete subject');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        // Initialize
        loadSubjects();
    </script>
    <script src="../assets/js/notifications.js"></script>
    
    <!-- Footer -->
    <footer style="background: transparent; color: white; padding: 2rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
