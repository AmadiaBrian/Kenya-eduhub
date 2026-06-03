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

// Read real log files
$logDir = __DIR__ . '/../logs';
$logs = [];
$currentLog = '';
$error = '';

// Get available log files and find the most recent one
$mostRecentFile = 'activity.log';
$mostRecentTime = 0;
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.log$/', $file)) {
            $filePath = $logDir . '/' . $file;
            $fileTime = filemtime($filePath);
            $logs[] = [
                'name' => $file,
                'path' => $filePath,
                'size' => filesize($filePath),
                'modified' => $fileTime
            ];
            
            // Track most recent file
            if ($fileTime > $mostRecentTime) {
                $mostRecentTime = $fileTime;
                $mostRecentFile = $file;
            }
        }
    }
}

// Handle log file selection - prioritize security.log if it exists
$securityLogPath = $logDir . '/security.log';
if (file_exists($securityLogPath)) {
    $logFile = $_GET['file'] ?? 'security.log';
} else {
    $logFile = $_GET['file'] ?? $mostRecentFile;
}
$logPath = $logDir . '/' . $logFile;

// Security: Validate log file path
if (!file_exists($logPath) || strpos($logFile, '..') !== false || !preg_match('/^[a-zA-Z0-9_.-]+$/', $logFile)) {
    $logPath = $logDir . '/activity.log';
    $logFile = 'activity.log';
}

// Read current log file
if (file_exists($logPath)) {
    $currentLog = file_get_contents($logPath);
} else {
    $error = 'Log file not found. No activity has been logged yet.';
}

// Handle log clearing
if (($_GET['action'] ?? '') === 'clear' && ($_GET['confirm'] ?? '') === 'yes') {
    $fileToClear = $logDir . '/' . $logFile;
    if (file_exists($fileToClear) && preg_match('/^[a-zA-Z0-9_.-]+$/', $logFile)) {
        file_put_contents($fileToClear, '');
        header("Location: logs.php?file=" . urlencode($logFile) . "&cleared=1");
        exit();
    }
}

// Handle log download
if (($downloadFile = $_GET['download'] ?? '') && preg_match('/^[a-zA-Z0-9_.-]+$/', $downloadFile)) {
    $downloadPath = $logDir . '/' . $downloadFile;
    if (file_exists($downloadPath)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
        header('Content-Length: ' . filesize($downloadPath));
        readfile($downloadPath);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Dashboard</title>
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

        /* Main Content */
        .admin-main {
            margin-left: 220px;
            padding: 24px;
            background: #000000;
            min-height: 100vh;
        }

        /* Microsoft-style Header */
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
            border-right: 3px solid #0078D4;
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

        /* Log Cards */
        .admin-card {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            border: 1px solid #333333;
            overflow: hidden;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
            margin-bottom: 24px;
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

        /* Log Filters */
        .log-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding: 20px;
            background: #1a1a1a;
            border-radius: 8px;
            border: 1px solid #333333;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            color: #ffffff;
            white-space: nowrap;
        }

        .filter-group select,
        .filter-group .admin-select {
            padding: 10px 14px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            background: #000000;
            color: #ffffff;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            min-width: 150px;
        }

        .filter-group input {
            padding: 10px 14px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            background: #000000;
            color: #ffffff;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            min-width: 200px;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        /* Log Entries */
        .log-entries {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #000000;
        }

        .log-entry {
            background: #000000;
            border: 1px solid #333333;
            border-radius: 8px;
            padding: 16px 20px;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .log-entry::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--ms-primary);
        }

        .log-entry.info::before {
            background: var(--ms-primary);
        }

        .log-entry.success::before {
            background: var(--ms-success);
        }

        .log-entry.warning::before {
            background: var(--ms-warning);
        }

        .log-entry.error::before {
            background: var(--ms-danger);
        }

        .log-entry:hover {
            box-shadow: 0 2px 8px var(--ms-shadow-light);
            transform: translateX(4px);
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .log-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .log-type.info {
            background: rgba(0, 120, 212, 0.1);
            color: var(--ms-primary);
            border: 1px solid rgba(0, 120, 212, 0.3);
        }

        .log-type.success {
            background: rgba(16, 124, 16, 0.1);
            color: var(--ms-success);
            border: 1px solid rgba(16, 124, 16, 0.3);
        }

        .log-type.warning {
            background: rgba(255, 140, 0, 0.1);
            color: var(--ms-warning);
            border: 1px solid rgba(255, 140, 0, 0.3);
        }

        .log-type.error {
            background: rgba(212, 52, 56, 0.1);
            color: var(--ms-danger);
            border: 1px solid rgba(212, 52, 56, 0.3);
        }

        .log-timestamp {
            font-size: 12px;
            color: var(--ms-text-tertiary);
            font-family: 'Courier New', monospace;
        }

        .log-message {
            font-size: 14px;
            color: #ffffff;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .log-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--ms-text-secondary);
        }

        .log-user {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .log-user i {
            color: var(--ms-text-tertiary);
        }

        .log-ip {
            font-family: 'Courier New', monospace;
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
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #000000;
            font-family: 'Segoe UI', sans-serif;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .admin-btn:hover {
            background: var(--ms-neutral-light);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .admin-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .admin-btn-primary {
            background: var(--ms-primary);
            color: white;
            border-color: var(--ms-primary);
            box-shadow: 0 2px 8px rgba(0, 120, 212, 0.3);
        }

        .admin-btn-primary:hover {
            background: var(--ms-primary-dark);
            border-color: var(--ms-primary-dark);
            box-shadow: 0 4px 12px rgba(0, 120, 212, 0.4);
        }

        .admin-btn-danger {
            background: #000000;
            color: #ffffff;
            border: 1px solid #d13438;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-danger:hover {
            background: #333333;
            color: #ffffff;
            border-color: #d13438;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        /* Pagination */
        .admin-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 32px;
            border-color: var(--ms-primary);
        }

        .admin-pagination a.active {
            background: var(--ms-primary);
            color: white;
            border-color: var(--ms-primary);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #000000;
            border: 1px solid #333333;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .stat-card:hover {
            box-shadow: 0 2px 8px var(--ms-shadow-light);
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #cccccc;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #ffffff;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #ffffff;
            color: var(--ms-text-primary);
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 20px;
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
            
            .log-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group input,
            .filter-group select {
                min-width: auto;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                    <i class="fas fa-file-alt" style="color: #003366; font-size: 32px;"></i>
                </div>
                <div class="hero-text">
                    <h1>System Logs</h1>
                    <p>Monitor and analyze system activity and events</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-history"></i>
                            <?php echo count($logs); ?> Entries
                        </span>
                        <span class="hero-stat">
                            <i class="fas fa-clock"></i>
                            Real-time
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="admin-content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($logs); ?></div>
                    <div class="stat-label">Log Files</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $totalSize = array_sum(array_column($logs, 'size'));
                        echo round($totalSize / 1024, 2); 
                        ?> KB
                    </div>
                    <div class="stat-label">Total Size</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $lines = $currentLog ? count(explode("\n", trim($currentLog))) : 0;
                        echo $lines; 
                        ?>
                    </div>
                    <div class="stat-label">Current Entries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $successCount = substr_count($currentLog, 'SUCCESS');
                        echo $successCount; 
                        ?>
                    </div>
                    <div class="stat-label">Success Actions</div>
                </div>
            </div>

            <!-- Logs Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h3>System Activity Logs</h3>
                        <p>
                            Recent system events and activities 
                            <span style="background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px;">
                                📁 <?php echo htmlspecialchars($logFile); ?>
                            </span>
                        </p>
                    </div>
                    <button class="admin-btn admin-btn-danger" onclick="confirmClearLogs()">
                        <i class="fas fa-trash"></i> Clear Logs
                    </button>
                </div>
                <div class="admin-card-body">
                    <!-- File Selector -->
                    <div style="margin-bottom: 20px; padding: 16px; background: #1a1a1a; border-radius: 8px; border: 1px solid #333333;">
                        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label for="fileSelect" style="font-weight: 500; color: #ffffff;">Select Log File:</label>
                                <select id="fileSelect" onchange="changeLogFile()" style="padding: 8px 12px; border: 1px solid #333333; border-radius: 4px; font-size: 14px; background: #000000; color: #ffffff;">
                                    <?php foreach ($logs as $log): ?>
                                        <option value="<?php echo htmlspecialchars($log['name']); ?>" 
                                                <?php echo $log['name'] === $logFile ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($log['name']); ?>
                                            (<?php echo round($log['size'] / 1024, 2); ?> KB)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <?php if (file_exists($logDir . '/security.log')): ?>
                                    <a href="?file=security.log" style="padding: 8px 16px; background: #000000; color: white; border: 1px solid #d13438; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-shield-alt"></i> Security Logs
                                    </a>
                                <?php endif; ?>
                                <?php if (file_exists($logDir . '/activity.log')): ?>
                                    <a href="?file=activity.log" style="padding: 8px 16px; background: #000000; color: white; border: 1px solid #0078D4; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-list"></i> Activity Logs
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="?download=<?php echo urlencode($logFile); ?>" style="padding: 8px 16px; background: #000000; color: white; border: 1px solid #107c10; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button class="admin-btn admin-btn-danger" onclick="confirmClearLogs()">
                                    <i class="fas fa-trash"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Success Message -->
                    <?php if (isset($_GET['cleared'])): ?>
                        <div style="background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #c3e6cb;">
                            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                            Log file cleared successfully!
                        </div>
                    <?php endif; ?>
                    
                    <!-- Log Entries -->
                    <div class="log-entries">
                        <?php if (empty($currentLog)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h3>No Log Entries</h3>
                                <p>No log entries found in the selected file.</p>
                            </div>
                        <?php else: ?>
                            <?php
                            // Parse and display log entries
                            $lines = explode("\n", $currentLog);
                            foreach ($lines as $line) {
                                if (empty(trim($line))) continue;
                                
                                // Try to parse as JSON first
                                $jsonData = json_decode($line, true);
                                
                                // If JSON fails, try to fix common issues
                                if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
                                    // Try to fix escaped slashes
                                    $fixedLine = str_replace('\/', '/', $line);
                                    $jsonData = json_decode($fixedLine, true);
                                }
                                
                                if ($jsonData && is_array($jsonData)) {
                                    // JSON format parsing
                                    $timestamp = $jsonData['time'] ?? date('Y-m-d H:i:s');
                                    $event = $jsonData['event'] ?? 'UNKNOWN_EVENT';
                                    $ip = $jsonData['ip'] ?? 'Unknown';
                                    $details = $jsonData['details'] ?? [];
                                    
                                    $userId = $details['user_id'] ?? 'Guest';
                                    $userEmail = $details['email'] ?? 'guest@example.com';
                                    $userAgent = $details['user_agent'] ?? 'Unknown';
                                    
                                    // Convert event to readable format
                                    $actionReadable = str_replace('_', ' ', strtolower($event));
                                    $actionReadable = ucwords($actionReadable);
                                    
                                    // Determine security level and icon
                                    $securityLevel = 'info';
                                    $securityIcon = 'fas fa-info-circle';
                                    $securityColor = '#17a2b8';
                                    $securityMessage = '';
                                    
                                    // Security-focused interpretation for JSON events
                                    switch($event) {
                                        case 'ADMIN_LOGIN_SUCCESS':
                                            $securityLevel = 'warning';
                                            $securityIcon = 'fas fa-shield-alt';
                                            $securityColor = '#ffc107';
                                            $securityMessage = 'Admin successfully logged in - monitor session';
                                            break;
                                        case 'ADMIN_LOGIN_FAILED':
                                            $securityLevel = 'danger';
                                            $securityIcon = 'fas fa-exclamation-triangle';
                                            $securityColor = '#dc3545';
                                            $securityMessage = 'Failed admin login attempt - potential security threat';
                                            break;
                                        case 'USER_LOGIN_SUCCESS':
                                            $securityLevel = 'success';
                                            $securityIcon = 'fas fa-sign-in-alt';
                                            $securityColor = '#28a745';
                                            $securityMessage = 'User successfully authenticated';
                                            break;
                                        case 'USER_LOGIN_FAILED':
                                            $securityLevel = 'danger';
                                            $securityIcon = 'fas fa-exclamation-triangle';
                                            $securityColor = '#dc3545';
                                            $securityMessage = 'Failed user login - potential brute force';
                                            break;
                                        case 'USER_LOGOUT':
                                            $securityLevel = 'info';
                                            $securityIcon = 'fas fa-sign-out-alt';
                                            $securityColor = '#6c757d';
                                            $securityMessage = 'User session ended';
                                            break;
                                        case 'PAGE_ACCESS':
                                            $securityLevel = 'info';
                                            $securityIcon = 'fas fa-eye';
                                            $securityColor = '#17a2b8';
                                            $securityMessage = 'Page accessed';
                                            break;
                                        case 'SECURITY_VIOLATION':
                                            $securityLevel = 'danger';
                                            $securityIcon = 'fas fa-ban';
                                            $securityColor = '#dc3545';
                                            $securityMessage = 'Security violation detected';
                                            break;
                                        case 'DATA_ACCESS':
                                            $securityLevel = 'warning';
                                            $securityIcon = 'fas fa-database';
                                            $securityColor = '#ffc107';
                                            $securityMessage = 'Data access - monitor for privacy';
                                            break;
                                        case 'FILE_OPERATION':
                                            $securityLevel = 'warning';
                                            $securityIcon = 'fas fa-file';
                                            $securityColor = '#ffc107';
                                            $securityMessage = 'File operation - scan for security';
                                            break;
                                        default:
                                            if (strpos($event, 'FAILED') !== false || strpos($event, 'ERROR') !== false) {
                                                $securityLevel = 'danger';
                                                $securityIcon = 'fas fa-exclamation-triangle';
                                                $securityColor = '#dc3545';
                                                $securityMessage = 'Failed operation - investigate';
                                            }
                                            break;
                                    }
                                    
                                    // Format time to be more readable
                                    $timeFormatted = date('M j, Y - g:i A', strtotime($timestamp));
                                    
                                    // Get user-friendly name from email
                                    $userName = explode('@', $userEmail)[0];
                                    $userName = ucfirst($userName);
                                    
                                    // Determine if user is admin or guest
                                    $userType = ($userId === '1' || strpos($userEmail, 'admin') !== false) ? 'Admin' : (($userId === 'Guest') ? 'Guest' : 'User');
                                    
                                    // Check for suspicious patterns
                                    $isSuspicious = false;
                                    $suspiciousReasons = [];
                                    
                                    if ($ip === '::1' || $ip === '127.0.0.1') {
                                        $suspiciousReasons[] = 'Localhost access';
                                    }
                                    
                                    if (strpos($userAgent, 'bot') !== false || strpos($userAgent, 'crawler') !== false) {
                                        $suspiciousReasons[] = 'Bot detected';
                                    }
                                    
                                    if ($securityLevel === 'danger') {
                                        $suspiciousReasons[] = 'Security threat detected';
                                    }
                                    
                                    $isSuspicious = !empty($suspiciousReasons);
                                    
                                    // Create description from event and details
                                    $description = $actionReadable;
                                    if (!empty($details)) {
                                        $detailParts = [];
                                        foreach ($details as $key => $value) {
                                            if (!in_array($key, ['user_id', 'email', 'ip', 'user_agent'])) {
                                                $detailParts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                            }
                                        }
                                        if (!empty($detailParts)) {
                                            $description .= ' - ' . implode(', ', $detailParts);
                                        }
                                    }
                                    
                                    $page = $details['page'] ?? 'Unknown';
                                    $method = $details['method'] ?? 'Unknown';
                                    $session = $details['session_id'] ?? 'Unknown';
                                    $agent = $userAgent;
                                    
                                    // Set type for JSON parsing
                                    $type = ($securityLevel === 'success') ? 'success' : (($securityLevel === 'danger') ? 'error' : 'info');
                                ?>
                                <div class="log-entry <?php echo $type; ?>" style="margin-bottom: 20px; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo $securityColor; ?>; background: <?php echo $securityLevel === 'danger' ? '#1a0000' : ($securityLevel === 'warning' ? '#1a1a00' : ($securityLevel === 'success' ? '#001a00' : '#000000')); ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.1); <?php echo $isSuspicious ? 'border: 2px solid #dc3545;' : ''; ?>">
                                    <!-- Security Alert Header -->
                                    <?php if ($isSuspicious || $securityLevel === 'danger' || $securityLevel === 'warning'): ?>
                                    <div style="background: <?php echo $securityLevel === 'danger' ? '#dc3545' : '#ffc107'; ?>; color: white; padding: 8px 12px; border-radius: 6px 6px 0 0; margin: -20px -20px 12px -20px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                        <i class="<?php echo $securityIcon; ?>"></i>
                                        <?php echo $securityMessage; ?>
                                        <?php if ($isSuspicious): ?>
                                            <span style="background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 10px; font-size: 10px;">
                                                ⚠️ SUSPICIOUS
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                                                <span style="background: <?php echo $securityColor; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
                                                    <i class="<?php echo $securityIcon; ?>"></i>
                                                    <?php echo $actionReadable; ?>
                                                </span>
                                                <span style="background: rgba(255,255,255,0.1); color: #ffffff; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">
                                                    <?php echo $userType; ?>
                                                </span>
                                                <span style="color: #cccccc; font-size: 14px;">
                                                    <i class="fas fa-clock" style="margin-right: 4px;"></i>
                                                    <?php echo $timeFormatted; ?>
                                                </span>
                                            </div>
                                            
                                            <div style="margin-bottom: 12px;">
                                                <div style="font-weight: 600; color: #ffffff; margin-bottom: 4px; font-size: 16px;">
                                                    <?php echo htmlspecialchars($description); ?>
                                                </div>
                                                <?php if ($isSuspicious): ?>
                                                    <div style="background: #d13438; color: white; padding: 6px 10px; border-radius: 4px; font-size: 12px; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <strong>Suspicious Activity:</strong> <?php echo implode(', ', $suspiciousReasons); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px; color: #cccccc;">
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-user" style="color: #0078D4;"></i>
                                                    <span><strong>User:</strong> <?php echo htmlspecialchars($userName); ?> (<?php echo htmlspecialchars($userId); ?>)</span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-envelope" style="color: #107c10;"></i>
                                                    <span><?php echo htmlspecialchars($userEmail); ?></span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-globe" style="color: #FFD700;"></i>
                                                    <span><strong>IP:</strong> <?php echo htmlspecialchars($ip); ?></span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-file-alt" style="color: #ff8c00;"></i>
                                                    <span><strong>Page:</strong> <?php echo htmlspecialchars($page); ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Security Details -->
                                            <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 6px; margin-top: 12px; font-size: 12px;">
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-weight: 600; color: #ffffff;">
                                                    <i class="fas fa-shield-alt" style="color: #ff8c00;"></i>
                                                    Security Analysis
                                                </div>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                                                    <div>
                                                        <strong>Risk Level:</strong> 
                                                        <span style="color: <?php echo $securityColor; ?>; font-weight: 600;">
                                                            <?php echo ucfirst($securityLevel); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong>Method:</strong> <?php echo htmlspecialchars($method); ?>
                                                    </div>
                                                    <div>
                                                        <strong>Session:</strong> <?php echo substr($session, 0, 8); ?>...
                                                    </div>
                                                    <div>
                                                        <strong>Agent:</strong> 
                                                        <span style="font-family: monospace; font-size: 10px;">
                                                            <?php echo substr($agent, 0, 30); ?>...
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        <div style="text-align: right; margin-left: 20px;">
                                            <div style="background: rgba(0,0,0,0.1); padding: 8px; border-radius: 6px; font-size: 11px;">
                                                <?php if ($securityLevel === 'danger'): ?>
                                                    <button style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; margin-bottom: 4px;">
                                                        <i class="fas fa-ban"></i> Block IP
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($securityLevel === 'warning'): ?>
                                                    <button style="background: #ffc107; color: #212529; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; margin-bottom: 4px;">
                                                        <i class="fas fa-search"></i> Investigate
                                                    </button>
                                                <?php endif; ?>
                                                <div>
                                                    <strong>Session ID:</strong><br>
                                                    <code style="font-size: 9px;"><?php echo substr($session, 0, 16); ?>...</code>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php    
                                } elseif (preg_match('/^\[([^\]]+)\]\s+(\w+)\s+\|\s+User:\s+(\w+)\s+\(([^)]+)\)\s+\|\s+IP:\s+([^\|]+)\s+\|\s+Action:\s+([^\|]+)\s+\|\s+([^\|]+)\s+\|\s+Page:\s+([^\|]+)\s+\|\s+Method:\s+([^\|]+)\s+\|\s+Session:\s+([^\|]+)\s+\|\s+Agent:\s+(.+)$/', $line, $matches)) {
                                    // Original text format parsing (keep existing logic)
                                    $timestamp = $matches[1];
                                    $status = $matches[2];
                                    $userId = $matches[3];
                                    $userEmail = $matches[4];
                                    $ip = $matches[5];
                                    $action = $matches[6];
                                    $description = $matches[7];
                                    $page = $matches[8];
                                    $method = $matches[9];
                                    $session = $matches[10];
                                    $agent = $matches[11];
                                    
                                    $type = ($status === 'SUCCESS') ? 'success' : (($status === 'FAILED') ? 'error' : 'info');
                                    
                                    // Make action more readable and add security context
                                    $actionReadable = ucwords(str_replace('_', ' ', strtolower($action)));
                                    
                                    // Determine security level and icon (reuse existing logic)
                                    $securityLevel = 'info';
                                    $securityIcon = 'fas fa-info-circle';
                                    $securityColor = '#17a2b8';
                                    $securityMessage = '';
                                    
                                    // Security-focused interpretation
                                    switch($action) {
                                        case 'ADMIN_ACCESS':
                                            $securityLevel = 'warning';
                                            $securityIcon = 'fas fa-shield-alt';
                                            $securityColor = '#ffc107';
                                            $securityMessage = 'Admin panel access - monitor for unauthorized access';
                                            break;
                                        case 'USER_LOGIN':
                                            $securityLevel = 'success';
                                            $securityIcon = 'fas fa-sign-in-alt';
                                            $securityColor = '#28a745';
                                            $securityMessage = 'Successful user authentication';
                                            break;
                                        case 'USER_LOGOUT':
                                            $securityLevel = 'info';
                                            $securityIcon = 'fas fa-sign-out-alt';
                                            $securityColor = '#6c757d';
                                            $securityMessage = 'User session ended';
                                            break;
                                        case 'PAGE_ACCESS':
                                            if (strpos($page, 'admin') !== false) {
                                                $securityLevel = 'warning';
                                                $securityIcon = 'fas fa-lock';
                                                $securityColor = '#ffc107';
                                                $securityMessage = 'Admin page accessed';
                                            } else {
                                                $securityLevel = 'info';
                                                $securityIcon = 'fas fa-eye';
                                                $securityColor = '#17a2b8';
                                                $securityMessage = 'Page visited';
                                            }
                                            break;
                                        case 'FAILED_LOGIN':
                                        case 'LOGIN_FAILED':
                                            $securityLevel = 'danger';
                                            $securityIcon = 'fas fa-exclamation-triangle';
                                            $securityColor = '#dc3545';
                                            $securityMessage = 'Failed login attempt - potential security threat';
                                            break;
                                        default:
                                            if (strpos($action, 'FAILED') !== false) {
                                                $securityLevel = 'danger';
                                                $securityIcon = 'fas fa-exclamation-triangle';
                                                $securityColor = '#dc3545';
                                                $securityMessage = 'Failed action - investigate';
                                            }
                                            break;
                                    }
                                    
                                    // Format time to be more readable
                                    $timeFormatted = date('M j, Y - g:i A', strtotime($timestamp));
                                    
                                    // Get user-friendly name from email
                                    $userName = explode('@', $userEmail)[0];
                                    $userName = ucfirst($userName);
                                    
                                    // Determine if user is admin or guest
                                    $userType = ($userId === '1' || strpos($userEmail, 'admin') !== false) ? 'Admin' : (($userId === 'Guest') ? 'Guest' : 'User');
                                    
                                    // Check for suspicious patterns
                                    $isSuspicious = false;
                                    $suspiciousReasons = [];
                                    
                                    if ($ip === '::1' || $ip === '127.0.0.1') {
                                        $suspiciousReasons[] = 'Localhost access';
                                    }
                                    
                                    if (strpos($agent, 'bot') !== false || strpos($agent, 'crawler') !== false) {
                                        $suspiciousReasons[] = 'Bot detected';
                                    }
                                    
                                    if (substr_count($currentLog, $userEmail) > 10) {
                                        $suspiciousReasons[] = 'High activity user';
                                    }
                                    
                                    $isSuspicious = !empty($suspiciousReasons);
                                    
                                ?>
                                <div class="log-entry <?php echo $type; ?>" style="margin-bottom: 20px; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo $securityColor; ?>; background: <?php echo $securityLevel === 'danger' ? '#1a0000' : ($securityLevel === 'warning' ? '#1a1a00' : ($securityLevel === 'success' ? '#001a00' : '#000000')); ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.1); <?php echo $isSuspicious ? 'border: 2px solid #dc3545;' : ''; ?>">
                                    <!-- Security Alert Header -->
                                    <?php if ($isSuspicious || $securityLevel === 'danger' || $securityLevel === 'warning'): ?>
                                    <div style="background: <?php echo $securityLevel === 'danger' ? '#dc3545' : '#ffc107'; ?>; color: white; padding: 8px 12px; border-radius: 6px 6px 0 0; margin: -20px -20px 12px -20px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                        <i class="<?php echo $securityIcon; ?>"></i>
                                        <?php echo $securityMessage; ?>
                                        <?php if ($isSuspicious): ?>
                                            <span style="background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 10px; font-size: 10px;">
                                                ⚠️ SUSPICIOUS
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                                                <span style="background: <?php echo $securityColor; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
                                                    <i class="<?php echo $securityIcon; ?>"></i>
                                                    <?php echo $actionReadable; ?>
                                                </span>
                                                <span style="background: rgba(255,255,255,0.1); color: #ffffff; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">
                                                    <?php echo $userType; ?>
                                                </span>
                                                <span style="color: #cccccc; font-size: 14px;">
                                                    <i class="fas fa-clock" style="margin-right: 4px;"></i>
                                                    <?php echo $timeFormatted; ?>
                                                </span>
                                            </div>
                                            
                                            <div style="margin-bottom: 12px;">
                                                <div style="font-weight: 600; color: #ffffff; margin-bottom: 4px; font-size: 16px;">
                                                    <?php echo htmlspecialchars($description); ?>
                                                </div>
                                                <?php if ($isSuspicious): ?>
                                                    <div style="background: #d13438; color: white; padding: 6px 10px; border-radius: 4px; font-size: 12px; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <strong>Suspicious Activity:</strong> <?php echo implode(', ', $suspiciousReasons); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px; color: #cccccc;">
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-user" style="color: #0078D4;"></i>
                                                    <span><strong>User:</strong> <?php echo htmlspecialchars($userName); ?> (<?php echo htmlspecialchars($userId); ?>)</span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-envelope" style="color: #107c10;"></i>
                                                    <span><?php echo htmlspecialchars($userEmail); ?></span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-globe" style="color: #FFD700;"></i>
                                                    <span><strong>IP:</strong> <?php echo htmlspecialchars($ip); ?></span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-file-alt" style="color: #ff8c00;"></i>
                                                    <span><strong>Page:</strong> <?php echo htmlspecialchars($page); ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Security Details -->
                                            <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 6px; margin-top: 12px; font-size: 12px;">
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-weight: 600; color: #ffffff;">
                                                    <i class="fas fa-shield-alt" style="color: #ff8c00;"></i>
                                                    Security Analysis
                                                </div>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                                                    <div>
                                                        <strong>Risk Level:</strong> 
                                                        <span style="color: <?php echo $securityColor; ?>; font-weight: 600;">
                                                            <?php echo ucfirst($securityLevel); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong>Method:</strong> <?php echo htmlspecialchars($method); ?>
                                                    </div>
                                                    <div>
                                                        <strong>Session:</strong> <?php echo substr($session, 0, 8); ?>...
                                                    </div>
                                                    <div>
                                                        <strong>Agent:</strong> 
                                                        <span style="font-family: monospace; font-size: 10px;">
                                                            <?php echo substr($agent, 0, 30); ?>...
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        <div style="text-align: right; margin-left: 20px;">
                                            <div style="background: rgba(0,0,0,0.1); padding: 8px; border-radius: 6px; font-size: 11px;">
                                                <?php if ($securityLevel === 'danger'): ?>
                                                    <button style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; margin-bottom: 4px;">
                                                        <i class="fas fa-ban"></i> Block IP
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($securityLevel === 'warning'): ?>
                                                    <button style="background: #ffc107; color: #212529; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; margin-bottom: 4px;">
                                                        <i class="fas fa-search"></i> Investigate
                                                    </button>
                                                <?php endif; ?>
                                                <div>
                                                    <strong>Session ID:</strong><br>
                                                    <code style="font-size: 9px;"><?php echo substr($session, 0, 16); ?>...</code>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                } else {
                                    // Display unparsed lines as-is
                                ?>
                                <div class="log-entry info" style="margin-bottom: 20px; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; background: #f8f9fa; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div style="color: #666; font-family: monospace; font-size: 13px;">
                                        <?php echo htmlspecialchars($line); ?>
                                    </div>
                                </div>
                                <?php
                                }
                            }
                            ?>
                        <?php endif; ?>
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
        function changeLogFile() {
            const selectedFile = document.getElementById('fileSelect').value;
            window.location.href = '?file=' + encodeURIComponent(selectedFile);
        }

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

        function confirmClearLogs() {
            if (confirm('Are you sure you want to clear all system logs? This action cannot be undone.')) {
                // Submit form to actually clear logs
                window.location.href = '?action=clear&confirm=yes';
            }
        }

        // Auto-refresh logs every 30 seconds
        setInterval(function() {
            const currentUrl = new URL(window.location);
            if (!currentUrl.searchParams.has('search') && !currentUrl.searchParams.has('type')) {
                // Only auto-refresh if no filters are applied
                // window.location.reload();
            }
        }, 30000);
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
</body>
</html>
