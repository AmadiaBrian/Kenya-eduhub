<?php
// School Profile Page
// Authentication is handled by index.php router
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get school profile data
try {
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching school profile: " . $e->getMessage());
    $school = [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $school_name = $_POST['school_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $county = $_POST['county'] ?? '';
    $school_type = $_POST['school_type'] ?? 'Primary';
    $admission_prefix = $_POST['admission_prefix'] ?? '';
    
    if ($school_name && $email && $phone) {
        try {
            $stmt = $pdo->prepare("UPDATE schools SET school_name = ?, email = ?, phone = ?, address = ?, county = ?, school_type = ?, admission_prefix = ? WHERE id = ?");
            $stmt->execute([$school_name, $email, $phone, $address, $county, $school_type, $admission_prefix, $school_id]);
            
            // Update session
            $_SESSION['school_name'] = $school_name;
            
            $success = "Profile updated successfully!";
            
            // Refresh school data
            $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
            $stmt->execute([$school_id]);
            $school = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($current_password && $new_password && $confirm_password) {
        // Verify current password
        if (password_verify($current_password, $school['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    try {
                        $stmt = $pdo->prepare("UPDATE schools SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $school_id]);
                        $success = "Password changed successfully!";
                    } catch (PDOException $e) {
                        $error = "Failed to change password: " . $e->getMessage();
                    }
                } else {
                    $error = "New password must be at least 8 characters";
                }
            } else {
                $error = "New passwords do not match";
            }
        } else {
            $error = "Current password is incorrect";
        }
    } else {
        $error = "Please fill in all password fields";
    }
}

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_logo'])) {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['logo']['type'];
        $file_size = $_FILES['logo']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= $max_size) {
                $upload_dir = '../uploads/schools/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $file_name = 'school_' . $school_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE schools SET logo = ? WHERE id = ?");
                        $stmt->execute([$file_name, $school_id]);
                        $success = "Logo uploaded successfully!";
                        
                        // Refresh school data
                        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
                        $stmt->execute([$school_id]);
                        $school = $stmt->fetch();
                    } catch (PDOException $e) {
                        $error = "Failed to update logo: " . $e->getMessage();
                    }
                } else {
                    $error = "Failed to upload file";
                }
            } else {
                $error = "File size must be less than 2MB";
            }
        } else {
            $error = "Only JPG, JPEG, and PNG files are allowed";
        }
    } else {
        $error = "Please select a file to upload";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Profile - <?php echo htmlspecialchars($school_name); ?></title>
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
            background: var(--card-bg);
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
        
        /* Form Styles */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 6px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        
        /* Logo Upload */
        .logo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f1f3f4;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .logo-preview i {
            font-size: 48px;
            color: #9aa0a6;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .btn-outline {
            background: transparent;
            border: 1px solid #e8eaed;
            color: #5f6368;
        }
        
        .btn-outline:hover {
            background: #f1f3f4;
        }
        
        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #137333;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
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
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
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
            <a class="nav-link" href="students">
                <i class="fas fa-users"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-school"></i> Classes
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link" href="disciplinary">
                <i class="fas fa-exclamation-triangle"></i> Disciplinary
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
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
        <h1 class="page-title">School Profile</h1>
        
        <!-- Profile Information -->
        <div class="card">
            <h2 class="card-title">School Information</h2>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>School Code</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($school['school_code'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>School Name *</label>
                        <input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars($school['school_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($school['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($school['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>County</label>
                        <input type="text" class="form-control" name="county" value="<?php echo htmlspecialchars($school['county'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>School Type</label>
                        <select class="form-control" name="school_type">
                            <option value="Primary" <?php echo ($school['school_type'] ?? '') === 'Primary' ? 'selected' : ''; ?>>Primary</option>
                            <option value="Secondary" <?php echo ($school['school_type'] ?? '') === 'Secondary' ? 'selected' : ''; ?>>Secondary</option>
                            <option value="College" <?php echo ($school['school_type'] ?? '') === 'College' ? 'selected' : ''; ?>>College</option>
                            <option value="University" <?php echo ($school['school_type'] ?? '') === 'University' ? 'selected' : ''; ?>>University</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Admission Prefix</label>
                    <input type="text" class="form-control" name="admission_prefix" value="<?php echo htmlspecialchars($school['admission_prefix'] ?? ''); ?>" placeholder="e.g., STU">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <input type="text" class="form-control" value="<?php echo ucfirst($school['status'] ?? 'pending'); ?>" readonly>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Logo Upload -->
        <div class="card">
            <h2 class="card-title">School Logo</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_logo" value="1">
                
                <div class="logo-preview">
                    <?php if (!empty($school['logo'])): ?>
                        <?php 
                        $logo_path = $school['logo'];
                        // If logo doesn't start with ../uploads/schools/, add the path
                        if (strpos($logo_path, '../uploads/schools/') !== 0) {
                            $logo_path = '../uploads/schools/' . $logo_path;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="School Logo">
                    <?php else: ?>
                        <i class="fas fa-school"></i>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Upload New Logo</label>
                    <input type="file" class="form-control" name="logo" accept="image/jpeg,image/png,image/jpg">
                    <small style="color: #5f6368;">Maximum file size: 2MB. Allowed formats: JPG, JPEG, PNG</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Logo
                </button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <small style="color: #5f6368;">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Show PHP success/error messages as custom notifications
        <?php if (isset($success)): ?>
            notificationSystem.success('Success', <?php echo json_encode($success); ?>);
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            notificationSystem.error('Error', <?php echo json_encode($error); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
