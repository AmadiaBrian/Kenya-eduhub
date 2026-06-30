<?php
session_start();

// Include security first
require_once '../includes/security_lite.php';

require_once '../config.php';
require_once '../includes/helpers.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Log dashboard access
logActivity('DASHBOARD_ACCESS', 'User accessed main dashboard', [
    'page' => 'dashboard/index.php',
    'user_role' => $_SESSION['user_role'] ?? 'unknown'
]);

// Security: Rate limiting for dashboard access
$dashboard_identifier = $_SERVER['REMOTE_ADDR'] . '_' . $_SESSION['user_id'] . '_dashboard';
if (!checkRateLimit($dashboard_identifier, 100, 300)) { // 100 requests per 5 minutes
    http_response_code(429);
    echo "Too many requests. Please try again later.";
    exit();
}

// Custom Header

// Output CSRF token to JavaScript for AJAX requests
$csrf_token = generateCSRFLite();
echo '<script>window.currentCSRFToken = "' . $csrf_token . '";</script>';

?>
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

<style>
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

/* Hero section matching colors */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: var(--gov-spacing-2xl) 0;
    position: relative;
    overflow: hidden;
    border-radius: var(--gov-border-radius-lg);
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0.1;
    z-index: 1;
    animation: imageCycle 12s infinite ease-in-out;
    backdrop-filter: blur(8px);
    transition: all 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: var(--gov-container-max);
    margin: 0 auto;
    padding: 0 var(--gov-spacing-md);
    text-align: center;
}

.hero-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(45deg, #FFD700, #FFA500);
    border: 2px solid #000000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 24px;
    color: white;
    backdrop-filter: blur(10px);
}

.hero-text {
    flex: 1;
    backdrop-filter: blur(5px);
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--gov-border-radius-lg);
    padding: var(--gov-spacing-md);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.hero-text h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 700;
    margin-bottom: var(--gov-spacing-lg);
    line-height: 1.2;
    color: var(--gov-white);
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.hero-text p {
    font-size: clamp(1.1rem, 2vw, 1.5rem);
    opacity: 0.9;
    margin-bottom: var(--gov-spacing-lg);
    line-height: 1.5;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--gov-spacing-lg);
    margin: var(--gov-spacing-2xl) 0;
    padding: var(--gov-spacing-xl);
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--gov-border-radius-lg);
    backdrop-filter: blur(10px);
}

.hero-stat {
    text-align: center;
    padding: var(--gov-spacing-md);
}

.hero-stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--gov-white);
    display: block;
    margin-bottom: var(--gov-spacing-sm);
}

.hero-stat-label {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
}

.cta-buttons {
    display: flex;
    gap: var(--gov-spacing-md);
    justify-content: center;
    margin-top: var(--gov-spacing-xl);
}

.cta-button {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--gov-white);
    text-decoration: none;
    padding: var(--gov-spacing-md) var(--gov-spacing-xl);
    border-radius: var(--gov-border-radius-lg);
    font-weight: 600;
    transition: all 0.3s ease;
}

.cta-button:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #FFD700;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
}

/* Mobile Hero Section */
@media (max-width: 768px) {
    .hero-section {
        padding: var(--gov-spacing-xl) var(--gov-spacing-lg);
        margin-bottom: var(--gov-spacing-lg);
    }

    .hero-content {
        flex-direction: column;
        text-align: center;
        gap: var(--gov-spacing-lg);
    }

    .hero-avatar {
        width: 60px;
        height: 60px;
        font-size: 20px;
        font-weight: bold;
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        border: 2px solid #000000;
    }

    .hero-text h1 {
        font-size: clamp(2rem, 4vw, 3rem);
    }

    .hero-text p {
        font-size: clamp(1rem, 1.8vw, 1.3rem);
        margin-bottom: var(--gov-spacing-md);
    }

    .hero-stats {
        grid-template-columns: 1fr;
        gap: var(--gov-spacing-md);
        margin: 0;
        padding: var(--gov-spacing-lg);
    }
}

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
        border: 2px solid #000000;
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
</style>

<?php

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info using MySQLi (like original)
if (!isset($conn)) {
    die("Database connection not established. Please check your configuration.");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize variables
$total_users = 0;
$total_resources = 0;
$total_downloads = 0;
$recent_users = [];
$recent_resources = [];
$resources = [];
$user_resources = [];
$error = '';

// Get dashboard statistics
try {
    // Log data access
    logActivity('DATA_ACCESS', 'Accessed dashboard statistics and data', [
        'operation' => 'dashboard_stats',
        'tables_accessed' => ['users', 'resources', 'downloads']
    ]);
    
    // Total resources
    $stmt = $conn->prepare("SELECT COUNT(*) as total_resources FROM resources");
    $stmt->execute();
    $total_resources = $stmt->get_result()->fetch_assoc()['total_resources'];
    
    // Total downloads
    $stmt = $conn->prepare("SELECT SUM(downloads) as total_downloads FROM resources");
    $stmt->execute();
    $total_downloads = $stmt->get_result()->fetch_assoc()['total_downloads'] ?? 0;
    
    // Recent resources
    $stmt = $conn->prepare("SELECT * FROM resources ORDER BY created_at DESC");
    $stmt->execute();
    $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get user-specific resources (check if user_id column exists first)
    $column_check = $conn->query("SHOW COLUMNS FROM resources LIKE 'user_id'");
    if ($column_check && $column_check->num_rows > 0) {
        // user_id column exists, filter by user
        $stmt = $conn->prepare("SELECT * FROM resources WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // user_id column doesn't exist yet, show empty array
        $user_resources = [];
    }
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
    // Keep variables as empty arrays/zero values
    $resources = [];
    $user_resources = [];
}

// Get recent downloads (this table might not exist yet, so we'll handle gracefully)
$recent_downloads = [];
// Check if downloads table exists
$downloads_check = $conn->query("SHOW TABLES LIKE 'downloads'");
if ($downloads_check && $downloads_check->num_rows > 0) {
    $sql = "SELECT d.*, r.title, r.filename 
            FROM downloads d 
            JOIN resources r ON d.resource_id = r.id 
            WHERE d.user_id = ? 
            ORDER BY d.download_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $recent_downloads = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get user statistics from database data
$user_stats = [
    'total_resources' => count($user_resources),
    'total_downloads' => $total_downloads,
    'recent_activity' => count($recent_downloads)
];

// Check if user can download (must have uploaded at least 2 resources)
$can_download = count($user_resources) >= 2;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Dashboard - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --ms-text-secondary: #ffffff;
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
            background: #000000;
            color: #ffffff;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Dark Sidebar */
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
        .main-content {
            margin-left: 220px;
            padding: 24px;
            background: #000000;
            min-height: 100vh;
        }

        /* Dark Header */
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
            color: #ffffff;
            font-size: 14px;
            font-weight: 400;
        }

        .header .text-muted {
            color: #ffffff !important;
            opacity: 1 !important;
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
            background: #000000;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
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
            color: #ffffff;
        }

        .user-info .text-muted {
            font-size: 12px;
            color: #ffffff;
        }

        /* Dark Hero Section */
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
            border: 2px solid #000000;
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

        /* Download Status Indicators */
        .hero-stat.download-allowed {
            background: rgba(16, 124, 16, 0.2);
            border: 1px solid rgba(16, 124, 16, 0.5);
            color: #107c10;
        }

        .hero-stat.download-restricted {
            background: rgba(255, 140, 0, 0.2);
            border: 1px solid rgba(255, 140, 0, 0.5);
            color: #ff8c00;
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
                border: 2px solid #000000;
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

        /* Dark Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #1a1a1a;
            border-radius: 4px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            border: 1px solid #333333;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: 0 8px 16px var(--ms-shadow-medium);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
            position: relative;
        }

        .stat-icon.primary {
            background: transparent;
            color: #0078D4;
            border: 2px solid #0078D4;
        }

        .stat-icon.success {
            background: #d4edda;
            color: var(--ms-success);
        }

        .stat-icon.warning {
            background: #fff4e6;
            color: var(--ms-warning);
        }

        .stat-icon.info {
            background: #e1f5fe;
            color: #0288d1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }

        .stat-label {
            color: #cccccc;
            font-size: 14px;
            font-weight: 400;
        }

        /* Dark Content Cards */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .card {
            background: #1a1a1a;
            border-radius: 4px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            border: 1px solid #333333;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #333333;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        /* Dark Resource Cards */
        .resource-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .resource-card {
            background: #1a1a1a;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            border: 1px solid #333333;
            position: relative;
            overflow: hidden;
        }

        .resource-card:hover {
            box-shadow: 0 8px 16px var(--ms-shadow-medium);
            transform: translateY(-2px);
            border-color: var(--ms-primary);
        }

        .resource-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .resource-icon {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .resource-icon.pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .resource-icon.doc {
            background: #fef3c7;
            color: #d97706;
        }

        .resource-icon.ppt {
            background: #dbeafe;
            color: #2563eb;
        }

        .resource-icon.xls {
            background: #d1fae5;
            color: #059669;
        }

        .resource-icon.default {
            background: var(--ms-primary-light);
            color: var(--ms-primary);
        }

        .resource-info {
            flex: 1;
            min-width: 0;
        }

        .resource-uploader {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #333333;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .resource-uploader small {
            font-size: 11px;
            line-height: 1.3;
            color: #888888;
        }

        .resource-uploader strong {
            color: var(--primary-gold);
        }

        .badge-my-upload {
            background: #008000;
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resource-title {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            font-size: 16px;
            letter-spacing: -0.01em;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .resource-subject {
            color: #cccccc;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resource-description {
            color: #cccccc;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .resource-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #333333;
            margin-bottom: 16px;
        }

        .resource-meta-left {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: #cccccc;
        }

        .resource-meta-left span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .resource-meta-left i {
            font-size: 11px;
            opacity: 0.7;
        }

        .resource-stats {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #cccccc;
        }

        .resource-actions {
            display: flex;
            gap: 8px;
        }

        .btn-download {
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            flex: 1;
            justify-content: center;
        }

        .btn-download:hover {
            background: #333333;
            border-color: #ffffff;
            box-shadow: 0 2px 4px rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .btn-download:active {
            transform: translateY(0);
        }

        .btn-view {
            background: transparent;
            color: var(--ms-primary);
            border: 1px solid var(--ms-primary);
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            flex: 1;
            justify-content: center;
        }

        .btn-view:hover {
            background: var(--ms-primary-light);
            color: var(--ms-primary-dark);
        }

        .resource-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--ms-primary);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resource-badge.secondary {
            background: #000000;
            color: #fff;
        }

        /* Loading state */
        .btn-loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-loading::after {
            content: '';
            width: 12px;
            height: 12px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Microsoft-style Buttons */
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
            color: #ffffff;
            border-color: #ffffff;
        }

        .btn-success {
            background: #000000;
            color: #ffffff;
            border: 1px solid #ffffff;
        }

        .btn-success:hover {
            background: #333333;
            border-color: #ffffff;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            width: 48px;
            height: 48px;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 1001;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
            padding: 12px;
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
                padding: 12px;
                padding-top: 70px;
            }

            .content-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 16px;
            }

            /* Mobile Resource Cards */
            .resource-cards {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .resource-card {
                padding: 16px;
                border-radius: 8px;
            }

            .resource-header {
                gap: 12px;
                margin-bottom: 12px;
            }

            .resource-icon {
                width: 40px;
                height: 40px;
                flex-shrink: 0;
            }

            .resource-info h4 {
                font-size: 14px;
                line-height: 1.3;
                margin-bottom: 4px;
            }

            .resource-meta {
                font-size: 11px;
                flex-wrap: wrap;
                gap: 4px;
            }

            .resource-description {
                font-size: 12px;
                line-height: 1.4;
                margin-bottom: 12px;
            }

            .resource-actions {
                flex-direction: column;
                gap: 8px;
            }

            .resource-actions .btn {
                width: 100%;
                justify-content: center;
                padding: 8px 12px;
                font-size: 12px;
            }

            /* Mobile Stats Cards */
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 20px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .stat-icon {
                margin-bottom: 12px;
                margin-left: auto;
                margin-right: auto;
            }

            .stat-value {
                font-size: 24px;
                text-align: center;
                margin-bottom: 6px;
            }

            .stat-label {
                font-size: 12px;
                text-align: center;
                line-height: 1.3;
            }

            /* Mobile Header */
            .header h1 {
                font-size: 20px;
            }

            .header p {
                font-size: 14px;
            }

            /* Mobile Search and Filters */
            .search-filter-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .search-box {
                padding: 12px 16px;
                font-size: 14px;
            }

            .filter-dropdown {
                padding: 12px 16px;
                font-size: 14px;
            }

            /* Mobile Buttons */
            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }

            .btn-sm {
                padding: 8px 12px;
                font-size: 12px;
            }

            /* Mobile Welcome Section */
            .welcome-header h2 {
                font-size: 18px;
            }

            .welcome-header p {
                font-size: 14px;
            }

            .quick-actions {
                flex-direction: column;
                gap: 8px;
            }

            .quick-actions .btn {
                width: 100%;
            }
        }

        /* Extra Small Mobile (320px and below) */
        @media (max-width: 480px) {
            .main-content {
                padding: 8px;
                padding-top: 60px;
            }

            .resource-card {
                padding: 12px;
            }

            .resource-header {
                gap: 8px;
            }

            .resource-icon {
                width: 32px;
                height: 32px;
            }

            .resource-info h4 {
                font-size: 13px;
            }

            .resource-meta {
                font-size: 10px;
            }

            .resource-description {
                font-size: 11px;
            }

            .header {
                padding: 12px;
            }

            .header h1 {
                font-size: 18px;
            }

            .stat-card {
                padding: 16px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
                margin-bottom: 10px;
                margin-left: auto;
                margin-right: auto;
            }

            .stat-value {
                font-size: 20px;
                text-align: center;
                margin-bottom: 4px;
            }

            .stat-label {
                font-size: 11px;
                text-align: center;
                line-height: 1.2;
            }
        }

        /* Microsoft-style Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        .fade-in {
            animation: fadeIn 0.267s cubic-bezier(0.1, 0.9, 0.2, 1);
        }

        /* Microsoft-style Empty States */
        .text-center.py-4 {
            text-align: center;
            padding: 32px 16px;
        }

        .fa-3x {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .text-muted {
            color: #ffffff;
            margin-bottom: 16px;
            opacity: 1;
        }

        /* Microsoft-style Grid */
        .d-grid {
            display: grid;
            gap: 12px;
        }

        .d-grid.gap-3 {
            gap: 12px;
        }

        /* Upload Form Styles */
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
            color: #ffffff;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #1a1a1a;
            color: #ffffff;
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
            border: 2px dashed #333333;
            border-radius: 4px;
            padding: 32px;
            text-align: center;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            cursor: pointer;
            background: #1a1a1a;
        }

        .file-upload-area:hover {
            border-color: #0078D4;
            background: rgba(0, 120, 212, 0.05);
        }

        .file-upload-area.has-file {
            border-color: #107c10;
            background: rgba(16, 124, 16, 0.05);
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
            color: #cccccc;
            margin-bottom: 16px;
            display: block;
        }

        .file-upload-label span {
            display: block;
            color: #ffffff;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .file-upload-label small {
            color: #cccccc;
            font-size: 12px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid #333333;
        }

        .upload-message {
            margin-top: 16px;
            padding: 16px;
            border-radius: 4px;
            font-size: 14px;
        }

        .upload-message.success {
            background: rgba(16, 124, 16, 0.1);
            border: 1px solid #107c10;
            color: #107c10;
        }

        .upload-message.error {
            background: rgba(196, 43, 28, 0.1);
            border: 1px solid #d13438;
            color: #d13438;
        }

        .btn-loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Search and Filter Styles */
        .search-filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .search-box {
            grid-column: span 1;
        }

        .input-group {
            display: flex;
            gap: 8px;
        }

        .input-group input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #1a1a1a;
            color: #ffffff;
        }

        .input-group input:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        .input-group .btn {
            padding: 12px 16px;
            border-radius: 4px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .filter-group select {
            padding: 12px 16px;
            border: 1px solid #333333;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.167s cubic-bezier(0.1, 0.9, 0.2, 1);
            background: #1a1a1a;
            color: #ffffff;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
        }

        .filter-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--ms-border);
        }

        .results-count {
            color: var(--ms-text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
            }
            
            .search-filter-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .search-box {
                grid-column: span 1;
            }
            
            .filter-actions {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
        }

        @media (max-width: 1024px) {
            .search-filter-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .search-box {
                grid-column: span 2;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Sidebar -->
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
            <a href="index.php" class="menu-item active">
                <i class="fas fa-dashboard"></i> Dashboard
            </a>
            <a href="index.php#resourcesSection" class="menu-item">
                <i class="fas fa-book"></i> My Resources
            </a>
            <a href="index.php#uploadSection" class="menu-item">
                <i class="fas fa-upload"></i> Upload Resource
            </a>
            <a href="https://sites.google.com/view/noteselectricalengineering/home" target="_blank" class="menu-item">
                <i class="fas fa-briefcase"></i> More Resources
            </a>
                        <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header fade-in">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'User'); ?>!</h1>
                <p class="text-muted mb-0">View your profile and manage your account settings</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? $user['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'User'); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
        </header>

        <!-- Professional Hero Section -->
        <div class="hero-section fade-in">
            <div class="hero-content">
                <div class="hero-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? $user['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="hero-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'User'); ?>!</h1>
                    <p>View your profile and manage your account settings</p>
                    <div class="hero-stats">
                        <span class="hero-stat">
                            <i class="fas fa-book"></i>
                            <?php echo count($resources); ?> Resources
                        </span>
                        <span class="hero-stat <?php echo $can_download ? 'download-allowed' : 'download-restricted'; ?>">
                            <i class="fas fa-<?php echo $can_download ? 'unlock' : 'lock'; ?>"></i>
                            <?php echo $can_download ? 'Downloads Unlocked' : 'Downloads Locked'; ?>
                        </span>
                        <span class="hero-stat">
                            <i class="fas fa-upload"></i>
                            <?php echo count($user_resources); ?>/2 Uploaded
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$can_download): ?>
        <!-- Download Restriction Notice -->
        <div class="alert alert-warning fade-in" style="background: linear-gradient(135deg, #fff3cd, #fef5e7); border: 1px solid #ffeaa7; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2); display: flex; align-items: center; gap: 16px;">
            <div style="font-size: 24px; color: #ff8c00;">
                <i class="fas fa-lock"></i>
            </div>
            <div style="flex: 1;">
                <h5 style="margin: 0 0 8px 0; color: #856404; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle"></i> Downloads Restricted
                </h5>
                <p style="margin: 0; color: #856404; font-size: 14px; line-height: 1.5;">
                    You need to upload at least <strong>2 resources</strong> to unlock downloads. 
                    You have uploaded <strong><?php echo count($user_resources); ?></strong> resource(s). 
                    <a href="#uploadSection" style="color: #0078d4; text-decoration: none; font-weight: 600;">Upload more resources</a> to gain access to all downloads.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-value"><?php echo count($resources); ?></div>
                <div class="stat-label">Total Resources</div>
            </div>
        </div>

        <!-- Upload Resource Section -->
        <div class="card fade-in" id="uploadSection">
            <div class="card-header">
                <h3 class="card-title">Upload New Resource</h3>
                <?php if (!$can_download): ?>
                <small class="text-warning">
                    <i class="fas fa-info-circle"></i> 
                    Upload <?php echo (2 - count($user_resources)); ?> more resource(s) to unlock downloads!
                </small>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFLite(); ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Resource Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="level">Education Level *</label>
                            <select id="level" name="level" required>
                                <option value="">Select Level</option>
                                <option value="Primary">Primary School</option>
                                <option value="Secondary">Secondary School</option>
                                <option value="College">College</option>
                                <option value="University">University</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required placeholder="e.g., Mathematics, English, Science">
                        </div>
                        
                        <div class="form-group">
                            <label for="type">File Type *</label>
                            <select id="type" name="type" required>
                                <option value="">Select File Type</option>
                                <option value="PDF">PDF Document</option>
                                <option value="DOC">Word Document (.doc/.docx)</option>
                                <option value="PPT">PowerPoint (.ppt/.pptx)</option>
                                <option value="XLS">Excel Spreadsheet (.xls/.xlsx)</option>
                                <option value="TXT">Text File (.txt)</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="3" required placeholder="Brief description of the resource..."></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="file">Upload File *</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt" required>
                                <div class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to browse or drag and drop</span>
                                    <small>PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Upload Resource
                        </button>
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
                
                <div id="uploadMessage" class="upload-message" style="display: none;"></div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title">Search & Filter Resources</h3>
            </div>
            <div class="card-body">
                <div class="search-filter-grid">
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" id="searchInput" placeholder="Search resources by title, subject, or description...">
                            <button class="btn btn-primary" onclick="searchResources()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <select id="filterLevel" onchange="filterResources()">
                            <option value="">All Levels</option>
                            <option value="Primary">Primary School</option>
                            <option value="Secondary">Secondary School</option>
                            <option value="College">College</option>
                            <option value="University">University</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select id="filterType" onchange="filterResources()">
                            <option value="">All File Types</option>
                            <option value="PDF">PDF</option>
                            <option value="DOC">Word Document</option>
                            <option value="PPT">PowerPoint</option>
                            <option value="XLS">Excel</option>
                            <option value="TXT">Text File</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select id="sortBy" onchange="sortResources()">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="title">Title (A-Z)</option>
                            <option value="downloads">Most Downloaded</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button class="btn btn-outline btn-sm" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                    <span class="results-count" id="resultsCount">Showing all resources</span>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid fade-in">
            <!-- All Resources -->
            <div class="card" id="resourcesSection">
                <div class="card-header">
                    <h3 class="card-title">All Resources</h3>
                </div>
                <div class="resource-cards">
                    <?php if (empty($resources)): ?>
                        <div class="text-center py-4" style="grid-column: 1 / -1;">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No resources available yet</p>
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
                            <div class="resource-card" data-filename="<?php echo htmlspecialchars(basename($resource['filename'])); ?>" data-user-id="<?php echo $resource['user_id'] ?? ''; ?>">
                                <div class="resource-header">
                                    <div class="resource-icon <?php echo $iconClass; ?>">
                                        <i class="fas <?php echo $iconFa; ?>"></i>
                                    </div>
                                    <div class="resource-info">
                                        <div class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></div>
                                        <div class="resource-subject"><?php echo htmlspecialchars($resource['subject']); ?></div>
                                        <div class="resource-uploader">
                                            <small>Uploaded by <strong><?php echo $resource['user_id'] == $_SESSION['user_id'] ? 'You' : htmlspecialchars($resource['name'] ?? 'Unknown'); ?></strong></small>
                                            <?php if ($resource['user_id'] == $_SESSION['user_id']): ?>
                                            <span class="badge-my-upload">My Upload</span>
                                            <?php endif; ?>
                                        </div>
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
                                    <a href="#" class="btn-download" onclick="downloadResource(<?php echo $resource['id']; ?>, this, <?php echo $resource['user_id'] == $_SESSION['user_id'] ? 'true' : 'false'; ?>)">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="d-grid gap-3">
                    <a href="#uploadSection" class="btn btn-primary" onclick="scrollToUploadSection(event)">
                        <i class="fas fa-upload"></i> Upload New Resource
                    </a>
                    <a href="#resourcesSection" class="btn btn-outline" onclick="scrollToResourcesSection(event)">
                        <i class="fas fa-search"></i> Browse Resources
                    </a>
                    <a href="https://sites.google.com/view/noteselectricalengineering/home" target="_blank" class="btn btn-success">
                        <i class="fas fa-briefcase"></i> More Resources
                    </a>
                    <a href="#" class="btn btn-outline">
                        <i class="fas fa-chart-bar"></i> View Statistics
                    </a>
                    <a href="profile.php" class="btn btn-outline">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Your Resources -->
        <div class="card fade-in" style="margin-top: 32px;">
            <div class="card-header">
                <h3 class="card-title">Your Resources</h3>
                <a href="#uploadSection" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>
            <div class="resource-cards">
                <?php if (empty($user_resources)): ?>
                    <div class="text-center py-4" style="grid-column: 1 / -1;">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">You haven't uploaded any resources yet</p>
                        <a href="#uploadSection" class="btn btn-primary">Upload Your First Resource</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($user_resources as $resource): ?>
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
                        <div class="resource-card" data-filename="<?php echo htmlspecialchars(basename($resource['filename'])); ?>">
                            <span class="resource-badge secondary">Your Upload</span>
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
                                <a href="#" class="btn-download" onclick="downloadResource(<?php echo $resource['id']; ?>, this)">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

        // Add fade-in animation to elements
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                }, index * 100);
            });

            // Typewriter effect for hero heading
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

        // Handle menu item clicks
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.getAttribute('href') === '#') {
                    e.preventDefault();
                    // Remove active class from all items
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    // Add active class to clicked item
                    this.classList.add('active');
                }
            });
        });

        // Download Resource Function
        function downloadResource(resourceId, button, isMyUpload = false) {
            // Check if user has uploaded at least 2 resources or if this is their own upload
            const canDownload = <?php echo $can_download ? 'true' : 'false'; ?> || isMyUpload;
            const userResourceCount = <?php echo count($user_resources); ?>;
            
            if (!canDownload) {
                // Log failed download attempt
                fetch('../api/log_download_attempt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        resource_id: resourceId,
                        reason: 'download_restricted',
                        user_resource_count: userResourceCount
                    })
                }).catch(err => console.log('Logging failed:', err));
                
                // Show restriction message
                const card = button.closest('.resource-card');
                const existingMessage = card.querySelector('.download-message');
                
                if (!existingMessage) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'download-message';
                    messageDiv.style.cssText = 'background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px 16px; border-radius: 4px; margin-top: 8px; font-size: 13px; animation: slideIn 0.3s ease-out; display: flex; align-items: center; gap: 8px;';
                    messageDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>
                        <div>
                            <strong>Download Restricted</strong><br>
                            <span style="font-size: 12px; opacity: 0.9;">You must upload at least 2 resources to download (you have uploaded ${userResourceCount}). Upload more resources to unlock downloads.</span>
                        </div>
                    `;
                    card.appendChild(messageDiv);
                    
                    // Remove message after 8 seconds
                    setTimeout(() => {
                        if (messageDiv.parentNode) {
                            messageDiv.style.animation = 'slideOut 0.3s ease-out';
                            setTimeout(() => {
                                if (messageDiv.parentNode) {
                                    messageDiv.parentNode.removeChild(messageDiv);
                                }
                            }, 300);
                        }
                    }, 8000);
                }
                return;
            }
            
            // Add loading state
            button.classList.add('btn-loading');
            button.innerHTML = '<i class="fas fa-download"></i> Downloading...';

            // Log download attempt
            fetch('../api/log_download_attempt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resource_id: resourceId,
                    reason: 'download_attempt',
                    status: 'initiated'
                })
            }).catch(err => console.log('Logging failed:', err));

            // Get the resource info from the card
            const card = button.closest('.resource-card');
            const title = card.querySelector('.resource-title').textContent;
            const filename = card.dataset.filename || `resource_${resourceId}`;
            
            // Try direct download from uploads folder
            const directUrl = `../api/uploads/${filename}`;
            
            // Create download link
            const link = document.createElement('a');
            link.href = directUrl;
            
            // Extract file extension from the stored filename
            let fileExtension = 'pdf'; // default
            if (filename.includes('.')) {
                // Get the part after the last dot
                const parts = filename.split('.');
                fileExtension = parts[parts.length - 1].toLowerCase();
            }
            
            // Validate extension is one of the allowed types
            const allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'];
            if (!allowedExtensions.includes(fileExtension)) {
                fileExtension = 'pdf'; // fallback to pdf if unknown
            }
            
            // Use clean title with correct extension
            link.download = title.replace(/[^\w\s.-]/gi, '') + '.' + fileExtension;
            link.style.display = 'none';
            
            // Handle download success/failure
            link.onload = function() {
                resetDownloadButton(button);
                updateDownloadCount(resourceId, button);
            };
            
            link.onerror = function() {
                showDownloadMessage(button, 'File not found in uploads folder');
                resetDownloadButton(button);
                updateDownloadCount(resourceId, button);
            };
            
            // Try to download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Check if download started (fallback)
            setTimeout(() => {
                if (button.classList.contains('btn-loading')) {
                    // If still loading, assume it failed
                    showDownloadMessage(button, 'File not available for download');
                    resetDownloadButton(button);
                    updateDownloadCount(resourceId, button);
                }
            }, 2000);
        }

        function resetDownloadButton(button) {
            // Reset button state
            setTimeout(() => {
                button.classList.remove('btn-loading');
                button.innerHTML = '<i class="fas fa-download"></i> Download';
            }, 1000);
        }

        function updateDownloadCount(resourceId, button) {
            // Update download count by calling API without download parameter
            fetch(`../api/download.php?id=${resourceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update download count
                        const statsElement = button.closest('.resource-card').querySelector('.resource-stats');
                        if (statsElement) {
                            statsElement.innerHTML = `<i class="fas fa-download"></i> ${data.downloads}`;
                        }
                    } else {
                        console.error('Download count update failed:', data.message);
                        // If file not found, still increment the count for UX
                        if (data.message && data.message.includes('File not found')) {
                            const statsElement = button.closest('.resource-card').querySelector('.resource-stats');
                            if (statsElement) {
                                const currentText = statsElement.textContent.trim();
                                const currentCount = parseInt(currentText.replace(/[^\d]/g, '')) || 0;
                                statsElement.innerHTML = `<i class="fas fa-download"></i> ${currentCount + 1}`;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Failed to update download count:', error);
                    // Fallback: increment count locally
                    const statsElement = button.closest('.resource-card').querySelector('.resource-stats');
                    if (statsElement) {
                        const currentText = statsElement.textContent.trim();
                        const currentCount = parseInt(currentText.replace(/[^\d]/g, '')) || 0;
                        statsElement.innerHTML = `<i class="fas fa-download"></i> ${currentCount + 1}`;
                    }
                });
        }

        function showDownloadMessage(button, message) {
            // Show a message that file is not available
            const card = button.closest('.resource-card');
            const existingMessage = card.querySelector('.download-message');
            
            if (!existingMessage) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'download-message';
                messageDiv.style.cssText = 'background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px 12px; border-radius: 4px; margin-top: 8px; font-size: 12px; animation: slideIn 0.3s ease-out; display: flex; align-items: center; gap: 8px;';
                messageDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
                    <div>
                        <strong>Download Unavailable</strong><br>
                        <span style="font-size: 11px; opacity: 0.9;">${message}</span>
                    </div>
                `;
                card.appendChild(messageDiv);
                
                // Remove message after 5 seconds
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.style.animation = 'slideOut 0.3s ease-out';
                        setTimeout(() => {
                            if (messageDiv.parentNode) {
                                messageDiv.parentNode.removeChild(messageDiv);
                            }
                        }, 300);
                    }
                }, 5000);
            }
        }

        // Add slide animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes slideOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-10px); }
            }
        `;
        document.head.appendChild(style);

        // View Resource Function
        function viewResource(resourceId) {
            // Open in new tab or modal
            const viewUrl = `../view-resource.php?id=${resourceId}`;
            window.open(viewUrl, '_blank');
        }

        // Scroll to Upload Section
        function scrollToUploadSection(event) {
            event.preventDefault();
            const uploadSection = document.getElementById('uploadSection');
            if (uploadSection) {
                uploadSection.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update active menu item
                document.querySelectorAll('.menu-item').forEach(item => {
                    item.classList.remove('active');
                });
                event.target.closest('.menu-item').classList.add('active');
            }
        }

        // Scroll to Resources Section
        function scrollToResourcesSection(event) {
            event.preventDefault();
            const resourcesSection = document.getElementById('resourcesSection');
            if (resourcesSection) {
                resourcesSection.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Upload Form Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');
            const fileInput = document.getElementById('file');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileUploadLabel = fileUploadArea.querySelector('.file-upload-label');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadMessage = document.getElementById('uploadMessage');

            // Handle file selection
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileUploadArea.classList.add('has-file');
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-file"></i>
                        <span>${file.name}</span>
                        <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    `;
                } else {
                    fileUploadArea.classList.remove('has-file');
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Click to browse or drag and drop</span>
                        <small>PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                    `;
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

            // Handle form submission
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!uploadForm.checkValidity()) {
                    uploadForm.reportValidity();
                    return;
                }
                
                // Show loading state
                uploadBtn.classList.add('btn-loading');
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                
                // Hide previous messages
                uploadMessage.style.display = 'none';
                
                // Create FormData
                const formData = new FormData(uploadForm);
                
                // Use fresh CSRF token from server
                if (window.currentCSRFToken) {
                    formData.set('csrf_token', window.currentCSRFToken);
                    console.log('Using fresh CSRF token:', window.currentCSRFToken);
                }
                
                // Get session ID from cookies
                const sessionId = document.cookie.match(/PHPSESSID=([^;]+)/);
                console.log('Session ID:', sessionId ? sessionId[1] : 'not found');
                
                // Send to API exactly like Android app - explicit Cookie header
                fetch('../api/upload.php', {
                    method: 'POST',
                    body: formData,
                    headers: sessionId ? {
                        'Cookie': `PHPSESSID=${sessionId[1]}`
                    } : {}
                })
                .then(async response => {
                    const text = await response.text();
                    console.log('Response text:', text);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    try {
                        // Strip PHP notices/errors from response before parsing JSON
                        const jsonStart = text.indexOf('{');
                        const jsonEnd = text.lastIndexOf('}');
                        if (jsonStart !== -1 && jsonEnd !== -1) {
                            const jsonText = text.substring(jsonStart, jsonEnd + 1);
                            return JSON.parse(jsonText);
                        }
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Invalid JSON response: ${text.substring(0, 200)}`);
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        uploadMessage.className = 'upload-message success';
                        uploadMessage.innerHTML = `
                            <i class="fas fa-check-circle"></i> ${data.message}
                        `;
                        uploadMessage.style.display = 'block';
                        
                        // Reset form
                        uploadForm.reset();
                        fileUploadArea.classList.remove('has-file');
                        fileUploadLabel.innerHTML = `
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Click to browse or drag and drop</span>
                            <small>PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                        `;
                        
                        // Refresh page after 2 seconds to show new resource
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        uploadMessage.className = 'upload-message error';
                        uploadMessage.innerHTML = `
                            <i class="fas fa-exclamation-circle"></i> ${data.message}
                        `;
                        uploadMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    uploadMessage.className = 'upload-message error';
                    uploadMessage.innerHTML = `
                        <i class="fas fa-exclamation-circle"></i> Upload failed: ${error.message || 'Please try again.'}
                    `;
                    uploadMessage.style.display = 'block';
                })
                .finally(() => {
                    // Reset button state
                    uploadBtn.classList.remove('btn-loading');
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Resource';
                });
            });

            // Handle form reset
            uploadForm.addEventListener('reset', function() {
                fileUploadArea.classList.remove('has-file');
                fileUploadLabel.innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Click to browse or drag and drop</span>
                    <small>PDF, DOC, PPT, XLS, TXT (Max 50MB)</small>
                `;
                uploadMessage.style.display = 'none';
            });
        });

        // Search and Filter Functionality
        let allResources = <?php echo json_encode($resources); ?>;
        let filteredResources = [...allResources];

        function searchResources() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const level = document.getElementById('filterLevel').value;
            const type = document.getElementById('filterType').value;
            
            filteredResources = allResources.filter(resource => {
                const matchesSearch = !searchTerm || 
                    resource.title.toLowerCase().includes(searchTerm) ||
                    resource.subject.toLowerCase().includes(searchTerm) ||
                    (resource.description && resource.description.toLowerCase().includes(searchTerm));
                
                const matchesLevel = !level || resource.level === level;
                const matchesType = !type || resource.type === type;
                
                return matchesSearch && matchesLevel && matchesType;
            });
            
            displayResources();
            updateResultsCount();
        }

        function filterResources() {
            searchResources();
        }

        function sortResources() {
            const sortBy = document.getElementById('sortBy').value;
            
            switch(sortBy) {
                case 'newest':
                    filteredResources.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    break;
                case 'oldest':
                    filteredResources.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    break;
                case 'title':
                    filteredResources.sort((a, b) => a.title.localeCompare(b.title));
                    break;
                case 'downloads':
                    filteredResources.sort((a, b) => (b.downloads || 0) - (a.downloads || 0));
                    break;
            }
            
            displayResources();
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterLevel').value = '';
            document.getElementById('filterType').value = '';
            document.getElementById('sortBy').value = 'newest';
            
            filteredResources = [...allResources];
            sortResources();
            updateResultsCount();
        }

        function displayResources() {
            const resourceCards = document.querySelector('.resource-cards');
            
            if (filteredResources.length === 0) {
                resourceCards.innerHTML = `
                    <div class="text-center py-4" style="grid-column: 1 / -1;">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No resources found matching your criteria</p>
                        <button class="btn btn-outline" onclick="clearFilters()">Clear Filters</button>
                    </div>
                `;
                return;
            }
            
            resourceCards.innerHTML = filteredResources.map(resource => {
                const fileType = resource.type || 'default';
                let iconClass = 'default';
                let iconFa = 'fa-file';
                
                switch(fileType.toLowerCase()) {
                    case 'pdf':
                        iconClass = 'pdf';
                        iconFa = 'fa-file-pdf';
                        break;
                    case 'doc':
                    case 'docx':
                        iconClass = 'doc';
                        iconFa = 'fa-file-word';
                        break;
                    case 'ppt':
                    case 'pptx':
                        iconClass = 'ppt';
                        iconFa = 'fa-file-powerpoint';
                        break;
                    case 'xls':
                    case 'xlsx':
                        iconClass = 'xls';
                        iconFa = 'fa-file-excel';
                        break;
                }
                
                return `
                    <div class="resource-card" data-filename="${resource.filename.split('/').pop()}">
                        <div class="resource-header">
                            <div class="resource-icon ${iconClass}">
                                <i class="fas ${iconFa}"></i>
                            </div>
                            <div class="resource-info">
                                <div class="resource-title">${resource.title}</div>
                                <div class="resource-subject">${resource.subject}</div>
                            </div>
                        </div>
                        <div class="resource-description">
                            ${resource.description || 'No description available'}
                        </div>
                        <div class="resource-meta">
                            <div class="resource-meta-left">
                                <span><i class="fas fa-graduation-cap"></i> ${resource.level}</span>
                                <span><i class="fas fa-calendar"></i> ${new Date(resource.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</span>
                            </div>
                            <div class="resource-stats">
                                <i class="fas fa-download"></i> ${resource.downloads || 0}
                            </div>
                        </div>
                        <div class="resource-actions">
                            <a href="#" class="btn-download" onclick="downloadResource(${resource.id}, this)">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateResultsCount() {
            const count = filteredResources.length;
            const total = allResources.length;
            const countElement = document.getElementById('resultsCount');
            
            if (count === total) {
                countElement.textContent = `Showing all ${total} resources`;
            } else {
                countElement.textContent = `Showing ${count} of ${total} resources`;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial resources display
            displayResources();
            updateResultsCount();
            
            // Add search on Enter key
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchResources();
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
        .card-title::first-letter {
            color: var(--primary-orange);
        }

        .header p,
        .hero-text p,
        .resource-description,
        .sidebar-header p {
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
        .settings-section h3 i,
        .resource-subject {
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
