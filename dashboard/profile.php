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
require_once '../includes/helpers.php';
$user_settings = getUserSettings($conn, $user_id);
$dark_mode_class = applyUserTheme($user_settings['theme'] ?? 'light');

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email is already taken by another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email is already taken by another user";
        } else {
            // Update user profile
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $name, $email, $user_id);
            
            if ($update_stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = "Failed to update profile";
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    if (password_verify($current_password, $user['password'])) {
        if (strlen($new_password) < 6) {
            $password_error = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "Passwords do not match";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $password_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($password_stmt->execute()) {
                $password_success = "Password changed successfully!";
            } else {
                $password_error = "Failed to change password";
            }
        }
    } else {
        $password_error = "Current password is incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Profile - Kenya EduHub</title>
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
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 500;
        }
        
        .profile-info h1 {
            font-size: 28px;
            font-weight: 400;
            color: var(--text-color);
            margin-bottom: 8px;
        }
        
        .profile-info p {
            color: var(--secondary-color);
            margin: 0;
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
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
        <a class="nav-link active" href="profile">
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
        <h1 class="page-title">Profile</h1>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h1>
                <p><?php echo htmlspecialchars($user['email'] ?? 'user@example.com'); ?></p>
            </div>
        </div>
        
        <!-- Profile Update Form -->
        <div class="card">
            <div class="card-header">
                <h2>Update Profile</h2>
            </div>
            <div class="card-body">
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
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password Form -->
        <div class="card">
            <div class="card-header">
                <h2>Change Password</h2>
            </div>
            <div class="card-body">
                <?php if (isset($password_error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($password_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($password_success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($password_success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
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
    </script>
</body>
</html>