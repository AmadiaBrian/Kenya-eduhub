<?php
// Load PHPMailer at the top of the file
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Session is started by index.php router
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/security_lite.php';

// Generate CSRF token
$csrf_token = generateCSRFLite();

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

// Handle form submissions
$success = '';
$error = '';

// Get current SMTP settings from database
$smtp_settings = [];
try {
    $stmt = $conn->prepare("SELECT * FROM admin_smtp_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $smtp_settings = $result->fetch_assoc();
} catch (Exception $e) {
    // Table might not exist yet
}

// Get current SMS settings from database
$sms_settings = [];
try {
    // Create table first if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS admin_sms_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sms_provider VARCHAR(20) DEFAULT 'mobitech',
        mobitech_api_key VARCHAR(255),
        mobitech_sender_id VARCHAR(11),
        textsms_api_key VARCHAR(255),
        textsms_partner_id VARCHAR(255),
        textsms_sender_id VARCHAR(11),
        sms_enabled INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("SELECT * FROM admin_sms_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $sms_settings = $result->fetch_assoc();
} catch (Exception $e) {
    // Table might not exist yet or other error
    $sms_settings = [];
}

// Get current site settings from database
$site_settings = [];
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
    
    $stmt = $conn->prepare("SELECT * FROM admin_site_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $site_settings = $result->fetch_assoc();
} catch (Exception $e) {
    // Table might not exist yet or other error
    $site_settings = [];
}

// Get current system settings from database
$system_settings = [];
try {
    // Create table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS admin_system_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        maintenance_mode INT DEFAULT 0,
        debug_mode INT DEFAULT 0,
        session_timeout INT DEFAULT 30,
        max_login_attempts INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("SELECT * FROM admin_system_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $system_settings = $result->fetch_assoc();
} catch (Exception $e) {
    // Table might not exist yet or other error
    $system_settings = [];
}

// Get current backup settings from database
$backup_settings = [];
try {
    // Create table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS admin_backup_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        backup_enabled INT DEFAULT 0,
        backup_frequency VARCHAR(20) DEFAULT 'daily',
        backup_path VARCHAR(255),
        backup_retention_days INT DEFAULT 7,
        auto_backup INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("SELECT * FROM admin_backup_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $backup_settings = $result->fetch_assoc();
} catch (Exception $e) {
    // Table might not exist yet or other error
    $backup_settings = [];
}

// Get recent backup files
$backup_files = [];
$backup_path = $backup_settings['backup_path'] ?? '../backups';
$backup_path = rtrim($backup_path, '/');

// Helper function to format bytes
function formatBytes($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

if (is_dir($backup_path)) {
    $files = glob($backup_path . '/backup_*');
    foreach ($files as $file) {
        $backup_files[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file),
            'path' => $file
        ];
    }
    
    // Sort by date descending
    usort($backup_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    // Show only last 10 backups
    $backup_files = array_slice($backup_files, 0, 10);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_site_settings':
                try {
                    // Update site settings in database
                    $site_name = $_POST['site_name'] ?? 'Kenya EduHub';
                    $site_description = $_POST['site_description'] ?? '';
                    $admin_email = $_POST['admin_email'] ?? '';
                    $max_file_size = intval($_POST['max_file_size'] ?? 10);
                    $allowed_extensions = $_POST['allowed_extensions'] ?? 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt';
                    
                    // Validate CSRF token
                    if (!validateCSRFLite($_POST['csrf_token'])) {
                        throw new Exception("Invalid CSRF token");
                    }
                    
                    // Check if settings exist
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_site_settings");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE admin_site_settings SET site_name = ?, site_description = ?, admin_email = ?, max_file_size = ?, allowed_extensions = ? WHERE id = 1");
                        $stmt->bind_param("sssis", $site_name, $site_description, $admin_email, $max_file_size, $allowed_extensions);
                        $stmt->execute();
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO admin_site_settings (site_name, site_description, admin_email, max_file_size, allowed_extensions) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssis", $site_name, $site_description, $admin_email, $max_file_size, $allowed_extensions);
                        $stmt->execute();
                    }
                    
                    // Refresh settings
                    $stmt = $conn->prepare("SELECT * FROM admin_site_settings LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $site_settings = $result->fetch_assoc();
                    
                    $success = "Site settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating site settings: " . $e->getMessage();
                }
                break;
                
            case 'update_system_settings':
                try {
                    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
                    $debug_mode = isset($_POST['debug_mode']) ? 1 : 0;
                    $session_timeout = intval($_POST['session_timeout'] ?? 30);
                    $max_login_attempts = intval($_POST['max_login_attempts'] ?? 5);
                    
                    // Validate CSRF token
                    if (!validateCSRFLite($_POST['csrf_token'])) {
                        throw new Exception("Invalid CSRF token");
                    }
                    
                    // Check if settings exist
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_system_settings");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE admin_system_settings SET maintenance_mode = ?, debug_mode = ?, session_timeout = ?, max_login_attempts = ? WHERE id = 1");
                        $stmt->bind_param("iiii", $maintenance_mode, $debug_mode, $session_timeout, $max_login_attempts);
                        $stmt->execute();
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO admin_system_settings (maintenance_mode, debug_mode, session_timeout, max_login_attempts) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiii", $maintenance_mode, $debug_mode, $session_timeout, $max_login_attempts);
                        $stmt->execute();
                    }
                    
                    // Refresh settings
                    $stmt = $conn->prepare("SELECT * FROM admin_system_settings LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $system_settings = $result->fetch_assoc();
                    
                    $success = "System settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating system settings: " . $e->getMessage();
                }
                break;
                
            case 'update_backup_settings':
                try {
                    $backup_enabled = isset($_POST['backup_enabled']) ? 1 : 0;
                    $backup_frequency = $_POST['backup_frequency'] ?? 'daily';
                    $backup_path = trim($_POST['backup_path'] ?? '');
                    $backup_retention_days = intval($_POST['backup_retention_days'] ?? 7);
                    $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
                    
                    // Validate CSRF token
                    if (!validateCSRFLite($_POST['csrf_token'])) {
                        throw new Exception("Invalid CSRF token");
                    }
                    
                    // Check if settings exist
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_backup_settings");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE admin_backup_settings SET backup_enabled = ?, backup_frequency = ?, backup_path = ?, backup_retention_days = ?, auto_backup = ? WHERE id = 1");
                        $stmt->bind_param("issii", $backup_enabled, $backup_frequency, $backup_path, $backup_retention_days, $auto_backup);
                        $stmt->execute();
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO admin_backup_settings (backup_enabled, backup_frequency, backup_path, backup_retention_days, auto_backup) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issii", $backup_enabled, $backup_frequency, $backup_path, $backup_retention_days, $auto_backup);
                        $stmt->execute();
                    }
                    
                    // Refresh settings
                    $stmt = $conn->prepare("SELECT * FROM admin_backup_settings LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $backup_settings = $result->fetch_assoc();
                    
                    $success = "Backup settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating backup settings: " . $e->getMessage();
                }
                break;
                
            case 'update_email_settings':
                try {
                    $smtp_host = trim($_POST['smtp_host'] ?? '');
                    $smtp_port = intval($_POST['smtp_port'] ?? 587);
                    $smtp_username = trim($_POST['smtp_username'] ?? '');
                    $smtp_password = trim($_POST['smtp_password'] ?? '');
                    $email_from = trim($_POST['email_from'] ?? '');
                    $encryption = trim($_POST['encryption'] ?? 'tls');
                    
                    // Create table if it doesn't exist
                    $conn->query("CREATE TABLE IF NOT EXISTS admin_smtp_settings (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        smtp_host VARCHAR(255) NOT NULL,
                        smtp_port INT NOT NULL DEFAULT 587,
                        smtp_username VARCHAR(255) NOT NULL,
                        smtp_password VARCHAR(255) NOT NULL,
                        email_from VARCHAR(255) NOT NULL,
                        encryption VARCHAR(10) DEFAULT 'tls',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    
                    // Check if settings exist
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_smtp_settings");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE admin_smtp_settings SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, email_from = ?, encryption = ? WHERE id = 1");
                        $stmt->bind_param("sissss", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $email_from, $encryption);
                        $stmt->execute();
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO admin_smtp_settings (smtp_host, smtp_port, smtp_username, smtp_password, email_from, encryption) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sissss", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $email_from, $encryption);
                        $stmt->execute();
                    }
                    
                    // Refresh settings
                    $stmt = $conn->prepare("SELECT * FROM admin_smtp_settings LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $smtp_settings = $result->fetch_assoc();
                    
                    $success = "Email settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating email settings: " . $e->getMessage();
                }
                break;
                
            case 'update_sms_settings':
                try {
                    $sms_provider = trim($_POST['sms_provider'] ?? 'mobitech');
                    $mobitech_api_key = trim($_POST['mobitech_api_key'] ?? '');
                    $mobitech_sender_id = trim($_POST['mobitech_sender_id'] ?? '');
                    $textsms_api_key = trim($_POST['textsms_api_key'] ?? '');
                    $textsms_partner_id = trim($_POST['textsms_partner_id'] ?? '');
                    $textsms_sender_id = trim($_POST['textsms_sender_id'] ?? '');
                    $sms_enabled = intval($_POST['sms_enabled'] ?? 0);
                    
                    // Validate provider
                    if (!in_array($sms_provider, ['mobitech', 'textsms'])) {
                        $error = "Invalid SMS provider";
                        break;
                    }
                    
                    // Validate sender ID lengths
                    if (!empty($mobitech_sender_id) && strlen($mobitech_sender_id) > 11) {
                        $error = "Mobitech sender ID must be 11 characters or less";
                        break;
                    }
                    if (!empty($textsms_sender_id) && strlen($textsms_sender_id) > 11) {
                        $error = "Text SMS sender ID must be 11 characters or less";
                        break;
                    }
                    
                    // Create table if it doesn't exist
                    $conn->query("CREATE TABLE IF NOT EXISTS admin_sms_settings (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        sms_provider VARCHAR(20) DEFAULT 'mobitech',
                        mobitech_api_key VARCHAR(255),
                        mobitech_sender_id VARCHAR(11),
                        textsms_api_key VARCHAR(255),
                        textsms_partner_id VARCHAR(255),
                        textsms_sender_id VARCHAR(11),
                        sms_enabled INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    
                    // Check if settings exist
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_sms_settings");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE admin_sms_settings SET sms_provider = ?, mobitech_api_key = ?, mobitech_sender_id = ?, textsms_api_key = ?, textsms_partner_id = ?, textsms_sender_id = ?, sms_enabled = ? WHERE id = 1");
                        $stmt->bind_param("ssssssi", $sms_provider, $mobitech_api_key, $mobitech_sender_id, $textsms_api_key, $textsms_partner_id, $textsms_sender_id, $sms_enabled);
                        $stmt->execute();
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO admin_sms_settings (sms_provider, mobitech_api_key, mobitech_sender_id, textsms_api_key, textsms_partner_id, textsms_sender_id, sms_enabled) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssi", $sms_provider, $mobitech_api_key, $mobitech_sender_id, $textsms_api_key, $textsms_partner_id, $textsms_sender_id, $sms_enabled);
                        $stmt->execute();
                    }
                    
                    // Refresh settings
                    $stmt = $conn->prepare("SELECT * FROM admin_sms_settings LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $sms_settings = $result->fetch_assoc();
                    
                    $success = "SMS settings updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating SMS settings: " . $e->getMessage();
                }
                break;
        }
    }
}

// Handle AJAX request for SMTP testing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'test_smtp') {
    header('Content-Type: application/json');
    
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = intval($_POST['smtp_port'] ?? 587);
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password = trim($_POST['smtp_password'] ?? '');
    $email_from = trim($_POST['email_from'] ?? '');
    $encryption = trim($_POST['encryption'] ?? 'tls');
    
    if (empty($smtp_host) || empty($smtp_port) || empty($smtp_username) || empty($smtp_password) || empty($email_from)) {
        echo json_encode(['success' => false, 'error' => 'All SMTP fields are required']);
        exit();
    }
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->Port = $smtp_port;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $encryption;
        
        // Use SMTP username as From address to match withdrawal system
        $mail->setFrom($smtp_username, 'Kenya EduHub');
        
        // Try to connect
        $mail->SMTPAuth = true;
        $mail->SMTPDebug = 0; // Disable debug output
        
        // Test connection by attempting to authenticate
        if ($mail->smtpConnect()) {
            $mail->smtpClose();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to connect to SMTP server']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 256px;
            --header-height: 64px;
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
            color: #202124;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease;
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
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
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
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: #FF6B35;
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
            color: #202124;
            margin-bottom: 24px;
        }
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
            border-bottom: 1px solid #e8eaed;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
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
            color: #202124;
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
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
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
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-secondary {
            background: #f1f3f4;
            color: #202124;
        }
        
        .btn-secondary:hover {
            background: #e8eaed;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #202124;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            color: #202124;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
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
        
        .alert {
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #e6f4ea;
            border-color: #137333;
            color: #137333;
        }
        
        .alert-error {
            background: #fce8e6;
            border-color: #c5221f;
            color: #c5221f;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            background: #dadce0;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.267s ease;
        }
        
        .toggle-switch.active {
            background: var(--primary-color);
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
        
        /* Terminal-like section */
        .terminal {
            background: #1e1e1e;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 15px;
            border-radius: 5px;
            height: 300px;
            overflow-y: auto;
            border: 1px solid #333;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .terminal-line {
            margin: 2px 0;
            word-wrap: break-word;
        }
        
        .terminal-line.success {
            color: #00ff00;
        }
        
        .terminal-line.error {
            color: #ff0000;
        }
        
        .terminal-line.info {
            color: #00ffff;
        }
        
        .terminal-line.warning {
            color: #ffff00;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link" href="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link" href="schools">
            <i class="fas fa-school"></i> Schools
        </a>
        <a class="nav-link" href="users">
            <i class="fas fa-users"></i> Users
        </a>
        <a class="nav-link" href="resources">
            <i class="fas fa-book"></i> Resources
        </a>
        <a class="nav-link" href="reports">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a class="nav-link" href="logs">
            <i class="fas fa-file-alt"></i> Logs
        </a>
        <a class="nav-link active" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Settings</h1>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Settings Grid -->
        <div class="settings-container">
            <!-- Site Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-globe me-2"></i> Site Settings
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_site_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_settings['site_name'] ?? 'Kenya EduHub'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea class="form-control" id="site_description" name="site_description" placeholder="Enter site description..."><?php echo htmlspecialchars($site_settings['site_description'] ?? 'Educational resource management system for Kenyan schools'); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($site_settings['admin_email'] ?? 'admin@kenyaeduhub.com'); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max File Size (MB)</label>
                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" value="<?php echo htmlspecialchars($site_settings['max_file_size'] ?? 10); ?>" min="1" max="100">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Allowed Extensions</label>
                                <input type="text" class="form-control" id="allowed_extensions" name="allowed_extensions" value="<?php echo htmlspecialchars($site_settings['allowed_extensions'] ?? 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt'); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cogs me-2"></i> System Settings
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_system_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" <?php echo ($system_settings['debug_mode'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="debug_mode">Debug Mode</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Session Timeout (minutes)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars($system_settings['session_timeout'] ?? 30); ?>" min="5" max="120">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Login Attempts</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" value="<?php echo htmlspecialchars($system_settings['max_login_attempts'] ?? 5); ?>" min="3" max="10">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Backup Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-2"></i> Backup Settings
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_backup_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="backup_enabled" name="backup_enabled" <?php echo ($backup_settings['backup_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="backup_enabled">Enable Backups</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Backup Frequency</label>
                            <select class="form-select" id="backup_frequency" name="backup_frequency">
                                <option value="hourly" <?php echo ($backup_settings['backup_frequency'] ?? 'daily') === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                                <option value="daily" <?php echo ($backup_settings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo ($backup_settings['backup_frequency'] ?? 'daily') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo ($backup_settings['backup_frequency'] ?? 'daily') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Backup Path</label>
                            <input type="text" class="form-control" id="backup_path" name="backup_path" value="<?php echo htmlspecialchars($backup_settings['backup_path'] ?? '../backups'); ?>" placeholder="Enter backup directory path">
                            <small class="text-muted">Relative or absolute path to store backup files</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Retention Period (days)</label>
                                <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days" value="<?php echo htmlspecialchars($backup_settings['backup_retention_days'] ?? 7); ?>" min="1" max="365">
                                <small class="text-muted">How long to keep backup files</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup" <?php echo ($backup_settings['auto_backup'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_backup">Auto Backup</label>
                                </div>
                                <small class="text-muted">Automatically create backups on schedule</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-success ms-2" onclick="triggerBackup()">
                            <i class="fas fa-download me-2"></i> Backup Now
                        </button>
                    </form>
                    
                    <!-- Recent Backups -->
                    <?php if (!empty($backup_files)): ?>
                    <div class="mt-4">
                        <h6 class="mb-3">Recent Backups</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backup_files as $file): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                                        <td><?php echo formatBytes($file['size']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', $file['date']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($backup_path . '/' . $file['name']); ?>" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mt-4">
                        <p class="text-muted">No backups found. Click "Backup Now" to create your first backup.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-envelope me-2"></i> Email SMTP Settings
                </div>
                <div class="card-body">
                    <!-- Current Settings Display -->
                    <div id="currentSmtpSettings" class="mb-4" style="display: <?php echo !empty($smtp_settings) ? 'block' : 'none'; ?>;">
                        <h5 class="mb-3">Current SMTP Settings</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <td><strong>SMTP Host:</strong></td>
                                    <td><?php echo htmlspecialchars($smtp_settings['smtp_host'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SMTP Port:</strong></td>
                                    <td><?php echo htmlspecialchars($smtp_settings['smtp_port'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SMTP Username:</strong></td>
                                    <td><?php echo htmlspecialchars($smtp_settings['smtp_username'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SMTP Password:</strong></td>
                                    <td>
                                        <span id="displayPassword">••••••••••••••••</span>
                                        <button type="button" class="btn btn-sm btn-link" onclick="toggleDisplayPassword()" style="padding: 0; margin-left: 10px;">
                                            <i class="fas fa-eye" id="displayPasswordIcon"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>From Email:</strong></td>
                                    <td><?php echo htmlspecialchars($smtp_settings['email_from'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Encryption:</strong></td>
                                    <td><?php echo htmlspecialchars($smtp_settings['encryption'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Updated:</strong></td>
                                    <td><?php echo !empty($smtp_settings['updated_at']) ? date('Y-m-d H:i:s', strtotime($smtp_settings['updated_at'])) : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_email_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com" value="<?php echo htmlspecialchars($smtp_settings['smtp_host'] ?? 'smtp.gmail.com'); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_settings['smtp_port'] ?? 587); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Encryption</label>
                                <select class="form-control" id="encryption" name="encryption">
                                    <option value="tls" <?php echo ($smtp_settings['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($smtp_settings['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Email</label>
                                <input type="email" class="form-control" id="email_from" name="email_from" placeholder="noreply@kenyaeduhub.com" value="<?php echo htmlspecialchars($smtp_settings['email_from'] ?? 'noreply@kenyaeduhub.com'); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" placeholder="your-email@gmail.com" value="<?php echo htmlspecialchars($smtp_settings['smtp_username'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Enter SMTP password" value="<?php echo htmlspecialchars($smtp_settings['smtp_password'] ?? ''); ?>">
                        </div>

                        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8eaed;">
                            <button type="button" class="btn btn-secondary">Cancel</button>
                            <button type="button" class="btn btn-outline-primary" onclick="testSmtpConnection()">
                                <i class="fas fa-vial"></i> Test Connection
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                    
                    <!-- Terminal Output Section -->
                    <div class="mt-4">
                        <h5 class="mb-2">SMTP Connection Test Output</h5>
                        <div id="terminal" class="terminal">
                            <div class="terminal-line info" id="terminalPath">Ready to test SMTP connection...</div>
                            <div class="terminal-line info">Enter SMTP settings above, then click "Test Connection"</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-2"></i> Backup Settings
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup">
                                <label class="form-check-label" for="auto_backup">Auto Backup</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Backup Frequency</label>
                            <select class="form-control" id="backup_frequency" name="backup_frequency">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Retention Period (days)</label>
                            <input type="number" class="form-control" id="backup_retention" name="backup_retention" value="30" min="7" max="365">
                        </div>

                        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8eaed;">
                            <button type="button" class="btn btn-secondary">
                                <i class="fas fa-trash"></i> Clear Backups
                            </button>
                            <button type="button" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download Backup
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SMS Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-sms me-2"></i> SMS Settings
                </div>
                <div class="card-body">
                    <!-- Current Settings Display -->
                    <div id="currentSmsSettings" class="mb-4" style="display: <?php echo !empty($sms_settings) ? 'block' : 'none'; ?>;">
                        <h5 class="mb-3">Current SMS Settings</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <td><strong>SMS Provider:</strong></td>
                                    <td><?php echo htmlspecialchars($sms_settings['sms_provider'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Mobitech API Key:</strong></td>
                                    <td>
                                        <span id="displayMobitechKey">••••••••••••••••</span>
                                        <button type="button" class="btn btn-sm btn-link" onclick="toggleMobitechKey()" style="padding: 0; margin-left: 10px;">
                                            <i class="fas fa-eye" id="displayMobitechKeyIcon"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Mobitech Sender ID:</strong></td>
                                    <td><?php echo htmlspecialchars($sms_settings['mobitech_sender_id'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Text SMS API Key:</strong></td>
                                    <td>
                                        <span id="displayTextsmsKey">••••••••••••••••</span>
                                        <button type="button" class="btn btn-sm btn-link" onclick="toggleTextsmsKey()" style="padding: 0; margin-left: 10px;">
                                            <i class="fas fa-eye" id="displayTextsmsKeyIcon"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Text SMS Partner ID:</strong></td>
                                    <td><?php echo htmlspecialchars($sms_settings['textsms_partner_id'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Text SMS Sender ID:</strong></td>
                                    <td><?php echo htmlspecialchars($sms_settings['textsms_sender_id'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SMS Enabled:</strong></td>
                                    <td><?php echo ($sms_settings['sms_enabled'] ?? 0) ? 'Yes' : 'No'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Updated:</strong></td>
                                    <td><?php echo !empty($sms_settings['updated_at']) ? date('Y-m-d H:i:s', strtotime($sms_settings['updated_at'])) : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_sms_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">SMS Provider</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card p-3" style="border: 2px solid #e0e0e0; cursor: pointer;" id="mobitechCard" onclick="selectSmsProvider('mobitech')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sms_provider" value="mobitech" id="mobitechProvider" <?php echo ($sms_settings['sms_provider'] ?? 'mobitech') === 'mobitech' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="mobitechProvider">
                                                <strong>Mobitech</strong>
                                                <br><small class="text-muted">Reliable SMS gateway for Kenya</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card p-3" style="border: 2px solid #e0e0e0; cursor: pointer;" id="textsmsCard" onclick="selectSmsProvider('textsms')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sms_provider" value="textsms" id="textsmsProvider" <?php echo ($sms_settings['sms_provider'] ?? 'mobitech') === 'textsms' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="textsmsProvider">
                                                <strong>Text SMS</strong>
                                                <br><small class="text-muted">Alternative SMS provider</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="mobitechFields">
                            <label class="form-label">Mobitech API Key</label>
                            <input type="password" class="form-control" id="mobitech_api_key" name="mobitech_api_key" placeholder="Enter your Mobitech API key" value="<?php echo htmlspecialchars($sms_settings['mobitech_api_key'] ?? ''); ?>">
                            <small class="text-muted">Get your API key from <a href="https://mobitech.co.ke" target="_blank">Mobitech</a></small>
                        </div>
                        
                        <div class="mb-3" id="mobitechSenderIdField">
                            <label class="form-label">Mobitech Sender ID</label>
                            <input type="text" class="form-control" id="mobitech_sender_id" name="mobitech_sender_id" placeholder="Enter your Mobitech sender ID" maxlength="11" value="<?php echo htmlspecialchars($sms_settings['mobitech_sender_id'] ?? ''); ?>">
                            <small class="text-muted">Your unique sender ID for Mobitech (max 11 characters)</small>
                        </div>
                        
                        <div class="mb-3" id="textsmsFields" style="display: none;">
                            <label class="form-label">Text SMS API Key</label>
                            <input type="password" class="form-control" id="textsms_api_key" name="textsms_api_key" placeholder="Enter your Text SMS API key" value="<?php echo htmlspecialchars($sms_settings['textsms_api_key'] ?? ''); ?>">
                            <small class="text-muted">Get your API key from <a href="https://textsms.co.ke" target="_blank">Text SMS</a></small>
                        </div>
                        
                        <div class="mb-3" id="textsmsPartnerIdField" style="display: none;">
                            <label class="form-label">Text SMS Partner ID</label>
                            <input type="text" class="form-control" id="textsms_partner_id" name="textsms_partner_id" placeholder="Enter your Partner ID" value="<?php echo htmlspecialchars($sms_settings['textsms_partner_id'] ?? ''); ?>">
                            <small class="text-muted">Your Partner ID from Text SMS account</small>
                        </div>
                        
                        <div class="mb-3" id="textsmsSenderIdField" style="display: none;">
                            <label class="form-label">Text SMS Sender ID</label>
                            <input type="text" class="form-control" id="textsms_sender_id" name="textsms_sender_id" placeholder="Enter your Text SMS sender ID" maxlength="11" value="<?php echo htmlspecialchars($sms_settings['textsms_sender_id'] ?? ''); ?>">
                            <small class="text-muted">Your unique sender ID for Text SMS (max 11 characters)</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" value="1" <?php echo ($sms_settings['sms_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_enabled">
                                    <strong>Enable SMS Notifications</strong>
                                </label>
                            </div>
                            <small class="text-muted">Turn on to send SMS notifications from admin panel</small>
                        </div>
                        
                        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8eaed;">
                            <button type="button" class="btn btn-secondary">Cancel</button>
                            <button type="button" class="btn btn-outline-primary" onclick="checkSmsBalance()">
                                <i class="fas fa-wallet"></i> Check Balance
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                    
                    <!-- Balance Display -->
                    <div id="smsBalanceDisplay" class="mt-4" style="display: none;">
                        <h5 class="mb-3">SMS Balance</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <td><strong>Mobitech Balance:</strong></td>
                                    <td id="mobitechBalance">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Text SMS Balance:</strong></td>
                                    <td id="textsmsBalance">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Checked:</strong></td>
                                    <td id="balanceLastChecked">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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
        
        function testSmtpConnection() {
            const terminal = document.getElementById('terminal');
            const smtpHost = document.getElementById('smtp_host').value;
            const smtpPort = document.getElementById('smtp_port').value;
            const smtpUsername = document.getElementById('smtp_username').value;
            const smtpPassword = document.getElementById('smtp_password').value;
            const emailFrom = document.getElementById('email_from').value;
            const encryption = document.getElementById('encryption').value;
            
            // Clear terminal
            terminal.innerHTML = '';
            
            // Add test output lines
            addTerminalLine('info', 'Starting SMTP connection test...');
            addTerminalLine('info', 'Host: ' + smtpHost);
            addTerminalLine('info', 'Port: ' + smtpPort);
            addTerminalLine('info', 'Username: ' + smtpUsername);
            addTerminalLine('info', 'From: ' + emailFrom);
            addTerminalLine('info', 'Encryption: ' + encryption);
            
            // Validate inputs
            if (!smtpHost || !smtpPort || !smtpUsername || !smtpPassword || !emailFrom) {
                addTerminalLine('error', 'Error: All SMTP fields are required');
                return;
            }
            
            addTerminalLine('warning', 'Testing connection...');
            
            // Send AJAX request to test SMTP
            fetch('settings.php?action=test_smtp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    smtp_host: smtpHost,
                    smtp_port: smtpPort,
                    smtp_username: smtpUsername,
                    smtp_password: smtpPassword,
                    email_from: emailFrom,
                    encryption: encryption
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addTerminalLine('success', '✓ SMTP connection successful!');
                    addTerminalLine('success', '✓ Authentication verified');
                    addTerminalLine('success', '✓ Email sending capability confirmed');
                } else {
                    addTerminalLine('error', '✗ SMTP connection failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                addTerminalLine('error', '✗ Test failed: ' + error.message);
            });
        }
        
        function addTerminalLine(type, message) {
            const terminal = document.getElementById('terminal');
            const line = document.createElement('div');
            line.className = 'terminal-line ' + type;
            line.textContent = message;
            terminal.appendChild(line);
            terminal.scrollTop = terminal.scrollHeight;
        }
        
        function toggleDisplayPassword() {
            const passwordElement = document.getElementById('displayPassword');
            const iconElement = document.getElementById('displayPasswordIcon');
            const actualPassword = '<?php echo htmlspecialchars($smtp_settings['smtp_password'] ?? ''); ?>';
            
            if (passwordElement.textContent === '••••••••••••••••') {
                passwordElement.textContent = actualPassword;
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                passwordElement.textContent = '••••••••••••••••';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
        
        function toggleMobitechKey() {
            const keyElement = document.getElementById('displayMobitechKey');
            const iconElement = document.getElementById('displayMobitechKeyIcon');
            const actualKey = '<?php echo htmlspecialchars($sms_settings['mobitech_api_key'] ?? ''); ?>';
            
            if (keyElement.textContent === '••••••••••••••••') {
                keyElement.textContent = actualKey;
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                keyElement.textContent = '••••••••••••••••';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
        
        function toggleTextsmsKey() {
            const keyElement = document.getElementById('displayTextsmsKey');
            const iconElement = document.getElementById('displayTextsmsKeyIcon');
            const actualKey = '<?php echo htmlspecialchars($sms_settings['textsms_api_key'] ?? ''); ?>';
            
            if (keyElement.textContent === '••••••••••••••••') {
                keyElement.textContent = actualKey;
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                keyElement.textContent = '••••••••••••••••';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
        
        function triggerBackup() {
            if (!confirm('Are you sure you want to create a database backup now?')) {
                return;
            }
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Creating Backup...';
            btn.disabled = true;
            
            fetch('backup.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Backup created successfully!\n\nFile: ' + data.backup_file + '\nSize: ' + formatBytes(data.size) + '\nOld backups deleted: ' + data.deleted_count);
                } else {
                    alert('Backup failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Backup failed: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        function selectSmsProvider(provider) {
            const mobitechCard = document.getElementById('mobitechCard');
            const textsmsCard = document.getElementById('textsmsCard');
            const mobitechProvider = document.getElementById('mobitechProvider');
            const textsmsProvider = document.getElementById('textsmsProvider');
            const mobitechFields = document.getElementById('mobitechFields');
            const mobitechSenderIdField = document.getElementById('mobitechSenderIdField');
            const textsmsFields = document.getElementById('textsmsFields');
            const textsmsPartnerIdField = document.getElementById('textsmsPartnerIdField');
            const textsmsSenderIdField = document.getElementById('textsmsSenderIdField');
            
            if (provider === 'mobitech') {
                mobitechCard.style.borderColor = '#FF6B35';
                textsmsCard.style.borderColor = '#e0e0e0';
                mobitechProvider.checked = true;
                mobitechFields.style.display = 'block';
                mobitechSenderIdField.style.display = 'block';
                textsmsFields.style.display = 'none';
                textsmsPartnerIdField.style.display = 'none';
                textsmsSenderIdField.style.display = 'none';
            } else {
                textsmsCard.style.borderColor = '#FF6B35';
                mobitechCard.style.borderColor = '#e0e0e0';
                textsmsProvider.checked = true;
                mobitechFields.style.display = 'none';
                mobitechSenderIdField.style.display = 'none';
                textsmsFields.style.display = 'block';
                textsmsPartnerIdField.style.display = 'block';
                textsmsSenderIdField.style.display = 'block';
            }
        }
        
        // Initialize SMS provider selection on page load
        document.addEventListener('DOMContentLoaded', function() {
            const currentProvider = document.querySelector('input[name="sms_provider"]:checked')?.value || 'mobitech';
            selectSmsProvider(currentProvider);
        });
        
        function checkSmsBalance() {
            const balanceDisplay = document.getElementById('smsBalanceDisplay');
            const mobitechBalance = document.getElementById('mobitechBalance');
            const textsmsBalance = document.getElementById('textsmsBalance');
            const balanceLastChecked = document.getElementById('balanceLastChecked');
            
            // Show loading state
            mobitechBalance.textContent = 'Checking...';
            textsmsBalance.textContent = 'Checking...';
            balanceDisplay.style.display = 'block';
            
            try {
                // Check Mobitech balance
                fetch('../sms/api/check_balance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ provider: 'mobitech' })
                })
                .then(response => response.json())
                .then(data => {
                    mobitechBalance.textContent = data.success ? data.balance + ' SMS' : 'Error: ' + (data.error || 'Unknown');
                })
                .catch(error => {
                    mobitechBalance.textContent = 'Error';
                });
                
                // Check Text SMS balance
                fetch('../sms/api/check_balance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ provider: 'textsms' })
                })
                .then(response => response.json())
                .then(data => {
                    textsmsBalance.textContent = data.success ? data.balance + ' SMS' : 'Error: ' + (data.error || 'Unknown');
                })
                .catch(error => {
                    textsmsBalance.textContent = 'Error';
                });
                
                // Update last checked time
                balanceLastChecked.textContent = new Date().toLocaleString();
            } catch (error) {
                mobitechBalance.textContent = 'Error';
                textsmsBalance.textContent = 'Error';
            }
        }
    </script>

    <!-- Footer -->
    <footer style="background: transparent; color: #5f6368; padding: 2rem; text-align: center; border-top: 1px solid #e8eaed; margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
