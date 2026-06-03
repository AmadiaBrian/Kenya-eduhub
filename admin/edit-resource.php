<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
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

        .custom-logo span:first-child {
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
            background: url('../dist/assets/Anjeline-C0XI691E.jpg');
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
                background: url('../dist/assets/Anjeline-C0XI691E.jpg'); 
                background-size: cover;
                background-position: center;
            }
            33% { 
                background: url('../dist/assets/logo2-UFkwg77b.png'); 
                background-size: cover;
                background-position: center;
            }
            66% { 
                background: url('../dist/assets/logo-DRV3mraH.png'); 
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
                <span>KE</span>
                <span>Kenya EduHub Admin</span>
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
                <div style="width: 32px; height: 32px; background: linear-gradient(45deg, #FFD700, #FFA500); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span style="color: white; font-weight: bold; font-size: 14px;">KE</span>
                </div>
                <h3 style="background: linear-gradient(45deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent; margin: 0;">nya EduHub</h3>
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
!-- Professional Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo">
                        <div style="width: 32px; height: 32px; background: linear-gradient(45deg, #FFD700, #FFA500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                            <span style="color: white; font-weight: bold; font-size: 14px;">KE</span>
                        </div>
                        Kenya EduHub
                    </a>
                    <div class="footer-description">
                        East Africa's premier educational platform, providing quality learning resources and collaborative tools for students and educators across Kenya and beyond.
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
                    <h3>Services</h3>
                    <div class="footer-links">
                        <a href="#uploadSection">Resource Library</a>
                        <a href="#resourcesSection">Study Materials</a>
                        <a href="#resourcesSection">Past Papers</a>
                        <a href="profile.php">Account Settings</a>
                    </div>
                </div>
                
                <!-- Platform Column -->
                <div class="footer-column">
                    <h3>Platform</h3>
                    <div class="footer-links">
                        <a href="#resourcesSection">Resources</a>
                        <a href="settings.php">Settings</a>
                        <a href="profile.php">Profile</a>
                        <a href="#uploadSection">Upload</a>
                    </div>
                </div>
                
                <!-- Legal Column -->
                <div class="footer-column">
                    <h3>Legal</h3>
                    <div class="footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Usage Guidelines</a>
                        <a href="../auth/logout.php">Logout</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div>
                    <p>&copy; 2026 Kenya EduHub. All rights reserved.</p>
                    <p>Empowering education across Kenya</p>
                </div>
                <div class="footer-bottom-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Support</a>
                    <a href="#">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.querySelector('.mobile-toggle');
            
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
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
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
</body>
</html>
