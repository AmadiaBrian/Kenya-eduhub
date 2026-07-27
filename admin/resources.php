<?php
// Session is started by index.php router
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/security_lite.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

// Handle resource actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_resource') {
        $resource_id = $_POST['resource_id'] ?? '';
        if ($resource_id) {
            // Get file info before deleting
            $stmt = $conn->prepare("SELECT filename FROM resources WHERE id = ?");
            $stmt->bind_param("i", $resource_id);
            $stmt->execute();
            $resource = $stmt->get_result()->fetch_assoc();
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
            $stmt->bind_param("i", $resource_id);
            $stmt->execute();
            
            // Delete file from uploads directory
            if ($resource && file_exists('../uploads/' . $resource['filename'])) {
                unlink('../uploads/' . $resource['filename']);
            }
            
            $success = "Resource deleted successfully";
        }
    } elseif ($action === 'toggle_featured') {
        $resource_id = $_POST['resource_id'] ?? '';
        $current_featured = $_POST['current_featured'] ?? 0;
        $new_featured = $current_featured ? 0 : 1;
        
        if ($resource_id) {
            $stmt = $conn->prepare("UPDATE resources SET featured = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_featured, $resource_id);
            $stmt->execute();
            $success = "Resource status updated successfully";
        }
    }
}

// Get all resources with pagination
$page = $_GET['page'] ?? 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$where_clause = '';
$params = [];
$types = '';

$conditions = [];
if (!empty($search)) {
    $conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($subject_filter)) {
    $conditions[] = "subject = ?";
    $params[] = $subject_filter;
    $types .= "s";
}

if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM resources $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_resources = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_resources / $per_page);

// Get resources
$sql = "SELECT * FROM resources $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $all_params = array_merge($params, [$per_page, $offset]);
    $all_types = $types . "ii";
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique subjects for filter
$stmt = $conn->prepare("SELECT DISTINCT subject FROM resources ORDER BY subject");
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics for the page
$total_resources_count = 0;
$total_downloads = 0;
$featured_count = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM resources");
    $stmt->execute();
    $total_resources_count = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT SUM(downloads) as total FROM resources");
    $stmt->execute();
    $total_downloads = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM resources WHERE featured = 1");
    $stmt->execute();
    $featured_count = $stmt->get_result()->fetch_assoc()['total'];
} catch (Exception $e) {
    // Keep defaults if queries fail
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources Management - Kenya EduHub</title>
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
            font-size: 14px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e8eaed;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: 400;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            color: var(--secondary-color);
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

        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
        }
        
        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        td {
            padding: 12px 15px;
            font-size: 13px;
            border: 1px solid #000;
            color: #000;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 8px 12px;
            width: 300px;
        }
        
        .search-box input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 14px;
        }
        
        .search-box i {
            color: #5f6368;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 4px;
            text-decoration: none;
            color: #202124;
            background: white;
        }
        
        .pagination a:hover {
            background: #f1f3f4;
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            color: #2e7d32;
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
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                <span>Main</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link active" href="resources">
                    <i class="fas fa-book"></i> Resources
                </a>
                <a class="nav-link" href="users">
                    <i class="fas fa-users"></i> Users
                </a>
                <a class="nav-link" href="schools">
                    <i class="fas fa-school"></i> Schools
                </a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                <span>Management</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a class="nav-link" href="logs">
                    <i class="fas fa-history"></i> Activity Logs
                </a>
                <a class="nav-link" href="settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                <span>Account</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../../dashboard">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a class="nav-link" href="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">Resources Management</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_resources_count; ?></h3>
                <p>Total Resources</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_downloads; ?></h3>
                <p>Total Downloads</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $featured_count; ?></h3>
                <p>Featured Resources</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Resources</h2>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search resources..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <select class="filter-select">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject['subject']); ?>" <?php echo $subject_filter === $subject['subject'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="card-body" style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <?php if (!empty($resources)): ?>
                        <?php foreach ($resources as $resource): ?>
                            <?php 
                            $fileType = strtoupper($resource['type'] ?? 'FILE');
                            $iconClass = 'fa-file';
                            $iconColor = '#5f6368';
                            
                            switch($fileType) {
                                case 'PDF':
                                    $iconClass = 'fa-file-pdf';
                                    $iconColor = '#d32f2f';
                                    break;
                                case 'DOC':
                                    $iconClass = 'fa-file-word';
                                    $iconColor = '#1976d2';
                                    break;
                                case 'PPT':
                                    $iconClass = 'fa-file-powerpoint';
                                    $iconColor = '#f57c00';
                                    break;
                                case 'XLS':
                                    $iconClass = 'fa-file-excel';
                                    $iconColor = '#388e3c';
                                    break;
                                case 'TXT':
                                    $iconClass = 'fa-file-alt';
                                    $iconColor = '#5f6368';
                                    break;
                            }
                            ?>
                            <div style="background: #f8f9fa; border: 1px solid #e8eaed; border-radius: 8px; padding: 20px; transition: box-shadow 0.2s;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                    <h3 style="font-size: 16px; font-weight: 500; color: #202124; flex: 1; margin: 0;"><?php echo htmlspecialchars($resource['title'] ?? 'N/A'); ?></h3>
                                    <i class="fas <?php echo $iconClass; ?>" style="color: <?php echo $iconColor; ?>; font-size: 24px; margin-left: 12px;"></i>
                                </div>
                                <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">
                                    <i class="fas fa-folder" style="color: #FF6B35; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($resource['subject'] ?? 'N/A'); ?>
                                </p>
                                <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">
                                    <i class="fas fa-graduation-cap" style="color: #008000; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($resource['level'] ?? 'N/A'); ?>
                                </p>
                                <p style="font-size: 12px; color: #5f6368; margin-bottom: 12px; line-height: 1.4;">
                                    <?php echo htmlspecialchars($resource['description'] ?? 'No description available'); ?>
                                </p>
                                <p style="font-size: 13px; color: #5f6368; margin-bottom: 12px;">
                                    <i class="fas fa-download" style="color: #1a73e8; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($resource['downloads'] ?? 0); ?> downloads
                                </p>
                                <p style="font-size: 12px; color: #5f6368; margin-bottom: 12px;">
                                    <i class="fas fa-calendar" style="color: #5f6368; margin-right: 8px;"></i>
                                    <?php echo date('M d, Y', strtotime($resource['created_at'])); ?>
                                </p>
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit-resource?id=<?php echo $resource['id']; ?>" class="btn btn-action btn-sm" style="padding: 8px 16px; font-size: 13px; background: #f1f3f4; color: #202124; border: 1px solid #000; text-decoration: none;">Edit</a>
                                    <button type="button" class="btn btn-action btn-sm" style="padding: 8px 16px; font-size: 13px; background: #d13438; color: white; border-color: #d13438;" onclick="deleteResource(<?php echo $resource['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #5f6368;">No resources found</p>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&subject=<?php echo urlencode($subject_filter); ?>">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <a href="#" class="active"><?php echo $i; ?></a>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&subject=<?php echo urlencode($subject_filter); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&subject=<?php echo urlencode($subject_filter); ?>">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background: transparent; color: #5f6368; padding: 2rem; text-align: center; border-top: 1px solid #e8eaed; margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmResourceModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <p id="confirmResourceMessage" style="font-size: 14px; color: #5f6368;"></p>
                    <input type="hidden" id="confirmResourceId">
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmDeleteResource()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }
        
        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }

        function deleteResource(id) {
            // Show custom delete confirmation modal
            const modalElement = document.getElementById('confirmResourceModal');
            const modalMessage = document.getElementById('confirmResourceMessage');
            
            // Update modal content for delete confirmation
            modalMessage.textContent = 'Are you sure you want to delete this resource? This action cannot be undone.';
            document.getElementById('confirmResourceId').value = id;
            
            // Show modal
            if (typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }

        function confirmDeleteResource() {
            const id = document.getElementById('confirmResourceId').value;
            
            // Hide modal
            if (typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmResourceModal'));
                if (modal) {
                    modal.hide();
                }
            }
            
            // Perform delete via API
            fetch('../api/delete_resource.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row from table
                    const row = document.querySelector(`button[onclick="deleteResource(${id})"]`).closest('tr');
                    if (row) {
                        row.remove();
                    }
                    showResultModal(data.message, true);
                } else {
                    showResultModal(data.message || 'Failed to delete resource', false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResultModal('An error occurred while deleting resource', false);
            });
        }

        function showResultModal(message, isSuccess) {
            const modalElement = document.getElementById('confirmResourceModal');
            const modalTitle = modalElement.querySelector('.modal-title');
            const modalMessage = document.getElementById('confirmResourceMessage');
            const modalFooter = modalElement.querySelector('.modal-footer');
            
            // Update modal content
            modalTitle.textContent = isSuccess ? 'Success' : 'Error';
            modalMessage.textContent = message;
            
            // Update footer buttons
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload()" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Close</button>
            `;
            
            // Show modal
            if (typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
    </script>
</body>
</html>
