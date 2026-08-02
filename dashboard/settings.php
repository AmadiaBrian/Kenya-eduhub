<?php
// Session and security are handled by index.php router
// No need to repeat session_start() and security checks here

require_once '../config.php';
require_once '../includes/helpers.php';

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user settings
$user_settings = getUserSettings($conn, $user_id);
$dark_mode_class = applyUserTheme($user_settings['theme'] ?? 'light');

// Create deleted_accounts table for soft delete functionality
try {
    $create_deleted_accounts_sql = "CREATE TABLE IF NOT EXISTS deleted_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_user_id INT NOT NULL,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        password VARCHAR(255),
        role VARCHAR(50),
        is_verified TINYINT(1) DEFAULT 0,
        verification_code VARCHAR(10),
        code_expires_at DATETIME,
        last_login DATETIME,
        created_at DATETIME,
        deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        deletion_reason TEXT,
        user_data JSON,
        resources_data JSON,
        activity_log JSON
    )";
    $conn->query($create_deleted_accounts_sql);
} catch (Exception $e) {
    error_log("Failed to create deleted_accounts table: " . $e->getMessage());
}

// Add created_at and last_login columns to users table if they don't exist
try {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME");
} catch (Exception $e) {
    error_log("Failed to add created_at/last_login columns: " . $e->getMessage());
}

// Create settings table if it doesn't exist (for new installations)
try {
    // First try with foreign key constraint
    $create_table_sql = "CREATE TABLE IF NOT EXISTS user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_uploads TINYINT(1) DEFAULT 1,
        email_comments TINYINT(1) DEFAULT 1,
        email_updates TINYINT(1) DEFAULT 0,
        show_profile TINYINT(1) DEFAULT 1,
        show_email TINYINT(1) DEFAULT 0,
        theme VARCHAR(20) DEFAULT 'light',
        language VARCHAR(10) DEFAULT 'en',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id)
    )";
    $conn->query($create_table_sql);
} catch (Exception $e) {
    // If foreign key constraint fails, try without it
    try {
        $create_table_sql_no_fk = "CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email_uploads TINYINT(1) DEFAULT 1,
            email_comments TINYINT(1) DEFAULT 1,
            email_updates TINYINT(1) DEFAULT 0,
            show_profile TINYINT(1) DEFAULT 1,
            show_email TINYINT(1) DEFAULT 0,
            theme VARCHAR(20) DEFAULT 'light',
            language VARCHAR(10) DEFAULT 'en',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id)
        )";
        $conn->query($create_table_sql_no_fk);
    } catch (Exception $e2) {
        // If that also fails, try without unique constraint
        try {
            $create_table_sql_simple = "CREATE TABLE IF NOT EXISTS user_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email_uploads TINYINT(1) DEFAULT 1,
                email_comments TINYINT(1) DEFAULT 1,
                email_updates TINYINT(1) DEFAULT 0,
                show_profile TINYINT(1) DEFAULT 1,
                show_email TINYINT(1) DEFAULT 0,
                theme VARCHAR(20) DEFAULT 'light',
                language VARCHAR(10) DEFAULT 'en',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($create_table_sql_simple);
        } catch (Exception $e3) {
            // Last resort - check if table exists and handle gracefully
            $check_table = $conn->query("SHOW TABLES LIKE 'user_settings'");
            if ($check_table->num_rows == 0) {
                // Table doesn't exist and we couldn't create it
                error_log("Failed to create user_settings table: " . $e3->getMessage());
            }
        }
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle account deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        $deletion_reason = $_POST['deletion_reason'] ?? 'User requested deletion';
        $confirm_email = $_POST['confirm_email'] ?? '';
        
        // Verify email matches
        if (strtolower($confirm_email) !== strtolower($user['email'])) {
            $error = "Email confirmation does not match. Please enter your correct email address.";
        } else {
            // Start transaction for safe deletion
            $conn->begin_transaction();
            
            try {
                // Collect user data for backup
                $user_data = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'is_verified' => $user['is_verified'],
                    'created_at' => $user['created_at'] ?? null,
                    'last_login' => $user['last_login'] ?? null
                ];
                
                // Collect user's resources
                $resources_stmt = $conn->prepare("SELECT * FROM resources WHERE user_id = ?");
                $resources_stmt->bind_param("i", $user_id);
                $resources_stmt->execute();
                $resources_result = $resources_stmt->get_result();
                $resources_data = [];
                while ($row = $resources_result->fetch_assoc()) {
                    $resources_data[] = $row;
                }
                
                // Collect user's activity log (if exists)
                $activity_data = [];
                try {
                    $activity_stmt = $conn->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
                    $activity_stmt->bind_param("i", $user_id);
                    $activity_stmt->execute();
                    $activity_result = $activity_stmt->get_result();
                    while ($row = $activity_result->fetch_assoc()) {
                        $activity_data[] = $row;
                    }
                } catch (Exception $e) {
                    // Activity log table might not exist, skip
                }
                
                // Create variables for bind_param (must be passed by reference)
                $original_user_id = $user['id'];
                $name = $user['name'];
                $email = $user['email'];
                $password = $user['password'];
                $role = $user['role'];
                $is_verified = $user['is_verified'];
                $verification_code = $user['verification_code'] ?? null;
                $code_expires_at = $user['code_expires_at'] ?? null;
                $last_login = $user['last_login'] ?? null;
                $created_at = $user['created_at'] ?? date('Y-m-d H:i:s');
                $user_data_json = json_encode($user_data);
                $resources_data_json = json_encode($resources_data);
                $activity_data_json = json_encode($activity_data);
                
                // Create variables for bind_param (must be passed by reference)
                $original_user_id = $user['id'];
                $name = $user['name'];
                $email = $user['email'];
                $password = $user['password'];
                $role = $user['role'];
                $is_verified = $user['is_verified'];
                $verification_code = $user['verification_code'] ?? null;
                $code_expires_at = $user['code_expires_at'] ?? null;
                $last_login = $user['last_login'] ?? null;
                $created_at = $user['created_at'] ?? date('Y-m-d H:i:s');
                $user_data_json = json_encode($user_data);
                $resources_data_json = json_encode($resources_data);
                $activity_data_json = json_encode($activity_data);
                
                // Check if email already exists in deleted_accounts and update it
                $check_deleted_stmt = $conn->prepare("SELECT id FROM deleted_accounts WHERE email = ?");
                $check_deleted_stmt->bind_param("s", $email);
                $check_deleted_stmt->execute();
                $existing_deleted = $check_deleted_stmt->get_result()->fetch_assoc();
                
                if ($existing_deleted) {
                    // Update existing deleted account entry
                    $update_deleted_stmt = $conn->prepare("UPDATE deleted_accounts SET 
                        original_user_id = ?, name = ?, email = ?, password = ?, role = ?, is_verified = ?, 
                        verification_code = ?, code_expires_at = ?, last_login = ?, created_at = ?, 
                        deletion_reason = ?, user_data = ?, resources_data = ?, activity_log = ?, deleted_at = NOW()
                        WHERE id = ?");
                    
                    $deleted_id = $existing_deleted['id'];
                    
                    // Ensure all variables are properly defined for bind_param
                    $v1 = $original_user_id;
                    $v2 = $name;
                    $v3 = $email;
                    $v4 = $password;
                    $v5 = $role;
                    $v6 = $is_verified;
                    $v7 = $verification_code;
                    $v8 = $code_expires_at;
                    $v9 = $last_login;
                    $v10 = $created_at;
                    $v11 = $deletion_reason;
                    $v12 = $user_data_json;
                    $v13 = $resources_data_json;
                    $v14 = $activity_data_json;
                    $v15 = $deleted_id;
                    
                    $update_deleted_stmt->bind_param("issssissssssssi",
                        $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11, $v12, $v13, $v14, $v15
                    );
                    $update_deleted_stmt->execute();
                } else {
                    // Insert new deleted account entry
                    $insert_deleted_stmt = $conn->prepare("INSERT INTO deleted_accounts 
                        (original_user_id, name, email, password, role, is_verified, verification_code, 
                         code_expires_at, last_login, created_at, deletion_reason, user_data, resources_data, activity_log)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $insert_deleted_stmt->bind_param("isssisssssssss",
                        $original_user_id,
                        $name,
                        $email,
                        $password,
                        $role,
                        $is_verified,
                        $verification_code,
                        $code_expires_at,
                        $last_login,
                        $created_at,
                        $deletion_reason,
                        $user_data_json,
                        $resources_data_json,
                        $activity_data_json
                    );
                    
                    $insert_deleted_stmt->execute();
                }
                
                // Delete user's resources
                $delete_resources_stmt = $conn->prepare("DELETE FROM resources WHERE user_id = ?");
                $delete_resources_stmt->bind_param("i", $user_id);
                $delete_resources_stmt->execute();
                
                // Delete user's settings
                $delete_settings_stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
                $delete_settings_stmt->bind_param("i", $user_id);
                $delete_settings_stmt->execute();
                
                // Delete remember tokens (if table exists)
                try {
                    $delete_tokens_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                    $delete_tokens_stmt->bind_param("i", $user_id);
                    $delete_tokens_stmt->execute();
                } catch (Exception $e) {
                    // Table might not exist, skip this step
                    error_log("Remember tokens table doesn't exist, skipping: " . $e->getMessage());
                }
                
                // Delete the user account
                $delete_user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_user_stmt->bind_param("i", $user_id);
                $delete_user_stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                // Log the deletion
                logActivity('ACCOUNT_DELETED', 'User account deleted', [
                    'user_id' => $user_id,
                    'user_email' => $user['email'],
                    'deletion_reason' => $deletion_reason,
                    'resources_count' => count($resources_data)
                ]);
                
                // Destroy session and redirect
                session_destroy();
                header("Location: ../index.php?account_deleted=true");
                exit();
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = "Failed to delete account: " . $e->getMessage();
                error_log("Account deletion failed: " . $e->getMessage());
            }
        }
    } else {
        // Handle regular settings update
        $email_uploads = isset($_POST['email_uploads']) ? 1 : 0;
        $email_comments = isset($_POST['email_comments']) ? 1 : 0;
        $email_updates = isset($_POST['email_updates']) ? 1 : 0;
        $show_profile = isset($_POST['show_profile']) ? 1 : 0;
        $show_email = isset($_POST['show_email']) ? 1 : 0;
        $theme = $_POST['theme'] ?? 'light';
        $language = $_POST['language'] ?? 'en';

        // Check if user settings already exist
        $check_stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing settings
            $update_stmt = $conn->prepare("UPDATE user_settings SET
                email_uploads = ?,
                email_comments = ?,
                email_updates = ?,
                show_profile = ?,
                show_email = ?,
                theme = ?,
                language = ?
                WHERE user_id = ?");

            $update_stmt->bind_param("iiiissii",
                $email_uploads,
                $email_comments,
                $email_updates,
                $show_profile,
                $show_email,
                $theme,
                $language,
                $user_id
            );

            if ($update_stmt->execute()) {
                $success = "Settings saved successfully!";
            } else {
                $error = "Failed to save settings. Please try again.";
            }
        } else {
            // Insert new settings
            $insert_stmt = $conn->prepare("INSERT INTO user_settings
                (user_id, email_uploads, email_comments, email_updates, show_profile, show_email, theme, language)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $insert_stmt->bind_param("iiiissii",
                $user_id,
                $email_uploads,
                $email_comments,
                $email_updates,
                $show_profile,
                $show_email,
                $theme,
                $language
            );

            if ($insert_stmt->execute()) {
                $success = "Settings saved successfully!";
            } else {
                $error = "Failed to save settings. Please try again.";
            }
        }

        // Refresh settings after save
        $user_settings = getUserSettings($conn, $user_id);
        $dark_mode_class = applyUserTheme($user_settings['theme'] ?? 'light');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Settings - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            color: var(--secondary-color);
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
        
        .dark-mode .dark-mode-toggle {
            color: var(--text-color);
        }
        
        .dark-mode .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
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
        
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--form-border-color);
            border-radius: 25px;
            font-size: 14px;
            background: var(--card-bg);
            color: var(--text-color);
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .form-check-label {
            cursor: pointer;
            color: var(--text-color);
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
        
        .btn-secondary {
            background: var(--card-hover-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #e8eaed;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #f5c6cb;
        }
        
        .settings-section {
            margin-bottom: 32px;
        }
        
        .settings-section h3 {
            font-size: 18px;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 16px;
        }
        
        .settings-description {
            color: var(--secondary-color);
            margin-bottom: 20px;
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
        <a class="nav-link" href="dashboard">
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
        <a class="nav-link active" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="../auth/logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Settings</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Account Settings -->
            <div class="card">
                <div class="card-header">
                    <h2>Account Settings</h2>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <h3>Email Notifications</h3>
                        <p class="settings-description">Choose which email notifications you want to receive.</p>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="email_uploads" name="email_uploads" <?php echo ($user_settings['email_uploads'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_uploads">
                                Notify me when my resources are downloaded
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="email_comments" name="email_comments" <?php echo ($user_settings['email_comments'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_comments">
                                Notify me of new comments on my resources
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="email_updates" name="email_updates" <?php echo ($user_settings['email_updates'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_updates">
                                Notify me about platform updates and new features
                            </label>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>Privacy Settings</h3>
                        <p class="settings-description">Control your privacy preferences.</p>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="show_profile" name="show_profile" <?php echo ($user_settings['show_profile'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="show_profile">
                                Make my profile visible to other users
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="show_email" name="show_email" <?php echo ($user_settings['show_email'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="show_email">
                                Show my email address on my profile
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="card">
                <div class="card-header">
                    <h2>Display Settings</h2>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <h3>Theme</h3>
                        <p class="settings-description">Choose your preferred theme.</p>

                        <div class="form-group">
                            <label for="theme">Theme Preference</label>
                            <select class="form-control" id="theme" name="theme">
                                <option value="light" <?php echo ($user_settings['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                                <option value="dark" <?php echo ($user_settings['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                <option value="auto" <?php echo ($user_settings['theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>>System Default</option>
                            </select>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>Language</h3>
                        <p class="settings-description">Select your preferred language.</p>

                        <div class="form-group">
                            <label for="language">Language</label>
                            <select class="form-control" id="language" name="language">
                                <option value="en" <?php echo ($user_settings['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="sw" <?php echo ($user_settings['language'] ?? 'en') === 'sw' ? 'selected' : ''; ?>>Kiswahili</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card" style="border-color: #fce8e6;">
                <div class="card-header">
                    <h2 style="color: #c5221f;">Danger Zone</h2>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <h3 style="color: #c5221f;">Delete Account</h3>
                        <p class="settings-description">
                            Once you delete your account, all your data will be archived for 30 days before permanent deletion. Please be certain.
                        </p>

                        <button class="btn btn-secondary" style="color: #c5221f; border-color: #c5221f;" type="button" id="openDeleteModalBtn">
                            <i class="fas fa-trash"></i> Delete My Account
                        </button>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div style="text-align: right; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </main>

    <!-- Custom Delete Account Modal (Google Search Console Style) -->
    <div id="deleteAccountModal" class="gsc-modal-overlay" style="display: none;">
        <div class="gsc-modal">
            <div class="gsc-modal-header">
                <h2 class="gsc-modal-title">Delete Account</h2>
                <button class="gsc-modal-close" id="closeDeleteModalBtn">&times;</button>
            </div>
            <div class="gsc-modal-body">
                <div class="gsc-modal-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Warning:</strong> This action cannot be undone. Your account and all associated data will be deleted.
                    </div>
                </div>
                
                <form id="deleteAccountForm" method="POST">
                    <input type="hidden" name="action" value="delete_account">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFLite(); ?>">
                    
                    <div class="gsc-form-group">
                        <label for="confirm_email" class="gsc-form-label">Confirm your email address</label>
                        <input type="email" class="gsc-form-input" id="confirm_email" name="confirm_email" required placeholder="<?php echo htmlspecialchars($user['email']); ?>">
                        <small class="gsc-form-hint">This is to prevent accidental deletion</small>
                    </div>
                    
                    <div class="gsc-form-group">
                        <label for="deletion_reason" class="gsc-form-label">Reason for deletion (optional)</label>
                        <textarea class="gsc-form-input gsc-form-textarea" id="deletion_reason" name="deletion_reason" rows="3" placeholder="Why are you leaving?"></textarea>
                    </div>
                    
                    <div class="gsc-form-group">
                        <label class="gsc-checkbox-label">
                            <input type="checkbox" id="confirmDelete" required class="gsc-checkbox">
                            <span class="gsc-checkbox-text">I understand that my account will be deleted and this action cannot be undone</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="gsc-modal-footer">
                <button class="gsc-btn gsc-btn-secondary" id="cancelDeleteBtn">Cancel</button>
                <button class="gsc-btn gsc-btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </div>
    </div>

    <!-- Custom Modal Styles -->
    <style>
        .gsc-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: gscFadeIn 0.2s ease-out;
        }

        @keyframes gscFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .gsc-modal {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 10px 20px rgba(0, 0, 0, 0.19);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: gscSlideUp 0.3s ease-out;
        }

        @keyframes gscSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .gsc-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e8eaed;
        }

        .gsc-modal-title {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
            margin: 0;
        }

        .gsc-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #5f6368;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .gsc-modal-close:hover {
            background: #f1f3f4;
        }

        .gsc-modal-body {
            padding: 24px;
        }

        .gsc-modal-warning {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: #fce8e6;
            border-radius: 4px;
            margin-bottom: 24px;
            color: #c5221f;
            font-size: 14px;
        }

        .gsc-modal-warning i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .gsc-form-group {
            margin-bottom: 20px;
        }

        .gsc-form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }

        .gsc-form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .gsc-form-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .gsc-form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .gsc-form-hint {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #5f6368;
        }

        .gsc-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            cursor: pointer;
        }

        .gsc-checkbox {
            margin-top: 2px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .gsc-checkbox-text {
            font-size: 14px;
            color: #202124;
            line-height: 1.5;
        }

        .gsc-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px;
            border-top: 1px solid #e8eaed;
            background: #f8f9fa;
        }

        .gsc-btn {
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s;
        }

        .gsc-btn-secondary {
            background: white;
            color: #5f6368;
            border: 1px solid #dadce0;
        }

        .gsc-btn-secondary:hover {
            background: #f1f3f4;
        }

        .gsc-btn-danger {
            background: #c5221f;
            color: white;
        }

        .gsc-btn-danger:hover {
            background: #b41520;
        }

        .dark-mode .gsc-modal {
            background: #1a1a1a;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3), 0 10px 20px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .gsc-modal-header {
            border-bottom-color: #2a2a2a;
        }

        .dark-mode .gsc-modal-title {
            color: #e8eaed;
        }

        .dark-mode .gsc-modal-close {
            color: #9aa0a6;
        }

        .dark-mode .gsc-modal-close:hover {
            background: #2a2a2a;
        }

        .dark-mode .gsc-form-label {
            color: #e8eaed;
        }

        .dark-mode .gsc-form-input {
            background: #252525;
            border-color: #3a3a3a;
            color: #e8eaed;
        }

        .dark-mode .gsc-form-input:focus {
            border-color: #8ab4f8;
            box-shadow: 0 0 0 2px rgba(138, 180, 248, 0.2);
        }

        .dark-mode .gsc-form-hint {
            color: #9aa0a6;
        }

        .dark-mode .gsc-checkbox-text {
            color: #e8eaed;
        }

        .dark-mode .gsc-modal-footer {
            background: #1a1a1a;
            border-top-color: #2a2a2a;
        }

        .dark-mode .gsc-btn-secondary {
            background: #252525;
            color: #e8eaed;
            border-color: #3a3a3a;
        }

        .dark-mode .gsc-btn-secondary:hover {
            background: #2a2a2a;
        }
    </style>

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
        document.addEventListener('DOMContentLoaded', function() {
            // Custom Modal Handlers
            const openDeleteModalBtn = document.getElementById('openDeleteModalBtn');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const deleteAccountModal = document.getElementById('deleteAccountModal');
            const deleteAccountForm = document.getElementById('deleteAccountForm');

            // Open modal
            if (openDeleteModalBtn) {
                openDeleteModalBtn.addEventListener('click', function() {
                    deleteAccountModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            }

            // Close modal functions
            function closeModal() {
                deleteAccountModal.style.display = 'none';
                document.body.style.overflow = '';
                // Reset form
                deleteAccountForm.reset();
            }

            // Close button
            if (closeDeleteModalBtn) {
                closeDeleteModalBtn.addEventListener('click', closeModal);
            }

            // Cancel button
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', closeModal);
            }

            // Close on overlay click
            deleteAccountModal.addEventListener('click', function(e) {
                if (e.target === deleteAccountModal) {
                    closeModal();
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && deleteAccountModal.style.display === 'flex') {
                    closeModal();
                }
            });

            // Confirm delete button
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    const confirmEmail = document.getElementById('confirm_email').value;
                    const confirmCheckbox = document.getElementById('confirmDelete');
                    
                    if (!confirmCheckbox.checked) {
                        alert('Please confirm that you understand this action cannot be undone.');
                        return;
                    }
                    
                    if (!confirmEmail) {
                        alert('Please enter your email address to confirm deletion.');
                        return;
                    }
                    
                    // Submit the form
                    deleteAccountForm.submit();
                });
            }
        });

        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const toggleBtn = document.querySelector('.dark-mode-toggle i');
            const themeSelect = document.getElementById('theme');
            
            if (document.body.classList.contains('dark-mode')) {
                toggleBtn.classList.remove('fa-moon');
                toggleBtn.classList.add('fa-sun');
                localStorage.setItem('darkMode', 'enabled');
                if (themeSelect) themeSelect.value = 'dark';
                
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
                if (themeSelect) themeSelect.value = 'light';
                
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
            const themeSelect = document.getElementById('theme');
            
            // Apply user's saved theme from database (already applied via PHP)
            // Update localStorage to match
            const currentTheme = themeSelect ? themeSelect.value : 'light';
            
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                const toggleBtn = document.querySelector('.dark-mode-toggle i');
                if (toggleBtn) {
                    toggleBtn.classList.remove('fa-moon');
                    toggleBtn.classList.add('fa-sun');
                }
                localStorage.setItem('darkMode', 'enabled');
            } else if (currentTheme === 'light') {
                document.body.classList.remove('dark-mode');
                const toggleBtn = document.querySelector('.dark-mode-toggle i');
                if (toggleBtn) {
                    toggleBtn.classList.remove('fa-sun');
                    toggleBtn.classList.add('fa-moon');
                }
                localStorage.setItem('darkMode', 'disabled');
            } else if (currentTheme === 'auto') {
                // Check system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.body.classList.add('dark-mode');
                    const toggleBtn = document.querySelector('.dark-mode-toggle i');
                    if (toggleBtn) {
                        toggleBtn.classList.remove('fa-moon');
                        toggleBtn.classList.add('fa-sun');
                    }
                } else {
                    document.body.classList.remove('dark-mode');
                    const toggleBtn = document.querySelector('.dark-mode-toggle i');
                    if (toggleBtn) {
                        toggleBtn.classList.remove('fa-sun');
                        toggleBtn.classList.add('fa-moon');
                    }
                }
                localStorage.setItem('darkMode', 'auto');
            }
            
            // Theme select handler
            if (themeSelect) {
                themeSelect.addEventListener('change', function() {
                    const theme = this.value;
                    if (theme === 'dark') {
                        document.body.classList.add('dark-mode');
                        const toggleBtn = document.querySelector('.dark-mode-toggle i');
                        if (toggleBtn) {
                            toggleBtn.classList.remove('fa-moon');
                            toggleBtn.classList.add('fa-sun');
                        }
                        localStorage.setItem('darkMode', 'enabled');
                    } else if (theme === 'light') {
                        document.body.classList.remove('dark-mode');
                        const toggleBtn = document.querySelector('.dark-mode-toggle i');
                        if (toggleBtn) {
                            toggleBtn.classList.remove('fa-sun');
                            toggleBtn.classList.add('fa-moon');
                        }
                        localStorage.setItem('darkMode', 'disabled');
                    } else if (theme === 'auto') {
                        // Check system preference
                        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            document.body.classList.add('dark-mode');
                            const toggleBtn = document.querySelector('.dark-mode-toggle i');
                            if (toggleBtn) {
                                toggleBtn.classList.remove('fa-moon');
                                toggleBtn.classList.add('fa-sun');
                            }
                        } else {
                            document.body.classList.remove('dark-mode');
                            const toggleBtn = document.querySelector('.dark-mode-toggle i');
                            if (toggleBtn) {
                                toggleBtn.classList.remove('fa-sun');
                                toggleBtn.classList.add('fa-moon');
                            }
                        }
                        localStorage.setItem('darkMode', 'auto');
                    }
                });
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
    </script>
</body>
</html>