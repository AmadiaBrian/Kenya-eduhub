<?php
// Session is started by index.php router
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/security_lite.php';

// Output CSRF token to JavaScript for AJAX requests
$csrf_token = generateCSRFLite();


// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin (you might need to add an 'role' column to users table)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Simple admin check - you can modify this based on your user roles
if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$total_users = 0;
$total_resources = 0;
$total_downloads = 0;
$recent_users = [];
$recent_resources = [];
$resources = [];
$user_resources = [];
$error = '';

// Get admin statistics
try {
    // Total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['total_users'];
    
    // Total resources
    $stmt = $conn->prepare("SELECT COUNT(*) as total_resources FROM resources");
    $stmt->execute();
    $total_resources = $stmt->get_result()->fetch_assoc()['total_resources'];
    
    // Total downloads
    $stmt = $conn->prepare("SELECT SUM(downloads) as total_downloads FROM resources");
    $stmt->execute();
    $total_downloads = $stmt->get_result()->fetch_assoc()['total_downloads'] ?? 0;
    
    // Recent users (ordered by id since created_at doesn't exist)
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Recent resources with uploader information
    $stmt = $conn->prepare("SELECT r.*, u.name, u.email FROM resources r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 9");
    $stmt->execute();
    $recent_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get all resources with uploader information for the Recent Resources section
    $stmt = $conn->prepare("SELECT r.*, u.name, u.email FROM resources r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
    $stmt->execute();
    $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // For admin, user_resources will be the same as recent_resources (showing admin's uploads)
    $user_resources = $recent_resources;
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
    // Keep variables as empty arrays/zero values
    $resources = [];
    $user_resources = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Dashboard - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-orange: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
            --sidebar-width: 256px;
            --header-height: 64px;
            --text-color: #202124;
            --border-color: #e8eaed;
            --form-border-color: #dadce0;
            --card-hover-bg: #f8f9fa;
        }
        
        .dark-mode {
            --bg-color: #1a1a1a;
            --card-bg: #1a1a1a;
            --text-color: #e8eaed;
            --border-color: #2a2a2a;
            --form-border-color: #2a2a2a;
            --card-hover-bg: #252525;
        }
        
        body {
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
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
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            transition: transform 0.3s ease, background 0.3s ease, border-color 0.3s ease;
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
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: var(--card-hover-bg);
        }
        
        .dark-mode .sidebar-title:hover {
            background: rgba(255, 255, 255, 0.05);
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
        
        .dark-mode .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .dark-mode .nav-link.active {
            background: rgba(26, 115, 232, 0.2);
            color: #8ab4f8;
        }
        
        .dark-mode .dark-mode-toggle {
            color: var(--text-color);
        }
        
        .dark-mode .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: 400;
            color: #FF6B35;
            margin-bottom: 8px;
        }
        
        .stat-card p {
            font-size: 14px;
            color: var(--secondary-color);
            margin: 0;
        }
        
        /* Upload form dark mode */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            background: var(--card-bg);
            color: var(--text-color);
            border-color: var(--border-color);
            transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus,
        textarea:focus {
            border-color: #FF6B35;
            outline: none;
        }
        
        #fileUploadArea {
            background: var(--card-bg);
            border-color: var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        #fileUploadArea:hover {
            border-color: #FF6B35;
        }
        
        .alert {
            background: var(--card-bg);
            color: var(--text-color);
            border-color: var(--border-color);
        }
        
        /* Upload section card background */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .card-header h2 {
            color: var(--text-color);
        }
        
        .card-body {
            background: var(--card-bg);
            transition: background 0.3s ease;
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: #FF6B35;
        }
        
        .nav-link.active i {
            color: var(--primary-color);
        }
        
        .dark-mode .card-header h2 {
            color: var(--text-color);
        }
        
        .dark-mode .card-body {
            background: var(--card-bg);
        }
        
        .dark-mode .btn-secondary {
            background: var(--card-hover-bg);
            color: var(--text-color);
            border-color: var(--border-color);
        }
        
        .dark-mode .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .dark-mode .table-responsive table {
            background: var(--card-bg);
            border-color: var(--border-color);
        }
        
        .dark-mode .table-responsive thead {
            background: var(--card-hover-bg);
            border-color: var(--border-color);
        }
        
        .dark-mode .table-responsive th {
            color: var(--text-color);
            border-color: var(--border-color);
        }
        
        .dark-mode .table-responsive td {
            color: var(--text-color);
            border-color: var(--border-color);
        }
        
        .dark-mode .table-responsive tbody tr:hover {
            background: var(--card-hover-bg);
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
            color: var(--text-color);
            margin-bottom: 24px;
        }
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
            transition: background 0.3s ease, border-color 0.3s ease;
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
            color: var(--text-color);
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
        
        /* Dark mode toggle button */
        .dark-mode-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            color: var(--secondary-color);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
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
            
            /* Stack stats cards vertically on mobile */
            
            /* Make resource cards fit better on mobile */
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div {
                padding: 15px !important;
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                transition: background 0.3s ease, border-color 0.3s ease;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div h3 {
                font-size: 14px !important;
                color: var(--text-color);
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div p {
                font-size: 11px !important;
                margin-bottom: 6px !important;
                color: var(--secondary-color);
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div i {
                font-size: 20px !important;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn {
                padding: 6px 12px !important;
                font-size: 12px !important;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download {
                background: var(--card-hover-bg);
                color: var(--text-color);
                border-color: var(--border-color);
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download:hover {
                background: #FF6B35;
                color: white;
                border-color: #FF6B35;
            }
        }
            
            /* Stack resource cards vertically on mobile */
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] {
                grid-template-columns: 1fr !important;
            }
        }
        

        

        
        .card {
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .card-body {
            padding: 24px;
            background: var(--card-bg);
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }
        
        /* Download button specific styling */
        .btn-download {
            background: var(--card-hover-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .btn-download:hover {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.5);
        }
        
        .btn-download:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn-secondary {
            background: var(--card-hover-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: var(--card-hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        thead {
            background: var(--card-hover-bg);
            border-bottom: 2px solid var(--border-color);
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-bottom: 2px solid var(--border-color);
        }
        
        td {
            padding: 12px 15px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        tbody tr:hover {
            background: var(--card-hover-bg);
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
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link active" href="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link" href="schools">
            <i class="fas fa-school"></i> Schools
        </a>
        <a class="nav-link" href="school-accounts">
            <i class="fas fa-wallet"></i> School Accounts
        </a>
        <a class="nav-link" href="users">
            <i class="fas fa-users"></i> Users
        </a>
        <a class="nav-link" href="resources">
            <i class="fas fa-book"></i> Resources
        </a>
        <a class="nav-link" href="transaction-rates">
            <i class="fas fa-percentage"></i> Transaction Rates
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
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Dashboard</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_resources; ?></h3>
                <p>Total Resources</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_downloads; ?></h3>
                <p>Total Downloads</p>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Users</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_users)): ?>
                                <?php foreach ($recent_users as $recent_user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($recent_user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($recent_user['name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($recent_user['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($recent_user['role'] ?? 'user'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Upload Section -->
        <div style="margin-top: 30px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 25px;">
            <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color); margin-bottom: 24px;">Upload Resource</h2>
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Resource Title *</label>
                            <input type="text" name="title" required style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Education Level *</label>
                            <select name="level" required style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                                <option value="">Select Level</option>
                                <option value="Primary">Primary School</option>
                                <option value="Secondary">Secondary School</option>
                                <option value="College">College</option>
                                <option value="University">University</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Subject *</label>
                            <input type="text" name="subject" required placeholder="e.g., Mathematics, English, Science" style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">File Type *</label>
                            <select name="type" required style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                                <option value="">Select File Type</option>
                                <option value="PDF">PDF Document</option>
                                <option value="DOC">Word Document (.doc/.docx)</option>
                                <option value="PPT">PowerPoint (.ppt/.pptx)</option>
                                <option value="XLS">Excel Spreadsheet (.xls/.xlsx)</option>
                                <option value="TXT">Text File (.txt)</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Description *</label>
                        <textarea name="description" rows="3" required placeholder="Brief description of the resource..." style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; resize: vertical; background: var(--card-bg); color: var(--text-color);"></textarea>
                    </div>
                    <div style="margin-top: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">File *</label>
                        <div id="fileUploadArea" style="position: relative; border: 2px dashed var(--border-color); border-radius: 25px; padding: 40px 20px; text-align: center; background: var(--card-bg); cursor: pointer; transition: all 0.2s;">
                            <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt" required style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                            <div id="fileUploadLabel">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                                <span style="display: block; color: var(--text-color); font-weight: 500;">Click to browse or drag and drop</span>
                                <small style="color: var(--secondary-color);">PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 24px; display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Upload Resource
                        </button>
                        <button type="reset" class="btn btn-secondary" style="background: var(--card-hover-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
                <div id="uploadMessage" style="margin-top: 16px; padding: 16px; border-radius: 8px; display: none;"></div>
        </div>
        
        <!-- Recent Resources -->
        <div style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color);">Recent Resources</h2>
                <a href="resources" class="btn btn-primary">View All</a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <?php if (!empty($recent_resources)): ?>
                        <?php foreach ($recent_resources as $resource): ?>
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
                            <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; transition: box-shadow 0.2s;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                    <h3 style="font-size: 16px; font-weight: 500; color: var(--text-color); flex: 1; margin: 0;"><?php echo htmlspecialchars($resource['title'] ?? 'N/A'); ?></h3>
                                    <i class="fas <?php echo $iconClass; ?>" style="color: <?php echo $iconColor; ?>; font-size: 24px; margin-left: 12px;"></i>
                                </div>
                                <?php if (!empty($resource['subject'])): ?>
                                <p style="font-size: 13px; color: var(--secondary-color); margin-bottom: 8px;">
                                    <i class="fas fa-folder" style="color: #FF6B35; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($resource['subject']); ?>
                                </p>
                                <?php endif; ?>
                                <p style="font-size: 13px; color: var(--secondary-color); margin-bottom: 8px;">
                                    <i class="fas fa-user" style="color: #008000; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($resource['name'] ?? 'Unknown'); ?>
                                </p>
                                <p style="font-size: 12px; color: var(--secondary-color); margin-bottom: 12px; line-height: 1.4;">
                                    <?php echo htmlspecialchars($resource['description'] ?? 'No description available'); ?>
                                </p>
                                <p style="font-size: 13px; color: var(--secondary-color); margin-bottom: 12px;">
                                    <i class="fas fa-download" style="color: #1a73e8; margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($resource['downloads'] ?? 0); ?> downloads
                                </p>
                                <div style="display: flex; gap: 8px;">
                                    <a href="#" onclick="downloadResource(<?php echo $resource['id']; ?>, this)" class="btn btn-download view-button">Download</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--secondary-color);">No resources found</p>
                    <?php endif; ?>
                </div>
    </main>

    <!-- Footer -->
    <footer style="background: transparent; color: var(--secondary-color); padding: 2rem; text-align: center; border-top: 1px solid var(--border-color); margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: var(--secondary-color);">. All rights reserved.</span>
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const toggleBtn = document.querySelector('.dark-mode-toggle i');
            
            if (document.body.classList.contains('dark-mode')) {
                toggleBtn.classList.remove('fa-moon');
                toggleBtn.classList.add('fa-sun');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                toggleBtn.classList.remove('fa-sun');
                toggleBtn.classList.add('fa-moon');
                localStorage.setItem('darkMode', 'disabled');
            }
        }
        
        // Check for saved dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedDarkMode = localStorage.getItem('darkMode');
            if (savedDarkMode === 'enabled') {
                document.body.classList.add('dark-mode');
                const toggleBtn = document.querySelector('.dark-mode-toggle i');
                if (toggleBtn) {
                    toggleBtn.classList.remove('fa-moon');
                    toggleBtn.classList.add('fa-sun');
                }
            }
        });
        
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

        // Upload Form Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');
            if (uploadForm) {
                const fileInput = document.getElementById('file');
                const fileUploadArea = document.getElementById('fileUploadArea');
                const fileUploadLabel = document.getElementById('fileUploadLabel');
                const uploadBtn = document.getElementById('uploadBtn');
                const uploadMessage = document.getElementById('uploadMessage');

                // Handle file selection
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        fileUploadArea.classList.add('has-file');
                        fileUploadArea.style.borderColor = '#107c10';
                        fileUploadArea.style.background = 'rgba(16, 124, 16, 0.05)';
                        fileUploadLabel.innerHTML = `
                            <i class="fas fa-file" style="font-size: 48px; color: #107c10; margin-bottom: 16px;"></i>
                            <span style="display: block; color: var(--text-color); font-weight: 500;">${file.name}</span>
                            <small style="color: var(--secondary-color);">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                        `;
                    } else {
                        fileUploadArea.classList.remove('has-file');
                        fileUploadArea.style.borderColor = 'var(--border-color)';
                        fileUploadArea.style.background = 'var(--card-bg)';
                        fileUploadLabel.innerHTML = `
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                            <span style="display: block; color: var(--text-color); font-weight: 500;">Click to browse or drag and drop</span>
                            <small style="color: var(--secondary-color);">PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                        `;
                    }
                });

                // Handle drag and drop
                fileUploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    fileUploadArea.style.borderColor = '#107c10';
                    fileUploadArea.style.background = 'rgba(16, 124, 16, 0.05)';
                });

                fileUploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    if (!fileInput.files[0]) {
                        fileUploadArea.style.borderColor = 'var(--border-color)';
                        fileUploadArea.style.background = 'var(--card-bg)';
                    }
                });

                fileUploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
            }
        });

        // Upload Form Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');
            const fileInput = document.getElementById('file');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileUploadLabel = document.getElementById('fileUploadLabel');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadMessage = document.getElementById('uploadMessage');

            // Handle file selection
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileUploadArea.classList.add('has-file');
                    fileUploadArea.style.borderColor = '#107c10';
                    fileUploadArea.style.background = 'rgba(16, 124, 16, 0.05)';
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-file" style="font-size: 48px; color: #107c10; margin-bottom: 16px;"></i>
                        <span style="display: block; color: #202124; font-weight: 500;">${file.name}</span>
                        <small style="color: #5f6368;">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    `;
                } else {
                    fileUploadArea.classList.remove('has-file');
                    fileUploadArea.style.borderColor = '#e8eaed';
                    fileUploadArea.style.background = '#f8f9fa';
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                        <span style="display: block; color: #202124; font-weight: 500;">Click to browse or drag and drop</span>
                        <small style="color: #5f6368;">PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                    `;
                }
            });

            // Handle form submission
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!uploadForm.checkValidity()) {
                    uploadForm.reportValidity();
                    return;
                }

                const file = fileInput.files[0];
                if (!file) {
                    uploadMessage.style.display = 'block';
                    uploadMessage.style.background = 'rgba(196, 43, 28, 0.1)';
                    uploadMessage.style.border = 'none';
                    uploadMessage.style.color = '#d13438';
                    uploadMessage.textContent = 'Please select a file to upload.';
                    return;
                }

                // Check file size (50MB max)
                if (file.size > 50 * 1024 * 1024) {
                    uploadMessage.style.display = 'block';
                    uploadMessage.style.background = 'rgba(196, 43, 28, 0.1)';
                    uploadMessage.style.border = 'none';
                    uploadMessage.style.color = '#d13438';
                    uploadMessage.textContent = 'File size exceeds 50MB limit.';
                    return;
                }

                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                uploadMessage.style.display = 'none';

                // Create FormData
                const formData = new FormData(uploadForm);

                // Use fresh CSRF token from server
                if (window.currentCSRFToken) {
                    formData.set('csrf_token', window.currentCSRFToken);
                }

                // AJAX upload
                console.log('Starting upload...');
                fetch('../api/upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Upload response received:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Upload response data:', data);
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Resource';
                    uploadMessage.style.display = 'block';

                    if (data.success) {
                        uploadMessage.style.background = 'rgba(16, 124, 16, 0.1)';
                        uploadMessage.style.border = 'none';
                        uploadMessage.style.color = '#107c10';
                        uploadMessage.textContent = 'Resource uploaded successfully!';

                        // Reset form
                        uploadForm.reset();
                        fileUploadArea.classList.remove('has-file');
                        fileUploadArea.style.borderColor = '#e8eaed';
                        fileUploadArea.style.background = '#f8f9fa';
                        fileUploadLabel.innerHTML = `
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                            <span style="display: block; color: #202124; font-weight: 500;">Click to browse or drag and drop</span>
                            <small style="color: #5f6368;">PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                        `;

                        // Refresh page after 2 seconds to show new resource
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        uploadMessage.style.background = 'rgba(196, 43, 28, 0.1)';
                        uploadMessage.style.border = 'none';
                        uploadMessage.style.color = '#d13438';
                        uploadMessage.textContent = data.message || 'Upload failed. Please try again.';
                        console.error('=== UPLOAD FAILED ===');
                        console.error('Error message:', data.message);
                        console.error('Full response:', data);
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Resource';
                    uploadMessage.style.display = 'block';
                    uploadMessage.style.background = 'rgba(196, 43, 28, 0.1)';
                    uploadMessage.style.border = 'none';
                    uploadMessage.style.color = '#d13438';
                    uploadMessage.textContent = 'Upload failed. Please try again.';
                });
            });

            // Handle form reset
            uploadForm.addEventListener('reset', function() {
                fileUploadArea.classList.remove('has-file');
                fileUploadArea.style.borderColor = '#e8eaed';
                fileUploadArea.style.background = '#f8f9fa';
                fileUploadLabel.innerHTML = `
                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                    <span style="display: block; color: #202124; font-weight: 500;">Click to browse or drag and drop</span>
                    <small style="color: #5f6368;">PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                `;
                uploadMessage.style.display = 'none';
            });
        });

        // Download Resource Function
        function downloadResource(resourceId, button, isMyUpload = false) {
            // Prevent duplicate clicks - disable button immediately
            if (button.disabled || button.classList.contains('btn-loading')) {
                return;
            }
            
            // Add loading state
            button.classList.add('btn-loading');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-download"></i> Downloading...';

            // Use the proper API download endpoint
            const downloadUrl = `../api/download.php?id=${resourceId}&download=true`;
            
            // Trigger download directly
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Reset button after a short delay
            setTimeout(() => {
                button.classList.remove('btn-loading');
                button.innerHTML = '<i class="fas fa-download"></i> Download';
                button.disabled = false;
            }, 2000);
        }
    </script>
</body>
</html>
