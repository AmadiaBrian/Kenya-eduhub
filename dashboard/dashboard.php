<?php
// Session and security are handled by index.php router
// No need to repeat session_start() and security checks here

require_once '../config.php';
require_once '../includes/helpers.php';

// Get section parameter if present
$section = $_GET['section'] ?? '';

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user settings
$user_settings = getUserSettings($conn, $user_id);
$dark_mode_class = applyUserTheme($user_settings['theme'] ?? 'light');

// Get user resources
$user_resources = [];
try {
    $stmt = $conn->prepare("SELECT r.*, u.name, u.email FROM resources r LEFT JOIN users u ON r.user_id = u.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $user_resources = [];
}

// Get all resources for browsing
$all_resources = [];
try {
    $stmt = $conn->prepare("SELECT r.*, u.name, u.email FROM resources r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
    $stmt->execute();
    $all_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $all_resources = [];
}

// Get statistics
$total_resources = count($all_resources);
$user_upload_count = count($user_resources);

// Get detailed statistics for user resources
$user_file_type_stats = [];
$user_total_downloads = 0;
$user_recent_uploads = 0;
$one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));

foreach ($user_resources as $resource) {
    $fileType = strtoupper(pathinfo($resource['filename'] ?? '', PATHINFO_EXTENSION));
    if (!empty($fileType)) {
        if (!isset($user_file_type_stats[$fileType])) {
            $user_file_type_stats[$fileType] = 0;
        }
        $user_file_type_stats[$fileType]++;
    }
    $user_total_downloads += ($resource['downloads'] ?? 0);
    if (strtotime($resource['created_at']) >= strtotime($one_week_ago)) {
        $user_recent_uploads++;
    }
}

// Get detailed statistics for all resources
$all_file_type_stats = [];
$all_subject_stats = [];
$all_total_downloads = 0;

foreach ($all_resources as $resource) {
    $fileType = strtoupper(pathinfo($resource['filename'] ?? '', PATHINFO_EXTENSION));
    if (!empty($fileType)) {
        if (!isset($all_file_type_stats[$fileType])) {
            $all_file_type_stats[$fileType] = 0;
        }
        $all_file_type_stats[$fileType]++;
    }
    
    $subject = $resource['subject'] ?? '';
    if (!empty($subject)) {
        if (!isset($all_subject_stats[$subject])) {
            $all_subject_stats[$subject] = 0;
        }
        $all_subject_stats[$subject]++;
    }
    
    $all_total_downloads += ($resource['downloads'] ?? 0);
}

// Get user account age and activity
$account_age = '';
$activity_level = '';
$user_role = 'User';
$user_email = '';

if (isset($user['created_at'])) {
    $created_date = new DateTime($user['created_at']);
    $now = new DateTime();
    $interval = $created_date->diff($now);
    
    if ($interval->y > 0) {
        $account_age = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
    } elseif ($interval->m > 0) {
        $account_age = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
    } else {
        $account_age = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
    }
    
    // Determine activity level based on uploads
    if ($user_upload_count >= 10) {
        $activity_level = 'Active Contributor';
    } elseif ($user_upload_count >= 5) {
        $activity_level = 'Regular User';
    } elseif ($user_upload_count >= 2) {
        $activity_level = 'New Member';
    } else {
        $activity_level = 'Getting Started';
    }
    
    $user_role = $user['role'] ?? 'User';
    $user_email = $user['email'] ?? '';
}

// Get unique subjects from database
$unique_subjects = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT subject FROM resources WHERE subject IS NOT NULL AND subject != '' ORDER BY subject");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unique_subjects[] = $row['subject'];
    }
} catch (Exception $e) {
    $unique_subjects = [];
}

// Get allowed file types from admin settings
$allowed_file_types = [];
try {
    // Create table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS admin_site_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        site_name VARCHAR(255) DEFAULT 'Kenya EduHub',
        site_description TEXT,
        admin_email VARCHAR(255),
        max_file_size INT DEFAULT 10,
        allowed_extensions VARCHAR(255) DEFAULT 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Initialize default settings if table is empty
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_site_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert default settings
        $site_name = 'Kenya EduHub';
        $site_description = 'Kenya\'s comprehensive education platform';
        $admin_email = 'admin@kenyaeduhub.com';
        $max_file_size = 10;
        $allowed_extensions = 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt';
        
        $stmt = $conn->prepare("INSERT INTO admin_site_settings (site_name, site_description, admin_email, max_file_size, allowed_extensions) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $site_name, $site_description, $admin_email, $max_file_size, $allowed_extensions);
        $stmt->execute();
    }
    
    $stmt = $conn->prepare("SELECT allowed_extensions FROM admin_site_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    
    if ($settings && !empty($settings['allowed_extensions'])) {
        $extensions = explode(',', $settings['allowed_extensions']);
        foreach ($extensions as $ext) {
            $allowed_file_types[] = strtoupper(trim($ext));
        }
    }
} catch (Exception $e) {
    // Fallback to default types if database query fails
    $allowed_file_types = ['PDF', 'DOC', 'DOCX', 'PPT', 'PPTX', 'XLS', 'XLSX', 'TXT'];
}

// If still empty, add fallback
if (empty($allowed_file_types)) {
    $allowed_file_types = ['PDF', 'DOC', 'DOCX', 'PPT', 'PPTX', 'XLS', 'XLSX', 'TXT'];
}

// Sort file types
sort($allowed_file_types);

// Create accept attribute for file input
$file_accept_string = '.' . strtolower(implode(',.', $allowed_file_types));

// Log dashboard access (already authenticated by router)
logActivity('DASHBOARD_ACCESS', 'User accessed main dashboard', [
    'page' => 'dashboard/index.php',
    'user_role' => $_SESSION['user_role'] ?? 'unknown',
    'section' => $section
]);

// Output CSRF token
$csrf_token = generateCSRFLite();
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
    <title>Dashboard - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        window.currentCSRFToken = "<?php echo $csrf_token; ?>";
        window.allowedFileTypes = <?php echo json_encode($allowed_file_types); ?>;
    </script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
            --sidebar-width: 256px;
            --header-height: 64px;
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
            --text-color: #202124;
            --border-color: #e8eaed;
            --form-border-color: #dadce0;
            --card-hover-bg: #f8f9fa;
        }
        
        .dark-mode {
            --bg-color: #1a1a1a;
            --card-bg: #1a1a1a;
            --text-color: #e8eaed;
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
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: var(--secondary-color);
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
        
        .dark-mode .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .dark-mode .dark-mode-toggle {
            color: var(--text-color);
        }
        
        .dark-mode .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link.active {
            background: #e8f0fe;
            color: var(--primary-color);
        }
        
        .dark-mode .nav-link.active {
            background: rgba(26, 115, 232, 0.2);
            color: #8ab4f8;
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
        
        .dark-mode .nav-link.active i {
            color: #8ab4f8;
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
        }
        
        .menu-btn:hover {
            background: #f1f3f4;
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
            color: var(--primary-orange);
        }
        
        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-orange);
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
            
            /* Resource cards mobile optimization */
            .resource-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
            
            .resource-grid > div,
            .resource-card {
                padding: 16px !important;
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                transition: background 0.3s ease, border-color 0.3s ease;
                width: 100% !important;
                min-width: auto !important;
            }
            
            .resource-grid > div h3,
            .resource-card h3 {
                font-size: 14px !important;
                color: var(--text-color);
                line-height: 1.4;
                margin-bottom: 8px !important;
            }
            
            .resource-grid > div p,
            .resource-card p {
                font-size: 12px !important;
                margin-bottom: 6px !important;
                color: var(--secondary-color);
                line-height: 1.3;
            }
            
            .resource-grid > div i,
            .resource-card i {
                font-size: 18px !important;
            }
            
            .resource-grid > div .btn,
            .resource-card .btn {
                padding: 8px 16px !important;
                font-size: 12px !important;
                border-radius: 20px;
            }
            
            .resource-grid > div .btn-download,
            .resource-card .btn-download {
                background: var(--card-hover-bg);
                color: var(--text-color);
                border: 1px solid #000;
            }
            
            .resource-grid > div .btn-download:hover,
            .resource-card .btn-download:hover {
                background: var(--primary-orange);
                color: white;
                border-color: #000;
            }
            
            /* Dark mode mobile download button */
            .dark-mode .resource-grid > div .btn-download,
            .dark-mode .resource-card .btn-download {
                background: #252525;
                color: #ffffff;
                border: 1px solid #ffffff;
            }
            
            .dark-mode .resource-grid > div .btn-download:hover,
            .dark-mode .resource-card .btn-download:hover {
                background: var(--primary-orange);
                color: white;
                border-color: #ffffff;
            }
            
            /* Hide view button on mobile */
            .view-button {
                display: none !important;
            }
            
            /* Stack stats cards vertically on mobile */
            
            /* Make resource cards fit better on mobile */
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div {
                padding: 16px !important;
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                transition: background 0.3s ease, border-color 0.3s ease;
                width: 100% !important;
                min-width: auto !important;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div h3 {
                font-size: 14px !important;
                color: var(--text-color);
                line-height: 1.4;
                margin-bottom: 8px !important;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div p {
                font-size: 12px !important;
                margin-bottom: 6px !important;
                color: var(--secondary-color);
                line-height: 1.3;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div i {
                font-size: 18px !important;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn {
                padding: 8px 16px !important;
                font-size: 12px !important;
                border-radius: 20px;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download {
                background: var(--card-hover-bg);
                color: var(--text-color);
                border: 1px solid #000;
            }
            
            .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download:hover {
                background: var(--primary-orange);
                color: white;
                border-color: #000;
            }
            
            /* Dark mode mobile download button */
            .dark-mode .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download {
                background: #252525;
                color: #ffffff;
                border: 1px solid #ffffff;
            }
            
            .dark-mode .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download:hover {
                background: var(--primary-orange);
                color: white;
                border-color: #ffffff;
            }
        }
            
        /* Additional mobile optimization for smaller screens */
        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
                padding-top: calc(var(--header-height) + 12px);
            }
            
            .resource-grid {
                gap: 10px !important;
            }
            
            .resource-grid > div,
            .resource-card {
                padding: 14px !important;
            }
            
            .resource-grid > div h3,
            .resource-card h3 {
                font-size: 13px !important;
            }
            
            .resource-grid > div p,
            .resource-card p {
                font-size: 11px !important;
            }
            
            .resource-grid > div .btn,
            .resource-card .btn {
                padding: 6px 12px !important;
                font-size: 11px !important;
            }
            
            /* Dark mode for smaller screens */
            .dark-mode .resource-grid > div .btn-download,
            .dark-mode .resource-card .btn-download {
                background: #252525;
                color: #ffffff;
                border: 1px solid #ffffff;
            }
            
            .dark-mode .resource-grid > div .btn-download:hover,
            .dark-mode .resource-card .btn-download:hover {
                background: var(--primary-orange);
                color: white;
                border-color: #ffffff;
            }
            
            /* Search and filter mobile optimization */
            .resource-section-filters {
                padding: 12px !important;
            }
            
            .resource-section-filters > div {
                flex-direction: column !important;
                gap: 8px !important;
            }
            
            .resource-section-filters > div > div {
                min-width: 100% !important;
            }
            
            /* Stat cards mobile optimization */
            .stat-card {
                padding: 16px !important;
            }
            
            .stat-card h3 {
                font-size: 24px !important;
            }
            
            .stat-card > div:first-child {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
            }
            
            .stat-card > div:first-child i {
                font-size: 20px !important;
            }
        }
        

        

        
        .card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid #e8eaed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Resource cards responsive styling */
        .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .resource-grid > div,
        .resource-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        
        .resource-grid > div:hover,
        .resource-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .dark-mode .resource-grid > div:hover,
        .dark-mode .resource-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .resource-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        
        .resource-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        
        .btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        
        .resource-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--primary-orange);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
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
            border: 1px solid #ffffff;
        }
        
        .dark-mode .btn-download:hover {
            background: var(--primary-orange);
            color: white;
            border-color: #ffffff;
        }
        
        /* Search and filter controls styling */
        input[type="text"], select {
            outline: none;
        }
        
        input[type="text"]:focus, select:focus {
            border-color: var(--primary-orange) !important;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .dark-mode input[type="text"], .dark-mode select {
            background: #252525 !important;
            color: #ffffff !important;
            border-color: #3a3a3a !important;
        }
        
        .dark-mode input[type="text"]::placeholder {
            color: #9aa0a6;
        }
        
        .dark-mode select option {
            background: #252525;
            color: #ffffff;
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
        
        /* Resource cards dark mode */
        .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div h3 {
            color: var(--text-color);
        }
        
        .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div p {
            color: var(--secondary-color);
        }
        
        /* Dark mode for old style selector */
        .dark-mode .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download {
            background: #252525;
            color: #ffffff;
            border: 1px solid #ffffff;
        }
        
        .dark-mode .main-content > div[style*="repeat(auto-fill, minmax(280px, 1fr))"] > div .btn-download:hover {
            background: var(--primary-orange);
            color: white;
            border-color: #ffffff;
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
            border-color: var(--primary-orange);
            outline: none;
        }
        
        #fileUploadArea {
            background: var(--card-bg);
            border-color: var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        #fileUploadArea:hover {
            border-color: var(--primary-orange);
        }
        
        .alert {
            background: var(--card-bg);
            color: var(--text-color);
            border-color: var(--border-color);
        }
        
        /* Upload section card background */
        #uploadSectionContent {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        #uploadSectionContent h2 {
            color: var(--text-color);
        }
        
        /* Remove card class styling conflicts */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .card-body {
            padding: 25px;
            background: var(--card-bg);
            transition: background 0.3s ease;
        }
        
        .btn-secondary {
            background: #f1f3f4;
            color: #202124;
            border: 1px solid #dadce0;
        }
        
        .btn-secondary:hover {
            background: #e8eaed;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e8eaed;
        }
        
        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #e8eaed;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: #202124;
            border-bottom: 1px solid #e8eaed;
        }
        
        td {
            padding: 12px 15px;
            font-size: 13px;
            border-bottom: 1px solid #e8eaed;
            color: #202124;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #202124;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #e8eaed;
            border-radius: 25px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            text-align: left;
        }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 400;
            color: var(--primary-orange);
            margin-bottom: 4px;
        }
        
        .stat-card p {
            font-size: 14px;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
            border: none;
        }
    </style>
</head>
<body class="<?php echo $dark_mode_class; ?>">
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 40px; height: 40px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: var(--primary-orange); font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: var(--primary-orange); font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link active" href="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link" href="resources">
            <i class="fas fa-book"></i> My Resources
        </a>
        <a class="nav-link" href="upload">
            <i class="fas fa-upload"></i> Upload Resource
        </a>
        <a class="nav-link" href="https://sites.google.com/view/noteselectricalengineering/home" target="_blank">
            <i class="fas fa-external-link-alt"></i> More Resources
        </a>
        <a class="nav-link" href="profile">
            <i class="fas fa-user"></i> Profile
        </a>
        <a class="nav-link" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="../auth/logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Dashboard</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-folder-open" style="color: #FF6B35; font-size: 24px;"></i>
                    <div>
                        <h3><?php echo $user_upload_count; ?></h3>
                        <p>My Resources</p>
                    </div>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Total Downloads</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px;"><?php echo $user_total_downloads; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Recent (1 week)</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px;"><?php echo $user_recent_uploads; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Status</span>
                        <span style="color: #137333; font-weight: 500; font-size: 12px;"><?php echo $activity_level; ?></span>
                    </div>
                    <?php if (!empty($user_file_type_stats)): ?>
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color);">
                        <span style="color: var(--secondary-color); font-size: 12px; display: block; margin-bottom: 4px;">My File Types:</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <?php foreach ($user_file_type_stats as $type => $count): ?>
                                <span style="background: var(--card-hover-bg); color: var(--text-color); padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 500;"><?php echo $type; ?>: <?php echo $count; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-book" style="color: #FF6B35; font-size: 24px;"></i>
                    <div>
                        <h3><?php echo $total_resources; ?></h3>
                        <p>Total Resources</p>
                    </div>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Total Downloads</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px;"><?php echo $all_total_downloads; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Unique Subjects</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px;"><?php echo count($all_subject_stats); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--secondary-color); font-size: 12px;">File Types</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px;"><?php echo count($all_file_type_stats); ?></span>
                    </div>
                    <?php if (!empty($all_file_type_stats)): ?>
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color);">
                        <span style="color: var(--secondary-color); font-size: 12px; display: block; margin-bottom: 4px;">System File Types:</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <?php foreach ($all_file_type_stats as $type => $count): ?>
                                <span style="background: var(--card-hover-bg); color: var(--text-color); padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 500;"><?php echo $type; ?>: <?php echo $count; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-calendar-alt" style="color: #FF6B35; font-size: 24px;"></i>
                    <div>
                        <h3><?php echo $account_age ?: 'New'; ?></h3>
                        <p>Member Since</p>
                    </div>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Account Type</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px;"><?php echo ucfirst($user_role); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--secondary-color); font-size: 12px;">Email</span>
                        <span style="color: var(--text-color); font-weight: 500; font-size: 12px; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars(substr($user_email, 0, 15)) . (strlen($user_email) > 15 ? '...' : ''); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links Section -->
        <div id="quickLinksSection" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 24px; margin-bottom: 32px;">
            <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color); margin-bottom: 24px;">Quick Links</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <a href="dashboard" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--card-hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.2s;">
                    <i class="fas fa-tachometer-alt" style="color: #FF6B35; font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 500;">Dashboard</span>
                </a>
                <a href="resources" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--card-hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.2s;">
                    <i class="fas fa-book" style="color: #FF6B35; font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 500;">My Resources</span>
                </a>
                <a href="upload" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--card-hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.2s;">
                    <i class="fas fa-upload" style="color: #FF6B35; font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 500;">Upload Resource</span>
                </a>
                <a href="https://sites.google.com/view/noteselectricalengineering/home" target="_blank" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--card-hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.2s;">
                    <i class="fas fa-external-link-alt" style="color: #FF6B35; font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 500;">More Resources</span>
                </a>
                <a href="profile" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--card-hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.2s;">
                    <i class="fas fa-user" style="color: #FF6B35; font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 500;">Profile</span>
                </a>
                <a href="settings" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--card-hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.2s;">
                    <i class="fas fa-cog" style="color: #FF6B35; font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 500;">Settings</span>
                </a>
            </div>
        </div>

        <!-- Upload Section -->
        <div id="uploadSectionContent">
            <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color); margin-bottom: 24px;">Upload Resource</h2>
            
            <?php if ($user_upload_count < 2): ?>
                <div class="alert alert-info" style="background: var(--card-bg); color: var(--text-color); border: none; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <p>You need to upload at least <strong>2 resources</strong> to unlock downloads.</p>
                    <p>You have uploaded <strong><?php echo $user_upload_count; ?></strong> resource(s).</p>
                </div>
            <?php endif; ?>
                
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
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Subject *</label>
                            <select name="subject" required style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                                <option value="">Select Subject</option>
                                <?php foreach ($unique_subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                                <?php endforeach; ?>
                                <option value="other">Other (specify below)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">File Type *</label>
                            <select name="type" required style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                                <option value="">Select File Type</option>
                                <?php foreach ($allowed_file_types as $type): ?>
                                    <option value="<?php echo $type; ?>">
                                        <?php 
                                        $type_label = $type;
                                        if ($type == 'PDF') $type_label = 'PDF Document';
                                        elseif ($type == 'DOC' || $type == 'DOCX') $type_label = 'Word Document (.doc/.docx)';
                                        elseif ($type == 'PPT' || $type == 'PPTX') $type_label = 'PowerPoint (.ppt/.pptx)';
                                        elseif ($type == 'XLS' || $type == 'XLSX') $type_label = 'Excel Spreadsheet (.xls/.xlsx)';
                                        elseif ($type == 'TXT') $type_label = 'Text File (.txt)';
                                        echo $type_label;
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 20px;" id="otherSubjectDiv" style="display: none;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Other Subject *</label>
                        <input type="text" name="other_subject" id="otherSubjectInput" placeholder="Enter subject name" style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                    </div>
                    <div style="margin-top: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">Description *</label>
                        <textarea name="description" rows="3" required placeholder="Brief description of the resource..." style="width: 100%; padding: 12px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; resize: vertical; background: var(--card-bg); color: var(--text-color);"></textarea>
                    </div>
                    <div style="margin-top: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color);">File *</label>
                        <div id="fileUploadArea" style="position: relative; border: 2px dashed var(--border-color); border-radius: 25px; padding: 40px 20px; text-align: center; background: var(--card-bg); cursor: pointer; transition: all 0.2s;">
                            <input type="file" id="file" name="file" accept="<?php echo $file_accept_string; ?>" required style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                            <div id="fileUploadLabel">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                                <span style="display: block; color: var(--text-color); font-weight: 500;">Click to browse or drag and drop</span>
                                <small style="color: var(--secondary-color);"><?php echo implode(', ', $allowed_file_types); ?> (Max 50MB)</small>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 24px; display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Upload Resource
                        </button>
                        <button type="reset" class="btn btn-secondary" style="background: var(--card-hover-bg); color: var(--text-color); border: 1px solid var(--border-color);">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
                <div id="uploadMessage" style="margin-top: 16px; padding: 16px; border-radius: 8px; display: none;"></div>
        </div>
        
        <!-- My Resources Section -->
        <div id="resourcesSection">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color);">My Resources</h2>
                <a href="upload" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>
            
            <!-- Search and Filter Controls -->
            <div class="resource-section-filters" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <div style="position: relative;">
                            <input type="text" id="searchMyResources" placeholder="Search my resources..." style="width: 100%; padding: 10px 16px 10px 40px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--secondary-color);"></i>
                        </div>
                    </div>
                    <div style="min-width: 150px;">
                        <select id="filterMyResources" style="width: 100%; padding: 10px 16px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color); cursor: pointer;">
                            <option value="">All Types</option>
                            <?php foreach ($allowed_file_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="min-width: 150px;">
                        <select id="filterMySubject" style="width: 100%; padding: 10px 16px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color); cursor: pointer;">
                            <option value="">All Subjects</option>
                            <?php foreach ($unique_subjects as $subject): ?>
                                <option value="<?php echo strtolower($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php if (empty($user_resources)): ?>
                <div style="text-align: center; padding: 40px; color: var(--text-color);">
                    <i class="fas fa-folder-open fa-3x" style="color: var(--secondary-color); margin-bottom: 16px;"></i>
                    <p style="color: var(--secondary-color);">You haven't uploaded any resources yet</p>
                    <a href="upload" class="btn btn-primary">Upload Your First Resource</a>
                </div>
            <?php else: ?>
                <div class="resource-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($user_resources as $resource): ?>
                            <?php 
                            // Get file type from various possible fields
                            $filePath = $resource['filename'] ?? $resource['file_path'] ?? $resource['file'] ?? '';
                            $fileType = strtoupper(pathinfo($filePath, PATHINFO_EXTENSION) ?? 'FILE');
                            
                            // Fallback to subject if no file extension
                            if (empty($fileType) || $fileType == 'FILE') {
                                $subject = strtolower($resource['subject'] ?? '');
                                if (strpos($subject, 'pdf') !== false) $fileType = 'PDF';
                                elseif (strpos($subject, 'doc') !== false) $fileType = 'DOC';
                                elseif (strpos($subject, 'ppt') !== false) $fileType = 'PPT';
                                elseif (strpos($subject, 'xls') !== false) $fileType = 'XLS';
                                else $fileType = 'FILE';
                            }
                            
                            $iconClass = 'fa-file';
                            $iconColor = '#5f6368';
                            
                            switch($fileType) {
                                case 'PDF':
                                    $iconClass = 'fa-file-pdf';
                                    $iconColor = '#d32f2f';
                                    break;
                                case 'DOC':
                                case 'DOCX':
                                    $iconClass = 'fa-file-word';
                                    $iconColor = '#1976d2';
                                    break;
                                case 'PPT':
                                case 'PPTX':
                                    $iconClass = 'fa-file-powerpoint';
                                    $iconColor = '#f57c00';
                                    break;
                                case 'XLS':
                                case 'XLSX':
                                    $iconClass = 'fa-file-excel';
                                    $iconColor = '#388e3c';
                                    break;
                                case 'TXT':
                                    $iconClass = 'fa-file-alt';
                                    $iconColor = '#5f6368';
                                    break;
                            }
                            ?>
                            <div class="resource-card" 
                                 data-title="<?php echo htmlspecialchars(strtolower($resource['title'] ?? '')); ?>" 
                                 data-type="<?php echo $fileType; ?>" 
                                 data-file-group="<?php 
                                     // Group file types for better filtering
                                     if (in_array($fileType, ['DOC', 'DOCX'])) echo 'WORD';
                                     elseif (in_array($fileType, ['PPT', 'PPTX'])) echo 'POWERPOINT';
                                     elseif (in_array($fileType, ['XLS', 'XLSX'])) echo 'EXCEL';
                                     else echo $fileType;
                                 ?>" 
                                 data-subject="<?php echo htmlspecialchars(strtolower($resource['subject'] ?? '')); ?>">
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
                                    <a href="#" class="btn btn-download view-button">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
        
        <!-- Browse Resources Section -->
        <div style="margin-top: 30px;">
            <h2 style="font-size: 22px; font-weight: 400; color: var(--text-color); margin-bottom: 24px;">Browse All Resources</h2>
            
            <!-- Search and Filter Controls for All Resources -->
            <div class="resource-section-filters" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <div style="position: relative;">
                            <input type="text" id="searchAllResources" placeholder="Search all resources..." style="width: 100%; padding: 10px 16px 10px 40px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color);">
                            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--secondary-color);"></i>
                        </div>
                    </div>
                    <div style="min-width: 150px;">
                        <select id="filterAllResources" style="width: 100%; padding: 10px 16px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color); cursor: pointer;">
                            <option value="">All Types</option>
                            <?php foreach ($allowed_file_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="min-width: 150px;">
                        <select id="filterAllSubject" style="width: 100%; padding: 10px 16px; border: 1px solid var(--form-border-color); border-radius: 25px; font-size: 14px; background: var(--card-bg); color: var(--text-color); cursor: pointer;">
                            <option value="">All Subjects</option>
                            <?php foreach ($unique_subjects as $subject): ?>
                                <option value="<?php echo strtolower($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php if (empty($all_resources)): ?>
                <p style="color: var(--secondary-color);">No resources available yet.</p>
            <?php else: ?>
                <div class="resource-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($all_resources as $resource): ?>
                            <?php 
                            // Get file type from various possible fields
                            $filePath = $resource['filename'] ?? $resource['file_path'] ?? $resource['file'] ?? '';
                            $fileType = strtoupper(pathinfo($filePath, PATHINFO_EXTENSION) ?? 'FILE');
                            
                            // Fallback to subject if no file extension
                            if (empty($fileType) || $fileType == 'FILE') {
                                $subject = strtolower($resource['subject'] ?? '');
                                if (strpos($subject, 'pdf') !== false) $fileType = 'PDF';
                                elseif (strpos($subject, 'doc') !== false) $fileType = 'DOC';
                                elseif (strpos($subject, 'ppt') !== false) $fileType = 'PPT';
                                elseif (strpos($subject, 'xls') !== false) $fileType = 'XLS';
                                else $fileType = 'FILE';
                            }
                            
                            $iconClass = 'fa-file';
                            $iconColor = '#5f6368';
                            
                            switch($fileType) {
                                case 'PDF':
                                    $iconClass = 'fa-file-pdf';
                                    $iconColor = '#d32f2f';
                                    break;
                                case 'DOC':
                                case 'DOCX':
                                    $iconClass = 'fa-file-word';
                                    $iconColor = '#1976d2';
                                    break;
                                case 'PPT':
                                case 'PPTX':
                                    $iconClass = 'fa-file-powerpoint';
                                    $iconColor = '#f57c00';
                                    break;
                                case 'XLS':
                                case 'XLSX':
                                    $iconClass = 'fa-file-excel';
                                    $iconColor = '#388e3c';
                                    break;
                                case 'TXT':
                                    $iconClass = 'fa-file-alt';
                                    $iconColor = '#5f6368';
                                    break;
                            }
                            ?>
                            <div class="resource-card" 
                                 data-title="<?php echo htmlspecialchars(strtolower($resource['title'] ?? '')); ?>" 
                                 data-type="<?php echo $fileType; ?>" 
                                 data-file-group="<?php 
                                     // Group file types for better filtering
                                     if (in_array($fileType, ['DOC', 'DOCX'])) echo 'WORD';
                                     elseif (in_array($fileType, ['PPT', 'PPTX'])) echo 'POWERPOINT';
                                     elseif (in_array($fileType, ['XLS', 'XLSX'])) echo 'EXCEL';
                                     else echo $fileType;
                                 ?>" 
                                 data-subject="<?php echo htmlspecialchars(strtolower($resource['subject'] ?? '')); ?>">
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
                                    <?php if ($user_upload_count >= 2 || $resource['user_id'] == $user_id): ?>
                                        <a href="#" onclick="downloadResource(<?php echo $resource['id']; ?>, this)" class="btn btn-download">Download</a>
                                    <?php else: ?>
                                        <button class="btn btn-download" disabled style="background: #f1f3f4; color: #5f6368; border: 1px solid #000; cursor: not-allowed;">
                                            <i class="fas fa-lock"></i> Upload 2 resources to unlock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                
                // Update user preference in database
                fetch('update_theme.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'theme=dark'
                });
            } else {
                toggleBtn.classList.remove('fa-sun');
                toggleBtn.classList.add('fa-moon');
                localStorage.setItem('darkMode', 'disabled');
                
                // Update user preference in database
                fetch('update_theme.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'theme=light'
                });
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
        
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }

        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.menu-btn');
            
            if (window.innerWidth <= 768 &&
                !sidebar.contains(event.target) &&
                !toggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
        
        // Search and Filter Functionality for My Resources
        const searchMyResources = document.getElementById('searchMyResources');
        const filterMyResources = document.getElementById('filterMyResources');
        const filterMySubject = document.getElementById('filterMySubject');
        
        if (searchMyResources && filterMyResources && filterMySubject) {
            function filterMyResourcesList() {
                const searchTerm = searchMyResources.value.toLowerCase();
                const typeFilter = filterMyResources.value;
                const subjectFilter = filterMySubject.value;
                
                const resourceCards = document.querySelectorAll('#resourcesSection .resource-card');
                let visibleCount = 0;
                
                resourceCards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const type = card.getAttribute('data-type') || '';
                    const fileGroup = card.getAttribute('data-file-group') || '';
                    const subject = card.getAttribute('data-subject') || '';
                    
                    const matchesSearch = title.includes(searchTerm);
                    // Match by exact type or file group
                    let matchesType = typeFilter === '' || type === typeFilter || fileGroup === typeFilter;
                    
                    const matchesSubject = subjectFilter === '' || subject === subjectFilter;
                    
                    if (matchesSearch && matchesType && matchesSubject) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show "no results" message if needed
                let noResultsMsg = document.querySelector('#resourcesSection .no-results-message');
                if (visibleCount === 0 && resourceCards.length > 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results-message';
                        noResultsMsg.style.cssText = 'text-align: center; padding: 40px; color: var(--text-color);';
                        noResultsMsg.innerHTML = '<i class="fas fa-search fa-2x" style="color: var(--secondary-color); margin-bottom: 16px;"></i><p style="color: var(--secondary-color);">No resources match your search criteria</p>';
                        document.querySelector('#resourcesSection .resource-grid').after(noResultsMsg);
                    }
                    noResultsMsg.style.display = 'block';
                } else if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }
            
            searchMyResources.addEventListener('input', filterMyResourcesList);
            filterMyResources.addEventListener('change', filterMyResourcesList);
            filterMySubject.addEventListener('change', filterMyResourcesList);
        }
        
        // Search and Filter Functionality for All Resources
        const searchAllResources = document.getElementById('searchAllResources');
        const filterAllResources = document.getElementById('filterAllResources');
        const filterAllSubject = document.getElementById('filterAllSubject');
        
        if (searchAllResources && filterAllResources && filterAllSubject) {
            function filterAllResourcesList() {
                const searchTerm = searchAllResources.value.toLowerCase();
                const typeFilter = filterAllResources.value;
                const subjectFilter = filterAllSubject.value;
                
                // Find the second resource grid (the one for "Browse All Resources")
                const allResourceGrids = document.querySelectorAll('.resource-grid');
                const browseResourcesGrid = allResourceGrids[1]; // Second grid is for browse resources
                
                if (browseResourcesGrid) {
                    const resourceCards = browseResourcesGrid.querySelectorAll('.resource-card');
                    let visibleCount = 0;
                    
                    resourceCards.forEach(card => {
                        const title = card.getAttribute('data-title') || '';
                        const type = card.getAttribute('data-type') || '';
                        const subject = card.getAttribute('data-subject') || '';
                        
                        const matchesSearch = title.includes(searchTerm);
                        const matchesType = typeFilter === '' || type === typeFilter;
                        const matchesSubject = subjectFilter === '' || subject === subjectFilter;
                        
                        if (matchesSearch && matchesType && matchesSubject) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Show "no results" message if needed
                    let noResultsMsg = browseResourcesGrid.parentElement.querySelector('.no-results-message');
                    if (visibleCount === 0 && resourceCards.length > 0) {
                        if (!noResultsMsg) {
                            noResultsMsg = document.createElement('div');
                            noResultsMsg.className = 'no-results-message';
                            noResultsMsg.style.cssText = 'text-align: center; padding: 40px; color: var(--text-color);';
                            noResultsMsg.innerHTML = '<i class="fas fa-search fa-2x" style="color: var(--secondary-color); margin-bottom: 16px;"></i><p style="color: var(--secondary-color);">No resources match your search criteria</p>';
                            browseResourcesGrid.after(noResultsMsg);
                        }
                        noResultsMsg.style.display = 'block';
                    } else if (noResultsMsg) {
                        noResultsMsg.style.display = 'none';
                    }
                }
            }
            
            searchAllResources.addEventListener('input', filterAllResourcesList);
            filterAllResources.addEventListener('change', filterAllResourcesList);
            filterAllSubject.addEventListener('change', filterAllResourcesList);
        }
        
        // Handle section parameter for auto-scrolling
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        const currentPath = window.location.pathname;
        
        // Check if we're on a clean URL route
        let targetSection = section;
        if (currentPath.includes('/upload')) {
            targetSection = 'upload';
        } else if (currentPath.includes('/resources')) {
            targetSection = 'resources';
        }
        
        if (targetSection === 'upload') {
            setTimeout(() => {
                const uploadSection = document.getElementById('uploadSectionContent');
                if (uploadSection) {
                    uploadSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 500);
        } else if (targetSection === 'resources') {
            setTimeout(() => {
                const resourcesSection = document.getElementById('resourcesSection');
                if (resourcesSection) {
                    resourcesSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 500);
        }
        
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
                        <span style="display: block; color: var(--text-color); font-weight: 500;">${file.name}</span>
                        <small style="color: var(--secondary-color);">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    `;
                } else {
                    fileUploadArea.classList.remove('has-file');
                    fileUploadArea.style.borderColor = 'var(--border-color)';
                    fileUploadArea.style.background = 'var(--card-bg)';
                    const allowedTypes = <?php echo json_encode($allowed_file_types); ?>;
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                        <span style="display: block; color: var(--text-color); font-weight: 500;">Click to browse or drag and drop</span>
                        <small style="color: var(--secondary-color);">${allowedTypes.join(', ')} (Max 50MB)</small>
                    `;
                }
            });

            // Handle subject selection - show "Other" input when selected
            const subjectSelect = document.querySelector('select[name="subject"]');
            const otherSubjectDiv = document.getElementById('otherSubjectDiv');
            const otherSubjectInput = document.getElementById('otherSubjectInput');
            
            if (subjectSelect && otherSubjectDiv && otherSubjectInput) {
                subjectSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        otherSubjectDiv.style.display = 'block';
                        otherSubjectInput.required = true;
                    } else {
                        otherSubjectDiv.style.display = 'none';
                        otherSubjectInput.required = false;
                        otherSubjectInput.value = '';
                    }
                });
            }

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
                    uploadMessage.style.background = '#fce8e6';
                    uploadMessage.style.color = '#c5221f';
                    uploadMessage.textContent = 'Please select a file to upload.';
                    return;
                }

                // Show loading state
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                uploadMessage.style.display = 'none';

                const formData = new FormData(uploadForm);
                formData.append('action', 'upload_resource');
                
                // Handle "other" subject selection
                if (subjectSelect && subjectSelect.value === 'other') {
                    if (!otherSubjectInput || !otherSubjectInput.value.trim()) {
                        uploadMessage.style.display = 'block';
                        uploadMessage.style.background = '#fce8e6';
                        uploadMessage.style.color = '#c5221f';
                        uploadMessage.textContent = 'Please enter a subject name.';
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Resource';
                        return;
                    }
                    // Add the custom subject as a separate field that the backend can use
                    formData.append('custom_subject', otherSubjectInput.value.trim());
                }

                fetch('../api/upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        uploadMessage.style.display = 'block';
                        uploadMessage.style.background = '#e6f4ea';
                        uploadMessage.style.color = '#137333';
                        uploadMessage.textContent = 'Resource uploaded successfully!';
                        
                        // Reset form after successful upload
                        setTimeout(() => {
                            uploadForm.reset();
                            fileUploadArea.classList.remove('has-file');
                            fileUploadArea.style.borderColor = 'var(--border-color)';
                            fileUploadArea.style.background = 'var(--card-bg)';
                            const allowedTypes = window.allowedFileTypes || ['PDF', 'DOC', 'PPT', 'XLS', 'TXT'];
                            fileUploadLabel.innerHTML = `
                                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9aa0a6; margin-bottom: 16px;"></i>
                                <span style="display: block; color: var(--text-color); font-weight: 500;">Click to browse or drag and drop</span>
                                <small style="color: var(--secondary-color);">${allowedTypes.join(', ')} (Max 50MB)</small>
                            `;
                            location.reload();
                        }, 2000);
                    } else {
                        uploadMessage.style.display = 'block';
                        uploadMessage.style.background = '#fce8e6';
                        uploadMessage.style.color = '#c5221f';
                        uploadMessage.textContent = 'Error: ' + data.message;
                    }
                })
                .catch(error => {
                    uploadMessage.style.display = 'block';
                    uploadMessage.style.background = '#fce8e6';
                    uploadMessage.style.color = '#c5221f';
                    uploadMessage.textContent = 'Error uploading resource: ' + error.message;
                })
                .finally(() => {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Resource';
                });
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
                    // Trigger change event
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        });
        
        // Download Resource Function
        function downloadResource(resourceId, element) {
            // Implement download functionality
            const originalText = element.innerHTML;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
            element.disabled = true;
            
            // Simulate download (replace with actual download logic)
            setTimeout(() => {
                element.innerHTML = originalText;
                element.disabled = false;
                alert('Download functionality to be implemented for resource ID: ' + resourceId);
            }, 1000);
        }
    </script>
</body>
</html>