<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // For now, we'll just show a success message
    // In a real implementation, you would store settings in a separate settings table
    // or add the necessary columns to the users table
    $success = "Settings saved successfully! (Note: Database columns need to be added for persistent storage)";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Custom Header Styles */
    .custom-header {
        background: #000000;
        padding: 15px 20px;
        padding-left: 240px;
        border-bottom: 3px solid #FFD700;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
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
        color: #0078D4;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 120, 212, 0.3);
        border-color: rgba(0, 120, 212, 0.4);
    }

    /* Mobile Header */
    @media (max-width: 768px) {
        .custom-header {
            padding-left: 20px;
        }
        
        .custom-header-content {
            flex-direction: column;
            gap: 20px;
        }
        
        .custom-nav {
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }
        
        .custom-nav a {
            padding: 8px 14px;
            font-size: 14px;
        }
    }

    /* Professional Hero Section */
    .hero-section {
        background: #1a1a1a;
        backdrop-filter: blur(15px) saturate(1.2);
        border: 1px solid #333333;
        border-radius: 12px;
        padding: 40px 32px;
        margin-bottom: 32px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('../assets/images/Anjeline-C0XI691E.jpg');
        background-size: cover;
        background-position: center;
        opacity: 0.6;
        animation: imageCycle 12s infinite ease-in-out;
        transition: all 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        filter: brightness(1.1) contrast(1.2);
    }

    @keyframes imageCycle {
        0%, 100% { 
            background: url('../assets/images/Anjeline-C0XI691E.jpg');
            background-position: center;
            backdrop-filter: blur(8px);
        }
        33% { 
            background: url('../assets/images/logo2-UFkwg77b.png');
            background-position: center;
            backdrop-filter: blur(10px);
        }
        66% { 
            background: url('../assets/images/logo-DRV3mraH.png');
            background-position: center;
            backdrop-filter: blur(12px);
        }
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
        color: #ffffff;
        box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        flex-shrink: 0;
        backdrop-filter: blur(12px) saturate(1.2);
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.5);
        position: relative;
        z-index: 3;
    }

    .hero-text {
        flex: 1;
        backdrop-filter: blur(8px) saturate(1.1);
        background: rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        padding: 16px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        position: relative;
        z-index: 2;
    }

    .hero-text h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0,0,0,0.8);
        overflow: hidden;
        border-right: 3px solid #ffffff;
        white-space: nowrap;
        animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
    }

    .hero-text p {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 16px;
        line-height: 1.5;
        color: #cccccc;
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
        padding: 8px 16px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #003366;
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        position: relative;
        z-index: 2;
    }

    /* Typewriter Effect */
    @keyframes typing {
        from { width: 0 }
        to { width: 100% }
    }

    @keyframes blink-caret {
        from, to { border-color: transparent }
        50% { border-color: #ffffff; }
    }

    /* Mobile Hero Section */
    @media (max-width: 768px) {
        .hero-section {
            padding: 24px 20px;
            margin-bottom: 24px;
        }

        .hero-content {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }

        .hero-avatar {
            width: 60px;
            height: 60px;
            font-size: 20px;
        }

        .hero-text h1 {
            font-size: 24px;
        }

        .hero-text p {
            font-size: 14px;
            margin-bottom: 12px;
        }

        .hero-stats {
            justify-content: center;
        }
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
        display: none;
        position: fixed;
        top: 16px;
        left: 16px;
        z-index: 1001;
        background: transparent;
        border: none;
        padding: 12px;
        cursor: pointer;
        width: 48px;
        height: 48px;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 4px;
    }

    .mobile-menu-toggle span {
        display: block;
        width: 100%;
        height: 4px;
        background: #ffffff;
        border-radius: 3px;
        transition: all 0.3s ease;
        margin: 0;
    }

    .mobile-menu-toggle:hover span:nth-child(1) {
        transform: translateY(-1px);
    }

    .mobile-menu-toggle:hover span:nth-child(3) {
        transform: translateY(1px);
    }

    /* Show hamburger only on mobile */
    @media (max-width: 768px) {
        body .mobile-menu-toggle {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
    }

        :root {
            --ms-primary: #0078d4;
            --ms-primary-dark: #106ebe;
            --ms-secondary: #6264a7;
            --ms-success: #107c10;
            --ms-warning: #ff8c00;
            --ms-error: #d83b01;
            --ms-neutral-light: #f3f2f1;
            --ms-neutral: #edebe9;
            --ms-neutral-dark: #605e5c;
            --ms-text-primary: #323130;
            --ms-text-secondary: #605e5c;
            --ms-border: #d2d0ce;
            --ms-shadow-light: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            --ms-shadow-medium: 0 6.4px 14.4px rgba(0, 0, 0, 0.132), 0 1.2px 3.6px rgba(0, 0, 0, 0.108);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000000;
            color: #ffffff;
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 220px;
            height: 100vh;
            background: #1a1a1a;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid #333333;
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
            color: #cccccc;
            font-size: 12px;
        }

        .sidebar-menu {
            padding: 16px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
        }

        .menu-item:hover {
            background: #333333;
            color: #0078D4;
        }

        .menu-item.active {
            background: rgba(0, 120, 212, 0.1);
            color: #0078D4;
            border-right: 3px solid #0078D4;
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
            background: #000000;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #1a1a1a;
            border-radius: 4px;
            padding: 24px 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #333333;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .header p {
            color: #cccccc;
            font-size: 14px;
            font-weight: 400;
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
            background: linear-gradient(45deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Card Styles */
        .card {
            background: #1a1a1a;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 24px;
            border: 1px solid #333333;
        }

        .card-header {
            padding: 24px 32px;
            border-bottom: 1px solid #333333;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
        }

        .card-body {
            padding: 32px;
        }

        /* Settings Form */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 32px;
        }

        .settings-section {
            border: 1px solid #333333;
            border-radius: 4px;
            padding: 24px;
            background: #1a1a1a;
        }

        .settings-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-section h3 i {
            color: #FFD700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #1a1a1a;
            color: #ffffff;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        /* Toggle Switch */
        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .toggle-label {
            font-weight: 500;
            color: #ffffff;
            font-size: 14px;
        }

        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            background: #333333;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            background: white;
            border-radius: 10px;
            transition: transform 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .toggle-switch input:checked + .toggle-slider {
            transform: translateX(24px);
        }

        .toggle-switch input:checked ~ .toggle-switch {
            background: var(--ms-success);
        }

        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
        }

        .btn-primary:hover {
            background: #333333;
            border-color: #ffffff;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
        }

        .btn-outline {
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
        }

        .btn-outline:hover {
            background: #333333;
            border-color: #ffffff;
        }

        .btn-danger {
            background: var(--ms-error);
            color: white;
        }

        .btn-danger:hover {
            background: #b32b01;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 24px;
            border-top: 1px solid #333333;
            margin-top: 24px;
        }

        /* Alert Styles */
        .alert {
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: rgba(16, 124, 16, 0.1);
            border: 1px solid var(--ms-success);
            color: var(--ms-success);
        }

        .alert-error {
            background: rgba(196, 43, 28, 0.1);
            border: 1px solid var(--ms-error);
            color: var(--ms-error);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: transparent;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px;
            cursor: pointer;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
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
                <span class="brand-name"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></span>
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
            <a href="index.php#resourcesSection" class="menu-item">
                <i class="fas fa-book"></i> My Resources
            </a>
            <a href="index.php#uploadSection" class="menu-item">
                <i class="fas fa-upload"></i> Upload Resource
            </a>
                        <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="settings.php" class="menu-item active">
                <i class="fas fa-cog"></i> Settings
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
                    <?php echo strtoupper(substr($user['name'] ?? $user['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="hero-text">
                    <h1>Settings Management</h1>
                    <p>Configure your account preferences and system settings</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-cog"></i>
                            Settings
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header -->
        <header class="header">
            <div>
                <h1>Settings</h1>
                <p class="text-muted mb-0">Manage your application preferences and account settings</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
        </header>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Settings Grid -->
        <div class="settings-grid">
            <!-- Notification Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-bell"></i> Notification Settings</h3>
                
                <div class="toggle-group">
                    <span class="toggle-label">Push Notifications</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notifications">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="toggle-group">
                    <span class="toggle-label">Email Notifications</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="email_notifications">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Appearance Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-palette"></i> Appearance</h3>
                
                <div class="form-group">
                    <label for="theme">Theme</label>
                    <select id="theme" name="theme">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="auto">Auto (System)</option>
                    </select>
                </div>
            </div>

            <!-- Language Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-language"></i> Language & Region</h3>
                
                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language">
                        <option value="en">English</option>
                        <option value="sw">Swahili</option>
                    </select>
                </div>
            </div>

            <!-- Account Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-shield-alt"></i> Account Security</h3>
                
                <div class="form-group">
                    <label>Two-Factor Authentication</label>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="color: var(--ms-text-secondary); font-size: 14px;">Not enabled</span>
                        <button type="button" class="btn btn-outline" style="padding: 8px 16px; font-size: 12px;">
                            <i class="fas fa-plus"></i> Enable
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="color: var(--ms-text-secondary); font-size: 14px;">Last changed: Never</span>
                        <a href="profile.php#password" class="btn btn-outline" style="padding: 8px 16px; font-size: 12px;">
                            <i class="fas fa-key"></i> Change
                        </a>
                    </div>
                </div>
            </div>

            <!-- Data & Privacy -->
            <div class="settings-section">
                <h3><i class="fas fa-database"></i> Data & Privacy</h3>
                
                <div class="form-group">
                    <label>Data Management</label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <button type="button" class="btn btn-outline" style="padding: 8px 16px; font-size: 12px; justify-content: flex-start;">
                            <i class="fas fa-download"></i> Download My Data
                        </button>
                        <button type="button" class="btn btn-danger" style="padding: 8px 16px; font-size: 12px; justify-content: flex-start;">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="card">
            <div class="card-body">
                <form method="POST" id="settingsForm">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    
    <!-- Professional Footer -->
    <footer role="contentinfo">
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
                        <a href="auth/login.php"><span class="text-white">Resource</span> <span class="text-orange">Library</span></a>
                        <a href="auth/login.php"><span class="text-white">Study</span> <span class="text-golden">Materials</span></a>
                        <a href="auth/login.php"><span class="text-orange">Past</span> <span class="text-white">Papers</span></a>
                        <a href="auth/login.php"><span class="text-white">Research</span> <span class="text-golden">Papers</span></a>
                        <a href="auth/login.php"><span class="text-white">Teaching</span> <span class="text-orange">Guides</span></a>
                    </div>
                </div>
                
                <!-- Company Column -->
                <div class="footer-column">
                    <h3><span class="text-orange">Platform</span></h3>
                    <div class="footer-links">
                        <a href="#features"><span class="text-golden">Features</span></a>
                        <a href="#resources"><span class="text-white">Resources</span></a>
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

        // Typewriter effect for hero heading
        document.addEventListener('DOMContentLoaded', function() {
            const heroHeading = document.querySelector('.hero-text h1');
            if (heroHeading) {
                const originalText = heroHeading.textContent;
                heroHeading.textContent = '';
                heroHeading.style.width = '0';
                
                setTimeout(() => {
                    typeWriter(heroHeading, originalText, 0);
                }, 500);
            }
        });

        function typeWriter(element, text, index) {
            if (index < text.length) {
                element.textContent += text.charAt(index);
                element.style.width = 'auto';
                setTimeout(() => {
                    typeWriter(element, text, index + 1);
                }, 50);
            } else {
                // Remove the blinking cursor after typing is complete
                setTimeout(() => {
                    element.style.borderRight = 'none';
                }, 1000);
            }
        }

        // Handle toggle switches
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSwitches = document.querySelectorAll('.toggle-switch input');
            
            toggleSwitches.forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    // Auto-save when toggle changes
                    document.getElementById('settingsForm').submit();
                });
            });
        });

        // Theme preview
        document.getElementById('theme').addEventListener('change', function() {
            const theme = this.value;
            // You could implement live theme preview here
            console.log('Theme changed to:', theme);
        });

        // Language change
        document.getElementById('language').addEventListener('change', function() {
            const language = this.value;
            // You could implement language change logic here
            console.log('Language changed to:', language);
        });
    </script>
    
    <!-- Footer Styles -->
    <style>
        /* Footer */
        footer {
            background: #000000;
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
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
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
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            padding: 8px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .footer-logo:hover {
            color: #0078D4;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 120, 212, 0.3);
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
            color: #0078D4;
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
            width: 40px;
            height: 3px;
            background: linear-gradient(135deg, #0078D4 0%, #106EBE 100%);
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
            color: #0078D4;
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
            background: linear-gradient(135deg, #0078D4 0%, #106EBE 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 120, 212, 0.3);
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

        /* Homepage color accents */
        :root {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
        }

        .text-orange {
            color: var(--primary-orange) !important;
        }

        .text-golden {
            color: var(--primary-gold) !important;
        }

        .text-white {
            color: #ffffff !important;
        }

        .custom-header {
            background: #000000;
            border-bottom-color: var(--primary-gold);
        }

        .custom-logo {
            color: #ffffff;
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
        .hero-text h1 {
            color: #ffffff;
        }

        .header h1::first-letter,
        .hero-text h1::first-letter,
        .card-title::first-letter,
        .settings-section h3::first-letter {
            color: var(--primary-orange);
        }

        .header p,
        .hero-text p,
        .sidebar-header p,
        .form-group label,
        .toggle-label {
            color: #ffffff;
        }

        .card-title,
        .settings-section h3 {
            color: var(--primary-gold);
        }

        .stat-value,
        .hero-stat-number {
            color: var(--primary-gold);
        }

        .stat-label,
        .hero-stat-label {
            color: #ffffff;
        }

        .menu-item i,
        .settings-section h3 i {
            color: var(--primary-orange);
        }

        .menu-item:hover,
        .menu-item.active {
            color: var(--primary-gold);
        }

        /* Homepage footer styling */
        footer {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
            background: #000000;
            color: white;
            padding: 4rem 2rem 2rem;
            margin-top: 4rem;
            position: relative;
            overflow: hidden;
        }

        footer .text-orange {
            color: var(--primary-orange) !important;
        }

        footer .text-golden {
            color: var(--primary-gold) !important;
        }

        footer .text-white {
            color: #ffffff !important;
        }

        @media (min-width: 769px) {
            footer {
                margin-left: 220px;
            }
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

        .footer-contact-item:hover {
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

        .footer-links a:hover {
            color: #667eea;
        }

        .footer-social a {
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .footer-social a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: #808080;
        }

        @media (max-width: 768px) {
            footer {
                margin-left: 0;
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
</body>
</html>
