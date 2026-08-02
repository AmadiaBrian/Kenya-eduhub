<?php
// Session is started by index.php router
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Output CSRF token variable for use in HTML
$csrf_token = generateCSRFLite();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard");
    exit();
}

// Get resource ID from URL
$resource_id = $_GET['id'] ?? '';
if (empty($resource_id)) {
    header("Location: resources");
    exit();
}

// Fetch resource data
$resource = null;
$error = '';
$success = '';

try {
    $stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();
    
    if (!$resource) {
        $error = "Resource not found";
    }
} catch (Exception $e) {
    $error = "Error fetching resource: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resource) {
    $title = $_POST['title'] ?? '';
    $level = $_POST['level'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Validate required fields
    if (empty($title) || empty($level) || empty($subject) || empty($type)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            // Handle file upload if new file is provided
            $filename = $resource['filename']; // Keep existing filename by default
            
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file'];
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'];
                
                if (!in_array($fileExt, $allowedExts)) {
                    $error = "Invalid file type. Allowed types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT";
                } elseif ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
                    $error = "File size too large. Maximum size: 50MB";
                } else {
                    // Generate unique filename
                    $newFilename = uniqid('', true) . '.' . $fileExt;
                    $uploadPath = '../uploads/' . $newFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // Delete old file
                        if ($resource['filename'] && file_exists('../uploads/' . basename($resource['filename']))) {
                            unlink('../uploads/' . basename($resource['filename']));
                        }
                        $filename = 'api/uploads/' . $newFilename;
                    } else {
                        $error = "Failed to upload file";
                    }
                }
            }
            
            if (empty($error)) {
                // Update resource in database
                $stmt = $conn->prepare("UPDATE resources SET title = ?, level = ?, subject = ?, type = ?, description = ?, filename = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $title, $level, $subject, $type, $description, $filename, $resource_id);
                
                if ($stmt->execute()) {
                    $success = "Resource updated successfully";
                    // Refresh resource data
                    $stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
                    $stmt->bind_param("i", $resource_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $resource = $result->fetch_assoc();
                } else {
                    $error = "Failed to update resource";
                }
            }
        } catch (Exception $e) {
            $error = "Error updating resource: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resource - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
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
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
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
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
        }
        
        .sidebar-title .chevron {
            transition: transform 0.3s ease;
        }
        
        .sidebar-title.collapsed .chevron {
            transform: rotate(-90deg);
        }
        
        .sidebar-links {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-links.collapsed {
            max-height: 0;
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
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
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
            font-size: 18px;
            font-weight: 400;
            color: #202124;
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
            font-size: 14px;
        }
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid #e8eaed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
        }
        
        .card-body {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #202124;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e8eaed;
            border-radius: 25px;
            font-size: 14px;
            transition: border 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
            cursor: pointer;
        }
        
        .btn-action:hover {
            background: #e9ecef;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #e8eaed;
            color: #5f6368;
        }
        
        .btn-outline:hover {
            background: #f1f3f4;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e8eaed;
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
                padding-top: calc(var(--header-height) + 16px);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link" href="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link" href="schools">
            <i class="fas fa-school"></i> Schools
        </a>
        <a class="nav-link active" href="resources">
            <i class="fas fa-book"></i> Resources
        </a>
        <a class="nav-link" href="users">
            <i class="fas fa-users"></i> Users
        </a>
        <a class="nav-link" href="reports">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a class="nav-link" href="logs">
            <i class="fas fa-file-alt"></i> Logs
        </a>
        <a class="nav-link" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">Edit Resource</h1>
        
        <div class="card">
            <div class="card-header">
                <h2>Resource Information</h2>
                <a href="../resources" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Resources
                </a>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($resource): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Resource Title *</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?php echo htmlspecialchars($resource['title']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="level">Education Level *</label>
                            <select id="level" name="level" required>
                                <option value="">Select Level</option>
                                <option value="Primary" <?php echo $resource['level'] === 'Primary' ? 'selected' : ''; ?>>Primary School</option>
                                <option value="Secondary" <?php echo $resource['level'] === 'Secondary' ? 'selected' : ''; ?>>Secondary School</option>
                                <option value="College" <?php echo $resource['level'] === 'College' ? 'selected' : ''; ?>>College</option>
                                <option value="University" <?php echo $resource['level'] === 'University' ? 'selected' : ''; ?>>University</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required 
                                   value="<?php echo htmlspecialchars($resource['subject']); ?>"
                                   placeholder="e.g., Mathematics, English, Science">
                        </div>
                        
                        <div class="form-group">
                            <label for="type">File Type *</label>
                            <select id="type" name="type" required>
                                <option value="">Select File Type</option>
                                <option value="PDF" <?php echo $resource['type'] === 'PDF' ? 'selected' : ''; ?>>PDF Document</option>
                                <option value="DOC" <?php echo $resource['type'] === 'DOC' ? 'selected' : ''; ?>>Word Document (.doc/.docx)</option>
                                <option value="PPT" <?php echo $resource['type'] === 'PPT' ? 'selected' : ''; ?>>PowerPoint (.ppt/.pptx)</option>
                                <option value="XLS" <?php echo $resource['type'] === 'XLS' ? 'selected' : ''; ?>>Excel Spreadsheet (.xls/.xlsx)</option>
                                <option value="TXT" <?php echo $resource['type'] === 'TXT' ? 'selected' : ''; ?>>Text File (.txt)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" required placeholder="Brief description of the resource..."><?php echo htmlspecialchars($resource['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="file">Replace File (Optional)</label>
                            <input type="file" id="file" name="file">
                            <small style="color: #5f6368; margin-top: 8px; display: block;">Current file: <?php echo htmlspecialchars(basename($resource['filename'])); ?></small>
                        </div>
                        
                        <div class="form-actions">
                            <a href="../resources" class="btn btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p style="color: #5f6368;">Resource not found.</p>
                <?php endif; ?>
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
        
        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
    </script>

    <!-- Footer -->
    <footer style="background: transparent; color: #5f6368; padding: 2rem; text-align: center; border-top: 1px solid #e8eaed; margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
