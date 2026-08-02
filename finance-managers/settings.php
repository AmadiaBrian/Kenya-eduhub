<?php
// Finance Manager Settings Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['finance_manager_id']) || !isset($_SESSION['school_id'])) {
    header('Location: index.php?route=login');
    exit;
}

$finance_manager_id = $_SESSION['finance_manager_id'];
$school_id = $_SESSION['school_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $errors = [];
    $success = '';
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE finance_managers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $finance_manager_id]);
            $success = 'Profile updated successfully!';
            
            // Update session
            $_SESSION['finance_manager_name'] = $first_name . ' ' . $last_name;
        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    $success = '';
    
    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    }
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters';
    }
    if ($new_password !== $confirm_password) {
        $errors[] = 'Password confirmation does not match';
    }
    
    if (empty($errors)) {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM finance_managers WHERE id = ?");
            $stmt->execute([$finance_manager_id]);
            $finance_manager = $stmt->fetch();
            
            if ($finance_manager && password_verify($current_password, $finance_manager['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE finance_managers SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $finance_manager_id]);
                $success = 'Password changed successfully!';
            } else {
                $errors[] = 'Current password is incorrect';
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to change password: ' . $e->getMessage();
        }
    }
}

// Get current finance manager details
try {
    // First try to get from finance_managers table
    $stmt = $pdo->prepare("SELECT * FROM finance_managers WHERE id = ?");
    $stmt->execute([$finance_manager_id]);
    $finance_manager = $stmt->fetch();
    
    if ($finance_manager) {
        $first_name = $finance_manager['first_name'] ?? '';
        $last_name = $finance_manager['last_name'] ?? '';
        $email = $finance_manager['email'] ?? '';
        $phone = $finance_manager['phone'] ?? '';
        
        // Try to get login email
        $stmt = $pdo->prepare("SELECT email FROM finance_manager_logins WHERE finance_manager_id = ?");
        $stmt->execute([$finance_manager_id]);
        $login_data = $stmt->fetch();
        $login_email = $login_data['email'] ?? '';
    } else {
        $first_name = '';
        $last_name = '';
        $email = '';
        $phone = '';
        $login_email = '';
        error_log("No finance manager found with ID: " . $finance_manager_id);
    }
} catch (PDOException $e) {
    error_log("Failed to fetch finance manager details: " . $e->getMessage());
    $finance_manager = null;
    $first_name = '';
    $last_name = '';
    $email = '';
    $phone = '';
    $login_email = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Settings - <?php echo htmlspecialchars($finance_manager_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
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
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-color);
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
            font-size: 20px;
            cursor: pointer;
            color: #5f6368;
            padding: 8px;
            border-radius: 50%;
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
            font-weight: 500;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease, margin-left 0.3s ease;
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
            padding: 12px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background 0.2s;
            border-bottom: 1px solid #f1f3f4;
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
            padding-bottom: 80px;
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
        
        .card {
            background: var(--card-bg);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #5f6368;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-secondary {
            background: #5f6368;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #45484d;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                z-index: 9999;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-bottom: 80px;
            }
            
            .header {
                padding: 0 16px;
            }
            
            .logo {
                font-size: 14px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
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
                <div style="width: 32px; height: 32px; background: #FFD700; border: 2px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 16px;">
                        <span style="color: #FF6B35; font-size: 20px;">K</span><span style="color: #008000; font-size: 16px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link" href="dashboard">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link" href="fees">
            <i class="fas fa-money-bill-wave"></i> Fees
        </a>
        <a class="nav-link" href="invoices">
            <i class="fas fa-file-invoice-dollar"></i> Invoices
        </a>
        <a class="nav-link" href="reminders">
            <i class="fas fa-bell"></i> Reminders
        </a>
        <a class="nav-link" href="reports">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a class="nav-link" href="account">
            <i class="fas fa-wallet"></i> Account Balance
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
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Profile Settings -->
        <div class="card">
            <h2 class="card-title">Profile Information</h2>
            <?php if (!$finance_manager): ?>
                <div class="alert alert-danger">
                    Unable to load profile data. Please contact support.
                </div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Password Settings -->
        <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" required minlength="6">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key me-2"></i> Change Password
                </button>
            </form>
        </div>
        
        <!-- Account Info -->
        <div class="card">
            <h2 class="card-title">Account Information</h2>
            <div class="mb-3">
                <label class="form-label">Login Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($login_email); ?>" readonly>
            </div>
            <p class="text-muted small">
                To change your login email, please contact the school administrator.
            </p>
        </div>
    </main>
    
    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: #f8f9fa; border-top: 1px solid #e8eaed; padding: 20px 0; z-index: 1000;">
        <div style="text-align: center;">
            <p style="margin: 0; color: #5f6368; font-size: 14px;">
                <span style="color: #FF6B35;">&copy; 2026</span>
                <span style="color: #FF6B35;">Kenya</span>
                <span style="color: #008000;">EduHub</span>
                <span style="color: #5f6368;">. All rights reserved.</span>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                // Mobile: toggle the 'show' class
                sidebar.classList.toggle('show');
            } else {
                // Desktop: toggle the 'collapsed' and 'expanded' classes
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }
    </script>
</body>
</html>
