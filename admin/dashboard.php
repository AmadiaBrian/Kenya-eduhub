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
$recent_uploads = 0;
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

    // Recent uploads (last 7 days)
    $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
    $stmt = $conn->prepare("SELECT COUNT(*) as recent_uploads FROM resources WHERE created_at >= ?");
    $stmt->bind_param("s", $one_week_ago);
    $stmt->execute();
    $recent_uploads = $stmt->get_result()->fetch_assoc()['recent_uploads'];

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
    $recent_uploads = 0;
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
            --primary-gold: #ffc107;
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
            --secondary-color: #ffffff;
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

        .dark-mode .nav-link {
            color: #ffffff;
        }
        
        .dark-mode .dark-mode-toggle {
            color: #ffc107;
        }

        .dark-mode .dark-mode-toggle:hover {
            background: rgba(255, 193, 7, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px !important;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
        }

        .stat-card {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            transition: box-shadow 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 4px;
        }

        .stat-card p {
            font-size: 14px;
            color: #5f6368;
            margin: 0;
            font-weight: 500;
        }

        .stat-card i {
            color: var(--primary-orange);
        }

        .stat-card > div > div span {
            color: #5f6368 !important;
        }

        .stat-card > div > div span[style*="font-weight: 500"] {
            color: #202124 !important;
        }

        .stat-card > div[style*="border-top"] {
            border-top: 1px solid #e8eaed !important;
        }

        .stat-card span[style*="background: rgba(255, 255, 255, 0.2)"] {
            background: #f1f3f4 !important;
            color: #202124 !important;
        }

        /* Dark mode stat cards */
        .dark-mode .stat-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .stat-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        }

        .dark-mode .stat-card h3 {
            color: #ffffff;
            text-shadow: none;
        }

        .dark-mode .stat-card p {
            color: rgba(255, 255, 255, 0.8);
        }

        .dark-mode .stat-card i {
            color: var(--primary-orange) !important;
            filter: none;
        }

        .dark-mode .stat-card > div > div span {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .dark-mode .stat-card > div > div span[style*="font-weight: 500"] {
            color: #ffffff !important;
        }

        .dark-mode .stat-card > div[style*="border-top"] {
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .dark-mode .stat-card span[style*="background: rgba(255, 255, 255, 0.2)"] {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }

        /* Dark mode stat card inline style overrides */
        .dark-mode .stat-card i[style*="color: #FF6B35"] {
            color: var(--primary-orange) !important;
        }

        .dark-mode .stat-card h3 {
            color: #ffffff !important;
        }

        .dark-mode .stat-card p {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .dark-mode .stat-card span[style*="color: #5f6368"] {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .dark-mode .stat-card span[style*="color: #202124"] {
            color: #ffffff !important;
        }

        .dark-mode .stat-card div[style*="border-top: 1px solid #e8eaed"] {
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .dark-mode .stat-card span[style*="background: #f1f3f4"] {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }

        .dark-mode .stat-card {
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Dark mode stat card inline style overrides for new card */
        .dark-mode .stat-card i[style*="color: #FF6B35"] {
            color: var(--primary-orange) !important;
        }

        .dark-mode .stat-card > div[style*="border-top: 1px solid #e8eaed"] {
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .dark-mode .stat-card > div > div span[style*="color: #5f6368"] {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .dark-mode .stat-card > div > div span[style*="color: #202124"] {
            color: #ffffff !important;
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

        .dark-mode input[type="text"],
        .dark-mode input[type="email"],
        .dark-mode input[type="password"],
        .dark-mode select,
        .dark-mode textarea {
            border-color: var(--primary-gold);
        }

        .dark-mode input[type="text"]:focus,
        .dark-mode input[type="email"]:focus,
        .dark-mode input[type="password"]:focus,
        .dark-mode select:focus,
        .dark-mode textarea:focus {
            border-color: var(--primary-gold);
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
            text-align: center;
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
            font-size: 18px;
        }

        .menu-btn:hover {
            background: #f1f3f4;
        }

        .dark-mode .menu-btn {
            color: #ffc107;
            font-size: 22px;
        }

        .dark-mode .menu-btn:hover {
            background: rgba(255, 193, 7, 0.1);
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
            justify-content: center;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
            text-align: center;
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
            width: 100%;
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
            background: #f1f3f4;
            color: #202124;
            border: 1px solid #000;
            padding: 8px 16px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            background: var(--primary-orange);
            color: white;
            border-color: #000;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.5);
        }

        .btn-download:active {
            transform: translateY(0) scale(0.98);
        }

        /* Dark mode download button styling */
        .dark-mode .btn-download {
            background: #252525;
            color: #ffffff;
            border: 1px solid #ffc107;
        }

        .dark-mode .btn-download:hover {
            background: var(--primary-orange);
            color: white;
            border-color: var(--primary-gold);
        }

        /* Upload buttons styling */
        .upload-buttons-container button[type="submit"] {
            background: var(--primary-orange);
            color: white;
            border: 1px solid var(--primary-orange);
            padding: 10px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .upload-buttons-container button[type="submit"]:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }

        .upload-buttons-container button[type="reset"] {
            background: #f1f3f4;
            color: #202124;
            border: 1px solid #000;
            padding: 10px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .upload-buttons-container button[type="reset"]:hover {
            background: #e8eaed;
            transform: translateY(-2px);
        }

        .dark-mode .upload-buttons-container button[type="reset"] {
            background: #252525;
            color: #ffffff;
            border: 1px solid #ffc107;
        }

        .dark-mode .upload-buttons-container button[type="reset"]:hover {
            background: #3a3a3a;
        }

        /* Mobile upload buttons */
        @media (max-width: 768px) {
            .upload-buttons-container {
                flex-direction: column !important;
                gap: 12px !important;
            }

            .upload-buttons-container button {
                width: 100% !important;
                padding: 12px 16px !important;
                font-size: 14px !important;
            }

            .upload-buttons-container button[type="submit"] {
                background: var(--primary-orange) !important;
                color: white !important;
                border: 1px solid var(--primary-orange) !important;
            }

            .upload-buttons-container button[type="reset"] {
                background: #252525 !important;
                color: #ffffff !important;
                border: 1px solid var(--primary-gold) !important;
            }

            /* Dark mode upload buttons */
            .dark-mode .upload-buttons-container button[type="submit"] {
                background: var(--primary-orange) !important;
                color: white !important;
                border: 1px solid var(--primary-orange) !important;
            }

            .dark-mode .upload-buttons-container button[type="reset"] {
                background: #252525 !important;
                color: #ffffff !important;
                border: 1px solid var(--primary-gold) !important;
            }

            /* View all button mobile */
            .view-all-button {
                width: 100% !important;
                text-align: center;
            }

            /* Recent resources header mobile */
            div[style*="justify-content: center"] + div[style*="justify-content: center"] {
                flex-direction: column !important;
                gap: 16px !important;
            }
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

        /* PDF-style table */
        .pdf-table {
            border: 2px solid #000;
            border-collapse: collapse;
            background: #ffffff;
            font-family: 'Times New Roman', Times, serif;
        }

        .pdf-table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .pdf-table th {
            border: 1px solid #000;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            background: #f0f0f0;
        }

        .pdf-table td {
            border: 1px solid #000;
            padding: 12px 15px;
            font-size: 12px;
            color: #000;
            font-family: 'Times New Roman', Times, serif;
        }

        .pdf-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .pdf-table tbody tr:hover {
            background: #e8e8e8;
        }

        /* Dark mode PDF table */
        .dark-mode .pdf-table {
            border: 2px solid var(--primary-gold);
            background: #1a1a1a;
        }

        .dark-mode .pdf-table thead {
            background: #252525;
            border-bottom: 2px solid var(--primary-gold);
        }

        .dark-mode .pdf-table th {
            border: 1px solid #ffc107;
            color: #ffffff;
            background: #252525;
        }

        .dark-mode .pdf-table td {
            border: 1px solid #ffc107;
            color: #ffffff;
        }

        .dark-mode .pdf-table tbody tr:nth-child(even) {
            background: #2a2a2a;
        }

        .dark-mode .pdf-table tbody tr:hover {
            background: #3a3a3a;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require_once 'includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php 
    $active_page = 'dashboard';
    require_once 'includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Dashboard</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-users" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-folder-open" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $total_resources; ?></h3>
                        <p>Total Resources</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-download" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $total_downloads; ?></h3>
                        <p>Total Downloads</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-clock" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $recent_uploads; ?></h3>
                        <p>Recent Uploads</p>
                    </div>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e8eaed;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #5f6368; font-size: 12px;">Last 7 Days</span>
                        <span style="color: #202124; font-weight: 500; font-size: 12px;">Activity</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Users</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="pdf-table">
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
            <div style="display: flex; justify-content: center; margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color);">Upload Resource</h2>
            </div>
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
                    <div style="margin-top: 24px; display: flex; gap: 12px;" class="upload-buttons-container">
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Upload Resource
                        </button>
                        <button type="reset" class="btn btn-secondary" id="clearBtn">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
                <div id="uploadMessage" style="margin-top: 16px; padding: 16px; border-radius: 8px; display: none;"></div>
        </div>
        
        <!-- Recent Resources -->
        <div style="margin-top: 30px;">
            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color);">Recent Resources</h2>
            </div>
            <div style="display: flex; justify-content: center; margin-bottom: 24px;">
                <a href="resources" class="btn btn-primary view-all-button">View All</a>
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

            // Use fetch to download the file (single call, handles errors properly)
            const downloadUrl = `../api/download.php?id=${resourceId}&download=true`;

            fetch(downloadUrl, {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Download failed');
                    });
                }

                // Get the content disposition header for the filename
                const contentDisposition = response.headers.get('Content-Disposition');
                let filename = 'resource_' + resourceId;

                if (contentDisposition) {
                    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (filenameMatch && filenameMatch[1]) {
                        filename = filenameMatch[1].replace(/['"]/g, '');
                    }
                }

                // Return the blob for successful downloads along with filename
                return response.blob().then(blob => ({ blob, filename }));
            })
            .then(({ blob, filename }) => {
                // Create a download link and trigger it
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                // Reset button after a short delay
                setTimeout(() => {
                    button.classList.remove('btn-loading');
                    button.innerHTML = '<i class="fas fa-download"></i> Download';
                    button.disabled = false;
                }, 2000);
            })
            .catch(error => {
                // Show user-friendly error message in the dashboard
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f44336; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10000; transition: all 0.3s ease;';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${error.message}`;
                document.body.appendChild(errorDiv);

                // Reset button state
                button.classList.remove('btn-loading');
                button.innerHTML = '<i class="fas fa-download"></i> Download';
                button.disabled = false;

                // Remove error message after 5 seconds
                setTimeout(() => {
                    errorDiv.style.opacity = '0';
                    errorDiv.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        document.body.removeChild(errorDiv);
                    }, 300);
                }, 5000);
            });
        }
    </script>
</body>
</html>
