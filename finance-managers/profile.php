<?php
// Finance Manager Profile Page
// Authentication is handled by index.php router
$finance_manager_id = $_SESSION['finance_manager_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get finance manager details
$finance_manager = null;
try {
    $stmt = $pdo->prepare("SELECT fm.*, s.school_name FROM finance_managers fm JOIN schools s ON fm.school_id = s.id WHERE fm.id = ?");
    $stmt->execute([$finance_manager_id]);
    $finance_manager = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch finance manager details: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($phone)) $errors[] = 'Phone is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE finance_managers SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $address, $finance_manager_id]);
            
            // Update session
            $_SESSION['finance_manager_name'] = $first_name . ' ' . $last_name;
            
            $success = 'Profile updated successfully!';
            
            // Refresh finance manager data
            $stmt = $pdo->prepare("SELECT fm.*, s.school_name FROM finance_managers fm JOIN schools s ON fm.school_id = s.id WHERE fm.id = ?");
            $stmt->execute([$finance_manager_id]);
            $finance_manager = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Failed to update profile: " . $e->getMessage());
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    if (empty($current_password)) $errors[] = 'Current password is required';
    if (empty($new_password)) $errors[] = 'New password is required';
    if (strlen($new_password) < 8) $errors[] = 'New password must be at least 8 characters';
    if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match';
    
    if (empty($errors)) {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM finance_manager_logins WHERE finance_manager_id = ?");
            $stmt->execute([$finance_manager_id]);
            $login = $stmt->fetch();
            
            if ($login && password_verify($current_password, $login['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE finance_manager_logins SET password = ? WHERE finance_manager_id = ?");
                $stmt->execute([$hashed_password, $finance_manager_id]);
                
                $success = 'Password changed successfully!';
            } else {
                $errors[] = 'Current password is incorrect';
            }
        } catch (PDOException $e) {
            error_log("Failed to change password: " . $e->getMessage());
            $errors[] = 'Failed to change password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($finance_manager_name); ?></title>
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
        
        /* Header */
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
            font-size: 20px;
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
        }
        
        /* Sidebar */
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
        
        /* Main Content */
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
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 14px;
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
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #1e8e3e;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        
        .profile-info {
            margin-bottom: 24px;
        }
        
        .profile-info-item {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e8eaed;
        }
        
        .profile-info-label {
            width: 150px;
            font-weight: 500;
            color: #5f6368;
        }
        
        .profile-info-value {
            color: #202124;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .profile-info-item {
                flex-direction: column;
            }
            
            .profile-info-label {
                width: 100%;
                margin-bottom: 4px;
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
                <?php echo strtoupper(substr($finance_manager_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link" href="reports">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link active" href="profile">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Profile</h1>
        
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
        
        <!-- Profile Info Card -->
        <div class="card">
            <h2 class="card-title">Profile Information</h2>
            <?php if ($finance_manager): ?>
                <div style="text-align: center; margin-bottom: 24px;">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($finance_manager['first_name'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($finance_manager['first_name'] . ' ' . $finance_manager['last_name']); ?></h3>
                    <p style="color: #5f6368;">Finance Manager</p>
                </div>
                
                <div class="profile-info">
                    <div class="profile-info-item">
                        <div class="profile-info-label">Email:</div>
                        <div class="profile-info-value"><?php echo htmlspecialchars($finance_manager['email']); ?></div>
                    </div>
                    <div class="profile-info-item">
                        <div class="profile-info-label">Phone:</div>
                        <div class="profile-info-value"><?php echo htmlspecialchars($finance_manager['phone']); ?></div>
                    </div>
                    <div class="profile-info-item">
                        <div class="profile-info-label">ID Number:</div>
                        <div class="profile-info-value"><?php echo htmlspecialchars($finance_manager['id_number']); ?></div>
                    </div>
                    <div class="profile-info-item">
                        <div class="profile-info-label">School:</div>
                        <div class="profile-info-value"><?php echo htmlspecialchars($finance_manager['school_name']); ?></div>
                    </div>
                    <div class="profile-info-item">
                        <div class="profile-info-label">Address:</div>
                        <div class="profile-info-value"><?php echo htmlspecialchars($finance_manager['address'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="profile-info-item">
                        <div class="profile-info-label">Status:</div>
                        <div class="profile-info-value">
                            <span class="badge <?php echo $finance_manager['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($finance_manager['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Edit Profile Card -->
        <div class="card">
            <h2 class="card-title">Edit Profile</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($finance_manager['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($finance_manager['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($finance_manager['phone'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($finance_manager['address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password Card -->
        <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" required minlength="8">
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" required minlength="8">
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key me-2"></i> Change Password
                </button>
            </form>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
    
    <!-- Footer -->
    <footer style="background: transparent; color: white; padding: 2rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
