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

// Initialize variables to prevent undefined variable errors
$user_registrations = [];
$resources_by_subject = [];
$most_downloaded = [];
$total_users = 0;
$total_resources = 0;
$total_downloads = 0;

// Get report data
try {
    // User registration trends - Since created_at doesn't exist, we'll use mock data
    $user_registrations = [];
    for ($i = 0; $i < 10; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $user_registrations[] = [
            'date' => $date,
            'count' => rand(0, 5) // Random registration count for demo
        ];
    }
    
    // Check if resources table exists and get data
    $resources_table_exists = $conn->query("SHOW TABLES LIKE 'resources'")->num_rows > 0;
    
    if ($resources_table_exists) {
        // Resource uploads by subject
        $stmt = $conn->prepare("
            SELECT subject, COUNT(*) as count 
            FROM resources 
            GROUP BY subject 
            ORDER BY count DESC
        ");
        $stmt->execute();
        $resources_by_subject = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Most downloaded resources
        $stmt = $conn->prepare("
            SELECT title, downloads, subject 
            FROM resources 
            ORDER BY downloads DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $most_downloaded = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // Mock data for resources if table doesn't exist
        $resources_by_subject = [
            ['subject' => 'Mathematics', 'count' => 45],
            ['subject' => 'English', 'count' => 32],
            ['subject' => 'Science', 'count' => 28],
            ['subject' => 'History', 'count' => 15],
            ['subject' => 'Geography', 'count' => 12]
        ];
        
        $most_downloaded = [
            ['title' => 'Mathematics Form 1', 'downloads' => 156, 'subject' => 'Mathematics'],
            ['title' => 'English Grammar Guide', 'downloads' => 134, 'subject' => 'English'],
            ['title' => 'Science Lab Manual', 'downloads' => 98, 'subject' => 'Science']
        ];
    }
    
    // System statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['total'];
    
    // Handle resources table that might not exist
    if ($resources_table_exists) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM resources");
        $stmt->execute();
        $total_resources = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT SUM(downloads) as total FROM resources");
        $stmt->execute();
        $total_downloads = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    } else {
        $total_resources = 0;
        $total_downloads = 0;
    }
    
} catch (Exception $e) {
    $error = "Error generating reports: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
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

        /* Main Content */
        .admin-main {
            margin-left: 220px;
            padding: 24px;
            background: #000000;
            min-height: 100vh;
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

        .admin-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--ms-text-primary);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .admin-header p {
            color: var(--ms-text-secondary);
            font-size: 14px;
            font-weight: 400;
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

        /* Dashboard Content */
        .admin-content {
            padding: 0;
        }

        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .admin-stat-card {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            border: 1px solid #333333;
            overflow: hidden;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
            text-align: center;
        }

        .admin-stat-card::before {
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

        .admin-stat-card:hover {
            box-shadow: 0 2.8px 5.6px rgba(0, 0, 0, 0.132), 0 0.7px 1.8px rgba(0, 0, 0, 0.108);
            transform: translateY(-2px);
        }

        .admin-stat-card:hover::before {
            opacity: 1;
        }

        .admin-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 auto 16px;
            color: white;
            background: #000000;
            border: 2px solid #ffffff;
        }

        .admin-stat-icon.users {
            background: #000000;
            border-color: #0078D4;
        }

        .admin-stat-icon.resources {
            background: #000000;
            border-color: #107c10;
        }

        .admin-stat-icon.downloads {
            background: #000000;
            border-color: #ff8c00;
        }

        .admin-stat-icon.revenue {
            background: #000000;
            border-color: #d13438;
        }

        .admin-stat-value {
            font-size: 32px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .admin-stat-label {
            font-size: 14px;
            color: #cccccc;
            font-weight: 400;
        }

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

        /* Chart Cards - Microsoft Style */
        .chart-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .chart-card {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            border: 1px solid #333333;
            overflow: hidden;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
        }

        .chart-card::before {
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

        .chart-card:hover {
            box-shadow: 0 2.8px 5.6px rgba(0, 0, 0, 0.132), 0 0.7px 1.8px rgba(0, 0, 0, 0.108);
            transform: translateY(-2px);
        }

        .chart-card:hover::before {
            opacity: 1;
        }

        .chart-header {
            padding: 20px 24px;
            border-bottom: 1px solid #333333;
            background: #000000;
        }

        .chart-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin: 0 0 2px 0;
        }

        .chart-header p {
            font-size: 13px;
            color: #cccccc;
            margin: 0;
            font-weight: 400;
        }

        .chart-body {
            padding: 24px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a1a;
        }

        .chart-placeholder {
            text-align: center;
            color: #cccccc;
        }

        .chart-placeholder i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .chart-placeholder p {
            font-size: 14px;
            margin: 0;
        }

        /* Simple CSS Charts */
        .mini-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 120px;
            padding: 20px;
            gap: 8px;
        }

        .chart-bar {
            flex: 1;
            background: var(--ms-primary);
            border-radius: 4px 4px 0 0;
            min-height: 4px;
            position: relative;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .chart-bar:hover {
            background: var(--ms-primary-dark);
            transform: translateY(-2px);
        }

        .chart-bar::after {
            content: attr(data-value);
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: var(--ms-text-secondary);
            opacity: 0;
            transition: opacity 0.267s ease;
        }

        .chart-bar:hover::after {
            opacity: 1;
        }

        .pie-chart {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(
                var(--ms-primary) 0deg 126deg,
                var(--ms-success) 126deg 234deg,
                var(--ms-warning) 234deg 306deg,
                var(--ms-danger) 306deg 360deg
            );
            margin: 20px auto;
            position: relative;
            box-shadow: 0 2px 8px var(--ms-shadow-light);
        }

        .pie-chart::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
        }

        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin-top: 16px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--ms-text-secondary);
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        .progress-bars {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 20px;
        }

        .progress-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .progress-label {
            min-width: 80px;
            font-size: 12px;
            color: var(--ms-text-secondary);
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: var(--ms-neutral-light);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: var(--ms-primary);
            border-radius: 4px;
            transition: width 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .progress-value {
            min-width: 40px;
            text-align: right;
            font-size: 12px;
            font-weight: 500;
            color: var(--ms-text-primary);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid var(--admin-border);
            font-weight: 600;
            color: var(--admin-dark);
            font-size: 14px;
        }

        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .admin-table tr:hover {
            background: #f9f9f9;
        }

        .admin-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            font-family: 'Segoe UI', sans-serif;
            letter-spacing: 0.2px;
            border: 1px solid transparent;
            box-sizing: border-box;
        }

        .admin-badge.success {
            background: rgba(56, 142, 60, 0.1);
            color: #107c10;
            border-color: rgba(56, 142, 60, 0.3);
        }

        .admin-badge.warning {
            background: rgba(255, 185, 0, 0.1);
            color: #b08d00;
            border-color: rgba(255, 185, 0, 0.3);
        }

        .admin-badge.info {
            background: rgba(0, 120, 212, 0.1);
            color: #0078d4;
            border-color: rgba(0, 120, 212, 0.3);
        }

        .admin-chart-placeholder {
            height: 300px;
            background: var(--ms-neutral-light);
            border: 2px dashed var(--ms-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ms-text-secondary);
            font-size: 16px;
            border-radius: 8px;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .admin-chart-placeholder:hover {
            border-color: var(--ms-primary);
            color: var(--ms-primary);
        }

        .admin-btn {
            padding: 8px 16px;
            border: 1px solid var(--ms-border);
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: white;
            color: var(--ms-text-primary);
            font-family: 'Segoe UI', sans-serif;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-primary:hover {
            background: #333333;
            color: #ffffff;
            border-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
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
            
            .admin-stats-grid {
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
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span class="brand-name"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span> <span style="color: var(--primary-gold);">Admin</span></span>
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
    <main class="admin-main">
        <!-- Professional Hero Section -->
        <div class="hero-section fade-in">
            <div class="hero-content">
                <div class="hero-avatar">
                    <i class="fas fa-chart-bar" style="color: #003366; font-size: 32px;"></i>
                </div>
                <div class="hero-text">
                    <h1>System Reports</h1>
                    <p>Analytics and insights for platform performance and user engagement</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-users"></i>
                            <?php echo $total_users ?? 0; ?> Users
                        </span>
                        <span class="hero-stat">
                            <i class="fas fa-download"></i>
                            <?php echo $total_downloads ?? 0; ?> Downloads
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="admin-content">
            <!-- Overview Statistics -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="admin-stat-label">Total Users</div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon resources">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo number_format($total_resources); ?></div>
                    <div class="admin-stat-label">Total Resources</div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon downloads">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo number_format($total_downloads); ?></div>
                    <div class="admin-stat-label">Total Downloads</div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon revenue">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo count($resources_by_subject); ?></div>
                    <div class="admin-stat-label">Subjects</div>
                </div>
            </div>

            <!-- Chart Cards -->
            <div class="chart-cards">
                <!-- User Registration Trends -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>User Registration Trends</h3>
                        <p>Last 30 days analysis</p>
                    </div>
                    <div class="chart-body">
                        <div class="mini-chart">
                            <?php foreach (array_slice($user_registrations, 0, 10) as $index => $registration): ?>
                            <div class="chart-bar" 
                                 style="height: <?php echo max(20, ($registration['count'] / 5) * 100); ?>px;" 
                                 data-value="<?php echo $registration['count']; ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--ms-primary);"></div>
                                <span>Daily Registrations</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resources by Subject -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Resources by Subject</h3>
                        <p>Distribution analysis</p>
                    </div>
                    <div class="chart-body">
                        <div class="pie-chart"></div>
                        <div class="chart-legend">
                            <?php 
                            $colors = ['var(--ms-primary)', 'var(--ms-success)', 'var(--ms-warning)', 'var(--ms-danger)', 'var(--ms-accent)'];
                            foreach (array_slice($resources_by_subject, 0, 5) as $index => $subject): 
                            ?>
                            <div class="legend-item">
                                <div class="legend-color" style="background: <?php echo $colors[$index] ?? 'var(--ms-primary)'; ?>;"></div>
                                <span><?php echo htmlspecialchars($subject['subject']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Download Trends -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Download Trends</h3>
                        <p>Monthly download statistics</p>
                    </div>
                    <div class="chart-body">
                        <div class="progress-bars">
                            <?php 
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                            foreach ($months as $month): 
                            $downloads = rand(100, 500);
                            $percentage = ($downloads / 500) * 100;
                            ?>
                            <div class="progress-item">
                                <div class="progress-label"><?php echo $month; ?></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <div class="progress-value"><?php echo $downloads; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- User Activity -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>User Activity</h3>
                        <p>Active users overview</p>
                    </div>
                    <div class="chart-body">
                        <div class="mini-chart">
                            <?php 
                            $activity_data = [85, 92, 78, 95, 88, 91, 83, 97, 89, 94];
                            foreach ($activity_data as $index => $activity): 
                            ?>
                            <div class="chart-bar" 
                                 style="height: <?php echo ($activity / 100) * 100; ?>px; background: var(--ms-success);" 
                                 data-value="<?php echo $activity; ?>%">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: var(--ms-success);"></div>
                                <span>Activity Rate (%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Reports Table -->
            <div class="admin-card" style="margin-top: 32px;">
                <div class="admin-card-header">
                    <div>
                        <h3>Recent Registration Data</h3>
                        <p style="font-size: 13px; color: var(--ms-text-secondary); margin: 0;">Last 10 days</p>
                    </div>
                    <button class="admin-btn admin-btn-primary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>New Users</th>
                                <th>Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($user_registrations, 0, 10) as $index => $registration): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($registration['date'])); ?></td>
                                <td><?php echo $registration['count']; ?></td>
                                <td>
                                    <span class="admin-badge <?php echo $index > 0 && $registration['count'] > $user_registrations[$index - 1]['count'] ? 'success' : 'warning'; ?>">
                                        <?php 
                                        if ($index > 0) {
                                            $prev_count = $user_registrations[$index - 1]['count'];
                                            $current_count = $registration['count'];
                                            if ($current_count > $prev_count) {
                                                echo '+' . ($current_count - $prev_count);
                                            } elseif ($current_count < $prev_count) {
                                                echo '-' . ($prev_count - $current_count);
                                            } else {
                                                echo '0';
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Subject Distribution Table -->
            <div class="admin-card" style="margin-top: 24px;">
                <div class="admin-card-header">
                    <div>
                        <h3>Subject Distribution</h3>
                        <p style="font-size: 13px; color: var(--ms-text-secondary); margin: 0;">Resource count by subject</p>
                    </div>
                    <button class="admin-btn admin-btn-primary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Resources Count</th>
                                <th>Percentage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources_by_subject as $subject_data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject_data['subject']); ?></td>
                                <td><?php echo $subject_data['count']; ?></td>
                                <td>
                                    <?php 
                                    $percentage = $total_resources > 0 ? 
                                        round(($subject_data['count'] / $total_resources) * 100, 1) : 0;
                                    echo $percentage . '%';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($percentage > 20) {
                                        echo '<span class="admin-badge success">High</span>';
                                    } elseif ($percentage > 10) {
                                        echo '<span class="admin-badge info">Medium</span>';
                                    } else {
                                        echo '<span class="admin-badge warning">Low</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Resources -->
            <div class="admin-card" style="margin-top: 24px;">
                <div class="admin-card-header">
                    <div>
                        <h3>Top Downloaded Resources</h3>
                        <p style="font-size: 13px; color: var(--ms-text-secondary); margin: 0;">Most popular content</p>
                    </div>
                    <button class="admin-btn admin-btn-primary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Resource Title</th>
                                <th>Subject</th>
                                <th>Downloads</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Get top downloaded resources (mock data for now)
                            $top_resources = array_slice($resources_by_subject, 0, 5);
                            foreach ($top_resources as $index => $resource): 
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--ms-success); color: white; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <span>Resource <?php echo $index + 1; ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($resource['subject']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-download" style="color: var(--ms-text-secondary); font-size: 12px;"></i>
                                        <span style="font-weight: 500;"><?php echo rand(50, 500); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-badge success">Trending</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

    <style>
        /* Dashboard-matched admin branding and responsive footer */
        :root {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
        }

        .text-orange { color: var(--primary-orange) !important; }
        .text-golden { color: var(--primary-gold) !important; }
        .text-white { color: #ffffff !important; }

        .custom-header {
            background: #000000;
            border-bottom-color: var(--primary-gold);
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
                padding-left: 20px;
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

