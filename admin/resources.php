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
$per_page = 10;
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources Management - Admin Dashboard</title>
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

        /* Dashboard Content */
        .admin-content {
            padding: 0;
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

        .admin-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
        }

        .admin-search-input, .admin-select {
            padding: 10px 14px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #000000;
            color: #ffffff;
        }

        .admin-search-input:focus, .admin-select:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        .admin-search-input::placeholder, .admin-select::placeholder {
            color: #cccccc;
        }

        .admin-search-input {
            flex: 1;
            min-width: 200px;
        }

        .admin-select {
            min-width: 150px;
        }

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

        .admin-btn-success {
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
        }

        .admin-btn-success:hover {
            background: #333333;
            color: #ffffff;
            border-color: #ffffff;
        }

        .admin-btn-warning {
            background: var(--admin-warning);
            color: white;
        }

        .admin-btn-warning:hover {
            background: #e67e22;
        }

        .admin-btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-table th {
            text-align: left;
            padding: 16px 20px;
            border-bottom: 1px solid var(--ms-border);
            font-weight: 600;
            color: var(--ms-text-primary);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(to bottom, #faf9f8, #f3f2f1);
            border-top: 1px solid transparent;
            position: relative;
        }

        .admin-table th:first-child {
            border-left: 1px solid var(--ms-border);
            border-top-left-radius: 4px;
        }

        .admin-table th:last-child {
            border-right: 1px solid var(--ms-border);
            border-top-right-radius: 4px;
        }

        .admin-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            color: var(--ms-text-primary);
            font-size: 14px;
            vertical-align: middle;
        }

        .admin-table td:first-child {
            border-left: 1px solid var(--ms-border);
        }

        .admin-table td:last-child {
            border-right: 1px solid var(--ms-border);
        }

        .admin-table tr:last-child td {
            border-bottom: 1px solid var(--ms-border);
        }

        .admin-table tr:last-child td:first-child {
            border-bottom-left-radius: 4px;
        }

        .admin-table tr:last-child td:last-child {
            border-bottom-right-radius: 4px;
        }

        .admin-table tr:hover {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
        }

        .admin-table tr:hover td {
            border-bottom-color: #e8e8e8;
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

        .admin-badge.danger {
            background: rgba(212, 52, 56, 0.1);
            color: #d13438;
            border-color: rgba(212, 52, 56, 0.3);
        }

        .admin-badge.star {
            background: rgba(255, 193, 7, 0.1);
            color: #b08d00;
            border-color: rgba(255, 193, 7, 0.3);
        }

        .admin-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* Resource Cards - Matching Dashboard Style */
        .resource-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .resource-card {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            border: 1px solid #333333;
            overflow: hidden;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
        }

        .resource-card::before {
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

        .resource-card:hover {
            box-shadow: 0 2.8px 5.6px rgba(0, 0, 0, 0.132), 0 0.7px 1.8px rgba(0, 0, 0, 0.108);
            transform: translateY(-2px);
        }

        .resource-card:hover::before {
            opacity: 1;
        }

        .resource-header {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #333333;
            background: #000000;
        }

        .resource-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 16px;
            color: white;
            background: #0078D4;
        }

        .resource-icon.pdf {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .resource-icon.doc {
            background: linear-gradient(135deg, #0078d4, #106ebe);
        }

        .resource-icon.ppt {
            background: linear-gradient(135deg, #ff8c00, #e67e00);
        }

        .resource-icon.xls {
            background: linear-gradient(135deg, #107c10, #0e5a0e);
        }

        .resource-info {
            flex: 1;
            min-width: 0;
        }

        .resource-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .resource-subject {
            font-size: 14px;
            color: #cccccc;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .resource-subject::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0078D4;
        }

        .resource-description {
            padding: 20px;
            font-size: 14px;
            color: #cccccc;
            line-height: 1.5;
            border-bottom: 1px solid #333333;
        }

        .resource-meta {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1a1a1a;
        }

        .resource-meta-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .resource-meta-left span {
            font-size: 12px;
            color: #cccccc;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .resource-meta-left i {
            width: 12px;
            text-align: center;
        }

        .resource-stats {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #cccccc;
            font-weight: 500;
        }

        .resource-actions {
            padding: 16px 20px;
            display: flex;
            gap: 8px;
            background: #000000;
        }

        .resource-actions .admin-btn {
            flex: 1;
            justify-content: center;
            padding: 8px 12px;
            font-size: 13px;
        }

        .admin-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .admin-pagination a {
            padding: 8px 12px;
            border: 1px solid var(--admin-border);
            border-radius: 5px;
            text-decoration: none;
            color: var(--admin-text);
            transition: all 0.3s ease;
        }

        .admin-pagination a:hover {
            background: var(--admin-accent);
            color: white;
            border-color: var(--admin-accent);
        }

        .admin-pagination a.active {
            background: var(--admin-accent);
            color: white;
            border-color: var(--admin-accent);
        }

        .admin-alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .admin-alert.success {
            background: #d4edda;
            border-color: var(--admin-success);
            color: #155724;
        }

        .admin-alert.error {
            background: #f8d7da;
            border-color: var(--admin-danger);
            color: #721c24;
        }

        .file-info {
            font-size: 12px;
            color: #666;
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
            
            .admin-filters {
                flex-direction: column;
            }
            
            .admin-table {
                font-size: 12px;
            }
            
            .admin-actions {
                flex-direction: column;
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
                    <i class="fas fa-folder" style="color: #003366; font-size: 32px;"></i>
                </div>
                <div class="hero-text">
                    <h1>Resources Management</h1>
                    <p>Manage and organize all educational resources in the system</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-folder"></i>
                            <?php echo $total_resources ?? 0; ?> Resources
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
            <?php if (isset($success)): ?>
                <div class="admin-alert success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Resources Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Resources (<?php echo $total_resources; ?>)</h3>
                </div>
                <div class="admin-card-body">
                    <!-- Filters -->
                    <form method="GET" class="admin-filters">
                        <input type="text" name="search" class="admin-search-input" 
                               placeholder="Search resources..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select name="subject" class="admin-select">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject']); ?>" 
                                        <?php echo $subject_filter === $subject['subject'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        
                        <?php if (!empty($search) || !empty($subject_filter)): ?>
                            <a href="resources.php" class="admin-btn admin-btn-danger">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- Resources Cards -->
                    <div class="resource-cards">
                        <?php if (empty($resources)): ?>
                            <div class="text-center py-5" style="grid-column: 1 / -1;">
                                <div style="width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: var(--ms-neutral-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-folder-open" style="font-size: 32px; color: var(--ms-text-secondary);"></i>
                                </div>
                                <h3 style="color: var(--ms-text-primary); margin-bottom: 8px;">No Resources Found</h3>
                                <p style="color: var(--ms-text-secondary); margin-bottom: 20px;">No resources match your current filters.</p>
                                <a href="resources.php" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($resources as $resource): ?>
                            <?php
                            // Determine file type and icon
                            $fileType = strtolower(pathinfo($resource['filename'], PATHINFO_EXTENSION));
                            $iconClass = 'default';
                            $iconFa = 'fa-file';
                            
                            switch($fileType) {
                                case 'pdf':
                                    $iconClass = 'pdf';
                                    $iconFa = 'fa-file-pdf';
                                    break;
                                case 'doc':
                                case 'docx':
                                    $iconClass = 'doc';
                                    $iconFa = 'fa-file-word';
                                    break;
                                case 'ppt':
                                case 'pptx':
                                    $iconClass = 'ppt';
                                    $iconFa = 'fa-file-powerpoint';
                                    break;
                                case 'xls':
                                case 'xlsx':
                                    $iconClass = 'xls';
                                    $iconFa = 'fa-file-excel';
                                    break;
                            }
                            ?>
                            <div class="resource-card">
                                <div class="resource-header">
                                    <div class="resource-icon <?php echo $iconClass; ?>">
                                        <i class="fas <?php echo $iconFa; ?>"></i>
                                    </div>
                                    <div class="resource-info">
                                        <div class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></div>
                                        <div class="resource-subject"><?php echo htmlspecialchars($resource['subject']); ?></div>
                                    </div>
                                </div>
                                <div class="resource-description">
                                    <?php echo htmlspecialchars($resource['description'] ?? 'No description available'); ?>
                                </div>
                                <div class="resource-meta">
                                    <div class="resource-meta-left">
                                        <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($resource['level']); ?></span>
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($resource['created_at'])); ?></span>
                                    </div>
                                    <div class="resource-stats">
                                        <i class="fas fa-download"></i> <?php echo $resource['downloads'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="resource-actions">
                                    <a href="../uploads/<?php echo htmlspecialchars($resource['filename']); ?>" class="admin-btn admin-btn-primary" target="_blank">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <a href="edit-resource.php?id=<?php echo $resource['id']; ?>" class="admin-btn" target="_blank">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                                <div class="resource-actions" style="border-top: 1px solid var(--ms-border); padding-top: 12px;">
                                    <form method="POST" style="display: inline; width: 100%;" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                        <input type="hidden" name="action" value="delete_resource">
                                        <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" style="width: 100%;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="admin-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&subject=<?php echo urlencode($subject_filter); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <a href="#" class="active"><?php echo $i; ?></a>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&subject=<?php echo urlencode($subject_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&subject=<?php echo urlencode($subject_filter); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
