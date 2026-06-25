<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

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
    header("Location: ../dashboard/index.php");
    exit();
}

// Get resource ID from URL
$resource_id = $_GET['id'] ?? '';
if (empty($resource_id)) {
    header("Location: resources.php");
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Edit Resource - Kenya EduHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Microsoft Fluent Design Colors */
            --ms-primary: #0078d4;
            --ms-primary-dark: #106ebe;
            --ms-primary-light: #deecf9;
            --ms-secondary: #f3f2f1;
            --ms-accent: #0078d4;
            --ms-success: #107c10;
            --ms-warning: #ff8c00;
            --ms-danger: #d13438;
            --ms-neutral-light: #faf9f8;
            --ms-neutral-medium: #e1dfdd;
            --ms-neutral-dark: #323130;
            --ms-text-primary: #323130;
            --ms-text-secondary: #605e5c;
            --ms-text-tertiary: #a19f9d;
            --ms-border: #edebe9;
            --ms-shadow-light: rgba(0, 0, 0, 0.133);
            --ms-shadow-medium: rgba(0, 0, 0, 0.16);
            --ms-shadow-heavy: rgba(0, 0, 0, 0.23);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', sans-serif;
            background: var(--ms-neutral-light);
            color: var(--ms-text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Microsoft-style Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 220px;
            background: var(--ms-secondary);
            border-right: 1px solid var(--ms-border);
            z-index: 1000;
            transition: transform 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--ms-border);
        }

        .sidebar-header h3 {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .sidebar-header p {
            color: var(--ms-text-secondary);
            font-size: 12px;
        }

        .sidebar-menu {
            padding: 16px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: var(--ms-text-primary);
            text-decoration: none;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
            font-weight: 400;
        }

        .menu-item:hover {
            background: var(--ms-neutral-light);
            color: var(--ms-primary);
        }

        .menu-item.active {
            background: rgba(0, 120, 212, 0.1);
            color: var(--ms-primary);
            border-right: 3px solid var(--ms-primary);
        }

        .menu-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 220px;
            padding: 24px;
            background: var(--ms-neutral-light);
            min-height: 100vh;
        }

        /* Custom Header */
        .custom-header {
            background: linear-gradient(135deg, #0066cc 0%, #004d99 100%);
            padding: 15px 20px;
            padding-left: 240px;
            border-bottom: 3px solid #FFD700;
            box-shadow: 0 4px 20px rgba(0, 102, 204, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .custom-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .custom-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: bold;
            font-size: 22px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .custom-logo > span:first-child {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .custom-nav {
            display: flex;
            gap: 25px;
        }

        .custom-nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .custom-nav a:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
            border-color: rgba(255, 215, 0, 0.4);
        }

        .custom-nav a.active {
            background: rgba(0, 120, 212, 0.1);
            color: white;
            border-right: 3px solid var(--ms-primary);
        }

        /* Professional Hero Section */
        .hero-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px) saturate(1.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 40px 32px;
            margin-bottom: 32px;
            color: #003366;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('../assets/images/Anjeline-C0XI691E.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.6;
            animation: imageCycle 12s infinite ease-in-out;
            filter: brightness(1.1) contrast(1.2);
            z-index: 0;
        }

        .hero-content {
            display: flex;
            align-items: center;
            gap: 32px;
            position: relative;
            z-index: 1;
        }

        .hero-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 24px;
            color: #003366;
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
            flex-shrink: 0;
            backdrop-filter: blur(12px) saturate(1.2);
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 3;
            transition: all 0.3s ease;
        }

        .hero-text {
            flex: 1;
            backdrop-filter: blur(8px) saturate(1.1);
            background: rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .hero-text h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #003366;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
            overflow: hidden;
            border-right: 3px solid #003366;
            white-space: nowrap;
            animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
            letter-spacing: -0.5px;
        }

        .hero-text p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 16px;
            line-height: 1.5;
            color: #004d99;
        }

        .hero-stats {
            display: flex;
            align-items: center;
            gap: 16px;
            backdrop-filter: blur(3px);
        }

        .hero-stat {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px) saturate(1.3);
            padding: 10px 18px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #003366;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        /* Image Cycling Animation */
        @keyframes imageCycle {
            0%, 100% { 
                background: url('../assets/images/Anjeline-C0XI691E.jpg'); 
                background-size: cover;
                background-position: center;
            }
            33% { 
                background: url('../assets/images/logo2-UFkwg77b.png'); 
                background-size: cover;
                background-position: center;
            }
            66% { 
                background: url('../assets/images/logo-DRV3mraH.png'); 
                background-size: cover;
                background-position: center;
            }
        }

        /* Typewriter Effect */
        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: #003366; }
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ms-primary), var(--ms-primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 2px 4px var(--ms-shadow-light);
        }

        .user-info div {
            text-align: right;
        }

        .user-info .fw-bold {
            font-size: 14px;
            font-weight: 600;
            color: var(--ms-text-primary);
        }

        .user-info .text-muted {
            font-size: 12px;
            color: var(--ms-text-secondary);
        }

        /* Form Styles */
        .card {
            background: white;
            border-radius: 4px;
            padding: 24px;
            box-shadow: 0 2px 8px var(--ms-shadow-light);
            border: 1px solid var(--ms-border);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--ms-border);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--ms-text-primary);
            letter-spacing: -0.02em;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--ms-text-primary);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 1px solid var(--ms-border);
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--ms-primary);
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .file-upload-area {
            position: relative;
            border: 2px dashed var(--ms-border);
            border-radius: 4px;
            padding: 32px;
            text-align: center;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            cursor: pointer;
            background: var(--ms-neutral-light);
        }

        .file-upload-area:hover {
            border-color: var(--ms-primary);
            background: rgba(0, 120, 212, 0.05);
        }

        .file-upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-label {
            pointer-events: none;
        }

        .file-upload-label i {
            font-size: 48px;
            color: var(--ms-text-secondary);
            margin-bottom: 16px;
            display: block;
        }

        .file-upload-label span {
            display: block;
            color: var(--ms-text-primary);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .file-upload-label small {
            color: var(--ms-text-secondary);
            font-size: 12px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid var(--ms-border);
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            letter-spacing: -0.01em;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--ms-primary);
            color: white;
            border: 1px solid var(--ms-primary);
        }

        .btn-primary:hover {
            background: var(--ms-primary-dark);
            border-color: var(--ms-primary-dark);
            box-shadow: 0 2px 8px var(--ms-shadow-light);
        }

        .btn-outline {
            background: transparent;
            color: var(--ms-primary);
            border: 1px solid var(--ms-primary);
        }

        .btn-outline:hover {
            background: var(--ms-primary-light);
            color: var(--ms-primary-dark);
        }

        .btn-danger {
            background: var(--ms-danger);
            color: white;
            border: 1px solid var(--ms-danger);
        }

        .btn-danger:hover {
            background: #b02e30;
            border-color: #b02e30;
        }

        /* Alert Styles */
        .alert {
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(16, 124, 16, 0.1);
            color: var(--ms-success);
            border-color: rgba(16, 124, 16, 0.3);
        }

        .alert-danger {
            background: rgba(212, 52, 56, 0.1);
            color: var(--ms-danger);
            border-color: rgba(212, 52, 56, 0.3);
        }

        .alert i {
            font-size: 20px;
        }

        /* Mobile Responsive */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1001;
            background: var(--ms-primary);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            box-shadow: 0 2px 8px var(--ms-shadow-light);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-top: 80px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Custom Header -->
    <div class="custom-header">
        <div class="custom-header-content">
            <div class="custom-logo">
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span class="brand-name"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span> <span style="color: var(--primary-gold);">Admin</span></span>
            </div>
            <div class="custom-nav">
                <a href="index.php">Dashboard</a>
                <a href="resources.php" class="active">Resources</a>
                <a href="users.php">Users</a>
                <a href="reports.php">Reports</a>
                <a href="settings.php">Settings</a>
                <a href="logs.php">Logs</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                <span style="font-weight: bold; font-size: 24px;">
                    <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                </span>
            </div>
            <h3 style="margin: 0;"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></h3>
            </div>
            <p>Educational Resources Platform</p>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-dashboard"></i> Dashboard
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i> Users Management
            </a>
            <a href="resources.php" class="menu-item">
                <i class="fas fa-book"></i> Resources Management
            </a>
            <a href="reports.php" class="menu-item">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="logs.php" class="menu-item">
                <i class="fas fa-file-alt"></i> System Logs
            </a>
            <a href="../dashboard/index.php" class="menu-item">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Professional Hero Section -->
        <div class="hero-section fade-in">
            <div class="hero-content">
                <div class="hero-avatar">
                    <i class="fas fa-edit" style="color: #003366; font-size: 32px;"></i>
                </div>
                <div class="hero-text">
                    <h1>Edit Resource</h1>
                    <p>Modify resource information and file details</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-file-alt"></i>
                            Resource ID: <?php echo htmlspecialchars($resource_id); ?>
                        </span>
                        <span class="hero-stat">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Resource Information</h3>
                <a href="resources.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Resources
                </a>
            </div>

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
                    <div class="form-grid">
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
                        
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Brief description of the resource..."><?php echo htmlspecialchars($resource['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="file">Update File (Optional)</label>
                            <div class="file-upload-area">
                                <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt">
                                <div class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to browse or drag and drop</span>
                                    <small>PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                                </div>
                            </div>
                            <?php if ($resource['filename']): ?>
                                <small style="color: var(--ms-text-secondary); margin-top: 8px; display: block;">
                                    Current file: <?php echo htmlspecialchars(basename($resource['filename'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="resources.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Resource
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--ms-warning); margin-bottom: 16px;"></i>
                    <p style="color: var(--ms-text-secondary);">Resource not found or has been deleted.</p>
                    <a href="resources.php" class="btn btn-primary" style="margin-top: 16px;">
                        <i class="fas fa-arrow-left"></i> Back to Resources
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Professional Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo">
                        <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                            <span style="font-weight: bold; font-size: 24px;">
                                <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                            </span>
                        </div>
                        <span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span>
                    </a>
                    <div class="footer-description">
                        <span class="text-white">East Africa's</span> <span class="text-orange">premier</span> <span class="text-white">educational platform, providing quality</span> <span class="text-golden">learning resources</span> <span class="text-white">and collaborative tools for students and educators across</span> <span class="text-orange">Kenya</span> <span class="text-white">and beyond.</span>
                    </div>
                    <div class="footer-contact">
                        <div class="footer-contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+254 717 016 902</span>
                        </div>
                        <div class="footer-contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>otienobrian029@gmail.com</span>
                        </div>
                        <div class="footer-contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Nairobi, Kenya</span>
                        </div>
                    </div>
                </div>
                
                <!-- Services Column -->
                <div class="footer-column">
                    <h3><span class="text-golden">Services</span></h3>
                    <div class="footer-links">
                        <a href="../auth/login.php"><span class="text-white">Resource</span> <span class="text-orange">Library</span></a>
                        <a href="../auth/login.php"><span class="text-white">Study</span> <span class="text-golden">Materials</span></a>
                        <a href="../auth/login.php"><span class="text-orange">Past</span> <span class="text-white">Papers</span></a>
                        <a href="../auth/login.php"><span class="text-white">Research</span> <span class="text-golden">Papers</span></a>
                        <a href="../auth/login.php"><span class="text-white">Teaching</span> <span class="text-orange">Guides</span></a>
                    </div>
                </div>
                
                <!-- Company Column -->
                <div class="footer-column">
                    <h3><span class="text-orange">Platform</span></h3>
                    <div class="footer-links">
                        <a href="../#features"><span class="text-golden">Features</span></a>
                        <a href="../#resources"><span class="text-white">Resources</span></a>
                        <a href="#"><span class="text-white">About</span> <span class="text-orange">Us</span></a>
                        <a href="#"><span class="text-white">Our</span> <span class="text-golden">Team</span></a>
                        <a href="#"><span class="text-orange">Contact</span></a>
                        <p><span class="text-golden">Empowering</span> <span class="text-white">education across</span> <span class="text-orange">Kenya</span></p>
                    </div>
                </div>
                
                <!-- Legal Column -->
                <div class="footer-column">
                    <h3><span class="text-white">Legal</span></h3>
                    <div class="footer-links">
                        <a href="#"><span class="text-white">Privacy</span> <span class="text-golden">Policy</span></a>
                        <a href="#"><span class="text-white">Terms of</span> <span class="text-orange">Service</span></a>
                        <a href="#"><span class="text-white">Usage</span> <span class="text-golden">Guidelines</span></a>
                        <a href="#"><span class="text-white">Copyright</span> <span class="text-orange">Policy</span></a>
                        <a href="#"><span class="text-white">Cookie</span> <span class="text-golden">Policy</span></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div>
                    <p><span class="text-white">&copy; 2026</span> <span class="text-orange">Kenya</span> <span class="text-golden">EduHub</span><span class="text-white">. All rights reserved.</span></p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (!sidebar || !toggle) return;
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Enhanced Typewriter effect for hero heading
        document.addEventListener('DOMContentLoaded', function() {
            const heroHeading = document.querySelector('.hero-text h1');
            if (heroHeading) {
                const originalText = heroHeading.textContent;
                heroHeading.textContent = '';
                heroHeading.style.width = '0';
                heroHeading.style.borderRight = '3px solid #003366';
                heroHeading.style.whiteSpace = 'nowrap';
                heroHeading.style.overflow = 'hidden';
                heroHeading.style.display = 'inline-block';
                
                // Start typing after a short delay
                setTimeout(() => {
                    enhancedTypeWriter(heroHeading, originalText, 0);
                }, 800);
            }
        });

        function enhancedTypeWriter(element, text, index) {
            if (index < text.length) {
                // Add character with random typing speed for more realistic effect
                const typingSpeed = Math.random() * 50 + 30; // Random speed between 30-80ms
                element.textContent += text.charAt(index);
                
                // Calculate width based on characters (rough approximation)
                const charWidth = 14; // Average character width in pixels
                element.style.width = (index + 1) * charWidth + 'px';
                
                setTimeout(() => {
                    enhancedTypeWriter(element, text, index + 1);
                }, typingSpeed);
            } else {
                // Keep blinking cursor for a while after typing is complete
                let blinkCount = 0;
                const blinkInterval = setInterval(() => {
                    element.style.borderRight = blinkCount % 2 === 0 ? '3px solid #003366' : '3px solid transparent';
                    blinkCount++;
                    
                    // Stop blinking after 6 blinks (3 on, 3 off)
                    if (blinkCount >= 6) {
                        clearInterval(blinkInterval);
                        element.style.borderRight = 'none';
                        // Add a subtle fade-in effect to the completed text
                        element.style.transition = 'opacity 0.5s ease';
                        element.style.opacity = '0.9';
                        setTimeout(() => {
                            element.style.opacity = '1';
                        }, 100);
                    }
                }, 500);
            }
        }

        // Add typewriter effect to hero description as well
        document.addEventListener('DOMContentLoaded', function() {
            const heroDescription = document.querySelector('.hero-text p');
            if (heroDescription) {
                const originalText = heroDescription.textContent;
                heroDescription.textContent = '';
                heroDescription.style.opacity = '0';
                
                // Start typing description after heading is complete
                setTimeout(() => {
                    heroDescription.style.opacity = '1';
                    heroDescription.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        typeDescription(heroDescription, originalText, 0);
                    }, 300);
                }, 2500); // Wait for heading to complete
            }
        });

        function typeDescription(element, text, index) {
            if (index < text.length) {
                element.textContent += text.charAt(index);
                setTimeout(() => {
                    typeDescription(element, text, index + 1);
                }, 20); // Faster typing for description
            }
        }

        // Add typewriter effect to stats
        document.addEventListener('DOMContentLoaded', function() {
            const heroStats = document.querySelectorAll('.hero-stat');
            heroStats.forEach((stat, index) => {
                stat.style.opacity = '0';
                stat.style.transform = 'translateY(20px)';
                
                // Animate stats one by one after typing effects
                setTimeout(() => {
                    stat.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    stat.style.opacity = '1';
                    stat.style.transform = 'translateY(0)';
                }, 3500 + (index * 200)); // Staggered animation
            });
        });
    </script>
    <!-- Footer Styles -->
    <style>
        /* Footer */
        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            color: white;
            padding: 4rem 2rem 2rem 242px;
            margin-top: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(74, 105, 189, 0.4), transparent);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 2rem;
        }
        
        .footer-brand {
            grid-column: 1;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            padding: 8px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .footer-logo:hover {
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
        }
        
        .footer-description {
            color: #b0b0b0;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        
        .footer-contact {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .footer-contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #b0b0b0;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-contact-item:hover {
            color: #667eea;
        }
        
        .footer-contact-item i {
            width: 20px;
            text-align: center;
        }
        
        .footer-column h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-links a {
            color: #b0b0b0;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 0;
        }
        
        .footer-links a::before {
            content: '';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 1px;
            background: #667eea;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #667eea;
            padding-left: 10px;
        }
        
        .footer-links a:hover::before {
            opacity: 1;
        }
        
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: #b0b0b0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-social a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85rem;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-bottom-links a {
            color: #808080;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.85rem;
        }
        
        .footer-bottom-links a:hover {
            color: #667eea;
        }

        @media (max-width: 768px) {
            footer {
                padding: 4rem 2rem 2rem;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .footer-brand {
                text-align: center;
            }
            
            .footer-logo {
                justify-content: center;
            }
            
            .footer-contact {
                align-items: center;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .footer-bottom-links {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>



    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (!sidebar || !toggle) return;
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Handle file upload
        const fileInput = document.getElementById('file');
        const fileUploadArea = document.querySelector('.file-upload-area');
        const fileUploadLabel = fileUploadArea.querySelector('.file-upload-label');

        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-file"></i>
                        <span>${file.name}</span>
                        <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    `;
                    fileUploadArea.style.borderColor = 'var(--ms-success)';
                    fileUploadArea.style.background = 'rgba(16, 124, 16, 0.05)';
                }
            });

            // Handle drag and drop
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--ms-primary)';
                this.style.background = 'rgba(0, 120, 212, 0.1)';
            });

            fileUploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--ms-border)';
                this.style.background = 'var(--ms-neutral-light)';
            });

            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--ms-border)';
                this.style.background = 'var(--ms-neutral-light)';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            });
        }
    </script>

    <style>
        /* Dashboard-matched admin branding and responsive footer */
        :root {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
        }

        .text-orange { color: var(--primary-orange) !important; }
        .text-golden { color: var(--primary-gold) !important; }
        .text-white { color: #ffffff !important; }

        body {
            background: #000000 !important;
            color: #ffffff !important;
        }

        .sidebar {
            background: #1a1a1a !important;
            border-right-color: #333333 !important;
        }

        .sidebar-header {
            border-bottom-color: #333333 !important;
        }

        .sidebar-header p,
        .menu-item,
        .user-info .fw-bold,
        .text-muted {
            color: #cccccc !important;
        }

        .menu-item {
            color: #ffffff !important;
        }

        .menu-item:hover,
        .menu-item.active {
            background: #333333 !important;
            border-right-color: var(--primary-gold) !important;
        }

        .main-content {
            background: #000000 !important;
            color: #ffffff !important;
        }

        .custom-header {
            background: #000000;
            border-bottom-color: var(--primary-gold);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .custom-header-content {
            justify-content: flex-start !important;
        }

        .custom-nav {
            display: none !important;
        }

        .mobile-menu-toggle {
            position: fixed !important;
            top: 16px !important;
            left: 16px !important;
            z-index: 1200 !important;
            width: 48px !important;
            height: 48px !important;
            padding: 12px !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            cursor: pointer !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
        }

        .mobile-menu-toggle span {
            display: block !important;
            width: 100% !important;
            height: 4px !important;
            margin: 0 !important;
            background: #ffffff !important;
            border-radius: 3px !important;
            transition: transform 0.3s ease, background-color 0.3s ease !important;
        }

        .mobile-menu-toggle:hover span,
        .mobile-menu-toggle:focus-visible span {
            background: var(--primary-gold) !important;
        }

        .mobile-menu-toggle:focus-visible {
            outline: 2px solid var(--primary-gold) !important;
            outline-offset: 2px !important;
        }

        .custom-logo .brand-name,
        .custom-logo .brand-name span {
            background: transparent;
            width: auto;
            height: auto;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            display: inline;
            font-size: inherit;
            margin: 0;
            padding: 0;
            text-shadow: none;
        }

        .sidebar-header h3 {
            background: transparent !important;
            -webkit-background-clip: border-box !important;
            background-clip: border-box !important;
            -webkit-text-fill-color: currentColor !important;
            color: #ffffff !important;
        }

        .header h1,
        .card-title,
        .section-title,
        .stat-value {
            color: var(--primary-gold) !important;
        }

        .header h1::first-letter,
        .card-title::first-letter,
        .section-title::first-letter {
            color: var(--primary-orange);
        }

        .menu-item i,
        .footer-contact-item i {
            color: var(--primary-orange);
        }

        .menu-item:hover,
        .menu-item.active {
            color: var(--primary-gold) !important;
        }

        .card,
        .file-upload-area {
            background: #1a1a1a !important;
            border-color: #333333 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        }

        .card-header,
        .form-actions {
            border-color: #333333 !important;
        }

        .form-group label,
        .file-upload-label span {
            color: #ffffff !important;
        }

        .file-upload-label small,
        .current-file,
        .current-file small {
            color: #cccccc !important;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background: #000000 !important;
            border-color: #333333 !important;
            color: #ffffff !important;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #888888 !important;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-gold) !important;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.12) !important;
        }

        .file-upload-area:hover {
            border-color: var(--primary-gold) !important;
            background: #202020 !important;
        }

        .btn-primary {
            background: var(--primary-gold) !important;
            color: #000000 !important;
        }

        .btn-primary:hover {
            background: var(--primary-orange) !important;
            color: #ffffff !important;
        }

        .btn-secondary,
        .btn-outline {
            background: #000000 !important;
            border: 1px solid #333333 !important;
            color: #ffffff !important;
        }

        .btn-secondary:hover,
        .btn-outline:hover {
            background: #111111 !important;
            border-color: #444444 !important;
            color: var(--primary-gold) !important;
        }

        .alert {
            background: #000000 !important;
            border-radius: 0 !important;
        }

        .alert-success {
            color: #2ecc71 !important;
            border-color: #2ecc71 !important;
        }

        .alert-danger {
            color: #ff4d4d !important;
            border-color: #ff4d4d !important;
        }

        footer {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
            background: #000000;
            color: white;
            padding: 4rem 2rem 2rem;
            margin-top: 4rem;
            margin-left: 220px;
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: auto;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        }

        .footer-grid {
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
            padding-bottom: 3rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: left;
        }

        .footer-logo {
            color: white;
            background: transparent;
            padding: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            margin-bottom: 0;
        }

        .footer-logo:hover {
            color: var(--primary-orange);
            transform: translateY(-2px);
            box-shadow: none;
        }

        .footer-description {
            max-width: 400px;
        }

        .footer-contact-item {
            font-size: 0.9rem;
        }

        .footer-contact-item:hover,
        .footer-links a:hover,
        .footer-bottom-links a:hover {
            color: #667eea;
        }

        .footer-column h3 {
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-column h3::after {
            width: 30px;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .footer-links a {
            font-weight: 400;
            font-size: 0.9rem;
        }

        .footer-links a::before {
            width: 6px;
            height: 6px;
            background: #667eea;
            border-radius: 50%;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: #808080;
        }

        @media (max-width: 768px) {
            .custom-header {
                padding-left: 84px !important;
            }

            .custom-header-content {
                flex-direction: row !important;
                justify-content: flex-start !important;
                gap: 12px !important;
            }

            footer {
                margin-left: 0;
                padding: 4rem 2rem 2rem;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
                text-align: left;
            }

            .footer-brand {
                grid-column: 1 / -1;
                text-align: left;
                padding-left: 0;
            }

            .footer-logo {
                justify-content: flex-start;
            }

            .footer-description {
                display: none;
            }

            .footer-contact {
                align-items: stretch;
                justify-content: flex-start;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
    </style>

    <style>
        /* Dashboard footer parity */
        :root {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
        }

        .text-orange { color: var(--primary-orange) !important; }
        .text-golden { color: var(--primary-gold) !important; }
        .text-white { color: #ffffff !important; }

        footer {
            background: #000000 !important;
            color: white !important;
            padding: 4rem 2rem 2rem !important;
            margin-top: 4rem !important;
            margin-left: 220px !important;
            position: relative !important;
            overflow: hidden !important;
        }

        footer::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: auto !important;
            height: 1px !important;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent) !important;
        }

        .footer-content {
            max-width: 1200px !important;
            margin: 0 auto !important;
            position: relative !important;
            z-index: 1 !important;
        }

        .footer-grid {
            display: grid !important;
            grid-template-columns: 2fr 1fr 1fr 1fr !important;
            gap: 2rem !important;
            margin-bottom: 3rem !important;
            padding-bottom: 3rem !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            text-align: left !important;
        }

        .footer-logo {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            color: white !important;
            text-decoration: none !important;
            font-size: 1.5rem !important;
            font-weight: bold !important;
            background: transparent !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin-bottom: 0 !important;
        }

        .footer-logo:hover {
            color: var(--primary-orange) !important;
            transform: translateY(-2px) !important;
            box-shadow: none !important;
        }

        .footer-description {
            color: #b0b0b0 !important;
            line-height: 1.7 !important;
            margin-bottom: 1.5rem !important;
            font-size: 0.95rem !important;
            max-width: 400px !important;
        }

        .footer-contact {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
        }

        .footer-contact-item {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            color: #b0b0b0 !important;
            text-decoration: none !important;
            font-size: 0.9rem !important;
        }

        .footer-contact-item i {
            width: 20px !important;
            text-align: center !important;
            color: var(--primary-orange) !important;
        }

        .footer-contact-item:hover,
        .footer-links a:hover {
            color: #667eea !important;
        }

        .footer-column h3 {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            margin-bottom: 1.5rem !important;
            color: white !important;
            position: relative !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
        }

        .footer-column h3::after {
            content: '' !important;
            position: absolute !important;
            bottom: -8px !important;
            left: 0 !important;
            width: 30px !important;
            height: 2px !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .footer-links {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
        }

        .footer-links a,
        .footer-links p {
            color: #b0b0b0 !important;
            text-decoration: none !important;
            font-weight: 400 !important;
            font-size: 0.9rem !important;
            margin: 0 !important;
            position: relative !important;
            padding-left: 0 !important;
        }

        .footer-links a::before {
            content: '' !important;
            position: absolute !important;
            left: -15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 6px !important;
            height: 6px !important;
            background: #667eea !important;
            border-radius: 50% !important;
            opacity: 0 !important;
            transition: all 0.3s ease !important;
        }

        .footer-links a:hover {
            padding-left: 10px !important;
        }

        .footer-links a:hover::before {
            opacity: 1 !important;
        }

        .footer-bottom {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding-top: 2rem !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
            color: #808080 !important;
            font-size: 0.85rem !important;
        }

        .footer-bottom p {
            margin: 0 !important;
        }

        @media (max-width: 768px) {
            footer {
                margin-left: 0 !important;
                padding: 4rem 2rem 2rem !important;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 2rem !important;
                text-align: left !important;
            }

            .footer-brand {
                grid-column: 1 / -1 !important;
                text-align: left !important;
                padding-left: 0 !important;
            }

            .footer-logo {
                justify-content: flex-start !important;
            }

            .footer-description {
                display: none !important;
            }

            .footer-contact {
                align-items: stretch !important;
                justify-content: flex-start !important;
            }

            .footer-bottom {
                flex-direction: column !important;
                text-align: center !important;
                gap: 1rem !important;
            }
        }

        @media (max-width: 480px) {
            .footer-grid {
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }
        }
    </style>
</body>
</html>


