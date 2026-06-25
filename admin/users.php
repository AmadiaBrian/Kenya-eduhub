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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? '';
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success = "User deleted successfully";
        }
    } elseif ($action === 'toggle_status') {
        $user_id = $_POST['user_id'] ?? '';
        $current_status = $_POST['current_status'] ?? '';
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
            $verified_status = $new_status === 'active' ? 1 : 0;
            $stmt->bind_param("ii", $verified_status, $user_id);
            $stmt->execute();
            $success = "User status updated successfully";
        }
    }
}

// Get all users with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Ensure pagination variables are properly set
if ($page < 1) $page = 1;
if ($per_page < 1) $per_page = 10;
if ($offset < 0) $offset = 0;

$search = $_GET['search'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = "ss";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$sql = "SELECT * FROM users $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $all_params = array_merge($params, [$per_page, $offset]);
    $all_types = $types . "ii";
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent users (ordered by id since created_at doesn't exist)
$stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Dashboard</title>
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
            color: #ffffff;
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
            background: rgba(0, 120, 212, 0.1);
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
            border-right: 3px solid #0078D4;
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
            color: #ffffff;
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
            background: #0078D4;
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

        /* User Cards - Matching Dashboard Style */
        .user-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .user-card {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.132), 0 0.3px 0.9px rgba(0, 0, 0, 0.108);
            border: 1px solid #333333;
            overflow: hidden;
            transition: all 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
            position: relative;
        }

        .user-card::before {
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

        .user-card:hover {
            box-shadow: 0 2.8px 5.6px rgba(0, 0, 0, 0.132), 0 0.7px 1.8px rgba(0, 0, 0, 0.108);
            transform: translateY(-2px);
        }

        .user-card:hover::before {
            opacity: 1;
        }

        .user-header {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #333333;
            background: #000000;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 16px;
            color: white;
            background: #000000;
            border: 2px solid #ffffff;
            font-weight: 600;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 14px;
            color: #cccccc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-status {
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

        .user-status.active {
            background: rgba(56, 142, 60, 0.1);
            color: #107c10;
            border-color: rgba(56, 142, 60, 0.3);
        }

        .user-status.inactive {
            background: rgba(255, 185, 0, 0.1);
            color: #b08d00;
            border-color: rgba(255, 185, 0, 0.3);
        }

        .user-meta {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1a1a1a;
        }

        .user-id {
            font-size: 12px;
            color: #cccccc;
            font-weight: 500;
        }

        .user-role {
            font-size: 12px;
            color: #cccccc;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .user-role::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0078D4;
        }

        .user-actions {
            padding: 16px 20px;
            display: flex;
            gap: 8px;
            background: #000000;
        }

        .user-actions .admin-btn {
            flex: 1;
            justify-content: center;
            padding: 8px 12px;
            font-size: 13px;
        }

        .admin-search-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            align-items: center;
        }

        .admin-search-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            background: #000000;
            color: #ffffff;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        .admin-search-input:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        .admin-btn {
            padding: 8px 16px;
            border: 1px solid #333333;
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
            border: 1px solid #107c10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .admin-btn-success:hover {
            background: #333333;
            color: #ffffff;
            border-color: #107c10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .admin-btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #333333;
            font-weight: 600;
            color: #ffffff;
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
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .admin-badge.success {
            background: #d4edda;
            color: #155724;
        }

        .admin-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .admin-badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .admin-actions {
            display: flex;
            gap: 5px;
        }

        .admin-pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .admin-pagination a {
            padding: 8px 12px;
            border: 1px solid #333333;
            border-radius: 5px;
            text-decoration: none;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .admin-pagination a:hover {
            background: #0078D4;
            color: white;
            border-color: #0078D4;
        }

        .admin-pagination a.active {
            background: #0078D4;
            color: white;
            border-color: #0078D4;
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
            
            .admin-search-bar {
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
                    <i class="fas fa-users" style="color: #003366; font-size: 32px;"></i>
                </div>
                <div class="hero-text">
                    <h1>Users Management</h1>
                    <p>Manage and monitor all registered users in the system</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-users"></i>
                            <?php echo $total_users ?? 0; ?> Total Users
                        </span>
                        <span class="hero-stat">
                            <i class="fas fa-user-check"></i>
                            <?php echo $active_users ?? 0; ?> Active
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

            <!-- Users Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Users (<?php echo $total_users; ?>)</h3>
                </div>
                <div class="admin-card-body">
                    <!-- Search Bar -->
                    <form method="GET" class="admin-search-bar">
                        <input type="text" name="search" class="admin-search-input" 
                               placeholder="Search users by name or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="users.php" class="admin-btn admin-btn-danger">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- Users Cards -->
                    <div class="user-cards">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-5" style="grid-column: 1 / -1;">
                                <div style="width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: #1a1a1a; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-users" style="font-size: 32px; color: #cccccc;"></i>
                                </div>
                                <h3 style="color: #ffffff; margin-bottom: 8px;">No Users Found</h3>
                                <p style="color: #cccccc; margin-bottom: 20px;">No users match your current search criteria.</p>
                                <a href="users.php" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-times"></i> Clear Search
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($users as $user_item): ?>
                            <div class="user-card">
                                <div class="user-header">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user_item['name'], 0, 1)); ?>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user_item['name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user_item['email']); ?></div>
                                    </div>
                                    <div class="user-status <?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'active' : 'inactive'; ?>">
                                        <?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'Verified' : 'Not Verified'; ?>
                                    </div>
                                </div>
                                <div class="user-meta">
                                    <div class="user-id">ID: <?php echo $user_item['id']; ?></div>
                                    <div class="user-role">
                                        <?php echo ucfirst($user_item['role'] ?? 'user'); ?>
                                    </div>
                                </div>
                                <div class="user-actions">
                                    <?php if ($user_item['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline; flex: 1;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user_item['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'active' : 'inactive'; ?>">
                                            <button type="submit" class="admin-btn admin-btn-sm <?php 
                                                echo ($user_item['is_verified'] ?? 0) == 1 ? 'admin-btn-warning' : 'admin-btn-success'; 
                                            ?>" style="flex: 1;">
                                                <i class="fas fa-<?php 
                                                    echo ($user_item['is_verified'] ?? 0) == 1 ? 'ban' : 'check'; 
                                                ?>"></i>
                                                <?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'Unverify' : 'Verify'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline; flex: 1;" 
                                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user_item['id']; ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" style="flex: 1;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="admin-badge info" style="flex: 1; text-align: center;">Current User</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="admin-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <a href="#" class="active"><?php echo $i; ?></a>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
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

