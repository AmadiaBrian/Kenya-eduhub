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

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_site_settings':
                try {
                    // Update site settings (this would normally update a database table)
                    $site_name = $_POST['site_name'] ?? 'Kenya EduHub';
                    $site_description = $_POST['site_description'] ?? '';
                    $admin_email = $_POST['admin_email'] ?? '';
                    $max_file_size = $_POST['max_file_size'] ?? 10;
                    $allowed_extensions = $_POST['allowed_extensions'] ?? 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt';
                    
                    // For demo purposes, we'll just show success
                    $success = "Site settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating site settings: " . $e->getMessage();
                }
                break;
                
            case 'update_system_settings':
                try {
                    $maintenance_mode = $_POST['maintenance_mode'] ?? 'off';
                    $debug_mode = $_POST['debug_mode'] ?? 'off';
                    $session_timeout = $_POST['session_timeout'] ?? 30;
                    $max_login_attempts = $_POST['max_login_attempts'] ?? 5;
                    
                    $success = "System settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating system settings: " . $e->getMessage();
                }
                break;
                
            case 'update_email_settings':
                try {
                    $smtp_host = $_POST['smtp_host'] ?? '';
                    $smtp_port = $_POST['smtp_port'] ?? 587;
                    $smtp_username = $_POST['smtp_username'] ?? '';
                    $smtp_password = $_POST['smtp_password'] ?? '';
                    $email_from = $_POST['email_from'] ?? '';
                    
                    $success = "Email settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating email settings: " . $e->getMessage();
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', sans-serif;
            background: #000000;
            color: #ffffff;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Microsoft-style Sidebar */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 220px;
            background: #1a1a1a;
            border-right: 1px solid #333333;
            z-index: 1000;
            transition: transform 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            overflow-y: auto;
        }

        .admin-sidebar-header {
            padding: 24px;
            border-bottom: 1px solid #333333;
        }

        .admin-sidebar-header h2 {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .admin-sidebar-header p {
            color: #cccccc;
            font-size: 12px;
        }

        .admin-menu {
            padding: 16px 0;
        }

        .admin-menu-item {
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
            font-weight: 400;
        }

        .admin-menu-item:hover {
            background: #333333;
            color: #0078D4;
        }

        .admin-menu-item.active {
            background: rgba(0, 120, 212, 0.1);
            color: #0078D4;
            border-right: 3px solid #0078D4;
        }

        .admin-menu-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        /* Microsoft-style Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 220px;
            background: #1a1a1a;
            border-right: 1px solid #333333;
            z-index: 1000;
            transition: transform 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
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
            font-weight: 400;
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
        .admin-main {
            margin-left: 220px;
            padding: 24px;
            background: #000000;
            min-height: 100vh;
        }

        /* Custom Header */
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
            color: #003366;
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
            color: #003366;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
            overflow: hidden;
            border-right: 3px solid #003366;
            white-space: nowrap;
            animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
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
            50% { border-color: #003366; }
        }

        .admin-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ms-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        /* Settings Cards */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .admin-card {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            border: 1px solid #333333;
            overflow: hidden;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
        }

        .admin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.8), transparent);
            opacity: 0;
            transition: opacity 0.267s ease;
        }

        .admin-card:hover {
            box-shadow: 0 2.8px 5.6px rgba(0, 0, 0, 0.132), 0 0.7px 1.8px rgba(0, 0, 0, 0.108);
            transform: translateY(-2px);
        }

        .admin-card:hover::before {
            opacity: 1;
        }

        .admin-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #333333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #000000;
            position: relative;
        }

        .admin-card-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #333333 20%, #333333 80%, transparent);
        }

        .admin-card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin: 0 0 2px 0;
        }

        .admin-card-header p {
            font-size: 13px;
            color: #cccccc;
            margin: 0;
            font-weight: 400;
        }

        .admin-card-body {
            padding: 24px;
        }

        /* Form Styles */
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: #ffffff;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 14px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            background: #000000;
            color: #ffffff;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Button Styles */
        .admin-btn {
            padding: 8px 16px;
            border: 1px solid #ffffff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #000000;
            color: #ffffff;
            font-family: 'Segoe UI', sans-serif;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .admin-btn:hover {
            background: #333333;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
            transform: translateY(-1px);
        }

        .admin-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-primary {
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-primary:hover {
            background: #333333;
            border-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .admin-btn-success {
            background: #000000;
            color: #ffffff;
            border: 1px solid #107c10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-success:hover {
            background: #0e5a0e;
            border-color: #0e5a0e;
            box-shadow: 0 4px 12px rgba(16, 124, 16, 0.4);
        }

        .admin-btn-danger {
            background: #000000;
            color: #ffffff;
            border: 1px solid #d13438;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-danger:hover {
            background: #333333;
            border-color: #d13438;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--ms-border);
        }

        /* Alert Styles */
        .admin-alert {
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-alert.success {
            background: rgba(56, 142, 60, 0.1);
            border-color: var(--ms-success);
            color: #107c10;
        }

        .admin-alert.error {
            background: rgba(212, 52, 56, 0.1);
            border-color: var(--ms-danger);
            color: #d13438;
        }

        .admin-alert i {
            font-size: 20px;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            background: var(--ms-neutral-medium);
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.267s ease;
        }

        .toggle-switch.active {
            background: var(--ms-primary);
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.267s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active::after {
            transform: translateX(24px);
        }

        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 40px;
            height: 40px;
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

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex !important;
                align-items: center;
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
    <main class="admin-main">
        <!-- Professional Hero Section -->
        <div class="hero-section fade-in">
            <div class="hero-content">
                <div class="hero-avatar">
                    <i class="fas fa-cog" style="color: #003366; font-size: 32px;"></i>
                </div>
                <div class="hero-text">
                    <h1>System Settings</h1>
                    <p>Configure your application settings and preferences</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-cogs"></i>
                            System Config
                        </span>
                        <span class="hero-stat">
                            <i class="fas fa-shield-alt"></i>
                            Security
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="admin-content">
            <?php if (!empty($success)): ?>
                <div class="admin-alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="admin-alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Settings Grid -->
            <div class="settings-grid">
                <!-- Site Settings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Site Settings</h3>
                            <p>Basic website configuration</p>
                        </div>
                        <i class="fas fa-globe" style="color: var(--ms-primary);"></i>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-form">
                            <input type="hidden" name="action" value="update_site_settings">
                            
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" id="site_name" name="site_name" value="Kenya EduHub" required>
                            </div>

                            <div class="form-group">
                                <label for="site_description">Site Description</label>
                                <textarea id="site_description" name="site_description" placeholder="Enter site description...">Educational resource management system for Kenyan schools</textarea>
                            </div>

                            <div class="form-group">
                                <label for="admin_email">Admin Email</label>
                                <input type="email" id="admin_email" name="admin_email" value="admin@kenyaeduhub.com" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="max_file_size">Max File Size (MB)</label>
                                    <input type="number" id="max_file_size" name="max_file_size" value="10" min="1" max="100">
                                </div>

                                <div class="form-group">
                                    <label for="allowed_extensions">Allowed Extensions</label>
                                    <input type="text" id="allowed_extensions" name="allowed_extensions" value="pdf,doc,docx,ppt,pptx,xls,xlsx,txt">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="admin-btn">Cancel</button>
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>System Settings</h3>
                            <p>System configuration and security</p>
                        </div>
                        <i class="fas fa-cogs" style="color: var(--ms-success);"></i>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-form">
                            <input type="hidden" name="action" value="update_system_settings">
                            
                            <div class="toggle-group">
                                <label>Maintenance Mode</label>
                                <div class="toggle-switch" onclick="this.classList.toggle('active')"></div>
                            </div>

                            <div class="toggle-group">
                                <label>Debug Mode</label>
                                <div class="toggle-switch" onclick="this.classList.toggle('active')"></div>
                            </div>

                            <div class="form-group">
                                <label for="session_timeout">Session Timeout (minutes)</label>
                                <input type="number" id="session_timeout" name="session_timeout" value="30" min="5" max="120">
                            </div>

                            <div class="form-group">
                                <label for="max_login_attempts">Max Login Attempts</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" value="5" min="3" max="10">
                            </div>

                            <div class="form-actions">
                                <button type="button" class="admin-btn">Cancel</button>
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Email Settings</h3>
                            <p>SMTP configuration for emails</p>
                        </div>
                        <i class="fas fa-envelope" style="color: var(--ms-warning);"></i>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-form">
                            <input type="hidden" name="action" value="update_email_settings">
                            
                            <div class="form-group">
                                <label for="smtp_host">SMTP Host</label>
                                <input type="text" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="smtp_port">SMTP Port</label>
                                    <input type="number" id="smtp_port" name="smtp_port" value="587">
                                </div>

                                <div class="form-group">
                                    <label for="email_from">From Email</label>
                                    <input type="email" id="email_from" name="email_from" placeholder="noreply@kenyaeduhub.com">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="smtp_username">SMTP Username</label>
                                <input type="text" id="smtp_username" name="smtp_username" placeholder="your-email@gmail.com">
                            </div>

                            <div class="form-group">
                                <label for="smtp_password">SMTP Password</label>
                                <input type="password" id="smtp_password" name="smtp_password" placeholder="Enter SMTP password">
                            </div>

                            <div class="form-actions">
                                <button type="button" class="admin-btn">Cancel</button>
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Backup Settings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Backup Settings</h3>
                            <p>Database backup configuration</p>
                        </div>
                        <i class="fas fa-database" style="color: var(--ms-danger);"></i>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-form">
                            <div class="toggle-group">
                                <label>Auto Backup</label>
                                <div class="toggle-switch active" onclick="this.classList.toggle('active')"></div>
                            </div>

                            <div class="form-group">
                                <label for="backup_frequency">Backup Frequency</label>
                                <select id="backup_frequency" name="backup_frequency">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="backup_retention">Retention Period (days)</label>
                                <input type="number" id="backup_retention" name="backup_retention" value="30" min="7" max="365">
                            </div>

                            <div class="form-actions">
                                <button type="button" class="admin-btn admin-btn-danger">
                                    <i class="fas fa-trash"></i> Clear Backups
                                </button>
                                <button type="button" class="admin-btn admin-btn-success">
                                    <i class="fas fa-download"></i> Download Backup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
<!-- Professional Footer -->
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

        // Form submission handlers
        document.querySelectorAll('.admin-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                }
            });
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
</body>
</html>
